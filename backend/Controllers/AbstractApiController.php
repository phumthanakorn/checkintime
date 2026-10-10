<?php
declare(strict_types=1);

namespace Api\Checkin\Controllers;

use Core\Jwt;
use Models\Checkin\CheckinAuthModel;

// Base class ของทุก resource controller ใน api/checkin — auth คนละแบบกับ api/v1/Controllers/
// AbstractApiController โดยสิ้นเชิง: v1 เช็ค token คงที่ 1 ตัวต่อ 1 client ภายนอก (เช่น EIX) ส่วนตัวนี้เช็ค
// JWT ที่ผูกกับพนักงานแต่ละคน (ออกจาก AuthController::login) เพราะ checkintime ต้องรู้ว่า "ใคร" กำลังเรียก
// ไม่ใช่แค่ "ระบบไหน" กำลังเรียก
//
// router.php ส่งชื่อ action (เช่น "login", "me") เข้ามาทาง constructor เสมอ ก่อนที่ controller method
// จริงจะถูกเรียก — เพราะ auth ต้องเช็คตั้งแต่ก่อนสร้าง response ด้วยซ้ำ (constructor time) ไม่ใช่เช็คทีหลัง
// ใน method นั้นๆ เอง ดังนั้น endpoint ไหนในคลาสเดียวกันที่ไม่ต้อง login (เช่น login() เอง) ต้อง override
// requiresAuth($action) แล้วเช็คค่า $action เทียบชื่อ method ของตัวเอง แทนที่จะ hardcode คืน false เฉยๆ
// (ซึ่งจะปิด auth ให้ทุก method ในคลาสนั้นโดยไม่ตั้งใจ)
abstract class AbstractApiController
{
    protected ?string $empcode = null;
    protected ?string $jti = null; // "session_token" ใน Employee_CheckinTime_Login_Log — มีเฉพาะ token ที่ออกหลังเพิ่ม claim นี้
    protected ?string $action;

    protected function requiresAuth(?string $action): bool
    {
        return true;
    }

    public function __construct(?string $action = null)
    {
        $this->action = $action;
        if (!$this->requiresAuth($action)) return;

        $token = $this->getBearerToken();
        if ($token === null) {
            self::error('Unauthorized', 401, 'NO_TOKEN');
        }

        $secretFile = dirname(__DIR__) . '/config/jwt.php';
        $config = is_file($secretFile) ? require $secretFile : null;
        if (!is_array($config) || empty($config['secret'])) {
            // ไฟล์ config/jwt.php หาย/ยังไม่สร้าง — fail แบบปลอดภัย ไม่มี token ไหนผ่านได้เลย
            self::error('Server auth is not configured', 500, 'AUTH_NOT_CONFIGURED');
        }

        $payload = Jwt::decode($token, $config['secret']);
        if ($payload === null || empty($payload['empcode'])) {
            self::error('Invalid or expired token', 401, 'INVALID_TOKEN');
        }

        $this->empcode = (string) $payload['empcode'];
        $this->jti = isset($payload['jti']) ? (string) $payload['jti'] : null;

        try {
            $accessState = (new CheckinAuthModel())->accessState($this->empcode);
        } catch (\Throwable $e) {
            error_log('[CheckInTime access validation] ' . $e->getMessage());
            self::error('ไม่สามารถตรวจสอบสิทธิ์การใช้งานได้ในขณะนี้', 503, 'ACCESS_CHECK_UNAVAILABLE');
        }
        if ($accessState !== 'active') {
            self::error('บัญชีนี้ไม่มีสิทธิ์ใช้งาน CheckInTime กรุณาติดต่อฝ่ายบุคคล', 401, 'ACCESS_REVOKED');
        }
    }

    private function getBearerToken(): ?string
    {
        $headers = getallheaders();
        $auth = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        if (!str_starts_with($auth, 'Bearer ')) return null;
        $token = trim(substr($auth, 7));
        return $token !== '' ? $token : null;
    }

    // รูปแบบ error ตาม docs/api-spec-for-backend.txt ข้อ 2.3 — { message, code } ตรงๆ ไม่ห่อ success/data
    // (คนละ format กับ Core\ApiController::error() ที่ api/v1 ใช้ เพราะ frontend ของแอปนี้ผูก format นี้ไว้ตายตัว
    // ใน frontend/src/api/axiosClient.js อยู่แล้ว)
    protected static function error(string $message, int $status, string $code): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['message' => $message, 'code' => $code], JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected static function json(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected static function body(): array
    {
        $raw = file_get_contents('php://input');
        $decoded = json_decode($raw ?: '', true);
        return is_array($decoded) ? $decoded : [];
    }

    // IP จริงของ client — ใช้ที่เดียวทุกจุดที่ต้องบันทึก IP (login log, simulate log ฯลฯ) แทนการอ่าน
    // REMOTE_ADDR ตรงๆ เพราะถ้ามี reverse proxy อยู่หน้า (เช่น nginx บน production ที่เจอมาแล้วตอนทดสอบ
    // api/v1 — เห็น error page เป็น nginx) REMOTE_ADDR จะเป็น IP ของ proxy เอง ไม่ใช่ IP เครื่องผู้ใช้จริง
    // ต้องอ่าน X-Forwarded-For/X-Real-IP ที่ proxy ใส่มาให้ก่อนเสมอ — X-Forwarded-For อาจมีหลาย IP คั่นด้วย
    // comma (ผ่านหลายชั้น proxy) เอาตัวแรกสุด (= client ตัวจริง ไม่ใช่ proxy ชั้นถัดไป)
    protected static function clientIp(): ?string
    {
        $headers = getallheaders();
        $xff = $headers['X-Forwarded-For'] ?? $headers['x-forwarded-for'] ?? null;
        if ($xff) {
            $first = trim(explode(',', $xff)[0]);
            if ($first !== '') return self::normalizeIp($first);
        }
        $xRealIp = $headers['X-Real-IP'] ?? $headers['x-real-ip'] ?? null;
        if ($xRealIp) return self::normalizeIp(trim($xRealIp));

        $remote = $_SERVER['REMOTE_ADDR'] ?? null;
        return $remote !== null ? self::normalizeIp($remote) : null;
    }

    // ตัด prefix "::ffff:" ออกถ้ามี — เจอจริงตอนทดสอบผ่าน Vite dev proxy (Node.js บน Windows รายงาน IPv4 แบบ
    // "IPv4-mapped IPv6" เวลาฟัง dual-stack socket เช่น "::ffff:192.168.4.212") ไม่ใช่ IP ผิดปกติ แค่หน้าตา
    // ไม่คุ้นตา/เทียบกับ log อื่นในระบบไม่ตรงกัน ตัดให้เหลือ IPv4 ล้วนๆ เพื่อความสม่ำเสมอกับ log จุดอื่น
    private static function normalizeIp(string $ip): string
    {
        return str_starts_with($ip, '::ffff:') ? substr($ip, 7) : $ip;
    }
}
