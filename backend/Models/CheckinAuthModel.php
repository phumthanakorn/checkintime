<?php
declare(strict_types=1);

namespace Models\Checkin;

use Core\Model;
use PDO;

// ข้อมูลพนักงานสำหรับ auth ของ CheckInTime โดยเฉพาะ — แยกออกมาจาก Models\Auth\AuthModel เดิม (ของเว็บหลัก
// GBG-MANAGEMENT) เพื่อไม่ให้ logic ของ CheckInTime ไปปนกับของเว็บหลัก ตอนนี้โปรเจกต์แยกเดี่ยวแล้ว ใช้ฐาน
// MySQL ในเครื่องฐานเดียว (ดู core/Model.php, core/Database.php)
class CheckinAuthModel extends Model
{
    // รูปโปรไฟล์ที่พนักงานอัปโหลดเองผ่านแอป CheckInTime — เก็บไฟล์จริงไว้นอก webroot แล้วเก็บ path สัมพัทธ์
    // ไว้ในคอลัมน์ profile_photo_path ของ Employee_Dempc_Roster (ดู structure/add_roster_profile_photo.sql)
    // ตามแพทเทิร์นเดียวกับ check_in_photo_path/check_out_photo_path ใน Employee_CheckinTime_Attendance_Log
    private const PHOTO_DIR = __DIR__ . '/../../storage/profile-photos';

