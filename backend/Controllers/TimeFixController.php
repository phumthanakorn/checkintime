<?php
declare(strict_types=1);
namespace Api\Checkin\Controllers;
use DateTimeImmutable;
use Models\Checkin\TimeFixRequestModel;
use Models\Checkin\TimeFixAttachmentStorage;

class TimeFixController extends AbstractApiController
{
    // Keep aligned with frontend/src/utils/constants.js.
    private const MAX_DAYS_BACK = 30;

    public function requests(): void
    {
        try {
            $rows = (new TimeFixRequestModel())->findRequests((string) $this->empcode);
        } catch (\Throwable $e) {
            error_log('[TimeFixController::requests] ' . $e->getMessage());
            self::error('ไม่สามารถโหลดคำขอได้ กรุณาลองใหม่', 500, 'TIME_FIX_LOAD_FAILED');
        }
        self::json(array_map([self::class, 'toResponse'], $rows));
    }

    public function createRequest(): void
    {
        $data = self::validateRequest(self::body());
        try {
            $row = (new TimeFixRequestModel())->createRequest((string) $this->empcode, $data);
        } catch (\Throwable $e) {
            error_log('[TimeFixController::createRequest] ' . $e->getMessage());
            if ($e instanceof \PDOException && (int) ($e->errorInfo[1] ?? 0) === 207) {
                self::error('โครงสร้างฐานข้อมูลยังไม่ครบ กรุณารัน SQL เพิ่มคอลัมน์ไฟล์แนบก่อน', 503, 'TIME_FIX_SCHEMA_REQUIRED');
            }
            self::error('ไม่สามารถส่งคำขอได้ กรุณาลองใหม่', 500, 'TIME_FIX_CREATE_FAILED');
        }
        if ($row === false) self::error('มีคำขอรอพิจารณาสำหรับวันที่เลือกอยู่แล้ว', 409, 'TIME_FIX_ALREADY_PENDING');
        self::json(self::toResponse($row), 201);
    }

    public function cancelRequest(string $id): void
    {
        if (!preg_match('/^[1-9]\d*$/D', $id) || strlen($id) > 10 || (int) $id > 2147483647) {
            self::error('รหัสคำขอไม่ถูกต้อง', 400, 'INVALID_REQUEST_ID');
        }
        try {
            $cancelled = (new TimeFixRequestModel())->cancelRequest((string) $this->empcode, (int) $id);
        } catch (\Throwable $e) {
            error_log('[TimeFixController::cancelRequest] ' . $e->getMessage());
            self::error('ไม่สามารถยกเลิกคำขอได้ กรุณาลองใหม่', 500, 'TIME_FIX_CANCEL_FAILED');
        }
        if (!$cancelled) self::error('ไม่พบคำขอที่รอพิจารณาของคุณ หรือคำขอนี้เปลี่ยนสถานะแล้ว', 409, 'TIME_FIX_NOT_CANCELLABLE');
        self::json(['id' => (int) $id, 'status' => 'cancelled']);
    }

    public function attachment(string $id, string $attachmentId): void
    {
        if (!preg_match('/^[1-9]\d*$/D', $id) || strlen($id) > 10 || (int) $id > 2147483647
            || !preg_match('/\A[a-f0-9]{32}\z/', $attachmentId)) {
            self::error('ไม่พบไฟล์แนบ', 404, 'ATTACHMENT_NOT_FOUND');
        }
        try {
            // JWT owner only. No HR/admin roles are introduced here.
            $row = (new TimeFixRequestModel())->findOwnRequest((string) $this->empcode, (int) $id);
            $files = $row === null ? [] : TimeFixAttachmentStorage::metadata($row['attachments_json']);
            $file = null;
            foreach ($files as $candidate) {
                if (($candidate['id'] ?? null) === $attachmentId) { $file = $candidate; break; }
            }
            $path = $file === null ? null : (new TimeFixAttachmentStorage())->path($file);
        } catch (\Throwable $e) {
            error_log('[TimeFixController::attachment] ' . $e->getMessage());
            self::error('ไม่สามารถเปิดไฟล์แนบได้ กรุณาลองใหม่', 500, 'ATTACHMENT_LOAD_FAILED');
        }
        if ($path === null) self::error('ไม่พบไฟล์แนบ', 404, 'ATTACHMENT_NOT_FOUND');
        $handle = @fopen($path, 'rb');
        if ($handle === false) self::error('ไม่พบไฟล์แนบ', 404, 'ATTACHMENT_NOT_FOUND');
        $stat = fstat($handle);
        header('Content-Type: ' . $file['type']);
        header('Content-Length: ' . $stat['size']);
        header('Content-Disposition: inline; filename="attachment"; filename*=UTF-8\'\'' . rawurlencode($file['name']));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        header("Content-Security-Policy: sandbox; default-src 'none'");
        fpassthru($handle);
        fclose($handle);
        exit;
    }

