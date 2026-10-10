<?php
declare(strict_types=1);

namespace Api\Checkin\Controllers;

// POST /simulate/check-in-log — เครื่องมือ dev เท่านั้น ไม่เกี่ยวกับ endpoint ลงเวลาจริง (attendance/check-in
// ยัง 501 อยู่) ใช้คู่กับ src/api/services/simulatedAttendance.js ฝั่ง frontend ที่จำลองการลงเวลาไว้ใน
// localStorage ของเครื่องผู้ใช้อยู่แล้ว — เพิ่มตัวนี้มาเสริมอีกชั้น เพราะอยากดูข้อมูลจาก "เครื่อง dev"
// (เปิดไฟล์ดูตรงๆ ได้เลย) โดยเฉพาะตอนทดสอบจากมือถือจริงที่เปิด DevTools ดู localStorage ไม่สะดวก — เขียน
// ทับทุกครั้งที่เรียก เก็บเป็น array ต่อท้ายไปเรื่อยๆ ไม่ลบของเก่า
class SimulationController extends AbstractApiController
{
    private const LOG_FILE = __DIR__ . '/../../storage/simulated_attendance_log.json';

    public function logCheckin(): void
    {
        $body = self::body();

        // เวลาที่ "นับจริง" ของการลงเวลา (ใช้คำนวณมาสาย/OT) ต้องมาจากนาฬิกาเซิร์ฟเวอร์เท่านั้น ห้ามเชื่อเวลา
        // ที่ client ส่งมาเด็ดขาด — นาฬิกาเครื่องผู้ใช้ปรับเองได้ (ตั้งใจหรือไม่ก็ตาม) ถ้าเชื่อเวลาจากเครื่อง
        // จะเปิดช่องให้โกงเวลาเข้า-ออกงานได้ง่ายๆ (ย้อนนาฬิกาเครื่องให้ดูเหมือนมาตรงเวลา เป็นต้น) — เหตุผลเดียว
        // กับที่ simulatedAttendance.js ฝั่ง frontend เปลี่ยนมา await เวลานี้แทนใช้ new Date() ของตัวเองแล้ว
        $serverTime = date('c');

        $entry = [
            'loggedAt'        => $serverTime,
            'empcode'         => (string) $this->empcode,
            'type'            => $body['type'] ?? null, // 'check-in' | 'check-out'
            // clientIp() อ่าน X-Forwarded-For ก่อน (Vite proxy ส่งมาให้ถ้าตั้ง xfwd:true ใน vite.config.js)
            // ได้ IP จริงของมือถือ แทนที่จะเห็นแค่ IP เครื่อง dev เหมือนตอนอ่าน REMOTE_ADDR ตรงๆ
            'ip'              => self::clientIp(),
            'location'        => $body['location'] ?? null,
            'nearestLocation' => $body['nearestLocation'] ?? null,
            'device'          => $body['device'] ?? null,
            'hasPhoto'        => !empty($body['photo']),
        ];

        $dir = dirname(self::LOG_FILE);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $existing = [];
        if (is_file(self::LOG_FILE)) {
            $decoded = json_decode((string) file_get_contents(self::LOG_FILE), true);
            if (is_array($decoded)) {
                $existing = $decoded;
            }
        }
        $existing[] = $entry;

        file_put_contents(self::LOG_FILE, json_encode($existing, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        // serverTime แยกไว้ชัดเจนเป็น top-level ให้ frontend เอาไปใช้เป็นเวลาลงเวลาจริง (ไม่ใช่ไปขุดจาก
        // entry.loggedAt ที่เผื่อวันหลังอยากแก้ความหมายแยกจากกัน)
        self::json(['success' => true, 'serverTime' => $serverTime, 'entry' => $entry]);
    }
}
