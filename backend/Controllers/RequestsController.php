<?php
declare(strict_types=1);

namespace Api\Checkin\Controllers;

use Models\Checkin\TimeFixRequestModel;

// GET /requests/status-summary — เลี้ยงการ์ด "สถานะการส่งคำขอ" หน้าแรก (RequestStatusList.vue) ตอนนี้นับได้
// จริงแค่ time_fix เพราะ leave ฝั่ง backend ยังเป็น skeleton ทั้งหมด ไม่มีข้อมูลจริงให้นับ — เพิ่ม leave
// เข้าไปทีหลังตอน /leave/* ต่อฐานข้อมูลจริงแล้ว (รูปแบบเดียวกันเป๊ะ แค่เปลี่ยน Model/type) ไม่มีแผนทำ "เบิกเงิน"
// แล้ว (บริษัทยืนยันว่าไม่เปิดให้บริการผ่านแอปนี้) จึงตัดออกจากทุก type ที่เกี่ยวข้องทั้งหมด
class RequestsController extends AbstractApiController
{
    public function statusSummary(): void
    {
        $pendingTimeFix = (new TimeFixRequestModel())->countPending((string) $this->empcode);
        self::json([
            ['type' => 'time_fix', 'pendingCount' => $pendingTimeFix],
        ]);
    }
}
