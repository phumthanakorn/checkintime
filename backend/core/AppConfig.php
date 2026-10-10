<?php
declare(strict_types=1);

namespace Core;

// อ่านค่าจาก config/app.php (โดเมน/ค่าที่ต่างกันตามสภาพแวดล้อม) — จุดเดียวที่ router.php (CORS) และ
// AuthController (ลิงก์อีเมลรีเซ็ตรหัสผ่าน) ใช้ร่วมกัน ไม่ต้องแก้โดเมนหลายที่ตอนย้ายไป production
class AppConfig
{
    /** @return string[] โดเมน frontend ที่อนุญาต (ตัวแรก = โดเมนหลัก) */
    public static function frontendOrigins(): array
    {
        $file = dirname(__DIR__) . '/config/app.php';
        $config = is_file($file) ? require $file : null;
        $origins = is_array($config) ? ($config['frontend_origins'] ?? []) : [];
        $origins = array_values(array_filter($origins, 'is_string'));
        if ($origins === []) {
            // ไม่มี config = ไม่อนุญาตโดเมนใดเลย (CORS ปิดหมด) ดีกว่าเดาโดเมนเอง
            error_log('[AppConfig] config/app.php ไม่มีหรือไม่มี frontend_origins — คัดลอกจาก app.example.php');
        }
        return $origins;
    }

    /** Client ID ของ Google OAuth — null ถ้ายังไม่ได้ตั้ง (ปิด /auth/google เอง) */
    public static function googleClientId(): ?string
    {
        $file = dirname(__DIR__) . '/config/app.php';
        $config = is_file($file) ? require $file : null;
        $id = is_array($config) ? ($config['google_client_id'] ?? '') : '';
        return is_string($id) && $id !== '' ? $id : null;
    }
}
