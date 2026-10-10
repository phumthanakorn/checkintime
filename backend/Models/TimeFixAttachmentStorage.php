<?php
declare(strict_types=1);
namespace Models\Checkin;

// Private storage outside the web root. Files are served only by the JWT-protected API.
class TimeFixAttachmentStorage
{
    public const MAX_BYTES = 5 * 1024 * 1024;
    private const TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'application/pdf' => 'pdf'];
    private string $directory;

    public function __construct(?string $directory = null)
    {
        $this->directory = $directory ?? (getenv('CHECKIN_TIMEFIX_STORAGE_DIR') ?: dirname(__DIR__, 2) . '/storage/timefix');
    }

    public static function validate(mixed $attachments): array
    {
        if (!is_array($attachments) || !array_is_list($attachments) || count($attachments) > 1) {
            throw new \InvalidArgumentException('แนบไฟล์ได้ไม่เกิน 1 ไฟล์');
        }
        $validated = [];
        foreach ($attachments as $file) {
            if (!is_array($file) || !is_string($file['name'] ?? null) || !is_string($file['dataUrl'] ?? null)) {
                throw new \InvalidArgumentException('ข้อมูลไฟล์แนบไม่ถูกต้อง');
            }
            $dataUrl = $file['dataUrl'];
            if (strlen($dataUrl) > 4 * (int) ceil(self::MAX_BYTES / 3) + 100) {
                throw new \InvalidArgumentException('ไฟล์แนบต้องไม่เกิน 5 MB');
            }
            if (!preg_match('#\Adata:(image/jpeg|image/png|image/webp|application/pdf);base64,([A-Za-z0-9+/]*={0,2})\z#', $dataUrl, $parts)) {
                throw new \InvalidArgumentException('รองรับเฉพาะรูป JPG, PNG, WEBP หรือ PDF');
            }
            $binary = base64_decode($parts[2], true);
            if ($binary === false || $binary === '' || strlen($binary) > self::MAX_BYTES) {
                throw new \InvalidArgumentException('ไฟล์แนบว่าง ไม่ถูกต้อง หรือใหญ่เกิน 5 MB');
            }
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($binary);
            if ($mime !== $parts[1] || !isset(self::TYPES[$mime])) {
                throw new \InvalidArgumentException('ชนิดไฟล์จริงไม่ตรงกับรูปภาพหรือ PDF ที่ระบุ');
            }
            if (str_starts_with($mime, 'image/')) {
                $image = @getimagesizefromstring($binary);
                if ($image === false || ($image['mime'] ?? '') !== $mime || $image[0] * $image[1] > 40000000) {
                    throw new \InvalidArgumentException('รูปภาพไม่ถูกต้องหรือมีความละเอียดสูงเกินไป');
                }
            } elseif (!str_starts_with($binary, '%PDF-')) {
                throw new \InvalidArgumentException('ไฟล์ PDF ไม่ถูกต้อง');
            }
            $name = preg_replace('/[\x00-\x1f\x7f\/\\\\]/u', '_', trim($file['name']));
            if ($name === null || $name === '') throw new \InvalidArgumentException('ชื่อไฟล์ไม่ถูกต้อง');
            preg_match('/\A.{1,180}/us', $name, $shortName);
            $validated[] = ['name' => $shortName[0], 'type' => $mime, 'size' => strlen($binary), 'binary' => $binary];
        }
        return $validated;
    }

    public function save(array $files): array
    {
        if ($files === []) return [];
        if (!is_dir($this->directory) && !@mkdir($this->directory, 0700, true) && !is_dir($this->directory)) {
            throw new \RuntimeException('Cannot create private attachment directory');
        }
        $saved = [];
        try {
            foreach ($files as $file) {
                $id = bin2hex(random_bytes(16));
                $storedName = $id . '.' . self::TYPES[$file['type']];
                $path = $this->directory . DIRECTORY_SEPARATOR . $storedName;
                $handle = @fopen($path, 'xb');
                if ($handle === false) throw new \RuntimeException('Cannot create attachment file');
                $metadata = ['id' => $id, 'name' => $file['name'], 'type' => $file['type'], 'size' => $file['size'], 'storedName' => $storedName];
                $saved[] = $metadata;
                try {
                    $written = fwrite($handle, $file['binary']);
                    if ($written !== strlen($file['binary'])) throw new \RuntimeException('Cannot write complete attachment file');
                } finally { fclose($handle); }
                @chmod($path, 0600);
            }
            return $saved;
        } catch (\Throwable $e) {
            $this->delete($saved);
            throw $e;
        }
    }

    public function delete(array $files): void
    {
        foreach ($files as $file) {
            if (self::validStoredName($file['storedName'] ?? null)) {
                @unlink($this->directory . DIRECTORY_SEPARATOR . $file['storedName']);
            }
        }
    }

    public function path(array $file): ?string
    {
        if (!self::validStoredName($file['storedName'] ?? null)) return null;
        $root = realpath($this->directory);
        $path = realpath($this->directory . DIRECTORY_SEPARATOR . $file['storedName']);
        if ($root === false || $path === false || dirname($path) !== $root || !is_file($path)) return null;
        return $path;
    }

    private static function validStoredName(mixed $name): bool
    {
        return is_string($name) && preg_match('/\A[a-f0-9]{32}\.(jpg|png|webp|pdf)\z/', $name) === 1;
    }

    public static function metadata(?string $json): array
    {
        if ($json === null || $json === '') return [];
        $files = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($files) || !array_is_list($files)) throw new \RuntimeException('Invalid attachment metadata');
        return $files;
    }

    public static function publicMetadata(?string $json): array
    {
        return array_map(static fn(array $file): array => [
            'id' => $file['id'], 'name' => $file['name'], 'type' => $file['type'], 'size' => $file['size'],
        ], self::metadata($json));
    }
}
