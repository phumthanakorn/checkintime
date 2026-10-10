<?php
declare(strict_types=1);

namespace Models\Checkin;

use Core\Model;
use PDO;

// บันทึกทุกครั้งที่มีการพยายามเข้าสู่ระบบผ่าน api/checkin (สำเร็จและไม่สำเร็จ) ลง GBG_Data.dbo.
// Employee_CheckinTime_Login_Log — ดู structure/create_checkintime_logs.sql สำหรับ schema เต็ม
// เวลาทุกช่องมาจากนาฬิกาเซิร์ฟเวอร์ (SYSDATETIME()) เสมอ ไม่รับจาก client
class CheckinLoginLogModel extends Model
{
    // ชี้ไป production เสมอ (ไม่ fallback ไป dbGbgDataTest แล้ว) — ให้ตรงกับโมเดลอื่นฝั่งแอปพนักงาน กัน
    // isLocal routing bug ที่ข้อมูลกระจายไปคนละฐานกันระหว่างทดสอบผ่าน localhost ตรงๆ กับผ่าน LAN IP/prod
    private function gbgDb(): ?PDO
    {
        return $this->gbgDataForCurrentEnvironment();
    }

    // ไม่ throw ถ้าเขียน log ไม่สำเร็จ (เช่น ต่อ DB ไม่ได้/ยังไม่ได้สร้างตาราง) — การ login จริงต้องผ่านต่อได้
    // แม้บันทึก log พลาด ไม่ควรเอา log มาเป็นจุดบล็อกการเข้าระบบ
    public function logAttempt(
        string $empcode,
        string $status,
        ?string $department,
        ?string $failReason,
        ?string $ipAddress,
        ?string $userAgent,
        ?string $sessionToken,
        ?string $expiresAt
    ): void {
        $db = $this->gbgDb();
        if ($db === null) return;

        try {
            $stmt = $db->prepare("
                INSERT INTO dbo.Employee_CheckinTime_Login_Log
                    (empcode, department, status, fail_reason, ip_address, user_agent, session_token, login_at, expires_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, SYSDATETIME(), ?)
            ");
            $stmt->execute([$empcode, $department, $status, $failReason, $ipAddress, $userAgent, $sessionToken, $expiresAt]);
        } catch (\Throwable $e) {
            error_log('[CheckinLoginLogModel::logAttempt] ' . $e->getMessage());
        }
    }

    // นับจำนวนครั้งที่ล็อกอินผิด (ของ empcode นี้) ในช่วง $windowMinutes นาทีที่ผ่านมา — ใช้ทำ lockout กัน
    // brute-force เดา password (ดู AuthController::login()/setupPassword()) ไม่มีตาราง/คอลัมน์ใหม่เพิ่มเลย
    // ใช้ log ที่มีอยู่แล้วตัวนี้ตรงๆ — ถ้าต่อ DB ไม่ได้ คืน 0 (ไม่ล็อก) เพราะ log พลาดไม่ควรบล็อกคนเข้าระบบปกติ
    public function countRecentFailures(string $empcode, int $windowMinutes): int
    {
        $db = $this->gbgDb();
        if ($db === null) return 0;

        try {
            // DATEADD ต้องการ argument ที่ 2 เป็น INT จริงๆ — ผ่าน ODBC ถ้า bind เป็น param ตรงๆ จะถูกส่งเป็น
            // nvarchar แล้ว SQL Server ปฏิเสธทันที (SQLSTATE 42000) ต้อง CAST ให้ชัดเจน (แพทเทิร์นเดียวกับที่
            // เจอมาแล้วใน CheckinAttendanceLogModel::findMonth() ตอน DATEADD(MONTH, 1, CAST(? AS DATE)))
            $stmt = $db->prepare("
                SELECT COUNT(*) FROM dbo.Employee_CheckinTime_Login_Log
                WHERE empcode = ? AND status = 'FAILED' AND login_at >= DATEADD(MINUTE, CAST(? AS INT), SYSDATETIME())
            ");
            $stmt->execute([$empcode, -$windowMinutes]);
            return (int) $stmt->fetchColumn();
        } catch (\Throwable $e) {
            error_log('[CheckinLoginLogModel::countRecentFailures] ' . $e->getMessage());
            return 0;
        }
    }

    // เหมือน countRecentFailures() แต่นับข้าม "ทุก empcode" จาก IP เดียวกัน — กันกรณีที่ countRecentFailures()
    // (นับต่อ 1 บัญชี) กันไม่ได้: คนเดาสุ่มอีเมลคนละตัวจาก IP เดียวกันรัวๆ (เช่น แอบรู้รายชื่อพนักงานมาแล้ว
    // ลองทีละคนด้วยรหัสผ่านเดา 1-2 ครั้งต่อคน จะไม่โดน per-empcode lockout เลยเพราะแต่ละบัญชีโดนแค่นิดเดียว)
    // เป็นเกราะชั้นแอป เสริมจาก WAF/rate-limit ระดับเครือข่ายที่ควรมีอยู่แล้วหน้า web server ด้วย (ดู
    // docs/production-checklist.md) — threshold สูงกว่า per-empcode (ไม่อยากบล็อก IP สำนักงานที่มีคนเข้าออก
    // พร้อมกันหลายคนจนบังเอิญมีคนพิมพ์รหัสผิดรวมกันเกิน)
    public function countRecentFailuresByIp(string $ip, int $windowMinutes): int
    {
        $db = $this->gbgDb();
        if ($db === null || $ip === '') return 0;

        try {
            $stmt = $db->prepare("
                SELECT COUNT(*) FROM dbo.Employee_CheckinTime_Login_Log
                WHERE ip_address = ? AND status = 'FAILED' AND login_at >= DATEADD(MINUTE, CAST(? AS INT), SYSDATETIME())
            ");
            $stmt->execute([$ip, -$windowMinutes]);
            return (int) $stmt->fetchColumn();
        } catch (\Throwable $e) {
            error_log('[CheckinLoginLogModel::countRecentFailuresByIp] ' . $e->getMessage());
            return 0;
        }
    }

    // ประวัติความพยายามเข้าสู่ระบบล่าสุดของ empcode นี้ — ให้หน้า HR (DempcEmployeeController::
    // checkinLoginLogs()) โชว์เป็น modal ตอนกดไอคอนประวัติข้าง "บัญชี CheckInTime" (เฉพาะตอน status=1
    // ใช้งานได้ปกติแล้ว ดู manage-dempc-employee.js::renderCheckinAccount())
    public function findRecent(string $empcode, int $limit = 50): array
    {
        $db = $this->gbgDb();
        if ($db === null) return [];

        try {
            // TOP (?) ผ่าน ODBC เจอปัญหาแบบเดียวกับ DATEADD(MINUTE, ?, ...) ข้างบน (bind เป็น nvarchar แล้ว
            // SQL Server ปฏิเสธ) — $limit เป็น int ที่ type-hint บังคับไว้แล้วจากโค้ดเท่านั้น ไม่ใช่ input จาก
            // client โดยตรง ปลอดภัยพอจะ interpolate ตรงๆ แทนการ bind param
            $limit = max(1, $limit);
            $stmt = $db->prepare("
                SELECT TOP ({$limit}) status, fail_reason, ip_address, user_agent, login_at, is_logout, logout_at
                FROM dbo.Employee_CheckinTime_Login_Log
                WHERE empcode = ?
                ORDER BY login_at DESC
            ");
            $stmt->execute([$empcode]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            error_log('[CheckinLoginLogModel::findRecent] ' . $e->getMessage());
            return [];
        }
    }

    // อัปเดตแถว login ที่ตรงกับ jti นี้ — ดู comment ใน create_checkintime_logs.sql: is_logout แค่บันทึกไว้
    // audit ว่าผู้ใช้กดออกจากระบบเอง ไม่ได้ทำให้ JWT ใช้ไม่ได้จริง (ไม่มี blocklist ตรวจสอบ)
    public function logLogout(string $sessionToken): void
    {
        $db = $this->gbgDb();
        if ($db === null || $sessionToken === '') return;

        try {
            $stmt = $db->prepare("
                UPDATE dbo.Employee_CheckinTime_Login_Log
                SET is_logout = 1, logout_at = SYSDATETIME()
                WHERE session_token = ?
            ");
            $stmt->execute([$sessionToken]);
        } catch (\Throwable $e) {
            error_log('[CheckinLoginLogModel::logLogout] ' . $e->getMessage());
        }
    }
}
