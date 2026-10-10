<?php
declare(strict_types=1);

namespace Core;

// ส่งอีเมลผ่าน SMTP ดิบๆ ด้วย stream socket เอง แทนที่จะพึ่ง PHPMailer/library ภายนอก — ไม่มี composer.json
// ติดตั้งอยู่ในโปรเจกต์นี้ (รูปแบบเดียวกับ core/Jwt.php ที่เขียน HS256 เองแทนพึ่ง firebase/php-jwt)
//
// รองรับเฉพาะ SMTPS (เชื่อมต่อผ่าน TLS ตั้งแต่แรก, พอร์ต 465) + AUTH LOGIN เท่านั้น — พอสำหรับ Gmail/
// Google Workspace (smtp.gmail.com:465) ที่ตั้งใจใช้ตอนนี้ ถ้าจะใช้ SMTP server อื่นที่ต้อง STARTTLS (พอร์ต
// 587) ต้องเพิ่ม flow STARTTLS ต่างหาก ยังไม่ทำเพราะยังไม่มี use case
//
// config/mail.php (ไม่ commit เข้า git เหมือน config/db.php) ต้องมี:
//   return ['host' => 'smtp.gmail.com', 'port' => 465, 'username' => '...', 'password' => '...',
//           'fromEmail' => '...', 'fromName' => 'CheckInTime'];
class Mailer
{
    private array $config;

    public function __construct()
    {
        $file = dirname(__DIR__) . '/config/mail.php';
        $config = is_file($file) ? require $file : null;
        if (!is_array($config) || empty($config['host']) || empty($config['username']) || empty($config['password'])) {
            throw new \RuntimeException('ยังไม่ได้ตั้งค่า config/mail.php');
        }
        $this->config = $config;
    }

    // คืน true ถ้าส่งสำเร็จ (SMTP ตอบ 250 ตอนจบคำสั่ง DATA) — โยน exception พร้อมข้อความ SMTP ดิบถ้าพัง เพื่อ
    // ให้ error_log ของผู้เรียกเห็นสาเหตุจริง (auth ผิด/ถูกบล็อก ฯลฯ) ไม่ใช่แค่ "ส่งไม่ได้" เฉยๆ
    public function send(string $toEmail, string $subject, string $bodyText): bool
    {
        $host = $this->config['host'];
        $port = (int) ($this->config['port'] ?? 465);

        $stream = @stream_socket_client(
            "ssl://{$host}:{$port}",
            $errno,
            $errstr,
            10,
            STREAM_CLIENT_CONNECT,
            stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true]])
        );
        if ($stream === false) {
            throw new \RuntimeException("เชื่อมต่อ SMTP ไม่ได้: {$errstr} ({$errno})");
        }

        try {
            $this->expect($stream, '220');
            $this->command($stream, 'EHLO ' . ($this->config['ehloHost'] ?? 'localhost'), '250');
            $this->command($stream, 'AUTH LOGIN', '334');
            $this->command($stream, base64_encode($this->config['username']), '334');
            $this->command($stream, base64_encode($this->config['password']), '235');

            $fromEmail = $this->config['fromEmail'];
            $fromName = $this->config['fromName'] ?? 'CheckInTime';

            $this->command($stream, "MAIL FROM:<{$fromEmail}>", '250');
            $this->command($stream, "RCPT TO:<{$toEmail}>", '250');
            $this->command($stream, 'DATA', '354');

            $headers = implode("\r\n", [
                'From: ' . $this->encodeHeader($fromName) . " <{$fromEmail}>",
                "To: <{$toEmail}>",
                'Subject: ' . $this->encodeHeader($subject),
                // Gmail/mail server หลายเจ้าเงียบๆ ทิ้งอีเมลที่ขาด Date/Message-ID (ดูเหมือนส่งสำเร็จ SMTP
                // ตอบ 250 แต่ไม่ถึงกล่อง แม้แต่ Spam) — MTA จริงใส่ 2 header นี้ให้เสมอ เขียนเองต้องใส่เอง
                'Date: ' . date('r'),
                'Message-ID: <' . bin2hex(random_bytes(16)) . '@' . explode('@', $fromEmail)[1] . '>',
                'MIME-Version: 1.0',
                'Content-Type: text/plain; charset=UTF-8',
                'Content-Transfer-Encoding: base64',
            ]);
            // แปลงเนื้อหาเป็น base64 กันปัญหาบรรทัดที่ขึ้นต้นด้วย "." ไปชนกับ SMTP data-terminator โดยไม่ตั้งใจ
            // (RFC 5321 dot-stuffing) — base64 ไม่มีจุดเปล่าๆ ต้น-ท้ายบรรทัดอยู่แล้ว ปลอดภัยกว่า escape เอง
            $encodedBody = chunk_split(base64_encode($bodyText));

            $this->write($stream, $headers . "\r\n\r\n" . $encodedBody . "\r\n.");
            $this->expect($stream, '250');

            $this->command($stream, 'QUIT', '221');
            return true;
        } finally {
            fclose($stream);
        }
    }

    private function encodeHeader(string $value): string
    {
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }

    private function command($stream, string $line, string $expectedCode): string
    {
        $this->write($stream, $line);
        return $this->expect($stream, $expectedCode);
    }

    private function write($stream, string $line): void
    {
        fwrite($stream, $line . "\r\n");
    }

    private function expect($stream, string $expectedCode): string
    {
        $response = '';
        while (($line = fgets($stream, 515)) !== false) {
            $response .= $line;
            // บรรทัดสุดท้ายของ multi-line response ใช้ "-" คั่น (เช่น "250-...") ส่วนบรรทัดจบจริงใช้ " "
            if (strlen($line) >= 4 && $line[3] === ' ') break;
        }
        if (!str_starts_with($response, $expectedCode)) {
            throw new \RuntimeException("SMTP error: {$response}");
        }
        return $response;
    }
}
