<?php
declare(strict_types=1);

namespace Api\Checkin\Controllers;

use Models\Checkin\CheckinAttendanceLogModel;

// GET /attendance/today, POST /attendance/check-in, POST /attendance/check-out — เขียนลง
// GBG_Data.dbo.Employee_CheckinTime_Attendance_Log (ตารางแยกเฉพาะมือถือ ไม่แตะ hrtime เลย ตามที่ตกลงกับ
// HR/IT ไว้ — ดู structure/create_checkintime_logs.sql) รูปแบบ record ที่ตอบกลับต้องตรงกับที่ frontend
// เคยใช้ตอนจำลองไว้ใน src/api/services/simulatedAttendance.js เป๊ะๆ (camelCase, ชื่อฟิลด์เดียวกัน) เพราะ
// หน้า UI (HomeView ฯลฯ) ผูก field เหล่านี้ไว้แล้วไม่ว่าจะมาจาก service ไหน
class AttendanceController extends AbstractApiController
{
    private function toRecord(array $row): array
    {
        $hasLat = $row['check_in_lat'] !== null;
        $hasOutLat = $row['check_out_lat'] !== null;
        $effective = CheckinAttendanceLogModel::effectiveTimes($row);

        return [
            'id' => (int) $row['id'],
            'empcode' => (string) $row['empcode'],
            'date' => (string) $row['work_date'],
            // checkIn/checkOut = เวลาที่มีผลจริง (อนุมัติแล้วใช้ค่าที่อนุมัติ ไม่งั้นใช้เวลาสแกน) — ชื่อ field เดิม
            // ไม่เปลี่ยน หน้า UI เดิมใช้ต่อได้เลย ส่วน *Source/scanned* แยกไว้ให้รู้ที่มา
            'checkIn' => self::toIso($effective['check_in']),
            'checkOut' => self::toIso($effective['check_out']),
            'checkInSource' => $effective['check_in_source'],
            'checkOutSource' => $effective['check_out_source'],
            'scannedCheckIn' => self::toIso($row['check_in']),
            'scannedCheckOut' => self::toIso($row['check_out']),
            'checkInLocation' => $hasLat ? [
                'lat' => (float) $row['check_in_lat'],
                'lng' => (float) $row['check_in_lng'],
                'accuracy' => $row['check_in_accuracy_m'] !== null ? (int) $row['check_in_accuracy_m'] : null,
            ] : null,
            'checkInContext' => [
                'nearestLocation' => $row['check_in_nearest_location_id'] !== null ? [
                    'location' => ['id' => (int) $row['check_in_nearest_location_id'], 'name' => $row['check_in_location_name'] ?? null],
                    'distance' => $row['check_in_nearest_distance_m'] !== null ? (float) $row['check_in_nearest_distance_m'] : null,
                ] : null,
                'device' => $row['check_in_device'],
                'hasPhoto' => !empty($row['check_in_photo_path']),
            ],
            'checkOutLocation' => $hasOutLat ? [
                'lat' => (float) $row['check_out_lat'],
                'lng' => (float) $row['check_out_lng'],
                'accuracy' => $row['check_out_accuracy_m'] !== null ? (int) $row['check_out_accuracy_m'] : null,
            ] : null,
            'checkOutContext' => !empty($row['check_out']) ? [
                'nearestLocation' => $row['check_out_nearest_location_id'] !== null ? [
                    'location' => ['id' => (int) $row['check_out_nearest_location_id'], 'name' => $row['check_out_location_name'] ?? null],
                    'distance' => $row['check_out_nearest_distance_m'] !== null ? (float) $row['check_out_nearest_distance_m'] : null,
                ] : null,
                'device' => $row['check_out_device'],
                'hasPhoto' => !empty($row['check_out_photo_path']),
            ] : null,
            'lateMinutes' => (int) $row['late_minutes'],
            'workMinutes' => $effective['work_minutes'],
            'otMinutes' => (int) $row['ot_minutes'],
        ];
    }

    // MSSQL DATETIME2 คืนมาจาก PDO เป็น "YYYY-MM-DD HH:MM:SS(.ffffff)" ไม่มี timezone — ต้องแปลงเป็น
    // ISO 8601 ที่มี offset +07:00 ชัดเจน (ตั้งไว้ที่ core/autoload.php) ไม่งั้น frontend (new Date(iso))
    // จะตีความเป็น UTC ผิดโซนเวลา
    private static function toIso(?string $datetime): ?string
    {
        if ($datetime === null) return null;
        $dt = new \DateTime($datetime, new \DateTimeZone(date_default_timezone_get()));
        return $dt->format('c');
    }

    public function today(): void
    {
        $model = new CheckinAttendanceLogModel();
        $row = $model->findToday((string) $this->empcode);
        if ($row !== null) {
            self::json($this->toRecord($row));
        }

        // ไม่มีข้อมูลจากตารางของแอปเองเลย (ไม่เคยสแกน/ไม่มีคำขอที่อนุมัติ) → ลองดึงจากตารางหลัก
        // (Employee_Dempc_TimeAttendance) แทน เผื่อมี Excel import หรือ HR แก้ตรงๆ ผ่านหน้าเดิมไว้แล้ว — ดู
        // CalendarController ที่ทำแบบเดียวกันกับปฏิทินทั้งเดือน
        $legacy = $model->findDempcToday((string) $this->empcode);
        $today = date('Y-m-d');
        if ($legacy !== null && (!empty($legacy['actual_in']) || !empty($legacy['actual_out']))) {
            $effective = CheckinAttendanceLogModel::legacyEffectiveTimes($today, $legacy);
            self::json([
                'id' => 0, 'empcode' => (string) $this->empcode, 'date' => $today,
                'checkIn' => self::toIso($effective['check_in']),
                'checkOut' => self::toIso($effective['check_out']),
                'checkInSource' => $effective['check_in_source'],
                'checkOutSource' => $effective['check_out_source'],
                'scannedCheckIn' => null, 'scannedCheckOut' => null,
                'checkInLocation' => null, 'checkOutLocation' => null,
                'checkInContext' => ['nearestLocation' => null, 'device' => null, 'hasPhoto' => false],
                'checkOutContext' => null,
                'lateMinutes' => 0, 'workMinutes' => $effective['work_minutes'], 'otMinutes' => 0,
            ]);
        }

        self::json(null);
    }