    // ฟิลด์ข้อมูลติดต่อที่พนักงานแก้ไขเองได้ผ่านหน้า "ข้อมูลส่วนตัว" — เก็บใน Employee_Dempc_Roster เอง (ดู
    // structure/add_roster_contact_fields.sql) คนละคอลัมน์กับข้อมูลที่ HR ดูแล (hrtime.dbo.employeesNew) เลย
    // แก้ตรงนี้ไม่กระทบข้อมูลต้นทางของ HR
    public function getContactFields(string $empcode): array
    {
        $db = $this->gbgDataForCurrentEnvironment();
        if ($db === null) {
            return ['email' => null, 'address' => null, 'emergency_name' => null, 'emergency_relation' => null, 'emergency_phone' => null];
        }

        $stm = $db->prepare('
            SELECT email, address, emergency_name, emergency_relation, emergency_phone
            FROM dbo.Employee_Dempc_Roster WHERE empcode = ?
        ');
        $stm->execute([$empcode]);
        $row = $stm->fetch(PDO::FETCH_ASSOC);
        return $row ?: ['email' => null, 'address' => null, 'emergency_name' => null, 'emergency_relation' => null, 'emergency_phone' => null];
    }

    // อัปเดตเฉพาะคอลัมน์ที่มีคีย์อยู่ใน $fields จริง (ไม่ใช่ทุกคอลัมน์เสมอ) — คีย์ที่ไม่ส่งมาจะไม่ถูกแตะเลย
    // กัน request ที่ตั้งใจแก้แค่บางฟิลด์ (เช่นแค่รูปโปรไฟล์) ไปเคลียร์ email/address/emergency_* ที่เหลือ
    // เป็น NULL ทิ้งโดยไม่ตั้งใจ — ตัวควบคุมว่าคีย์ไหน "ส่งมาจริง" อยู่ที่ AuthController::updateProfile()
    private const CONTACT_COLUMNS = ['email', 'address', 'emergency_name', 'emergency_relation', 'emergency_phone'];

    public function updateContactFields(string $empcode, array $fields): void
    {
        $columns = array_values(array_intersect(self::CONTACT_COLUMNS, array_keys($fields)));
        if ($columns === []) return;

        $db = $this->gbgDataForCurrentEnvironment();
        if ($db === null) throw new \RuntimeException('เชื่อมต่อฐานข้อมูลไม่ได้');

        $set = implode(', ', array_map(static fn(string $c): string => "$c = ?", $columns));
        $stm = $db->prepare("UPDATE dbo.Employee_Dempc_Roster SET {$set} WHERE empcode = ?");
        $stm->execute([...array_map(static fn(string $c) => $fields[$c] !== '' ? $fields[$c] : null, $columns), $empcode]);
    }

    public function getPhotoPath(string $empcode): ?string
    {
        $db = $this->gbgDataForCurrentEnvironment();
        if ($db === null) return null;

        $stm = $db->prepare('SELECT profile_photo_path FROM dbo.Employee_Dempc_Roster WHERE empcode = ?');
        $stm->execute([$empcode]);
        $path = $stm->fetchColumn();
        return $path !== false && $path !== null ? (string) $path : null;
    }

    // path เต็มบนดิสก์ของรูปที่เก็บไว้ (เช็ค prefix ให้ตรง PHOTO_DIR เสมอ ป้องกัน path traversal แม้ว่า
    // path ที่เก็บใน DB จะมาจาก savePhoto() ของเราเองไม่ใช่ input จาก client ตรงๆ ก็ตาม — กันเหนียวอีกชั้น
    // รูปแบบเดียวกับ CheckinAttendanceLogModel::resolvePhotoPath())
    public function resolvePhotoPath(?string $storedPath): ?string
    {
        if (empty($storedPath)) return null;
        $full = realpath(__DIR__ . '/../../' . preg_replace('~^/?checkin/~', '', ltrim($storedPath, '/')));
        if ($full === false) return null;
        if (!str_starts_with($full, realpath(self::PHOTO_DIR))) return null;
        return $full;
    }

    // นามสกุลไฟล์ (บนดิสก์) ต่อ mime ที่ยอมรับ — ต้องตรงกับชนิดไฟล์จริงที่ตรวจด้วย finfo ด้านล่างเสมอ
    // (เดิมเขียนเป็น .jpg คงที่ไม่ว่าอัปโหลดรูปชนิดไหนจริง ทำให้ path บนดิสก์กับเนื้อไฟล์จริงไม่ตรงกัน แก้ให้
    // ตรวจชนิดจริงแล้วตั้งนามสกุล/mime ตามนั้น เหมือนแพทเทิร์นที่ TimeFixAttachmentStorage ใช้อยู่แล้ว)
    private const PHOTO_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    // decode dataURL (data:image/jpeg;base64,...) จากฝั่ง client (ถ่าย/เลือกรูปแล้ว canvas เป็น base64 ก่อน
    // ส่งมา เหมือนรูป check-in/check-out) ตรวจชนิดไฟล์จริงก่อนเขียนไฟล์ลง PHOTO_DIR แล้วอัปเดต path ใน
    // Roster — ใช้ชื่อไฟล์คงที่ต่อ empcode (ไม่ผูก timestamp) ตั้งใจให้อัปโหลดใหม่ทับของเดิมได้เลย ไม่สะสม
    // ไฟล์เก่าค้าง (ลบไฟล์เดิมทิ้งก่อนเขียนใหม่ เผื่อเปลี่ยนชนิดไฟล์ระหว่างสองครั้งทำให้นามสกุลเปลี่ยน)
    public function savePhoto(string $empcode, string $dataUrl): string
    {
        if (!preg_match('#\Adata:(image/jpeg|image/png|image/webp);base64,([A-Za-z0-9+/]*={0,2})\z#', $dataUrl, $m)) {
            throw new \InvalidArgumentException('รองรับเฉพาะรูป JPG, PNG หรือ WEBP');
        }
        $binary = base64_decode($m[2], true);
        if ($binary === false || $binary === '') {
            throw new \InvalidArgumentException('รูปภาพไม่ถูกต้อง');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($binary);
        if ($mime !== $m[1] || !isset(self::PHOTO_TYPES[$mime])) {
            throw new \InvalidArgumentException('ชนิดไฟล์จริงไม่ตรงกับรูปภาพที่ระบุ');
        }
        $image = @getimagesizefromstring($binary);
        if ($image === false || ($image['mime'] ?? '') !== $mime) {
            throw new \InvalidArgumentException('รูปภาพไม่ถูกต้อง');
        }

        if (!is_dir(self::PHOTO_DIR)) {
            mkdir(self::PHOTO_DIR, 0777, true);
        }
        // ลบไฟล์เดิมของ empcode นี้ทุกนามสกุลที่เป็นไปได้ก่อน กันเศษไฟล์ค้างถ้าอัปโหลดคนละชนิดจากครั้งก่อน
        foreach (self::PHOTO_TYPES as $ext) {
            @unlink(self::PHOTO_DIR . '/' . $empcode . '.' . $ext);
        }

        $filename = $empcode . '.' . self::PHOTO_TYPES[$mime];
        file_put_contents(self::PHOTO_DIR . '/' . $filename, $binary);
        $relativePath = 'storage/profile-photos/' . $filename;

        $db = $this->gbgDataForCurrentEnvironment();
        if ($db === null) throw new \RuntimeException('เชื่อมต่อฐานข้อมูลไม่ได้');
        $stm = $db->prepare('UPDATE dbo.Employee_Dempc_Roster SET profile_photo_path = ? WHERE empcode = ?');
        $stm->execute([$relativePath, $empcode]);

        return $relativePath;
    }

    // ลบรูปโปรไฟล์ (ปุ่ม "ลบรูปโปรไฟล์" ใน PersonalInfoView.vue ที่ตั้ง form.avatarUrl = null แล้วกด
    // บันทึก) ลบทั้งไฟล์จริงบนดิสก์และล้างคอลัมน์ใน Roster — เงียบๆ ถ้าไม่มีรูปอยู่แล้ว ไม่ต้อง error
    public function deletePhoto(string $empcode): void
    {
        $path = $this->resolvePhotoPath($this->getPhotoPath($empcode));
        if ($path !== null && is_file($path)) {
            @unlink($path);
        }

        $db = $this->gbgDataForCurrentEnvironment();
        if ($db === null) throw new \RuntimeException('เชื่อมต่อฐานข้อมูลไม่ได้');
        $stm = $db->prepare('UPDATE dbo.Employee_Dempc_Roster SET profile_photo_path = NULL WHERE empcode = ?');
        $stm->execute([$empcode]);
    }

    // คืนรูปโปรไฟล์เป็น base64 dataURL ฝังตรงใน JSON response ("avatarUrl") แทนที่จะคืน URL ให้ไปโหลดต่อ —
    // เพราะ endpoint ของ CheckInTime ทุกเส้นต้องมี JWT (Authorization header) ซึ่ง <img src="..."> ธรรมดาส่ง
    // header ไม่ได้ ใช้วิธีนี้ได้เพราะรูปถูกบีบเล็กแล้วตั้งแต่ฝั่ง client (maxSide 400, quality .85 — ดู
    // utils/files.js::compressImage()) ไม่หนักเกินไปที่จะฝังใน JSON
    public function getPhotoDataUrl(string $empcode): ?string
    {
        $path = $this->resolvePhotoPath($this->getPhotoPath($empcode));
        if ($path === null) return null;
        $binary = @file_get_contents($path);
        if ($binary === false) return null;
        $mime = array_search(strtolower(pathinfo($path, PATHINFO_EXTENSION)), self::PHOTO_TYPES, true) ?: 'image/jpeg';
        return 'data:' . $mime . ';base64,' . base64_encode($binary);
    }

    /**
     * Revalidate access for every authenticated API request.
     * JWT proves identity only; current employment and DEMPC membership remain server-side state.
     */
    public function accessState(string $empcode): string
    {
        $gbg = $this->gbgDataForCurrentEnvironment();
        if ($gbg === null || $this->dbHrtime === null) {
            throw new \RuntimeException('Access validation database unavailable');
        }

        $stmt = $gbg->prepare('SELECT 1 FROM dbo.Employee_Dempc_Roster WHERE empcode = ?');
        $stmt->execute([$empcode]);
        if (!$stmt->fetchColumn()) return 'roster_removed';

        $stmt = $this->dbHrtime->prepare(
            'SELECT TOP 1 workstatus FROM hrtime.dbo.employeesNew
             WHERE empcode = ? ORDER BY CASE WHEN workstatus = 1 THEN 0 ELSE 1 END, empid DESC'
        );
        $stmt->execute([$empcode]);
        if ((int) $stmt->fetchColumn() !== 1) return 'employment_inactive';

        return 'active';
    }

    public function findByEmpcode(string $empcode): ?array
    {
        if ($this->dbHrtime === null) return null;

        $stm = $this->dbHrtime->prepare("
            SELECT TOP 1 empid AS id, empcode, empname, emplname, workstatus
            FROM hrtime.dbo.employeesNew
            WHERE empcode = ? AND workstatus = 1
        ");

        $stm->execute([$empcode]);
        $stm->setFetchMode(PDO::FETCH_ASSOC);
        $row = $stm->fetch();

        return $row ? array_merge($row, $this->getPermissionRecord($row['empcode'])) : null;
    }

    // ตำแหน่ง/แผนก/หน่วยงาน/เบอร์โทร/วันเริ่มงาน สำหรับหน้า "ข้อมูลส่วนตัว" — เขียนแยกเป็นของ CheckInTime เอง
    // แทนที่จะพึ่ง Models\Employee\EmployeeModel::getUserPersonalDetail() ของเว็บหลัก เพราะไฟล์นั้น import
    // Models\Management\AttendanceModel.php (3,226 บรรทัด แกนระบบ attendance ของเว็บหลักทั้งระบบ) มาด้วย
    // ทั้งที่ CheckInTime ใช้แค่ไม่กี่ field — ตัดการพึ่งพาออกไปเลย จะได้แยก checkin/ ไปเป็นโปรเจกต์เดี่ยวได้
    // ง่ายขึ้นในอนาคต (ดู checkin/docs/api-database-map.md)
    //
    // ที่มาของ mobile: ของเดิม (EmployeeModel) ดึงจาก MySQL SGBJobs_live.employees แต่ตัวนี้ดึงจาก
    // Employee_Dempc_Roster.phone (MSSQL, GBG_Data) แทน — ค่าเดียวกันในทางปฏิบัติ (import มาจาก HR
    // เหมือนกัน) แต่ไม่ต้องพึ่ง MySQL เลยสักฐาน ตรงตามที่ตั้งใจไว้แต่แรกของคลาสนี้ทั้งคลาส (ดู comment บนสุด
    // ของไฟล์)
    public function getPersonalDetail(string $empcode): array
    {
        $detail = ['Position' => null, 'Department' => null, 'UNIT' => null, 'mobile' => null, 'startdate' => null];

        if ($this->dbHrtime !== null) {
            $stm = $this->dbHrtime->prepare("
                SELECT
                    ISNULL(ds.DeptSub,      '') AS Department,
                    ISNULL(ds3.Description, '') AS UNIT,
                    ISNULL(p.description,   '') AS Position,
                    CONVERT(VARCHAR(10), e.startdate, 23) AS startdate
                FROM hrtime.dbo.employeesNew e
                LEFT JOIN hrtime.dbo.DeptSub       ds  ON ds.DeptSubcode = e.DeptSubcode
                LEFT JOIN hrtime.dbo.DepartmentSub ds3 ON ds3.ID         = e.DepartmentSub
                LEFT JOIN hrtime.dbo.positions     p   ON p.posicode     = e.posicode
                WHERE e.empcode = ?
                ORDER BY e.workstatus ASC
            ");
            $stm->execute([$empcode]);
            $row = $stm->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $detail['Position'] = $row['Position'] ?: null;
                $detail['Department'] = $row['Department'] ?: null;
                $detail['UNIT'] = $row['UNIT'] ?: null;
                $detail['startdate'] = $row['startdate'] ?: null;
            }
        }

        $gbg = $this->gbgDataForCurrentEnvironment();
        if ($gbg !== null) {
            $stm = $gbg->prepare('SELECT phone FROM dbo.Employee_Dempc_Roster WHERE empcode = ?');
            $stm->execute([$empcode]);
            $phone = $stm->fetchColumn();
            $detail['mobile'] = $phone !== false && $phone !== null && $phone !== '' ? $phone : null;
        }

        return $detail;
    }

    // resolve username ที่กรอกมาเป็นอีเมล -> empcode จาก Employee_Dempc_Roster.email (MSSQL, GBG_Data) —
    // คนละตารางกับที่เว็บหลักใช้ตอน Google login (sgb_master_db.employee_detail.email) ตั้งใจแยกกันเพราะ
    // อีเมลของ CheckInTime (กรอกเอง/HR กรอกให้ใน Roster) คนละความหมายกับอีเมลที่ยืนยันผ่าน Google OAuth จริง
    public function findByEmail(string $email): ?array
    {
        $db = $this->gbgDataForCurrentEnvironment();
        if ($db === null) return null;

        $stm = $db->prepare('SELECT empcode FROM dbo.Employee_Dempc_Roster WHERE email = ?');
        $stm->execute([$email]);
        $row = $stm->fetch(PDO::FETCH_ASSOC);
        if ($row === false || empty($row['empcode'])) return null;

        return $this->findByEmpcode((string) $row['empcode']);
    }

    // ใช้เป็นปัจจัยที่สองตอน setupPassword() — กัน "ยึดบัญชีคนอื่น" ด้วยการรู้แค่ empcode (เดาง่าย ไม่ใช่
    // ความลับจริง) ต้องรู้อีเมลที่ลงทะเบียนไว้ใน Employee_Dempc_Roster ด้วย ถึงจะตั้งรหัสผ่านให้ empcode นั้นได้
    // คืน false ทั้งกรณีอีเมลไม่ตรง และกรณี empcode นี้ยังไม่มีอีเมลลงทะเบียนไว้เลย (บล็อกไปเลย ให้ติดต่อ HR แทน)
    public function emailMatches(string $empcode, string $email): bool
    {
        $db = $this->gbgDataForCurrentEnvironment();
        if ($db === null || $email === '') return false;

        $stm = $db->prepare('SELECT 1 FROM dbo.Employee_Dempc_Roster WHERE empcode = ? AND email = ?');
        $stm->execute([$empcode, $email]);
        return (bool) $stm->fetch(PDO::FETCH_ASSOC);
    }

    // สิทธิ์การใช้งานยังมาจากตารางเดียวกับเว็บหลัก (Employee_User_Permissions, MSSQL คนละเครื่อง) — ชี้ prod
    // ตรงๆ เสมอ (ไม่มี dbGbgDataTest fallback แบบที่ AuthModel ของเว็บหลักมี เพราะ CheckInTime ทั้งระบบตกลง
    // กันไว้แล้วว่าใช้ prod ตรงๆ เท่านั้น)
    private function getPermissionRecord(string $empcode): array    
    {
        $db = $this->gbgDataForCurrentEnvironment();
        if ($db === null) {
            return ['permission_active' => null, 'permission_id' => null];
        }

        try {
            $stm = $db->prepare('SELECT TOP 1 id, active FROM dbo.Employee_User_Permissions WHERE empcode = ?');
            $stm->execute([$empcode]);
            $stm->setFetchMode(PDO::FETCH_ASSOC);
            $row = $stm->fetch();
        } catch (\Throwable $e) {
            error_log('[CheckinAuthModel::getPermissionRecord] ' . $e->getMessage());
            return ['permission_active' => null, 'permission_id' => null];
        }

        return [
            'permission_active' => $row ? $row['active'] : null,
            'permission_id'     => $row ? $row['id'] : null,
        ];
    }
}
