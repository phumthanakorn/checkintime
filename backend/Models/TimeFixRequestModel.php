<?php
declare(strict_types=1);
namespace Models\Checkin;
use Core\Model;
use PDO;
use PDOException;

// Writes requests only. Attendance is read for the original-time snapshot.
class TimeFixRequestModel extends Model
{
    private function gbgDb(): PDO
    {
        // ชี้ไป production เสมอ (ไม่ fallback ไป dbGbgDataTest แล้ว) — ให้ตรงกับ DempcTimeFixModel (หน้า HR)
        // ที่ต่อ production อย่างเดียวอยู่แล้ว กัน isLocal routing bug ที่เคยทำให้ข้อมูลที่ HR อนุมัติไปแล้วบน
        // prod ไม่โผล่ฝั่งแอปพนักงานที่ทดสอบผ่าน localhost ตรงๆ (ไปอ่าน local test คนละฐานกัน)
        $db = $this->gbgDataForCurrentEnvironment();
        if ($db === null) throw new \RuntimeException('Attendance database unavailable');
        return $db;
    }

    // จำนวนคำขอที่ยังรอพิจารณา — ใช้เลี้ยงการ์ด "ขอลงเวลาย้อนหลัง" ใน "สถานะการส่งคำขอ" หน้าแรก (ดู
    // RequestsController::statusSummary())
    public function countPending(string $empcode): int
    {
        $stmt = $this->gbgDb()->prepare("SELECT COUNT(*) FROM dbo.Employee_Attendance_TimeFix_Request
            WHERE empcode = ? AND status = 'pending'");
        $stmt->execute([$empcode]);
        return (int) $stmt->fetchColumn();
    }

    public function findRequests(string $empcode): array
    {
        $stmt = $this->gbgDb()->prepare('SELECT * FROM dbo.Employee_Attendance_TimeFix_Request
            WHERE empcode = ? ORDER BY created_at DESC, id DESC');
        $stmt->execute([$empcode]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function hasPending(string $empcode, string $date): bool
    {
        $stmt = $this->gbgDb()->prepare("SELECT TOP (1) id FROM dbo.Employee_Attendance_TimeFix_Request
            WHERE empcode = ? AND work_date = ? AND status = 'pending'");
        $stmt->execute([$empcode, $date]);
        return $stmt->fetchColumn() !== false;
    }

    // false includes simultaneous requests hitting the pending unique index.
    public function createRequest(string $empcode, array $data): array|false
    {
        $db = $this->gbgDb();
        if ($this->hasPending($empcode, $data['date'])) return false;
        $stmt = $db->prepare('SELECT id, check_in, check_out FROM dbo.Employee_CheckinTime_Attendance_Log
            WHERE empcode = ? AND work_date = ?');
        $stmt->execute([$empcode, $data['date']]);
        $attendance = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $storage = new TimeFixAttachmentStorage();
        $saved = [];
        try {
            $db->beginTransaction();
            $saved = $storage->save($data['attachments'] ?? []);
            $stmt = $db->prepare("INSERT INTO dbo.Employee_Attendance_TimeFix_Request
                (empcode, work_date, attendance_log_id, original_check_in, original_check_out,
                 requested_check_in, requested_check_out, reason, status, created_by, attachments_json)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?)");
            $stmt->execute([$empcode, $data['date'], $attendance['id'] ?? null,
                $attendance['check_in'] ?? null, $attendance['check_out'] ?? null,
                $data['requested_check_in'], $data['requested_check_out'], $data['reason'], $empcode,
                $saved === [] ? null : json_encode($saved, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)]);
            $sel = $db->prepare('SELECT * FROM dbo.Employee_Attendance_TimeFix_Request WHERE id = ?');
            $sel->execute([(int) $db->lastInsertId()]);
            $row = $sel->fetch(PDO::FETCH_ASSOC);
            if ($row === false) throw new \RuntimeException('Inserted request not returned');
            $db->commit();
            return $row;
        } catch (\Throwable $e) {
            try {
                if ($db->inTransaction()) $db->rollBack();
            } finally {
                $storage->delete($saved);
            }
            if ($e instanceof PDOException && in_array((int) ($e->errorInfo[1] ?? 0), [2601, 2627, 1062], true)) return false;
            throw $e;
        }
    }

    public function findOwnRequest(string $empcode, int $id): ?array
    {
        $stmt = $this->gbgDb()->prepare('SELECT id, empcode, attachments_json
            FROM dbo.Employee_Attendance_TimeFix_Request WHERE id = ? AND empcode = ?');
        $stmt->execute([$id, $empcode]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function cancelRequest(string $empcode, int $id): bool
    {
        // Ownership and status are checked atomically, including approval/cancel races.
        // cancelled_by = ผู้ยกเลิก (ตอนนี้มีแค่เจ้าของคำขอเองผ่าน JWT) ใช้ empcode เดียวกับเงื่อนไขเจ้าของ
        $stmt = $this->gbgDb()->prepare("UPDATE dbo.Employee_Attendance_TimeFix_Request
            SET status = 'cancelled', cancelled_by = ?, cancelled_at = SYSDATETIME(), updated_at = SYSDATETIME()
            WHERE id = ? AND empcode = ? AND status = 'pending'");
        $stmt->execute([$empcode, $id, $empcode]);
        return $stmt->rowCount() > 0;
    }
}
