<?php
declare(strict_types=1);

namespace Api\Checkin\Controllers;

// GET/POST /leave/requests, GET /leave/balances — skeleton เท่านั้น ของจริงควรต่อกับ logic การลาที่มีอยู่
// แล้วของหน้า "เพิ่มการลาของพนักงาน"/"ตรวจสอบรายการลา" (ดู Controllers/management ฝั่งนั้น) ไม่สร้างระบบลา
// คู่ขนานใหม่ — ดู docs/api-spec-for-backend.txt ข้อ 4.3
class LeaveController extends AbstractApiController
{
    public function balances(): void
    {
        // TODO: ต่อกับตาราง/Model การลาเดิม กรองด้วย $this->empcode
        self::json([]);
    }

    public function requests(): void
    {
        // TODO: ต่อกับตาราง/Model การลาเดิม กรองด้วย $this->empcode
        self::json([]);
    }

    public function createRequest(): void
    {
        self::error('ยังไม่เปิดใช้งาน', 501, 'NOT_IMPLEMENTED');
    }

    public function cancelRequest(string $id): void
    {
        self::error('ยังไม่เปิดใช้งาน', 501, 'NOT_IMPLEMENTED');
    }
}
