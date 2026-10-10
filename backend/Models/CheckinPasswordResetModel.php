<?php
declare(strict_types=1);

namespace Models\Checkin;

use Core\Model;
use PDO;

// จัดการ token ลิงก์รีเซ็ตรหัสผ่าน (ดู structure/create_checkin_password_reset.sql) — token ดิบส่งออกไป
// ทางอีเมลเท่านั้น เก็บใน DB แค่ hash (SHA-256) กันถ้า DB รั่วแล้วเดา token จริงไม่ได้
class CheckinPasswordResetModel extends Model
{
    private const TTL_MINUTES = 30;

    private function gbgDb(): ?PDO
    {
        return $this->gbgDataForCurrentEnvironment();
    }

    // สร้าง token ใหม่ให้ empcode นี้ คืน token ดิบ (เอาไปใส่ในลิงก์อีเมล) — ไม่ลบ token เก่าที่ยังไม่หมดอายุ
    // ทิ้ง (เผื่อเปิดหลายแท็บ/ขอซ้ำ) แค่ไม่ยืนยันให้ก็พอ เพราะ consume() เช็ค token_hash ตรงเป๊ะอยู่แล้ว
    public function createToken(string $empcode, ?string $ip): string
    {
        $db = $this->gbgDb();
        if ($db === null) throw new \RuntimeException('เชื่อมต่อฐานข้อมูลไม่ได้');

        $token = bin2hex(random_bytes(32));
        $hash = hash('sha256', $token);
        $expiresAt = (new \DateTime('+' . self::TTL_MINUTES . ' minutes'))->format('Y-m-d H:i:s');

        $stmt = $db->prepare('
            INSERT INTO dbo.Employee_Checkin_Password_Reset (empcode, token_hash, expires_at, ip)
            VALUES (?, ?, ?, ?)
        ');
        $stmt->execute([$empcode, $hash, $expiresAt, $ip]);

        return $token;
    }

    // ตรวจ token ว่ายังใช้ได้ไหม (มีอยู่จริง + ยังไม่หมดอายุ + ยังไม่เคยใช้) คืน empcode เจ้าของถ้าผ่าน
    public function findValid(string $token): ?string
    {
        $db = $this->gbgDb();
        if ($db === null) return null;

        $hash = hash('sha256', $token);
        $stmt = $db->prepare('
            SELECT TOP 1 empcode FROM dbo.Employee_Checkin_Password_Reset
            WHERE token_hash = ? AND used_at IS NULL AND expires_at > SYSDATETIME()
            ORDER BY id DESC
        ');
        $stmt->execute([$hash]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (string) $row['empcode'] : null;
    }

    // ตั้ง used_at กัน token เดิมถูกใช้ซ้ำ (เรียกหลังตั้งรหัสผ่านใหม่สำเร็จเท่านั้น)
    public function consume(string $token): void
    {
        $db = $this->gbgDb();
        if ($db === null) throw new \RuntimeException('เชื่อมต่อฐานข้อมูลไม่ได้');

        $hash = hash('sha256', $token);
        $stmt = $db->prepare('
            UPDATE dbo.Employee_Checkin_Password_Reset SET used_at = SYSDATETIME()
            WHERE token_hash = ? AND used_at IS NULL
        ');
        $stmt->execute([$hash]);
    }
}
