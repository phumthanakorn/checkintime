<?php
declare(strict_types=1);

namespace Models\Checkin;

use PDO;

// Sync เวลาเข้า/ออกงานจริงจาก CheckInTime (ทั้งสแกนจริงและ HR อนุมัติคำขอย้อนหลัง) เข้าไปเติมที่
// Employee_Dempc_TimeAttendance (ตารางหลักเดิมที่ระบบอื่น เช่น OT/payroll ยังอ่านอยู่) — เขียนเฉพาะช่องที่
// ยังว่าง (actual_in/actual_out เป็น NULL) เท่านั้น ไม่เขียนทับข้อมูลที่มีอยู่แล้วไม่ว่าจะมาจาก Excel import
// หรือ HR แก้มือผ่านหน้าเดิม ตามที่ตกลงกันไว้ (Excel import/แก้มือชนะเสมอถ้ามีอยู่ก่อน)
//
// เรียกจาก CheckinAttendanceLogModel::checkIn()/checkOut() (ตอนสแกนจริง) และ DempcTimeFixModel::review()
// (ตอน HR อนุมัติคำขอ) — เป็นแค่ best-effort เสริมเท่านั้น: sync พลาดไม่ควรบล็อกการสแกน/อนุมัติ จึง catch
// ข้อผิดพลาดไว้ภายในเมธอดนี้เองทั้งหมด ไม่ throw ออกไปให้ผู้เรียกต้องจัดการเอง แค่ log error ไว้
final class DempcSync
{
    public static function write(
        PDO $db,
        string $empcode,
        string $dateKey,
        string $field,
        string $timeHHMM,
        string $source,
        ?int $requestId,
        string $actor
    ): void {
        $actualCol = $field === 'in' ? 'actual_in' : 'actual_out';
        $sourceCol = $field === 'in' ? 'checkin_source' : 'checkout_source';
        $reqCol = $field === 'in' ? 'checkin_timefix_request_id' : 'checkout_timefix_request_id';

        try {
            $stmt = $db->prepare("
                UPDATE dbo.Employee_Dempc_TimeAttendance
                SET $actualCol = ?, $sourceCol = ?, $reqCol = ?, is_manual = 1, updated_by = ?, updated_at = SYSDATETIME()
                WHERE empcode = ? AND work_date = ? AND $actualCol IS NULL
            ");
            $stmt->execute([$timeHHMM, $source, $requestId, $actor, $empcode, $dateKey]);

            if ($stmt->rowCount() === 0) {
                // ไม่มีแถวของวันนั้นเลย หรือมีแถวแล้วแต่ช่องนี้ถูกเขียนไปก่อนแล้ว (Excel import/แก้มือ) —
                // เช็คว่ามีแถวอยู่ไหม ถ้าไม่มีเลยค่อย INSERT ใหม่ ถ้ามีแล้วแปลว่าช่องนี้มีเจ้าของอยู่ก่อน ไม่ทับ
                $check = $db->prepare('SELECT 1 FROM dbo.Employee_Dempc_TimeAttendance WHERE empcode = ? AND work_date = ?');
                $check->execute([$empcode, $dateKey]);
                if ($check->fetchColumn() === false) {
                    $ins = $db->prepare("
                        INSERT INTO dbo.Employee_Dempc_TimeAttendance
                            (empcode, work_date, $actualCol, $sourceCol, $reqCol, is_manual, updated_by, updated_at)
                        VALUES (?, ?, ?, ?, ?, 1, ?, SYSDATETIME())
                    ");
                    $ins->execute([$empcode, $dateKey, $timeHHMM, $source, $requestId, $actor]);
                }
            }
        } catch (\Throwable $e) {
            error_log('[DempcSync::write] ' . $e->getMessage());
        }
    }
}