    // GET /attendance/photo?date=YYYY-MM-DD&side=checkIn|checkOut — สตรีมรูปถ่ายยืนยันตัวตนตอนสแกนของ
    // ตัวเองเท่านั้น (เจ้าของ JWT เท่านั้น ไม่มี role HR/admin ใดๆ มาดูของคนอื่นได้ในเส้นนี้) รูปแบบการสตรีม
    // ไฟล์เลียนแบบ TimeFixController::attachment() ที่มีอยู่แล้ว (fopen + fpassthru + header ชุดเดียวกัน)
    public function photo(): void
    {
        $date = (string) ($_GET['date'] ?? '');
        $side = (string) ($_GET['side'] ?? '');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !in_array($side, ['checkIn', 'checkOut'], true)) {
            self::error('พารามิเตอร์ไม่ถูกต้อง', 400, 'INVALID_PARAMS');
        }

        $model = new CheckinAttendanceLogModel();
        $row = $model->findDay((string) $this->empcode, $date);
        $column = $side === 'checkIn' ? 'check_in_photo_path' : 'check_out_photo_path';
        $path = $row !== null ? $model->resolvePhotoPath($row[$column] ?? null) : null;
        if ($path === null) self::error('ไม่พบรูปถ่าย', 404, 'PHOTO_NOT_FOUND');

        $handle = @fopen($path, 'rb');
        if ($handle === false) self::error('ไม่พบรูปถ่าย', 404, 'PHOTO_NOT_FOUND');
        $mime = CheckinAttendanceLogModel::photoMimeFromPath($path);
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: inline; filename="checkin-photo.' . pathinfo($path, PATHINFO_EXTENSION) . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        header("Content-Security-Policy: sandbox; default-src 'none'");
        fpassthru($handle);
        fclose($handle);
        exit;
    }

    public function history(): void
    {
        // TODO: ยังไม่มีการเรียกใช้จริงจาก frontend รอบนี้ — ต่อเมื่อต้องทำหน้าประวัติ
        self::json([]);
    }

    public function summary(): void
    {
        // TODO: ต่อกับยอดลาคงเหลือจริงทีหลัง — ตอนนี้ยังคืนศูนย์ไปก่อนกัน UI พัง
        self::json(['leaveRemainingDays' => 0, 'lateCount' => 0, 'lateMinutes' => 0, 'otMinutes' => 0]);
    }

    public function checkIn(): void
    {
        $body = self::body();
        $model = new CheckinAttendanceLogModel();

        // เช็คจาก check_in เป็น null ไหม ไม่ใช่แค่ "มีแถวหรือยัง" — ถ้า HR อนุมัติคำขอลงเวลาย้อนหลังของวันนี้
        // ไว้ล่วงหน้า (ก่อนพนักงานสแกนจริง) จะมีแถวของวันนี้อยู่แล้วแต่ check_in ยังเป็น NULL กรณีนี้ต้องให้
        // สแกนเข้าได้ปกติ ไม่ใช่ถูกบล็อกว่า "ลงเวลาไปแล้ว" ทั้งที่ยังไม่เคยสแกนจริงเลย
        $existing = $model->findToday((string) $this->empcode);
        if ($existing !== null && !empty($existing['check_in'])) {
            self::error('วันนี้คุณลงเวลาเข้างานไปแล้ว', 409, 'ALREADY_CHECKED_IN');
        }

        $row = $model->checkIn((string) $this->empcode, [
            'location' => $body['location'] ?? null,
            'nearestLocation' => $body['nearestLocation'] ?? null,
            'device' => $body['device'] ?? null,
            'photo' => $body['photo'] ?? null,
            'ip' => self::clientIp(),
        ]);

        if ($row === false) {
            self::error('วันนี้คุณลงเวลาเข้างานไปแล้ว', 409, 'ALREADY_CHECKED_IN');
        }

        self::json($this->toRecord($row));
    }

    public function checkOut(): void
    {
        $body = self::body();
        $model = new CheckinAttendanceLogModel();

        $today = $model->findToday((string) $this->empcode);
        if ($today === null || empty($today['check_in'])) {
            self::error('กรุณาลงเวลาเข้างานก่อน', 400, 'NOT_CHECKED_IN');
        }
        if (!empty($today['check_out'])) {
            self::error('วันนี้คุณลงเวลาออกงานไปแล้ว', 409, 'ALREADY_CHECKED_OUT');
        }

        $row = $model->checkOut((string) $this->empcode, [
            'location' => $body['location'] ?? null,
            'nearestLocation' => $body['nearestLocation'] ?? null,
            'device' => $body['device'] ?? null,
            'photo' => $body['photo'] ?? null,
            'ip' => self::clientIp(),
        ]);

        if ($row === false) {
            self::error('วันนี้คุณลงเวลาออกงานไปแล้ว', 409, 'ALREADY_CHECKED_OUT');
        }

        self::json($this->toRecord($row));
    }
}
