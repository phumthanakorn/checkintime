<?php
// ตั้งไว้ที่นี่ที่เดียว ให้ทุก entry point ที่ require ไฟล์นี้ (api/v1/router.php, api/checkin/router.php,
// api/*.php แบบเดิมทั้งหมด) ได้เวลาไทยตรงกัน — ไม่ต้องไปตั้งซ้ำทีละไฟล์ เหมือนที่ index.php/cron/bootstrap.php
// ตั้งไว้อยู่แล้วของตัวเอง (คนละ entry point กัน ไม่ได้ require ไฟล์นี้) — ถ้าไม่ตั้งไว้ PHP จะใช้ timezone
// ตาม php.ini ของเครื่อง ซึ่งอาจไม่ใช่ Asia/Bangkok (เจอมาแล้วจริงบนเครื่อง dev นี้ ตั้งเป็นยุโรปไว้)
date_default_timezone_set('Asia/Bangkok');

$_apiLogDir = dirname(__DIR__) . '/logs';
if (!is_dir($_apiLogDir)) mkdir($_apiLogDir, 0777, true);
ini_set('log_errors', '1');
ini_set('error_log', "$_apiLogDir/error.log");
unset($_apiLogDir);

spl_autoload_register(function (string $class) {
    $baseDir = dirname(__DIR__) . DIRECTORY_SEPARATOR;

    $parts = explode('\\', ltrim($class, '\\'));
    if (empty($parts)) return;

    $file = array_pop($parts) . '.php';
    $top  = array_shift($parts);

    $topMap = [
        'Controllers' => 'Controllers',
        'Models'      => 'Models',
        'Core'        => 'core',
    ];
    $topDir = $topMap[$top] ?? $top;

    // Models อยู่แบนใน backend/Models/ (ไม่แยกโฟลเดอร์ checkin/) — namespace Models\Checkin ชี้ตรงมาที่นี่,
    // Models\Dempc → Models/dempc/, Models\Traits → Models/Traits/ (ระบุ case ตรงๆ เพราะ prod เป็น Linux)
    if ($top === 'Models' && $parts) {
        $modelDirs = ['Checkin' => '', 'Dempc' => 'dempc' . DIRECTORY_SEPARATOR, 'Traits' => 'Traits' . DIRECTORY_SEPARATOR];
        if (isset($modelDirs[$parts[0]]) && count($parts) === 1) {
            $mapped = $baseDir . 'Models' . DIRECTORY_SEPARATOR . $modelDirs[$parts[0]] . $file;
            if (is_file($mapped)) {
                require_once $mapped;
                return;
            }
        }
    }

    $sub  = $parts ? implode(DIRECTORY_SEPARATOR, array_map('strtolower', $parts)) . DIRECTORY_SEPARATOR : '';
    $path = $baseDir . $topDir . DIRECTORY_SEPARATOR . $sub . $file;

    if (is_file($path)) {
        require_once $path;
        return;
    }

    $fallback = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
    if (is_file($fallback)) {
        require_once $fallback;
    }
});
