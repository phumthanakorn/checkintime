<?php

namespace Models\Dempc;

use Core\Model;
use PDO;

// [NEW] "สถานที่ลงเวลา" DEMPC (geofence GPS/Beacon ต่อจุด) — ตาราง Master_Dempc_Location
// (ดู structure/create_dempc_location.sql) ดีไซน์ตามระบบอ้างอิง (HumanOS) ที่ผู้ใช้ส่งภาพหน้าจอมาให้ดู:
// list + form เพิ่ม/แก้ไข + import Excel รูปแบบเดียวกับที่ export ออกมา
class DempcLocationModel extends Model
{
    private const TYPE_LABELS = [
        'office'      => 'สำนักงาน',
        'client_site' => 'ไซต์ลูกค้า',
        'personal'    => 'ส่วนตัว',
    ];

    private function gbgDb(): ?\PDO
    {
        return $this->dbGbgDataTest ?? $this->dbGbgData;
    }

    public static function typeLabel(string $type): string
    {
        return self::TYPE_LABELS[$type] ?? $type;
    }

    public static function typeOptions(): array
    {
        return self::TYPE_LABELS;
    }

    // แปลงป้ายกำกับไทยจากไฟล์ import ("สำนักงาน"/"ไซต์ลูกค้า"/"ส่วนตัว") กลับเป็นรหัสภายใน
    // รองรับกรณีไฟล์ใส่รหัสภาษาอังกฤษมาตรงๆ ด้วย (เผื่อ export ย้อนกลับจากระบบเราเอง)
    private static function typeFromLabel(string $label): ?string
    {
        $label = trim($label);
        if (isset(self::TYPE_LABELS[$label])) return $label; // เป็นรหัสอยู่แล้ว
        $flip = array_flip(self::TYPE_LABELS);
        return $flip[$label] ?? null;
    }

    private static function parseYN(mixed $v, bool $default = false): int
    {
        if ($v === null || $v === '') return $default ? 1 : 0;
        $v = strtoupper(trim((string) $v));
        if (in_array($v, ['Y', 'YES', '1', 'TRUE', 'ได้', 'ใช้งาน'], true)) return 1;
        if (in_array($v, ['N', 'NO', '0', 'FALSE', 'ไม่ได้', 'ไม่ใช้งาน'], true)) return 0;
        return $default ? 1 : 0;
    }

    public function getAll(): array
    {
        $db = $this->gbgDb();
        if (!$db) return [];

        $stmt = $db->query("SELECT location_id, location_name, location_type, empcode,
                   is_active, allow_edit_mobile, allow_gps_checkin, allow_gps_job,
                   latitude, longitude, radius_meters,
                   allow_beacon_checkin, beacon_id,
                   created_by, created_at, updated_by, updated_at
            FROM Master_Dempc_Location
            ORDER BY location_id ASC
        ");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$r) {
            $r['location_type_label'] = self::typeLabel($r['location_type']);
        }
        return $rows;
    }

