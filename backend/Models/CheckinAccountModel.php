<?php
declare(strict_types=1);

namespace Models\Checkin;

use Core\Model;
use PDO;

// บัญชีรหัสผ่านของแอป CheckInTime — แยกเดี่ยวจากระบบรหัสผ่านกลางเดิม (View_CheckUSER ที่ใช้ร่วมกับเว็บ
// intranet หลัก/Deskmap) ตามที่ตกลงกันไว้ ดู structure/create_checkintime_account.sql สำหรับ schema เต็ม
//
// status=0 (ไม่มีแถว = เสมือน status 0) หมายถึง "ต้องตั้งรหัสผ่านใหม่" ครอบคลุมทั้ง 2 เคส: ยังไม่เคยสมัครเลย
// กับ HR รีเซ็ตให้เพราะพนักงานลืมรหัสผ่าน (ไม่มีขั้นตอน OTP/อีเมลเอง — ให้ HR เป็นคนรีเซ็ตแทนตามที่ตกลงกัน)
// ทั้งสองเคสพนักงานเจอ flow เดียวกัน: กรอก empcode เป็นรหัสผ่านชั่วคราว -> ตั้งรหัสใหม่ -> status กลับเป็น 1
class CheckinAccountModel extends Model
{
    // ตาราง Employee_Checkin_Account อยู่ใน GBG_Data ฐานหลัก
    private function gbgDb(): ?PDO
    {
        return $this->gbgDataForCurrentEnvironment();
    }

    public function find(string $empcode): ?array
    {
        $db = $this->gbgDb();
        if ($db === null) return null;

        $stmt = $db->prepare('SELECT empcode, password_hash, status FROM dbo.Employee_Checkin_Account WHERE empcode = ?');
        $stmt->execute([$empcode]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function needsSetup(string $empcode): bool
    {
        $row = $this->find($empcode);
        return $row === null || (int) $row['status'] === 0;
    }

    public function verifyPassword(string $empcode, string $password): bool
    {
        $row = $this->find($empcode);
        if ($row === null || (int) $row['status'] !== 1 || $row['password_hash'] === null) return false;
        return password_verify($password, $row['password_hash']);
    }

    // Update only the active account whose current hash was verified. Never create/reactivate an account here.
    public function changePassword(string $empcode, string $currentPassword, string $newPassword): bool
    {
        $db = $this->gbgDb();
        if ($db === null) throw new \RuntimeException('เชื่อมต่อฐานข้อมูลไม่ได้');

        $row = $this->find($empcode);
        if ($row === null || (int) $row['status'] !== 1 || empty($row['password_hash'])
            || !password_verify($currentPassword, $row['password_hash'])) return false;

        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $db->prepare('UPDATE dbo.Employee_Checkin_Account
            SET password_hash = ?, updated_at = SYSDATETIME()
            WHERE empcode = ? AND status = 1 AND password_hash = ?');
        $stmt->execute([$hash, $empcode, $row['password_hash']]);
        return $stmt->rowCount() === 1;
    }
    // ตั้ง/เปลี่ยนรหัสผ่าน แล้วเปิดใช้งานบัญชี (status=1) — upsert เพราะตอนเรียกยังไม่รู้ว่ามีแถวอยู่แล้ว
    // (สมัครครั้งแรก) หรือมีแถวอยู่แล้วแต่ status=0 (HR เพิ่งรีเซ็ตให้) ใช้โค้ดเดียวกันทั้งสองเคส
    public function setPassword(string $empcode, string $newPassword): void
    {
        $db = $this->gbgDb();
        if ($db === null) throw new \RuntimeException('เชื่อมต่อฐานข้อมูลไม่ได้');

        $hash = password_hash($newPassword, PASSWORD_DEFAULT);

        $stmt = $db->prepare('UPDATE dbo.Employee_Checkin_Account SET password_hash = ?, status = 1, updated_at = SYSDATETIME() WHERE empcode = ?');
        $stmt->execute([$hash, $empcode]);

        if ($stmt->rowCount() === 0) {
            $stmt = $db->prepare('INSERT INTO dbo.Employee_Checkin_Account (empcode, password_hash, status) VALUES (?, ?, 1)');
            $stmt->execute([$empcode, $hash]);
        }
    }

    // ให้ HR/แอดมินเรียกตอนพนักงานแจ้งลืมรหัสผ่าน — เคลียร์กลับไปสถานะ "ต้องตั้งรหัสผ่านใหม่" (เหมือนยังไม่เคย
    // สมัคร) ไม่ได้ลบแถวทิ้ง เผื่ออยากเก็บ created_at เดิมไว้อ้างอิง — ยังไม่มีหน้า UI เรียกเมธอดนี้จริง (รอทำ
    // หน้า admin ทีหลัง) เตรียมไว้ให้พร้อมใช้งานก่อน
    public function resetByEmpcode(string $empcode): void
    {
        $db = $this->gbgDb();
        if ($db === null) throw new \RuntimeException('เชื่อมต่อฐานข้อมูลไม่ได้');

        $stmt = $db->prepare('UPDATE dbo.Employee_Checkin_Account SET password_hash = NULL, status = 0, updated_at = SYSDATETIME() WHERE empcode = ?');
        $stmt->execute([$empcode]);

        if ($stmt->rowCount() === 0) {
            $stmt = $db->prepare('INSERT INTO dbo.Employee_Checkin_Account (empcode, password_hash, status) VALUES (?, NULL, 0)');
            $stmt->execute([$empcode]);
        }
    }
}
