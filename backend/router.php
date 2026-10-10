<?php
declare(strict_types=1);

// Router ของ api/checkin — แยกจาก api/v1/router.php โดยสิ้นเชิง (คนละ auth: JWT ต่อ user ไม่ใช่ token
// คงที่ต่อ client ภายนอก, คนละรูปแบบ response: {message,code} ตรงๆ ตาม checkin/docs/api-spec-for-backend.txt
// ไม่ใช่ {success,message} แบบ Core\ApiController) รองรับ GET/POST ครบตามที่ frontend เรียกจริง (v1 ตั้งใจ
// ทำแค่ GET เท่านั้น ดู comment ในไฟล์นั้น)
//
// แกะ path เหมือน v1: PATH_INFO ก่อน (ถ้า Apache ส่งมาให้) ไม่งั้น fallback ไป REQUEST_URI ตัด prefix
// "api/checkin/" ทิ้ง — ใช้ routing table แบบ literal segment + {param} แทนที่จะเป็น resource/id แบบ v1
// เพราะ endpoint จริงมีรูปทรงหลากหลายกว่า (เช่น /attendance/check-in, /leave/requests/{id}/cancel)

require_once __DIR__ . '/core/autoload.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Model.php';
require_once __DIR__ . '/core/Jwt.php';

// require ตรงๆ ทุกไฟล์ (ไม่พึ่ง spl_autoload_register ใน core/autoload.php) เพราะตัว autoloader นั้น map
// namespace segment แรกผ่าน $topMap ที่รู้จักแค่ Controllers/Models/Core — "Api" ไม่อยู่ในนั้น จะ fallback ไป
// หาโฟลเดอร์ชื่อ "Api" (ตัวพิมพ์ใหญ่) ซึ่งบน Windows (dev) หาเจอเพราะ filesystem ไม่สน case แต่บน prod
// (Linux, case-sensitive) จะหาไม่เจอเพราะโฟลเดอร์จริงชื่อ "api" ตัวเล็ก — require ตรงๆ แบบนี้ชัวร์ทั้งคู่
// (รูปแบบเดียวกับที่ api/v1/router.php ใช้)
require_once __DIR__ . '/Controllers/AbstractApiController.php';
require_once __DIR__ . '/Controllers/AuthController.php';
require_once __DIR__ . '/Controllers/AttendanceController.php';
require_once __DIR__ . '/Controllers/CalendarController.php';
require_once __DIR__ . '/Controllers/TimeFixController.php';
require_once __DIR__ . '/Controllers/LeaveController.php';
require_once __DIR__ . '/Controllers/LocationController.php';
require_once __DIR__ . '/Controllers/NotificationController.php';
require_once __DIR__ . '/Controllers/RequestsController.php';
require_once __DIR__ . '/Controllers/SimulationController.php';

use Api\Checkin\Controllers\AttendanceController;
use Api\Checkin\Controllers\AuthController;
use Api\Checkin\Controllers\CalendarController;
use Api\Checkin\Controllers\TimeFixController;
use Api\Checkin\Controllers\LeaveController;
use Api\Checkin\Controllers\LocationController;
use Api\Checkin\Controllers\NotificationController;
use Api\Checkin\Controllers\RequestsController;
use Api\Checkin\Controllers\SimulationController;

$method = $_SERVER['REQUEST_METHOD'];

// Security headers พื้นฐาน — ใส่ที่นี่แทนที่ web server config เพราะ portable ข้าม Apache/nginx (ตอน dev เป็น
// Apache, prod เดิมเป็น nginx) ไม่ต้องไปตั้งซ้ำสองที่ ครอบคลุมทุก response ของ router นี้
header('X-Content-Type-Options: nosniff');   // กัน browser เดา content-type เอง (MIME sniffing)
header('X-Frame-Options: DENY');             // กันเอาไป iframe ครอบ (clickjacking) — API นี้ไม่มีใครต้องฝัง
header('Referrer-Policy: strict-origin-when-cross-origin');
// HSTS ใส่เฉพาะตอนเป็น HTTPS จริงเท่านั้น (ใส่ตอน HTTP เปล่าๆ ไม่มีผล แต่เผื่อ proxy header บอกผิดจะพังได้)
if (($_SERVER['HTTPS'] ?? '') !== '' || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// CORS — frontend (Vite dev server, localhost:5173) กับ backend นี้ (localhost:8080) คนละ origin กัน
// เบราว์เซอร์เลยต้องมี header พวกนี้ก่อนถึงจะยอมให้ axios เรียกข้ามมาได้ และ POST ที่มี
// Content-Type: application/json (เช่น login) จะมี preflight OPTIONS มาก่อนเสมอ ต้องตอบ 204 เปล่าๆ ให้
// ผ่านก่อน ไม่งั้น browser บล็อก request จริงไม่ให้ยิงออกไปเลย — origin ใส่ไว้แค่ dev ports ที่ใช้จริง
// (5173 = vite dev, 4173 = vite preview) ไม่ใช้ "*" เพราะต้องส่ง credentials (Authorization header) ด้วย
// เพิ่ม IP วง LAN ของเครื่อง dev (192.168.2.208) ไว้ด้วย สำหรับทดสอบจากมือถือ/เครื่องอื่นในวงเดียวกัน —
// ถ้า dev เปลี่ยนเครื่อง/IP ต้องมาแก้ตรงนี้ใหม่ให้ตรง (ดู vite.config.js: server.host = true คู่กัน)
$allowedOrigins = \Core\AppConfig::frontendOrigins(); // ตั้งที่ config/app.php
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
}
if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ?path=attendance/today เป็นทางที่ชัวร์สุด ใช้ได้แน่นอนไม่ว่าเซิร์ฟเวอร์จะเป็น apache/nginx หรือ config
// แบบไหน — เช็คก่อนเป็นอันดับแรก (บทเรียนจาก api/v1: PATH_INFO ใช้ไม่ได้บน nginx จริงที่ไม่ได้ตั้ง
// fastcgi_split_path_info ไว้ เจอปัญหานี้มาแล้วตอนทดสอบ api/v1/attendance บน prod)
if (isset($_GET['path'])) {
    $path = trim((string) $_GET['path'], '/');
} elseif (($_SERVER['PATH_INFO'] ?? '') !== '') {
    $path = trim($_SERVER['PATH_INFO'], '/');
} else {
    $path = trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
    $path = preg_replace('#^.*?api/checkin/?#', '', $path);
}
$segments = $path === '' ? [] : explode('/', $path);

