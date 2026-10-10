<?php
declare(strict_types=1);

namespace Api\Checkin\Controllers;

use Models\Dempc\DempcLocationModel;

// GET /locations — รายชื่อ "สถานที่ลงเวลา" DEMPC (geofence GPS/Beacon) ให้แอปมือถือใช้ตรวจว่าพนักงานอยู่ใน
// พื้นที่ที่อนุญาตให้ลงเวลาไหม — ใช้ DempcLocationModel ตัวเดียวกับหน้าแอดมิน "สถานที่ลงเวลา DEMPC" ไม่เขียน
// query ซ้ำ แค่เพิ่ม method ที่กรองเฉพาะแถว active + ตัดคอลัมน์ audit (created_by/at ฯลฯ) ที่ไม่เกี่ยวกับ
// แอปมือถือออก
//
// ตามที่คุยกันไว้: เขียน GET ให้พร้อมใช้งานจริงก่อน (ไม่ใช่ skeleton แบบ AttendanceController) ส่วนจุดที่เอา
// ไปใช้ตรวจ geofence จริงตอนลงเวลา (useGeofence composable ฝั่ง frontend) ยังไม่ต่อเข้าด้วยกัน — ทำทีหลัง
class LocationController extends AbstractApiController
{
    public function list(): void
    {
        $model = new DempcLocationModel();
        $rows = $model->getActiveForEmployee((string) $this->empcode);

        self::json(array_map([$this, 'toResource'], $rows));
    }

    private function toResource(array $r): array
    {
        return [
            'id'                 => (int) $r['location_id'],
            'name'               => $r['location_name'],
            'type'               => $r['location_type'],
            'typeLabel'          => $r['location_type_label'],
            'latitude'           => (float) $r['latitude'],
            'longitude'          => (float) $r['longitude'],
            'radiusMeters'       => $r['radius_meters'] !== null ? (int) $r['radius_meters'] : null,
            'allowGpsCheckin'    => (bool) $r['allow_gps_checkin'],
            'allowBeaconCheckin' => (bool) $r['allow_beacon_checkin'],
            'beaconId'           => $r['beacon_id'] ?: null,
        ];
    }
}