    // [NEW] สำหรับ api/checkin (แอปมือถือ) — คืนเฉพาะสถานที่ "เปิดใช้งานจริง" (is_active=1) ต่างจาก getAll()
    // ที่หน้าแอดมินใช้ (เห็นทุกแถวรวมที่ปิดใช้งาน เพื่อให้แก้ไข/เปิดกลับมาได้) และกรองประเภท "ส่วนตัว"
    // (personal) ให้เห็นเฉพาะของ empcode ที่ขอมาเอง — กันพนักงานคนอื่นเห็นพิกัดที่อยู่ส่วนตัวของเพื่อนร่วมงาน
    public function getActiveForEmployee(string $empcode): array
    {
        $db = $this->gbgDb();
        if (!$db) return [];

        $stmt = $db->prepare("
            SELECT location_id, location_name, location_type, empcode,
                   latitude, longitude, radius_meters,
                   allow_gps_checkin, allow_beacon_checkin, beacon_id
            FROM Master_Dempc_Location
            WHERE is_active = 1
              AND (location_type <> 'personal' OR empcode = ?)
            ORDER BY location_id ASC
        ");
        $stmt->execute([$empcode]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$r) {
            $r['location_type_label'] = self::typeLabel($r['location_type']);
        }
        return $rows;
    }

    public function getById(int $id): ?array
    {
        $db = $this->gbgDb();
        if (!$db) return null;

        $stmt = $db->prepare("
            SELECT location_id, location_name, location_type, empcode,
                   is_active, allow_edit_mobile, allow_gps_checkin, allow_gps_job,
                   latitude, longitude, radius_meters,
                   allow_beacon_checkin, beacon_id,
                   created_by, created_at, updated_by, updated_at
            FROM Master_Dempc_Location WHERE location_id = ?
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // $locationId: ระบุตรงๆ เมื่อ insert จากไฟล์ import (ต้องใช้รหัสเดียวกับ "รหัสสถานที่" ในไฟล์ เพื่อให้
    // ลิงก์กับระบบต้นทาง/ข้อมูลอื่นที่ใช้รหัสเดียวกันได้ในอนาคต) — ปล่อย null เมื่อสร้างจากหน้าเว็บเราเอง
    // (ไม่มีรหัสจากระบบต้นทาง) จะ generate เลขติดลบให้แทนผ่าน nextManualId() ตาราง location_id เป็น
    // INT ธรรมดา ไม่ใช่ IDENTITY แล้ว (ดู structure/create_dempc_location.sql) ต้องระบุค่าเองเสมอ
    public function save(array $d, ?string $createdBy = null, ?int $locationId = null): int|false
    {
        $db = $this->gbgDb();
        if (!$db) return false;

        $locationId = $locationId ?: $this->nextManualId($db);

        $stmt = $db->prepare("
            INSERT INTO Master_Dempc_Location
                (location_id, location_name, location_type, empcode, is_active, allow_edit_mobile,
                 allow_gps_checkin, allow_gps_job, latitude, longitude, radius_meters,
                 allow_beacon_checkin, beacon_id, created_by, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, GETDATE())
        ");
        $ok = $stmt->execute([
            $locationId, $d['location_name'], $d['location_type'], $d['empcode'] ?: null,
            $d['is_active'], $d['allow_edit_mobile'], $d['allow_gps_checkin'], $d['allow_gps_job'],
            $d['latitude'], $d['longitude'], $d['radius_meters'],
            $d['allow_beacon_checkin'], $d['beacon_id'] ?: null, $createdBy,
        ]);
        return $ok ? $locationId : false;
    }

    // เลขติดลบเสมอ (แยกจากรหัสจริงจากไฟล์ที่เป็นเลขบวก) ให้เห็นชัดว่าแถวไหนสร้างจากหน้าเว็บเราเอง ไม่ได้
    // มาจากไฟล์ระบบต้นทาง
    private function nextManualId(PDO $db): int
    {
        $min = (int) $db->query("SELECT ISNULL(MIN(location_id), 0) FROM Master_Dempc_Location")->fetchColumn();
        return ($min >= 0 ? 0 : $min) - 1;
    }

    public function update(int $id, array $d, ?string $updatedBy = null): bool
    {
        $db = $this->gbgDb();
        if (!$db) return false;

        $stmt = $db->prepare("
            UPDATE Master_Dempc_Location SET
                location_name = ?, location_type = ?, empcode = ?, is_active = ?,
                allow_edit_mobile = ?, allow_gps_checkin = ?, allow_gps_job = ?,
                latitude = ?, longitude = ?, radius_meters = ?,
                allow_beacon_checkin = ?, beacon_id = ?,
                updated_by = ?, updated_at = GETDATE()
            WHERE location_id = ?
        ");
        return $stmt->execute([
            $d['location_name'], $d['location_type'], $d['empcode'] ?: null, $d['is_active'],
            $d['allow_edit_mobile'], $d['allow_gps_checkin'], $d['allow_gps_job'],
            $d['latitude'], $d['longitude'], $d['radius_meters'],
            $d['allow_beacon_checkin'], $d['beacon_id'] ?: null,
            $updatedBy, $id,
        ]);
    }

    public function delete(int $id): bool
    {
        $db = $this->gbgDb();
        if (!$db) return false;

        $stmt = $db->prepare("DELETE FROM Master_Dempc_Location WHERE location_id = ?");
        return $stmt->execute([$id]);
    }

    // นำเข้าจากไฟล์ Excel/CSV รูปแบบเดียวกับที่ export จากระบบอ้างอิง (คอลัมน์เรียงตามลำดับคงที่ ไม่อ่าน
    // จาก header) — แถวที่มี "รหัสสถานที่" ตรงกับ location_id ที่มีอยู่แล้ว = UPDATE, ถ้าว่าง/ไม่ตรง = INSERT
    // ใหม่ ฟิลด์ beacon ไม่ได้อยู่ในไฟล์ตัวอย่าง ปล่อยเป็นค่าเริ่มต้น (ปิด) ไปก่อน แก้เพิ่มทีหลังผ่านฟอร์มได้
    public function importFromFile(string $filePath, string $fileExtension, ?string $importedBy = null): array
    {
        $db = $this->gbgDb();
        if ($db === null) {
            return ['status' => 'error', 'message' => 'ไม่สามารถเชื่อมต่อฐานข้อมูล GBG_Data ได้'];
        }
        if (!class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            return ['status' => 'error', 'message' => 'ไม่พบไลบรารี PhpSpreadsheet ในระบบ'];
        }

        try {
            if ($fileExtension === 'csv') {
                $reader  = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Csv');
                $content = (string) file_get_contents($filePath);
                if (method_exists($reader, 'setInputEncoding')) {
                    $reader->setInputEncoding(mb_check_encoding($content, 'UTF-8') ? 'UTF-8' : 'Windows-874');
                }
                $spreadsheet = $reader->load($filePath);
            } else {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            }
            $rows = $spreadsheet->getActiveSheet()->toArray(null, true, false);
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'อ่านไฟล์ไม่ได้: ' . $e->getMessage()];
        }

        if (empty($rows)) {
            return ['status' => 'error', 'message' => 'ไฟล์ไม่มีข้อมูล'];
        }

        $inserted = 0;
        $updated  = 0;
        $skipped  = 0;
        $errors   = [];

        foreach ($rows as $rowIdx => $row) {
            $lineNo = $rowIdx + 1;
            $col0   = trim((string) ($row[0] ?? ''));
            $name   = trim((string) ($row[1] ?? ''));

            // ข้ามแถว header (คอลัมน์ชื่อไม่ใช่ตัวเลขและไม่ใช่ค่าว่าง แถวแรกมักเป็นหัวตาราง)
            if ($rowIdx === 0 && !is_numeric($col0) && stripos($name, 'ชื่อสถานที่') !== false) {
                continue;
            }
            if ($name === '') {
                continue; // แถวว่าง
            }

            $typeLabel = trim((string) ($row[2] ?? ''));
            $type      = self::typeFromLabel($typeLabel);
            if (!$type) {
                $errors[] = "แถว {$lineNo}: ประเภท \"{$typeLabel}\" ไม่ถูกต้อง (ต้องเป็น สำนักงาน/ไซต์ลูกค้า/ส่วนตัว)";
                $skipped++;
                continue;
            }

            $empcode = trim((string) ($row[3] ?? ''));
            if ($type === 'personal' && $empcode === '') {
                $errors[] = "แถว {$lineNo}: ประเภท \"ส่วนตัว\" ต้องระบุรหัสพนักงาน";
                $skipped++;
                continue;
            }

            $data = [
                'location_name'        => $name,
                'location_type'        => $type,
                'empcode'              => $type === 'personal' ? $empcode : null,
                'is_active'            => self::parseYN($row[4] ?? null, true),
                'allow_edit_mobile'    => self::parseYN($row[5] ?? null, false),
                'allow_gps_checkin'    => self::parseYN($row[6] ?? null, true),
                'allow_gps_job'        => self::parseYN($row[7] ?? null, false),
                'latitude'             => is_numeric($row[8] ?? null) ? (float) $row[8] : null,
                'longitude'            => is_numeric($row[9] ?? null) ? (float) $row[9] : null,
                'radius_meters'        => is_numeric($row[10] ?? null) ? (int) $row[10] : null,
                'allow_beacon_checkin' => 0,
                'beacon_id'            => null,
            ];

            $locationId = is_numeric($col0) ? (int) $col0 : 0;

            try {
                if ($locationId > 0 && $this->getById($locationId)) {
                    if ($this->update($locationId, $data, $importedBy)) {
                        $updated++;
                    } else {
                        $errors[] = "แถว {$lineNo}: อัปเดตไม่สำเร็จ";
                        $skipped++;
                    }
                } else {
                    // ใช้รหัสจากไฟล์ตรงๆ เป็น location_id เสมอเมื่อมี (ไม่ปล่อยให้ระบบ generate เอง) เพื่อให้
                    // รหัสตรงกับระบบต้นทาง — ปล่อย null (ให้ nextManualId คิดเลขติดลบ) เฉพาะแถวที่ไม่มีรหัสจริงๆ
                    if ($this->save($data, $importedBy, $locationId > 0 ? $locationId : null) !== false) {
                        $inserted++;
                    } else {
                        $errors[] = "แถว {$lineNo}: เพิ่มไม่สำเร็จ";
                        $skipped++;
                    }
                }
            } catch (\Exception $e) {
                $errors[] = "แถว {$lineNo}: " . $e->getMessage();
                $skipped++;
            }
        }

        $status = 'success';
        if ($skipped > 0 && ($inserted > 0 || $updated > 0)) $status = 'partial';
        elseif ($skipped > 0 && $inserted === 0 && $updated === 0) $status = 'error';

        return [
            'status'   => $status,
            'message'  => "นำเข้าสำเร็จ: เพิ่มใหม่ {$inserted} | อัปเดต {$updated} | ข้าม {$skipped}",
            'inserted' => $inserted,
            'updated'  => $updated,
            'skipped'  => $skipped,
            'errors'   => $errors,
        ];
    }
}