// [HTTP method, segment pattern (string ตรงตัว หรือ "{}" = รับอะไรก็ได้ 1 segment), controller class,
// controller method] — เทียบ pattern ทีละ segment ความยาวต้องเท่ากันเป๊ะ เจอ "{}" ให้เก็บค่าจริงไว้เป็น
// path param ส่งต่อให้ controller method ตามลำดับ
$routes = [
    ['POST', ['auth', 'login'],                AuthController::class,       'login'],
    ['POST', ['auth', 'google'],               AuthController::class,       'googleLogin'],
    ['POST', ['auth', 'setup-password'],       AuthController::class,       'setupPassword'],
    ['POST', ['auth', 'password', 'forgot'],   AuthController::class,       'requestPasswordReset'],
    ['POST', ['auth', 'password', 'reset'],    AuthController::class,       'resetPassword'],
    ['GET',  ['auth', 'me'],                   AuthController::class,       'me'],
    ['POST', ['auth', 'logout'],               AuthController::class,       'logout'],
    ['POST', ['me', 'consents'],               AuthController::class,       'consents'],
    ['PUT',  ['me', 'profile'],                AuthController::class,       'updateProfile'],
    ['POST', ['me', 'password'],               AuthController::class,       'changePassword'],
    ['POST', ['me', 'password', 'verify'],     AuthController::class,       'verifyCurrentPassword'],
    ['GET',  ['attendance', 'today'],           AttendanceController::class, 'today'],
    ['GET',  ['attendance', 'photo'],           AttendanceController::class, 'photo'],
    ['GET',  ['attendance', 'history'],         AttendanceController::class, 'history'],
    ['GET',  ['attendance', 'summary'],         AttendanceController::class, 'summary'],
    ['POST', ['attendance', 'check-in'],        AttendanceController::class, 'checkIn'],
    ['POST', ['attendance', 'check-out'],       AttendanceController::class, 'checkOut'],
    ['GET',  ['calendar'],                      CalendarController::class,   'getMonth'],
    ['GET',  ['time-fix', 'requests'],           TimeFixController::class,    'requests'],
    ['GET',  ['time-fix', 'requests', '{}', 'attachments', '{}'], TimeFixController::class, 'attachment'],
    ['POST', ['time-fix', 'requests'],           TimeFixController::class,    'createRequest'],
    ['POST', ['time-fix', 'requests', '{}', 'cancel'], TimeFixController::class, 'cancelRequest'],
    ['GET',  ['leave', 'balances'],             LeaveController::class,      'balances'],
    ['GET',  ['leave', 'requests'],              LeaveController::class,      'requests'],
    ['POST', ['leave', 'requests'],              LeaveController::class,      'createRequest'],
    ['POST', ['leave', 'requests', '{}', 'cancel'], LeaveController::class,  'cancelRequest'],
    ['GET',  ['locations'],                     LocationController::class,   'list'],
    ['GET',  ['notifications'],                 NotificationController::class, 'list'],
    ['POST', ['notifications', 'read-all'],     NotificationController::class, 'markAllRead'],
    ['POST', ['notifications', '{}', 'read'],   NotificationController::class, 'markRead'],
    ['GET',  ['requests', 'status-summary'],    RequestsController::class,   'statusSummary'],
    ['POST', ['simulate', 'check-in-log'],      SimulationController::class, 'logCheckin'],
];

foreach ($routes as [$routeMethod, $pattern, $class, $action]) {
    if ($routeMethod !== $method) continue;
    if (count($pattern) !== count($segments)) continue;

    $params = [];
    $match = true;
    foreach ($pattern as $i => $part) {
        if ($part === '{}') {
            $params[] = $segments[$i];
            continue;
        }
        if ($part !== $segments[$i]) {
            $match = false;
            break;
        }
    }
    if (!$match) continue;

    // ส่งชื่อ action เข้า constructor เสมอ — AbstractApiController ใช้ตัดสินใจว่า endpoint นี้ต้องมี
    // token ก่อนไหม (ดู comment ใน AbstractApiController.php) ต้องรู้ก่อนที่ method จริงจะถูกเรียกด้วยซ้ำ
    $controller = new $class($action);
    $controller->$action(...$params);
    exit;
}

http_response_code(404);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['message' => 'Not found', 'code' => 'NOT_FOUND'], JSON_UNESCAPED_UNICODE);
