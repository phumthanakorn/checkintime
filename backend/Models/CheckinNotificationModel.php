<?php
declare(strict_types=1);

namespace Models\Checkin;

use Core\Model;
use DateTimeImmutable;
use PDO;

class CheckinNotificationModel extends Model
{
    private function gbgDb(): PDO
    {
        $db = $this->gbgDataForCurrentEnvironment();
        if ($db === null) throw new \RuntimeException('Notification database unavailable');
        return $db;
    }

    public function findAll(string $empcode, int $limit = 100): array
    {
        if (!$this->tableExists()) return [];
        $limit = max(1, min(100, $limit));
        $stmt = $this->gbgDb()->prepare("SELECT TOP ({$limit}) id, notification_type, reference_type,
            reference_id, title, body, is_read, created_at
            FROM dbo.Employee_CheckinTime_Notification
            WHERE empcode = ? ORDER BY created_at DESC, id DESC");
        $stmt->execute([$empcode]);
        return array_map([self::class, 'toResponse'], $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
    }

    public function markRead(string $empcode, int $id): bool
    {
        if (!$this->tableExists()) return false;
        $stmt = $this->gbgDb()->prepare('UPDATE dbo.Employee_CheckinTime_Notification
            SET is_read = 1, read_at = COALESCE(read_at, SYSDATETIME())
            WHERE id = ? AND empcode = ?');
        $stmt->execute([$id, $empcode]);
        return $stmt->rowCount() > 0;
    }

    public function markAllRead(string $empcode): int
    {
        if (!$this->tableExists()) return 0;
        $stmt = $this->gbgDb()->prepare('UPDATE dbo.Employee_CheckinTime_Notification
            SET is_read = 1, read_at = SYSDATETIME() WHERE empcode = ? AND is_read = 0');
        $stmt->execute([$empcode]);
        return $stmt->rowCount();
    }

    private function tableExists(): bool
    {
        $db = $this->gbgDb();
        if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') return true; // schema สร้างผ่าน database/schema.mysql.sql
        return (bool) $db->query("SELECT CASE WHEN OBJECT_ID(N'dbo.Employee_CheckinTime_Notification', N'U') IS NULL THEN 0 ELSE 1 END")
            ->fetchColumn();
    }

    private static function toResponse(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'type' => $row['notification_type'],
            'title' => $row['title'],
            'body' => $row['body'],
            'createdAt' => (new DateTimeImmutable((string) $row['created_at']))->format('c'),
            'read' => (bool) $row['is_read'],
            'link' => $row['reference_type'] === 'time_fix'
                ? ['name' => 'time-fix', 'query' => ['request' => (string) $row['reference_id']]]
                : null,
        ];
    }
}
