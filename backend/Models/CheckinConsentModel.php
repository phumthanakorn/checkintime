<?php
declare(strict_types=1);

namespace Models\Checkin;

use Core\Model;
use PDO;

// ประวัติความยินยอม PDPA ของ CheckInTime — append-only (ดู structure/create_checkintime_consent.sql)
// แทนที่ storage/checkin/consents.json เดิมที่เก็บแค่สถานะล่าสุด ไม่มีประวัติ/IP/เครื่อง
class CheckinConsentModel extends Model
{
    private bool $schemaReady = false;

    // ต้องตรงกับ CHECK constraint ของตาราง (CK_checkin_consent_type/CK_checkin_consent_action) — เช็คซ้ำฝั่ง
    // PHP ก่อนด้วย กัน PDOException ที่ไม่ได้ดักจับหลุดออกมาเป็น fatal error ถ้ามี caller ในอนาคตส่งค่าอื่นเข้ามา
    // (ตอนนี้ยังปลอดภัยเพราะ AuthController เรียกด้วยค่าคงที่เท่านั้น แต่ไม่ควรฝากความถูกต้องไว้ที่ DB อย่างเดียว)
    private const CONSENT_TYPES = ['privacy_policy', 'gps_location'];
    private const ACTIONS = ['granted', 'revoked'];

    // policy_version VARCHAR(20) / user_agent NVARCHAR(500) — ตัดความยาวก่อนเสมอ กัน SQL Server error "String
    // or binary data would be truncated" (ไม่ได้ดักจับ จะหลุดเป็น fatal error) ถ้า client ส่งค่ายาวผิดปกติมา
    private const MAX_POLICY_VERSION_LEN = 20;
    private const MAX_USER_AGENT_LEN = 500;

    // ชี้ prod ตรงๆ — ตาราง Employee_Checkin_Consent ถูกสร้างไว้บน prod แล้ว (ต่างจาก CheckinAccountModel
    // ที่ยังรอสร้างบน prod อยู่ ดู comment ที่นั่น)
    private function gbgDb(): ?PDO
    {
        return $this->gbgDataForCurrentEnvironment();
    }

    private function ensureSchema(PDO $db): void
    {
        if ($this->schemaReady) return;
        if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') { $this->schemaReady = true; return; } // ใช้ database/schema.mysql.sql
        $db->exec("
            IF OBJECT_ID(N'dbo.Employee_Checkin_Consent', N'U') IS NULL
            BEGIN
                CREATE TABLE dbo.Employee_Checkin_Consent (
                    id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
                    empcode VARCHAR(20) NOT NULL,
                    consent_type VARCHAR(30) NOT NULL,
                    action VARCHAR(10) NOT NULL,
                    policy_version VARCHAR(20) NULL,
                    ip_address VARCHAR(45) NULL,
                    user_agent NVARCHAR(500) NULL,
                    created_at DATETIME2 NOT NULL
                        CONSTRAINT DF_checkin_consent_created_at DEFAULT SYSDATETIME(),
                    CONSTRAINT CK_checkin_consent_type CHECK (consent_type IN ('privacy_policy', 'gps_location')),
                    CONSTRAINT CK_checkin_consent_action CHECK (action IN ('granted', 'revoked'))
                );
                CREATE INDEX IX_checkin_consent_empcode_type
                    ON dbo.Employee_Checkin_Consent (empcode, consent_type, created_at DESC);
            END
        ");
        $this->schemaReady = true;
    }

    public function record(
        string $empcode,
        string $consentType,
        string $action,
        ?string $policyVersion,
        ?string $ip,
        ?string $userAgent
    ): void {
        if (!in_array($consentType, self::CONSENT_TYPES, true)) {
            throw new \InvalidArgumentException("consent_type ไม่ถูกต้อง: {$consentType}");
        }
        if (!in_array($action, self::ACTIONS, true)) {
            throw new \InvalidArgumentException("action ไม่ถูกต้อง: {$action}");
        }

        $db = $this->gbgDb();
        if ($db === null) throw new \RuntimeException('เชื่อมต่อฐานข้อมูลไม่ได้');
        $this->ensureSchema($db);

        $stmt = $db->prepare('
            INSERT INTO dbo.Employee_Checkin_Consent (empcode, consent_type, action, policy_version, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $empcode,
            $consentType,
            $action,
            $policyVersion !== null ? mb_substr($policyVersion, 0, self::MAX_POLICY_VERSION_LEN) : null,
            $ip,
            $userAgent !== null ? mb_substr($userAgent, 0, self::MAX_USER_AGENT_LEN) : null,
        ]);
    }

    // สถานะล่าสุดในรูปแบบเดิมที่ frontend ใช้อยู่แล้ว ({policyVersion, location, acceptedAt}) — คืน null ถ้ายัง
    // ไม่เคยยินยอมนโยบายความเป็นส่วนตัวเลย (router guard ฝั่ง frontend เช็ค user.consent?.policyVersion ตรงๆ
    // ดู checkin/frontend/src/router/index.js) ไม่สนว่า gps_location ยินยอมหรือยัง เพราะเป็นคนละ step กัน
    public function getSummary(string $empcode): ?array
    {
        $db = $this->gbgDb();
        if ($db === null) return null;
        $this->ensureSchema($db);

        $privacy = $this->latest($db, $empcode, 'privacy_policy');
        if ($privacy === null || $privacy['action'] !== 'granted') return null;

        $gps = $this->latest($db, $empcode, 'gps_location');

        return [
            'policyVersion' => $privacy['policy_version'],
            'location'      => $gps !== null && $gps['action'] === 'granted',
            'acceptedAt'    => $this->toIso((string) $privacy['created_at']),
        ];
    }

    private function latest(PDO $db, string $empcode, string $consentType): ?array
    {
        $stmt = $db->prepare('
            SELECT TOP 1 action, policy_version, created_at
            FROM dbo.Employee_Checkin_Consent
            WHERE empcode = ? AND consent_type = ?
            ORDER BY created_at DESC, id DESC
        ');
        $stmt->execute([$empcode, $consentType]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function toIso(?string $datetime): ?string
    {
        if ($datetime === null) return null;
        $dt = new \DateTime($datetime, new \DateTimeZone(date_default_timezone_get()));
        return $dt->format('c');
    }
}
