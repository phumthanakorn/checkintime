<?php
declare(strict_types=1);

namespace Models\Checkin;

use Core\Model;
use PDO;

// บันทึกลงเวลาเข้า-ออกงานผ่านแอป CheckInTime ลง GBG_Data.dbo.Employee_CheckinTime_Attendance_Log
// (1 แถวต่อ 1 คนต่อ 1 วัน — ดู structure/create_checkintime_logs.sql) ไม่แตะ hrtime เลยตามที่ตกลงกับ
// HR/IT ไว้ (hrtime รับข้อมูลจากเครื่องสแกนนิ้วเป็นหลัก ไม่ควรเขียนทับจากทางอื่น)
//
// ไม่มีการวิเคราะห์สาย/โอทีใดๆ ทั้งสิ้น — ระบบนี้ไม่มีกะ/เวลาเข้างานที่แน่ชัดกำหนดไว้ (ตามที่ผู้ใช้ยืนยัน)
// หน้าที่เดียวคือ "บันทึกข้อเท็จจริง": เวลาเข้า-ออก + ตำแหน่งจริงที่ลงเวลา + สถานที่ (Dempc Location) ที่
// อยู่ใกล้ที่สุดตอนนั้น late_minutes/ot_minutes ในตารางเก็บไว้เป็น 0 เสมอ (คอลัมน์ยังคงไว้เผื่ออนาคต แต่ไม่
// คำนวณ) work_minutes เก็บเป็นระยะเวลาที่อยู่จริง (ข้อเท็จจริง ไม่ใช่การตัดสินว่าสาย/โอที)
class CheckinAttendanceLogModel extends Model
{
    private const PHOTO_DIR = __DIR__ . '/../../storage/photos';
    // รูปแบบเดียวกับ CheckinAuthModel::PHOTO_TYPES — ตรวจชนิดไฟล์จริงก่อนตั้งนามสกุล ไม่เขียนเป็น .jpg
    // คงที่เหมือนเดิม (กล้องในแอปบีบเป็น JPEG เป็นหลัก แต่เผื่อ browser/อุปกรณ์บางรุ่นส่ง PNG/WEBP มาแทน)
    private const PHOTO_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    // ชี้ไป production เสมอ (ไม่ fallback ไป dbGbgDataTest แล้ว) — ให้ตรงกับ DempcTimeFixModel/TimeFixRequestModel
    // กัน isLocal routing bug ที่ข้อมูลที่ HR อนุมัติบน prod ไม่โผล่ฝั่งแอปพนักงานที่ทดสอบผ่าน localhost ตรงๆ
    private function gbgDb(): ?PDO
    {
        return $this->gbgDataForCurrentEnvironment();
    }

    // เวลาที่ "มีผลจริง" ของแต่ละฝั่ง — ถ้า HR อนุมัติคำขอลงเวลาย้อนหลังไว้ (approved_check_in/out) ใช้ค่านั้นก่อน
    // ไม่งั้นใช้เวลาสแกนจริง (check_in/out) ไม่เขียนทับเวลาสแกนเดิมเลย แค่เลือกตอนอ่าน — source บอกที่มาให้ UI
    // แยกแสดงได้ ('approved' | 'scan' | null ถ้าไม่มีทั้งคู่)
    public static function effectiveTimes(array $row): array
    {
        $pick = static function (?string $approved, ?string $scanned): array {
            if ($approved !== null && $approved !== '') return [$approved, 'approved'];
            if ($scanned !== null && $scanned !== '') return [$scanned, 'scan'];
            return [null, null];
        };
        [$in, $inSource] = $pick($row['approved_check_in'] ?? null, $row['check_in'] ?? null);
        [$out, $outSource] = $pick($row['approved_check_out'] ?? null, $row['check_out'] ?? null);

        // นาทีทำงานคิดจากเวลาที่มีผลจริง (ไม่ใช่ work_minutes ในตารางที่คิดจากเวลาสแกนตอนกดออกงาน)
        $workMinutes = null;
        if ($in !== null && $out !== null) {
            $diff = (int) floor((strtotime($out) - strtotime($in)) / 60);
            $workMinutes = $diff >= 0 ? $diff : null;
        }

        return [
            'check_in' => $in, 'check_in_source' => $inSource,
            'check_out' => $out, 'check_out_source' => $outSource,
            'work_minutes' => $workMinutes,
        ];
    }