    private static function validateRequest(array $body): array
    {
        $date = $body['date'] ?? null;
        $type = $body['fixType'] ?? null;
        $reason = $body['reason'] ?? null;
        if (!is_string($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/D', $date)) {
            self::error('รูปแบบวันที่ไม่ถูกต้อง', 400, 'INVALID_DATE');
        }
        $day = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if ($day === false || $day->format('Y-m-d') !== $date || (int) substr($date, 0, 4) < 1) {
            self::error('วันที่ไม่ถูกต้อง', 400, 'INVALID_DATE');
        }
        $today = new DateTimeImmutable('today');
        if ($day > $today || $day < $today->modify('-' . self::MAX_DAYS_BACK . ' days')) {
            self::error('ขอลงเวลาได้ตั้งแต่วันนี้ย้อนหลังไม่เกิน 30 วัน', 400, 'DATE_OUT_OF_RANGE');
        }
        if (!in_array($type, ['check_in', 'check_out', 'both'], true)) self::error('ประเภทคำขอไม่ถูกต้อง', 400, 'INVALID_FIX_TYPE');
        if (!is_string($reason) || preg_match('/\S/u', $reason) !== 1) self::error('กรุณาระบุเหตุผล', 400, 'INVALID_REASON');
        $reason = trim($reason);
        // NVARCHAR length is measured in UTF-16 units, including surrogate pairs.
        $units = preg_match_all('/./us', $reason) + preg_match_all('/[\x{10000}-\x{10FFFF}]/u', $reason);
        if ($units > 1000) self::error('เหตุผลต้องไม่เกิน 1,000 ตัวอักษร', 400, 'REASON_TOO_LONG');
        try {
            $attachments = TimeFixAttachmentStorage::validate($body['attachments'] ?? []);
        } catch (\InvalidArgumentException $e) {
            self::error($e->getMessage(), 400, 'INVALID_ATTACHMENT');
        }
        $checkIn = $type !== 'check_out' ? self::requestedTime($date, $body['checkIn'] ?? null) : null;
        $checkOut = $type !== 'check_in' ? self::requestedTime($date, $body['checkOut'] ?? null) : null;
        // Current form sends HH:mm and allows checkout on the same day only.
        if ($checkIn !== null && $checkOut !== null && $checkOut <= $checkIn) {
            self::error('เวลาออกงานต้องหลังเวลาเข้างาน', 400, 'INVALID_TIME_ORDER');
        }
        return ['date' => $date, 'reason' => $reason, 'attachments' => $attachments,
            'requested_check_in' => $checkIn, 'requested_check_out' => $checkOut];
    }

    private static function requestedTime(string $date, mixed $time): string
    {
        if (!is_string($time) || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D', $time)) {
            self::error('กรุณาระบุเวลาที่ต้องการขอเป็น HH:mm ให้ถูกต้อง', 400, 'INVALID_TIME');
        }
        return $date . ' ' . $time . ':00';
    }

    private static function toResponse(array $row): array
    {
        $in = $row['requested_check_in'];
        $out = $row['requested_check_out'];
        return [
            'id' => (int) $row['id'], 'date' => substr((string) $row['work_date'], 0, 10),
            'fixType' => $in !== null ? ($out !== null ? 'both' : 'check_in') : 'check_out',
            'checkIn' => $in !== null ? substr((string) $in, 11, 5) : null,
            'checkOut' => $out !== null ? substr((string) $out, 11, 5) : null,
            'reason' => $row['reason'], 'status' => $row['status'], 'reviewNote' => $row['review_note'],
            'createdAt' => (new DateTimeImmutable((string) $row['created_at']))->format('c'),
            'attachments' => TimeFixAttachmentStorage::publicMetadata($row['attachments_json'] ?? null),
        ];
    }
}
