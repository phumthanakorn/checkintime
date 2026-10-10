<?php
declare(strict_types=1);

namespace Api\Checkin\Controllers;

use Models\Checkin\CheckinNotificationModel;

class NotificationController extends AbstractApiController
{
    public function list(): void
    {
        try {
            self::json((new CheckinNotificationModel())->findAll((string) $this->empcode));
        } catch (\Throwable $e) {
            error_log('[NotificationController::list] ' . $e->getMessage());
            self::error('ไม่สามารถโหลดการแจ้งเตือนได้ กรุณาลองใหม่', 500, 'NOTIFICATION_LOAD_FAILED');
        }
    }

    public function markRead(string $id): void
    {
        $notificationId = self::validId($id);
        try {
            $found = (new CheckinNotificationModel())->markRead((string) $this->empcode, $notificationId);
        } catch (\Throwable $e) {
            error_log('[NotificationController::markRead] ' . $e->getMessage());
            self::error('ไม่สามารถบันทึกสถานะการอ่านได้', 500, 'NOTIFICATION_UPDATE_FAILED');
        }
        if (!$found) self::error('ไม่พบการแจ้งเตือน', 404, 'NOTIFICATION_NOT_FOUND');
        self::json(['id' => $notificationId, 'read' => true]);
    }

    public function markAllRead(): void
    {
        try {
            $count = (new CheckinNotificationModel())->markAllRead((string) $this->empcode);
        } catch (\Throwable $e) {
            error_log('[NotificationController::markAllRead] ' . $e->getMessage());
            self::error('ไม่สามารถบันทึกสถานะการอ่านได้', 500, 'NOTIFICATION_UPDATE_FAILED');
        }
        self::json(['updated' => $count]);
    }

    private static function validId(string $id): int
    {
        if (!preg_match('/^[1-9]\d*$/D', $id) || strlen($id) > 19) {
            self::error('รหัสการแจ้งเตือนไม่ถูกต้อง', 400, 'INVALID_NOTIFICATION_ID');
        }
        $value = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($value === false) self::error('รหัสการแจ้งเตือนไม่ถูกต้อง', 400, 'INVALID_NOTIFICATION_ID');
        return $value;
    }
}