    // JOIN ชื่อสถานที่ (Master_Dempc_Location) ด้วยเลย — อยู่ฐานข้อมูล GBG_Data เดียวกัน ให้หน้าหลัก
    // (TodayAttendanceCard.vue) โชว์ชื่อสถานที่ได้โดยไม่ต้องยิง /locations แยกมา merge เอง
    public function findToday(string $empcode): ?array
    {
        $db = $this->gbgDb();
        if ($db === null) return null;

        $stmt = $db->prepare("
            SELECT a.*, locIn.location_name AS check_in_location_name, locOut.location_name AS check_out_location_name
            FROM dbo.Employee_CheckinTime_Attendance_Log a
            LEFT JOIN dbo.Master_Dempc_Location locIn ON locIn.location_id = a.check_in_nearest_location_id
            LEFT JOIN dbo.Master_Dempc_Location locOut ON locOut.location_id = a.check_out_nearest_location_id
            WHERE a.empcode = ? AND a.work_date = CAST(SYSDATETIME() AS DATE)
        ");
        $stmt->execute([$empcode]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // ดึงทุกแถวของเดือนนั้น (monthKey = 'YYYY-MM') ใช้ทำปฏิทิน /calendar — คืน array เรียงตาม work_date
    // (อาจมีน้อยกว่าจำนวนวันในเดือน เพราะมีแค่วันที่ลงเวลาจริงเท่านั้น ผู้เรียกต้อง merge เข้ากับ
    // รายการวันทั้งหมดของเดือนเอง) JOIN ชื่อสถานที่ (Master_Dempc_Location) ด้วยเลย เพราะอยู่ฐานข้อมูลเดียวกัน
    // (GBG_Data) — ไม่ใช่ dbRecruit ที่ JOIN ข้ามเครื่องไม่ได้
    public function findMonth(string $empcode, string $monthKey): array
    {
        $db = $this->gbgDb();
        if ($db === null) return [];

        $stmt = $db->prepare("
            SELECT a.*, locIn.location_name AS check_in_location_name, locOut.location_name AS check_out_location_name,
                   reqIn.reason AS check_in_request_reason, reqIn.reviewed_by AS check_in_reviewed_by,
                   reqIn.reviewed_at AS check_in_reviewed_at, reqIn.review_note AS check_in_review_note, reqIn.attachments_json AS check_in_attachments_json,
                   reqOut.reason AS check_out_request_reason, reqOut.reviewed_by AS check_out_reviewed_by,
                   reqOut.reviewed_at AS check_out_reviewed_at, reqOut.review_note AS check_out_review_note, reqOut.attachments_json AS check_out_attachments_json
            FROM dbo.Employee_CheckinTime_Attendance_Log a
            LEFT JOIN dbo.Master_Dempc_Location locIn ON locIn.location_id = a.check_in_nearest_location_id
            LEFT JOIN dbo.Master_Dempc_Location locOut ON locOut.location_id = a.check_out_nearest_location_id
            LEFT JOIN dbo.Employee_Attendance_TimeFix_Request reqIn ON reqIn.id = a.approved_check_in_request_id AND reqIn.empcode = a.empcode AND reqIn.work_date = a.work_date AND reqIn.status = 'approved'
            LEFT JOIN dbo.Employee_Attendance_TimeFix_Request reqOut ON reqOut.id = a.approved_check_out_request_id AND reqOut.empcode = a.empcode AND reqOut.work_date = a.work_date AND reqOut.status = 'approved'
            WHERE a.empcode = ? AND a.work_date >= ? AND a.work_date < DATEADD(MONTH, 1, CAST(? AS DATE))
            ORDER BY a.work_date ASC
        ");
        $firstOfMonth = $monthKey . '-01';
        $stmt->execute([$empcode, $firstOfMonth, $firstOfMonth]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    // เสริม: ถ้าวันไหนไม่มีแถวในตารางของแอปเองเลย (ไม่เคยสแกน/ไม่มีคำขอลงเวลาย้อนหลังที่อนุมัติ) ให้ลองดึงจาก
    // Employee_Dempc_TimeAttendance แทน — ตารางนั้นเป็น "ตารางหลัก" ที่รวมข้อมูลจากหลายทาง (Excel import,
    // แก้มือผ่านหน้า DEMPC เดิม, และที่ sync มาจากแอปนี้เองด้วย) ถ้า HR แก้เวลาตรงๆ ผ่านหน้าเดิมโดยไม่ผ่านแอป
    // เลย ปฏิทินของแอปก็ควรเห็นด้วย ไม่งั้นพนักงานจะเห็นว่า "ขาดลงเวลา" ทั้งที่จริงมีคนลงเวลาให้แล้ว
    // คืน array คีย์ด้วย work_date ('YYYY-MM-DD') => ['actual_in'=>'HH:MM'|null, 'actual_out'=>..., ...]
    public function findDempcMonth(string $empcode, string $monthKey): array
    {
        $db = $this->gbgDb();
        if ($db === null) return [];

        $stmt = $db->prepare("
            SELECT CONVERT(VARCHAR(10), work_date, 120) AS work_date,
                   actual_in, actual_out, checkin_location, checkin_dist_m, checkin_method,
                   checkout_location, checkout_dist_m
            FROM dbo.Employee_Dempc_TimeAttendance
            WHERE empcode = ? AND work_date >= ? AND work_date < DATEADD(MONTH, 1, CAST(? AS DATE))
        ");
        $firstOfMonth = $monthKey . '-01';
        $stmt->execute([$empcode, $firstOfMonth, $firstOfMonth]);

        $map = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $r) {
            $map[substr((string) $r['work_date'], 0, 10)] = $r;
        }
        return $map;
    }

    // ดึงแถวของวันที่ระบุวันเดียว (ไม่ใช่ "วันนี้" แบบ findToday()) — ใช้โดย /attendance/photo ตอนย้อนดู
    // รายละเอียดการสแกนของวันในอดีตจากปฏิทิน ไม่ JOIN ชื่อสถานที่เพราะผู้เรียกสนใจแค่ path รูปเท่านั้น
    public function findDay(string $empcode, string $dateKey): ?array
    {
        $db = $this->gbgDb();
        if ($db === null) return null;

        $stmt = $db->prepare("
            SELECT a.* FROM dbo.Employee_CheckinTime_Attendance_Log a
            WHERE a.empcode = ? AND a.work_date = CAST(? AS DATE)
        ");
        $stmt->execute([$empcode, $dateKey]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // path เต็มบนดิสก์ของรูปที่เก็บไว้ (เช็ค prefix ให้ตรง PHOTO_DIR เสมอ ป้องกัน path traversal แม้ว่า
    // path ที่เก็บใน DB จะมาจาก savePhoto() ของเราเองไม่ใช่ input จาก client ตรงๆ ก็ตาม — กันเหนียวอีกชั้น)
    public function resolvePhotoPath(?string $storedPath): ?string
    {
        if (empty($storedPath)) return null;
        $full = realpath(__DIR__ . '/../../' . preg_replace('~^/?checkin/~', '', ltrim($storedPath, '/')));
        if ($full === false) return null;
        if (!str_starts_with($full, realpath(self::PHOTO_DIR))) return null;
        return $full;
    }

    // ชนิดไฟล์จริงจากนามสกุลที่ resolvePhotoPath() คืนมา — ใช้ตั้ง Content-Type ตอนสตรีมรูปให้ตรงกับไฟล์จริง
    // (ก่อนหน้านี้ AttendanceController::photo() ฮาร์ดโค้ด image/jpeg เสมอ)
    public static function photoMimeFromPath(string $path): string
    {
        return array_search(strtolower(pathinfo($path, PATHINFO_EXTENSION)), self::PHOTO_TYPES, true) ?: 'image/jpeg';
    }

    // เหมือน findDempcMonth() แต่ของวันนี้วันเดียว — ใช้โดย /attendance/today (การ์ดหน้าแรก)
    public function findDempcToday(string $empcode): ?array
    {
        $db = $this->gbgDb();
        if ($db === null) return null;

        $stmt = $db->prepare("
            SELECT actual_in, actual_out, checkin_location, checkin_dist_m, checkin_method,
                   checkout_location, checkout_dist_m
            FROM dbo.Employee_Dempc_TimeAttendance
            WHERE empcode = ? AND work_date = CAST(SYSDATETIME() AS DATE)
        ");
        $stmt->execute([$empcode]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // แปลงแถว Employee_Dempc_TimeAttendance (actual_in/out เป็น "HH:MM") ให้อยู่ในรูปแบบเดียวกับ
    // effectiveTimes() — ใช้ตอน fallback เท่านั้น (เรียกเมื่อไม่มีข้อมูลจากตารางของแอปเองแล้ว)
    public static function legacyEffectiveTimes(string $dateKey, array $legacy): array
    {
        $toIso = static fn(?string $hhmm): ?string => !empty($hhmm) ? "$dateKey " . substr($hhmm, 0, 5) . ':00' : null;
        $in = $toIso($legacy['actual_in'] ?? null);
        $out = $toIso($legacy['actual_out'] ?? null);

        $workMinutes = null;
        if ($in !== null && $out !== null) {
            $diff = (int) floor((strtotime($out) - strtotime($in)) / 60);
            $workMinutes = $diff >= 0 ? $diff : null;
        }

        return [
            'check_in' => $in, 'check_in_source' => $in !== null ? 'legacy' : null,
            'check_out' => $out, 'check_out_source' => $out !== null ? 'legacy' : null,
            'work_minutes' => $workMinutes,
        ];
    }

    // คืน record ที่เพิ่งสร้าง (คืน false ถ้าวันนี้ลงเวลาเข้างานไปแล้ว — ผู้เรียกต้องเช็คก่อนเองด้วย findToday()
    // แต่กันซ้ำอีกชั้นด้วย UNIQUE constraint ของตาราง เผื่อ race condition กดพร้อมกัน 2 ครั้ง)
    public function checkIn(string $empcode, array $data): array|false
    {
        $db = $this->gbgDb();
        if ($db === null) throw new \RuntimeException('เชื่อมต่อฐานข้อมูลไม่ได้');

        $photoPath = $this->savePhoto($empcode, 'in', $data['photo'] ?? null);
        $nearest = $data['nearestLocation'] ?? null;
        $params = [
            $data['location']['lat'] ?? null,
            $data['location']['lng'] ?? null,
            $data['location']['accuracy'] ?? null,
            $nearest['location']['id'] ?? null,
            $nearest['distance'] ?? null,
            $data['device'] ?? null,
            $data['ip'] ?? null,
            $photoPath,
        ];

        try {
            // ลอง UPDATE ก่อนเสมอ — ครอบกรณีที่ HR อนุมัติคำขอลงเวลาย้อนหลังของวันนี้ไว้ล่วงหน้า (สร้างแถว
            // ของวันนี้ไว้แล้วด้วย approved_check_in แต่ check_in ยังเป็น NULL เพราะยังไม่เคยสแกนจริง) ถ้าเจอ
            // แถวแบบนี้ให้เติมข้อมูลสแกนจริงเข้าไปแทนที่จะ INSERT ซ้ำแล้วชน UNIQUE(empcode, work_date)
            $stmt = $db->prepare("
                UPDATE dbo.Employee_CheckinTime_Attendance_Log SET
                    check_in = SYSDATETIME(), check_in_lat = ?, check_in_lng = ?, check_in_accuracy_m = ?,
                    check_in_nearest_location_id = ?, check_in_nearest_distance_m = ?, check_in_device = ?,
                    check_in_ip = ?, check_in_photo_path = ?, updated_at = SYSDATETIME()
                WHERE empcode = ? AND work_date = CAST(SYSDATETIME() AS DATE) AND check_in IS NULL
            ");
            $stmt->execute([...$params, $empcode]);

            if ($stmt->rowCount() === 0) {
                // ไม่มีแถวให้ UPDATE — อาจเพราะยังไม่เคยมีแถวของวันนี้เลย (ปกติ) หรือมีแถวแล้วแต่ check_in
                // ไม่ใช่ NULL (ลงเวลาไปแล้วจริงๆ) ทั้งสองกรณีให้ลอง INSERT: กรณีแรกจะสำเร็จ กรณีหลังจะชน
                // UNIQUE constraint แล้วตกไป catch ด้านล่าง คืน false ตามเดิม (พฤติกรรมเดิมไม่เปลี่ยน)
                $stmt = $db->prepare("
                    INSERT INTO dbo.Employee_CheckinTime_Attendance_Log
                        (empcode, work_date, check_in, check_in_lat, check_in_lng, check_in_accuracy_m,
                         check_in_nearest_location_id, check_in_nearest_distance_m, check_in_device, check_in_ip,
                         check_in_photo_path, late_minutes)
                    VALUES
                        (?, CAST(SYSDATETIME() AS DATE), SYSDATETIME(), ?, ?, ?, ?, ?, ?, ?, ?, 0)
                ");
                $stmt->execute([$empcode, ...$params]);
            }
        } catch (\Throwable $e) {
            // ชน UNIQUE(empcode, work_date) = วันนี้เพิ่งลงเวลาไปแล้ว (เช่นกดพร้อมกัน 2 แท็บ)
            error_log('[CheckinAttendanceLogModel::checkIn] ' . $e->getMessage());
            return false;
        }

        // sync เติมเข้า Employee_Dempc_TimeAttendance ด้วย (เฉพาะช่องที่ยังว่าง, best-effort ไม่บล็อกถ้าพลาด)
        DempcSync::write($db, $empcode, date('Y-m-d'), 'in', date('H:i'), 'checkintime_scan', null, $empcode);

        return $this->findToday($empcode) ?? [];
    }

    public function checkOut(string $empcode, array $data): array|false
    {
        $db = $this->gbgDb();
        if ($db === null) throw new \RuntimeException('เชื่อมต่อฐานข้อมูลไม่ได้');

        $today = $this->findToday($empcode);
        if ($today === null || empty($today['check_in']) || !empty($today['check_out'])) {
            return false;
        }

        $photoPath = $this->savePhoto($empcode, 'out', $data['photo'] ?? null);
        $nearest = $data['nearestLocation'] ?? null;

        $stmt = $db->prepare("
            UPDATE dbo.Employee_CheckinTime_Attendance_Log
            SET check_out = SYSDATETIME(),
                check_out_lat = ?, check_out_lng = ?, check_out_accuracy_m = ?,
                check_out_nearest_location_id = ?, check_out_nearest_distance_m = ?,
                check_out_device = ?, check_out_ip = ?, check_out_photo_path = ?,
                work_minutes = DATEDIFF(MINUTE, check_in, SYSDATETIME()),
                ot_minutes = 0,
                updated_at = SYSDATETIME()
            WHERE empcode = ? AND work_date = CAST(SYSDATETIME() AS DATE) AND check_out IS NULL
        ");
        $stmt->execute([
            $data['location']['lat'] ?? null,
            $data['location']['lng'] ?? null,
            $data['location']['accuracy'] ?? null,
            $nearest['location']['id'] ?? null,
            $nearest['distance'] ?? null,
            $data['device'] ?? null,
            $data['ip'] ?? null,
            $photoPath,
            $empcode,
        ]);

        // เช็ค check_out IS NULL ใน WHERE ข้างบนแล้ว (เหมือน checkIn() เช็ค check_in IS NULL) กัน 2 request
        // ออกงานพร้อมกัน (เช่นกดสองแท็บ) ชิงกันเขียนทับ — ถ้า row ไม่ match เลย (อีกฝั่งชนะไปก่อนแล้ว) ให้ตอบ
        // false แทนที่จะเงียบๆ คืนข้อมูลของอีกฝั่งราวกับสำเร็จ
        if ($stmt->rowCount() === 0) {
            return false;
        }

        // sync เติมเข้า Employee_Dempc_TimeAttendance ด้วย (เฉพาะช่องที่ยังว่าง, best-effort ไม่บล็อกถ้าพลาด)
        DempcSync::write($db, $empcode, date('Y-m-d'), 'out', date('H:i'), 'checkintime_scan', null, $empcode);

        return $this->findToday($empcode) ?? [];
    }

    // decode dataURL (data:image/jpeg;base64,...) แล้วเขียนไฟล์จริงลง checkin/storage/photos/ — คืน path
    // สัมพัทธ์ (ไม่ใช่ absolute path ของเครื่อง) เก็บลง DB กัน path เปลี่ยนตอนย้ายเครื่อง/deploย — ตรวจชนิด
    // ไฟล์จริงก่อนตั้งนามสกุล (เหมือน CheckinAuthModel::savePhoto()) ไม่เขียนเป็น .jpg คงที่เสมอ
    private function savePhoto(string $empcode, string $type, ?string $dataUrl): ?string
    {
        if ($dataUrl === null) return null;
        if (!preg_match('#\Adata:(image/jpeg|image/png|image/webp);base64,([A-Za-z0-9+/]*={0,2})\z#', $dataUrl, $m)) {
            return null;
        }
        $binary = base64_decode($m[2], true);
        if ($binary === false || $binary === '') return null;
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($binary);
        if ($mime !== $m[1] || !isset(self::PHOTO_TYPES[$mime])) return null;
        $image = @getimagesizefromstring($binary);
        if ($image === false || ($image['mime'] ?? '') !== $mime) return null;

        if (!is_dir(self::PHOTO_DIR)) {
            mkdir(self::PHOTO_DIR, 0777, true);
        }

        $filename = sprintf('%s_%s_%s.%s', $empcode, date('Ymd'), $type, self::PHOTO_TYPES[$mime]);
        file_put_contents(self::PHOTO_DIR . '/' . $filename, $binary);

        return 'storage/photos/' . $filename;
    }
}
