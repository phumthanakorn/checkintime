<?php
declare(strict_types=1);

namespace Api\Checkin\Controllers;

use Models\Checkin\CheckinAttendanceLogModel;

// GET /calendar?month=YYYY-MM — ใช้โดยหน้า "ประวัติ" (ปฏิทินการลงเวลา) ดึงจาก
// Employee_CheckinTime_Attendance_Log เท่านั้น (ข้อเท็จจริงเรื่องเข้า-ออกงานล้วนๆ) ส่วน shift/holiday/leave
// ยังคืน null ทุกวัน เพราะยังไม่มีแหล่งข้อมูลจริงมาต่อ (ระบบกะ/วันหยุด/ใบลา ยังไม่ได้ทำ endpoint นี้) —
// frontend (CalendarMonth.vue/CalendarDayDetail.vue) รองรับค่า null พวกนี้อยู่แล้วโดยไม่พัง แค่ไม่แสดงผล
// ส่วนนั้น ไม่มีการตัดสิน "สาย/OT" ใดๆ ทั้งสิ้น ตามที่ตัดออกไปแล้วทั้งระบบ
class CalendarController extends AbstractApiController
{
    public function getMonth(): void
    {
        $month = (string) ($_GET['month'] ?? date('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            self::error('รูปแบบเดือนไม่ถูกต้อง', 400, 'INVALID_MONTH');
        }

        $model = new CheckinAttendanceLogModel();
        $rows = $model->findMonth((string) $this->empcode, $month);
        // ตารางหลัก (Excel import / แก้มือผ่านหน้า DEMPC เดิม / sync จากแอปนี้) — ใช้เป็น fallback ตอนตาราง
        // ของแอปเองไม่มีข้อมูลของวันนั้นเลย ดู comment ที่ findDempcMonth()
        $dempcByDate = $model->findDempcMonth((string) $this->empcode, $month);

        $byDate = [];
        foreach ($rows as $row) {
            $byDate[substr((string) $row['work_date'], 0, 10)] = $row;
        }

        [$y, $m] = array_map('intval', explode('-', $month));
        $dayCount = (int) date('t', mktime(0, 0, 0, $m, 1, $y));
        $today = date('Y-m-d');

        $days = [];
        for ($d = 1; $d <= $dayCount; $d++) {
            $dateKey = sprintf('%s-%02d', $month, $d);
            $row = $byDate[$dateKey] ?? null;
            $effective = $row !== null ? CheckinAttendanceLogModel::effectiveTimes($row) : null;

            // ไม่มีข้อมูลจากตารางของแอปเองเลย (ไม่เคยสแกน/ไม่มีคำขอที่อนุมัติ) → ลองดึงจากตารางหลัก
            // (Employee_Dempc_TimeAttendance) แทน เผื่อมี Excel import หรือ HR แก้ตรงๆ ผ่านหน้าเดิมไว้แล้ว
            if (($effective === null || (empty($effective['check_in']) && empty($effective['check_out'])))
                && isset($dempcByDate[$dateKey])) {
                $effective = CheckinAttendanceLogModel::legacyEffectiveTimes($dateKey, $dempcByDate[$dateKey]);
            }

            $days[] = [
                'date' => $dateKey,
                'shift' => null,
                'holiday' => null,
                'leave' => null,
                'attendance' => self::attendanceStatus($dateKey, $today, $effective),
                // เวลาที่มีผลจริง (อนุมัติแล้วใช้ค่าที่อนุมัติก่อน) + แหล่งที่มา — ดู effectiveTimes()
                'checkIn' => self::toIso($effective['check_in'] ?? null),
                'checkOut' => self::toIso($effective['check_out'] ?? null),
                'checkInSource' => $effective['check_in_source'] ?? null,
                'checkOutSource' => $effective['check_out_source'] ?? null,
                'checkInAdjustment' => $row !== null ? self::adjustment($row, 'check_in') : null,
                'checkOutAdjustment' => $row !== null ? self::adjustment($row, 'check_out') : null,
                'checkInDetail' => $row !== null ? self::detail($row, 'check_in') : null,
                'checkOutDetail' => $row !== null ? self::detail($row, 'check_out') : null,
            ];
        }

        self::json(['month' => $month, 'days' => $days]);
    }

    // ไม่มีการตัดสินสาย/OT — แค่บอกว่าวันนั้น "มีลงเวลา" (ครบ/ไม่ครบ/กำลังทำงานอยู่) หรือ "ไม่มีลงเวลาเลย"
    // เช็คแค่ "มีวันที่" อย่างเดียว ไม่สนว่าวันนั้นเป็นวันอะไรของสัปดาห์ (ไม่แยกเสาร์-อาทิตย์ออก) ตามที่ยืนยัน
    // ไว้ — ถ้าไม่มีการสแกนเข้าของวันที่ผ่านมาแล้ว ก็ถือว่า "ขาดลงเวลา" เสมอ ไม่ว่าจะวันไหนก็ตาม
    private static function attendanceStatus(string $dateKey, string $today, ?array $row): ?string
    {
        if ($dateKey > $today) return null;

        if ($row !== null && !empty($row['check_in'])) {
            if (!empty($row['check_out'])) return 'on_time';
            return $dateKey === $today ? 'working' : 'incomplete';
        }

        if ($dateKey === $today) return null;

        return 'missing';
    }

    // รายละเอียดเพิ่มเติมของการลงเวลาแต่ละฝั่ง (เข้า/ออก) ใช้ตอนกดดูวันที่มีการลงเวลาแล้วในปฏิทิน คืน null
    // ถ้ายังไม่ได้ลงเวลาฝั่งนั้นเลย (ไม่ใช่แค่ไม่มีรายละเอียด) — "time" ตรงนี้คือเวลาที่ "สแกนจริง" เสมอ
    // (ไม่ใช่ effective time ที่ day.checkIn/checkOut ใช้ ซึ่งอาจถูกค่าที่อนุมัติทับไปแล้ว) ตั้งใจแยกไว้ให้
    // bottom sheet ดูรายละเอียดการสแกนจริงได้ แม้วันนั้นจะมีการขอลงเวลาย้อนหลังทับอยู่ก็ตาม
    private static function adjustment(array $row, string $prefix): ?array
    {
        if (empty($row["approved_{$prefix}"])) return null;
        return [
            'requestId' => isset($row["approved_{$prefix}_request_id"]) ? (int) $row["approved_{$prefix}_request_id"] : null,
            'time' => self::toIso($row["approved_{$prefix}"]),
            'reason' => $row["{$prefix}_request_reason"] ?? null,
            'status' => 'approved',
            'reviewedBy' => $row["{$prefix}_reviewed_by"] ?? null,
            'reviewedAt' => self::toIso($row["{$prefix}_reviewed_at"] ?? null),
            'reviewNote' => $row["{$prefix}_review_note"] ?? null,
            'attachments' => \Models\Checkin\TimeFixAttachmentStorage::publicMetadata($row["{$prefix}_attachments_json"] ?? null),
        ];
    }
    private static function detail(array $row, string $prefix): ?array
    {
        if (empty($row[$prefix])) return null;

        return [
            'time' => self::toIso($row[$prefix]),
            'locationName' => $row["{$prefix}_location_name"] ?? null,
            'distanceMeters' => $row["{$prefix}_nearest_distance_m"] !== null ? (float) $row["{$prefix}_nearest_distance_m"] : null,
            'device' => $row["{$prefix}_device"] ?: null,
            'lat' => $row["{$prefix}_lat"] !== null ? (float) $row["{$prefix}_lat"] : null,
            'lng' => $row["{$prefix}_lng"] !== null ? (float) $row["{$prefix}_lng"] : null,
            'accuracyMeters' => $row["{$prefix}_accuracy_m"] !== null ? (int) $row["{$prefix}_accuracy_m"] : null,
            'hasPhoto' => !empty($row["{$prefix}_photo_path"]),
        ];
    }

    // MSSQL DATETIME2 ไม่มี timezone ติดมา — แปลงเป็น ISO 8601 ที่มี offset ชัดเจน (ตั้งไว้ที่
    // core/autoload.php) ไม่งั้น frontend (new Date(iso)) จะตีความเป็น UTC ผิดโซนเวลา
    private static function toIso(?string $datetime): ?string
    {
        if ($datetime === null) return null;
        $dt = new \DateTime($datetime, new \DateTimeZone(date_default_timezone_get()));
        return $dt->format('c');
    }
}
