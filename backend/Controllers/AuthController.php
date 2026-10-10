<?php
declare(strict_types=1);

namespace Api\Checkin\Controllers;

use Core\Jwt;
use Core\Mailer;
use Models\Checkin\CheckinAccountModel;
use Models\Checkin\CheckinAuthModel;
use Models\Checkin\CheckinConsentModel;
use Models\Checkin\CheckinLoginLogModel;
use Models\Checkin\CheckinPasswordResetModel;

// POST /auth/login, POST /auth/setup-password, GET /auth/me, POST /me/consents — ยืนยันตัวตนด้วย
// empcode+password ของตัวเอง เก็บแยกใน Employee_Checkin_Account (ไม่ใช้ View_CheckUSER ร่วมกับเว็บหลัก/
// Deskmap แบบเดิมอีกต่อไป ดู Models\Checkin\CheckinAccountModel) — ข้อมูลพนักงาน/resolve อีเมล ใช้
// Models\Checkin\CheckinAuthModel (ดึงจาก MSSQL ล้วนๆ ไม่พึ่ง MySQL เลย) แยกต่างหากจาก Models\Auth\AuthModel
// ที่เว็บหลักใช้ร่วมอยู่ ไม่ปนกันให้สับสนว่าเมธอดไหนเว็บหลักใช้/อันไหน CheckInTime ใช้
//
// บัญชีที่ status=0 (ไม่มีแถว = เสมือน status 0) ต้องตั้งรหัสผ่านใหม่ก่อน (รหัสผ่านชั่วคราว = empcode ตัวเอง)
// — ครอบคลุมทั้งสมัครครั้งแรกและ HR รีเซ็ตให้เพราะลืมรหัสผ่าน (เป็น flow เดียวกัน) login ในเคสนี้จะไม่ออก JWT
// ให้ทันที แต่คืน {needsSetup:true} ให้ frontend พาไปหน้าตั้งรหัสผ่านใหม่ (เรียก /auth/setup-password ต่อ)
//
// login/setupPassword ไม่ต้องมี token ก่อนเรียก (requiresAuth() = false) ส่วน me()/consents() ต้อง login
// แล้วเท่านั้น (ใช้ default requiresAuth() = true ของ parent)
class AuthController extends AbstractApiController
{
    // ป้องกัน brute-force เดารหัสผ่าน/รหัสผ่านชั่วคราว — ใช้ log ที่มีอยู่แล้ว (Employee_CheckinTime_Login_Log)
    // นับความพยายามที่ล้มเหลวของ empcode เดียวกันในช่วงเวลานี้ ไม่ต้องเพิ่มตาราง/คอลัมน์ใหม่เลย
    private const LOCKOUT_MAX_FAILURES = 5;
    private const LOCKOUT_WINDOW_MINUTES = 15;

    // ชั้นที่สอง: กันเดารหัสผ่านข้ามหลายบัญชีจาก IP เดียวกัน (per-empcode lockout ข้างบนกันไม่ได้ถ้าคนลองแค่
    // 1-2 ครั้งต่อบัญชีแต่ไล่ไปเรื่อยๆ หลายบัญชี) threshold สูงกว่า per-empcode ตั้งใจไม่ให้ IP สำนักงานที่มี
    // คนพิมพ์รหัสผิดพร้อมกันหลายคนโดนบล็อกง่ายเกินไป — เป็นเกราะชั้นแอป เสริมจาก rate-limit ระดับเครือข่าย/
    // WAF ที่ควรมีอยู่แล้วหน้า web server ด้วย (ดู docs/production-checklist.md)
    private const IP_LOCKOUT_MAX_FAILURES = 20;
    private const IP_LOCKOUT_WINDOW_MINUTES = 15;

    // login()/setupPassword()/requestPasswordReset()/resetPassword()/googleLogin() เท่านั้นที่เรียกได้โดย
    // ไม่ต้องมี token — me()/logout()/consents() ต้อง login มาก่อน
    protected function requiresAuth(?string $action): bool
    {
        return !in_array($action, ['login', 'setupPassword', 'requestPasswordReset', 'resetPassword', 'googleLogin'], true);
    }

    public function login(): void
    {
        // บังคับ login ด้วยอีเมลเท่านั้น (ตัด empcode-as-username ออก) ตามที่ตกลงกันไว้ — ให้สอดคล้องกับ
        // setupPassword() ที่บังคับอีเมลอยู่แล้ว และลดทางเข้าเหลือทางเดียวให้เดาน้อยลง (empcode เดาง่ายกว่า
        // อีเมลมาก) field ชื่อ "username" ไว้เหมือนเดิม (ตาม docs/api-spec-for-backend.txt ข้อ 4.1) แต่ต้องเป็น
        // รูปแบบอีเมลเท่านั้น — resolve identity จาก Employee_Dempc_Roster.email (MSSQL, GBG_Data)
        //
        // ผลข้างเคียงที่รู้แล้ว: พนักงานที่ยังไม่มีอีเมลลงทะเบียนใน Roster เลย (ไม่ครบทุกคน ดู
        // structure/create_checkintime_account.sql) จะ login ไม่ได้จนกว่า HR จะกรอกอีเมลให้ก่อน
        $body = self::body();
        $username = trim((string) ($body['username'] ?? ''));
        $password = (string) ($body['password'] ?? '');

        // บันทึกทุกครั้งที่พยายามล็อกอิน (สำเร็จ/ไม่สำเร็จ) ลง Employee_CheckinTime_Login_Log — ip/user_agent
        // อ่านจาก request โดยตรงเสมอ ไม่รับจาก client (กันปลอมแปลง เหมือนเวลาที่ login_at ต้องมาจากเซิร์ฟเวอร์)
        $logModel = new CheckinLoginLogModel();
        $ip = self::clientIp();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        if ($username === '' || $password === '') {
            $logModel->logAttempt($username !== '' ? $username : 'UNKNOWN', 'FAILED', null, 'VALIDATION_ERROR', $ip, $userAgent, null, null);
            self::error('กรุณากรอกอีเมลและรหัสผ่าน', 400, 'VALIDATION_ERROR');
        }
        if (!str_contains($username, '@')) {
            $logModel->logAttempt($username, 'FAILED', null, 'EMAIL_REQUIRED', $ip, $userAgent, null, null);
            self::error('กรุณาเข้าสู่ระบบด้วยอีเมลที่ลงทะเบียนไว้ (ไม่ใช่รหัสพนักงาน)', 400, 'EMAIL_REQUIRED');
        }

        $model = new CheckinAuthModel();
        $byEmail = $model->findByEmail($username);
        if ($byEmail === null) {
            $logModel->logAttempt($username, 'FAILED', null, 'INVALID_CREDENTIALS', $ip, $userAgent, null, null);
            self::error('อีเมลหรือรหัสผ่านไม่ถูกต้อง', 401, 'INVALID_CREDENTIALS');
        }
        $empcode = $byEmail['empcode'];
        // ส่งอีเมลที่ resolve มาต่อไปใน response ตอน needsSetup เลย ไม่ต้องให้พิมพ์ซ้ำอีกรอบในหน้าตั้งรหัสผ่าน
        // เพราะ /auth/setup-password ต้องใช้ยืนยันตัวตนด้วย (ดูที่นั่น)
        $resolvedEmail = $username;

        // กัน brute-force — เช็คก่อนเทียบรหัสผ่านทุกครั้ง ไม่ว่าจะเข้าทาง empcode ตรงๆ หรือ resolve จากอีเมลมา
        self::checkLockout($logModel, $empcode, $ip, 'พยายามเข้าสู่ระบบผิดหลายครั้งเกินไป กรุณาลองใหม่อีกครั้งใน 15 นาที');

        // รหัสผ่านเก็บแยกใน Employee_Checkin_Account ของตัวเอง (ไม่ใช้ View_CheckUSER ร่วมกับเว็บหลัก/
        // Deskmap แล้ว) ถ้า status=0 (ยังไม่เคยตั้งรหัสผ่าน หรือ HR เพิ่งรีเซ็ตให้) ต้องเทียบรหัสผ่านที่กรอก
        // กับ empcode ตัวเองก่อน (รหัสผ่านชั่วคราว) แล้วพาไปตั้งรหัสใหม่ผ่าน /auth/setup-password ต่อ — ยังไม่
        // ออก JWT ให้ตอนนี้
        $accountModel = new CheckinAccountModel();
        if ($accountModel->needsSetup($empcode)) {
            if ($password !== $empcode) {
                $logModel->logAttempt($empcode, 'FAILED', null, 'INVALID_CREDENTIALS', $ip, $userAgent, null, null);
                self::error('อีเมลหรือรหัสผ่านไม่ถูกต้อง', 401, 'INVALID_CREDENTIALS');
            }
            self::json(['needsSetup' => true, 'empcode' => $empcode, 'email' => $resolvedEmail]);
        }

        if (!$accountModel->verifyPassword($empcode, $password)) {
            $logModel->logAttempt($empcode, 'FAILED', null, 'INVALID_CREDENTIALS', $ip, $userAgent, null, null);
            self::error('อีเมลหรือรหัสผ่านไม่ถูกต้อง', 401, 'INVALID_CREDENTIALS');
        }

        $employee = $model->findByEmpcode($empcode);
        if ($employee === null) {
            $logModel->logAttempt($empcode, 'FAILED', null, 'INVALID_CREDENTIALS', $ip, $userAgent, null, null);
            self::error('ไม่พบข้อมูลพนักงาน', 401, 'INVALID_CREDENTIALS');
        }

        $this->issueSession($employee, $logModel, $ip, $userAgent);
    }

    // POST /auth/google — เข้าสู่ระบบด้วยบัญชี Google: frontend ใช้ Google Identity Services ยืนยันตัวตนกับ
    // Google เองก่อน ได้ "ID token" (JWT ที่ Google เซ็นมาให้) กลับมา ส่งมาที่นี่เป็น "credential" แล้ว backend
    // ตรวจสอบ token นั้นกับ Google อีกชั้น (ไม่เชื่อ client เปล่าๆ) ก่อนจะ resolve อีเมล -> empcode เหมือน
    // login() ปกติ — ไม่มีการ "สมัครอัตโนมัติ" ด้วย Google เด็ดขาด ต้องเป็นอีเมลที่ตรงกับ Employee_Dempc_Roster
    // อยู่แล้วเท่านั้น และบัญชีต้องตั้งรหัสผ่านไปแล้ว (status=1) ถ้ายัง ให้พาไปตั้งรหัสผ่านก่อนเหมือนทางอีเมล/
    // รหัสผ่านปกติ (ตั้งใจ ไม่ให้ Google login เป็นช่องทางลัดข้ามการตั้งรหัสผ่านครั้งแรก)
    public function googleLogin(): void
    {
        $clientId = \Core\AppConfig::googleClientId();
        if ($clientId === null) {
            self::error('ยังไม่ได้เปิดใช้งานการเข้าสู่ระบบด้วย Google', 501, 'GOOGLE_LOGIN_NOT_CONFIGURED');
        }

        $body = self::body();
        $credential = trim((string) ($body['credential'] ?? ''));
        if ($credential === '') {
            self::error('ข้อมูลจาก Google ไม่ถูกต้อง', 400, 'VALIDATION_ERROR');
        }

        $logModel = new CheckinLoginLogModel();
        $ip = self::clientIp();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        $payload = self::verifyGoogleIdToken($credential, $clientId);
        if ($payload === null) {
            self::error('ยืนยันตัวตนกับ Google ไม่สำเร็จ กรุณาลองใหม่อีกครั้ง', 401, 'GOOGLE_TOKEN_INVALID');
        }
        $email = (string) $payload['email'];

        $model = new CheckinAuthModel();
        $employee = $model->findByEmail($email);
        if ($employee === null) {
            $logModel->logAttempt($email, 'FAILED', null, 'GOOGLE_EMAIL_NOT_FOUND', $ip, $userAgent, null, null);
            self::error(
                'ไม่พบบัญชีพนักงานที่ใช้อีเมล Google นี้ กรุณาเข้าสู่ระบบด้วยอีเมล/รหัสผ่าน หรือติดต่อฝ่ายบุคคล (HR)',
                404,
                'EMAIL_NOT_FOUND',
            );
        }
        $empcode = $employee['empcode'];

        // ยังต้องเคยตั้งรหัสผ่านไปแล้วอย่างน้อยหนึ่งครั้งก่อนถึงจะ login ด้วย Google ได้ (ดู comment บน method)
        if ((new CheckinAccountModel())->needsSetup($empcode)) {
            $logModel->logAttempt($empcode, 'FAILED', null, 'GOOGLE_NEEDS_SETUP', $ip, $userAgent, null, null);
            self::json(['needsSetup' => true, 'empcode' => $empcode, 'email' => $email]);
        }

        $this->issueSession($employee, $logModel, $ip, $userAgent);
    }

    // ตรวจ ID token กับ Google ตรงๆ ผ่าน tokeninfo endpoint (Google ตรวจลายเซ็น/วันหมดอายุให้เสร็จ ไม่ต้องมา
    // verify RS256 เอง — เบากว่าและโปรเจกต์นี้ไม่มี library ภายนอกอยู่แล้ว เหมือน core/Jwt.php, core/Mailer.php)
    // คืน ['email' => ...] ถ้าผ่านครบ (aud ตรงกับ client ของเรา, email_verified=true, ยังไม่หมดอายุ) ไม่งั้น null
    private static function verifyGoogleIdToken(string $credential, string $clientId): ?array
    {
        $url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($credential);
        $context = stream_context_create(['http' => ['timeout' => 8, 'ignore_errors' => true]]);
        $raw = @file_get_contents($url, false, $context);
        if ($raw === false) return null;

        $data = json_decode($raw, true);
        if (!is_array($data) || isset($data['error'])) return null;
        if (($data['aud'] ?? null) !== $clientId) return null;
        if (($data['email_verified'] ?? 'false') !== 'true') return null;
        if (empty($data['email']) || !is_string($data['email'])) return null;

        return ['email' => $data['email']];
    }

    // ตั้งรหัสผ่านใหม่ — ใช้ทั้งสมัครครั้งแรก (ยังไม่เคยมีบัญชี) และ HR รีเซ็ตให้เพราะลืมรหัสผ่าน (status=0)
    // เป็น flow เดียวกัน ไม่แยก endpoint ตามที่ตกลงกัน ต้องกรอก "รหัสผ่านชั่วคราว" (= empcode ตัวเอง) ซ้ำมา
    // ยืนยันอีกชั้นฝั่ง server เสมอ ไม่เชื่อแค่ที่ frontend เช็คผ่านมาจากหน้า login แล้ว
    //
    // ปัจจัยที่สอง: ต้องกรอกอีเมลที่ลงทะเบียนไว้ใน Employee_Dempc_Roster ให้ตรงด้วย — empcode เดาง่าย/เห็นจาก
    // บัตรพนักงานได้ ไม่ใช่ความลับจริง ถ้าไม่บังคับอีเมลด้วย ใครก็ตั้งรหัสผ่านแทนคนอื่นได้เลยก่อนเจ้าของตัวจริง
    // (ดู CheckinAuthModel::emailMatches()) — คนที่ยังไม่มีอีเมลลงทะเบียนใน Roster เลยจะตั้งรหัสผ่านเองไม่ได้
    // จนกว่า HR จะกรอกอีเมลให้ก่อน (ตั้งใจ ปลอดภัยกว่าเปิดให้ตั้งได้อย่างเสรี)
    public function setupPassword(): void
    {
        $body = self::body();
        $empcode = trim((string) ($body['empcode'] ?? ''));
        $email = trim((string) ($body['email'] ?? ''));
        $tempPassword = (string) ($body['password'] ?? '');
        $newPassword = (string) ($body['newPassword'] ?? '');

        if ($empcode === '' || $email === '' || $tempPassword === '' || $newPassword === '') {
            self::error('กรุณากรอกข้อมูลให้ครบถ้วน', 400, 'VALIDATION_ERROR');
        }

        $logModel = new CheckinLoginLogModel();
        $ip = self::clientIp();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        // กัน brute-force เดา empcode/อีเมลคนอื่น — ใช้ log ตัวเดียวกับ login() (นับรวมกัน ไม่แยกประเภท เพราะ
        // ทั้งคู่คือ "ความพยายามยืนยันตัวตนของ empcode นี้" เหมือนกัน)
        self::checkLockout($logModel, $empcode, $ip, 'พยายามตั้งรหัสผ่านผิดหลายครั้งเกินไป กรุณาลองใหม่อีกครั้งใน 15 นาที');

        if ($tempPassword !== $empcode) {
            $logModel->logAttempt($empcode, 'FAILED', null, 'INVALID_CREDENTIALS', $ip, $userAgent, null, null);
            self::error('รหัสผ่านชั่วคราวไม่ถูกต้อง', 401, 'INVALID_CREDENTIALS');
        }
        if (!(new CheckinAuthModel())->emailMatches($empcode, $email)) {
            $logModel->logAttempt($empcode, 'FAILED', null, 'EMAIL_MISMATCH', $ip, $userAgent, null, null);
            self::error('อีเมลไม่ตรงกับที่ลงทะเบียนไว้ กรุณาติดต่อฝ่ายบุคคล (HR) หากไม่แน่ใจ', 401, 'EMAIL_MISMATCH');
        }
        if (mb_strlen($newPassword) < 8) {
            self::error('รหัสผ่านใหม่ต้องมีอย่างน้อย 8 ตัวอักษร', 400, 'VALIDATION_ERROR');
        }
        if ($newPassword === $empcode) {
            self::error('กรุณาตั้งรหัสผ่านใหม่ที่ไม่ใช่รหัสพนักงานของตัวเอง', 400, 'VALIDATION_ERROR');
        }

        $accountModel = new CheckinAccountModel();
        if (!$accountModel->needsSetup($empcode)) {
            self::error('บัญชีนี้ตั้งรหัสผ่านไปแล้ว กรุณาเข้าสู่ระบบตามปกติ', 409, 'ALREADY_SET_UP');
        }

        $model = new CheckinAuthModel();
        $employee = $model->findByEmpcode($empcode);
        if ($employee === null) {
            self::error('ไม่พบข้อมูลพนักงาน', 404, 'NOT_FOUND');
        }

        $accountModel->setPassword($empcode, $newPassword);

        $this->issueSession($employee, $logModel, $ip, $userAgent);
    }

    // ออก JWT + บันทึก log SUCCESS — ใช้ร่วมกันทั้ง login() สำเร็จปกติ และ setupPassword() สำเร็จ (ตั้งรหัสผ่าน
    // เสร็จแล้วเข้าสู่ระบบให้อัตโนมัติเลย ไม่ต้องให้กรอก login ซ้ำอีกรอบ)
    private function issueSession(array $employee, CheckinLoginLogModel $logModel, ?string $ip, ?string $userAgent): never
    {
        $secretFile = dirname(__DIR__) . '/config/jwt.php';
        $config = is_file($secretFile) ? require $secretFile : null;
        if (!is_array($config) || empty($config['secret'])) {
            self::error('Server auth is not configured', 500, 'AUTH_NOT_CONFIGURED');
        }

        // jti = ID เฉพาะของ token ใบนี้ (ไม่ใช่ตัว JWT เต็ม) เก็บเป็น session_token ในตาราง log ไว้จับคู่กับ
        // ตอน logout — ฝัง claim นี้ไปใน JWT เองด้วย เพื่อให้ AbstractApiController ถอดกลับมาใช้ตอน logout() ได้
        $jti = self::generateUuidV4();
        $ttlSeconds = (int) $config['ttl_seconds'];
        $token = Jwt::encode(['empcode' => $employee['empcode'], 'jti' => $jti], $config['secret'], $ttlSeconds);

        $user = $this->buildUserPayload($employee);
        $expiresAt = date('c', time() + $ttlSeconds);
        $logModel->logAttempt($employee['empcode'], 'SUCCESS', $user['department'], null, $ip, $userAgent, $jti, $expiresAt);

        self::json([
            'token' => $token,
            'user' => $user,
        ]);
    }

    // เช็ค lockout ทั้งสองชั้น (ต่อบัญชี + ต่อ IP ข้ามบัญชี) — เรียกก่อนเทียบรหัสผ่านทุกครั้งที่ endpoint
    // ไหนต้องกัน brute-force ตอบ error เดียวกันทั้งคู่ (ไม่บอกว่าโดนล็อกแบบไหน กันคนโจมตีรู้ threshold ที่
    // ตั้งไว้) ดู comment ที่ IP_LOCKOUT_MAX_FAILURES ด้านบนประกอบ
    private static function checkLockout(CheckinLoginLogModel $logModel, string $empcode, ?string $ip, string $message): void
    {
        if ($logModel->countRecentFailures($empcode, self::LOCKOUT_WINDOW_MINUTES) >= self::LOCKOUT_MAX_FAILURES) {
            self::error($message, 429, 'TOO_MANY_ATTEMPTS');
        }
        if ($ip !== null && $logModel->countRecentFailuresByIp($ip, self::IP_LOCKOUT_WINDOW_MINUTES) >= self::IP_LOCKOUT_MAX_FAILURES) {
            self::error($message, 429, 'TOO_MANY_ATTEMPTS');
        }
    }

    private static function generateUuidV4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    public function me(): void
    {
        $model = new CheckinAuthModel();
        $employee = $model->findByEmpcode((string) $this->empcode);
        if ($employee === null) {
            self::error('ไม่พบข้อมูลพนักงาน', 404, 'NOT_FOUND');
        }
        self::json($this->buildUserPayload($employee));
    }

    // POST /me/password: identity comes from the authenticated JWT, never the request body.
    // Read-only verification for the current-password field on blur; still requires JWT.
    public function verifyCurrentPassword(): void
    {
        $password = self::body()['currentPassword'] ?? null;
        if (!is_string($password) || $password === '') {
            self::error('กรุณากรอกรหัสผ่านปัจจุบัน', 400, 'VALIDATION_ERROR');
        }
        $empcode = (string) $this->empcode;
        $logModel = new CheckinLoginLogModel();
        try {
            if ($logModel->countRecentFailures($empcode, self::LOCKOUT_WINDOW_MINUTES) >= self::LOCKOUT_MAX_FAILURES) {
                self::error('ตรวจรหัสผ่านผิดหลายครั้งเกินไป กรุณาลองใหม่อีกครั้งใน 15 นาที', 429, 'TOO_MANY_ATTEMPTS');
            }
            $valid = (new CheckinAccountModel())->verifyPassword($empcode, $password);
        } catch (\Throwable $e) {
            error_log('[AuthController::verifyCurrentPassword] ' . $e->getMessage());
            self::error('ไม่สามารถตรวจรหัสผ่านได้ในขณะนี้ กรุณาลองใหม่อีกครั้ง', 500, 'PASSWORD_VERIFY_FAILED');
        }
        if (!$valid) {
            $logModel->logAttempt($empcode, 'FAILED', null, 'CURRENT_PASSWORD_INVALID', self::clientIp(), $_SERVER['HTTP_USER_AGENT'] ?? null, null, null);
            self::error('รหัสผ่านปัจจุบันไม่ถูกต้อง', 400, 'CURRENT_PASSWORD_INVALID');
        }
        self::json(['valid' => true]);
    }
    public function changePassword(): void
    {
        $body = self::body();
        $currentPassword = $body['currentPassword'] ?? null;
        $newPassword = $body['newPassword'] ?? null;
        if (!is_string($currentPassword) || $currentPassword === ''
            || !is_string($newPassword) || $newPassword === '') {
            self::error('กรุณากรอกรหัสผ่านปัจจุบันและรหัสผ่านใหม่', 400, 'VALIDATION_ERROR');
        }
        if (mb_strlen($newPassword) < 8 || !preg_match('/[A-Za-z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword)) {
            self::error('รหัสผ่านใหม่ต้องมีอย่างน้อย 8 ตัวอักษร และมีทั้งตัวอักษรภาษาอังกฤษกับตัวเลข', 400, 'VALIDATION_ERROR');
        }
        // PASSWORD_DEFAULT currently uses bcrypt, which only processes 72 bytes.
        if (strlen($newPassword) > 72) {
            self::error('รหัสผ่านใหม่ยาวเกินไป กรุณาใช้ไม่เกิน 72 ไบต์', 400, 'VALIDATION_ERROR');
        }
        if ($newPassword === $currentPassword || $newPassword === (string) $this->empcode) {
            self::error('กรุณาใช้รหัสผ่านใหม่ที่ต่างจากรหัสผ่านปัจจุบันและรหัสพนักงาน', 400, 'VALIDATION_ERROR');
        }

        $empcode = (string) $this->empcode;
        $logModel = new CheckinLoginLogModel();
        try {
            if ($logModel->countRecentFailures($empcode, self::LOCKOUT_WINDOW_MINUTES) >= self::LOCKOUT_MAX_FAILURES) {
                self::error('พยายามยืนยันรหัสผ่านผิดหลายครั้งเกินไป กรุณาลองใหม่อีกครั้งใน 15 นาที', 429, 'TOO_MANY_ATTEMPTS');
            }
            $changed = (new CheckinAccountModel())->changePassword($empcode, $currentPassword, $newPassword);
        } catch (\Throwable $e) {
            error_log('[AuthController::changePassword] ' . $e->getMessage());
            self::error('ไม่สามารถเปลี่ยนรหัสผ่านได้ในขณะนี้ กรุณาลองใหม่อีกครั้ง', 500, 'PASSWORD_CHANGE_FAILED');
        }
        if (!$changed) {
            $logModel->logAttempt($empcode, 'FAILED', null, 'PASSWORD_CHANGE_FAILED', self::clientIp(), $_SERVER['HTTP_USER_AGENT'] ?? null, null, null);
            // 400 keeps the authenticated session intact; the frontend clears tokens on 401.
            self::error('รหัสผ่านปัจจุบันไม่ถูกต้อง หรือบัญชีมีการเปลี่ยนแปลง กรุณาตรวจสอบอีกครั้ง', 400, 'CURRENT_PASSWORD_INVALID');
        }
        self::json(['success' => true]);
    }
    public function logout(): void
    {
        // JWT เป็น stateless ไม่มี session ให้ลบฝั่ง server จริง (token เดิมยังใช้งานต่อได้จนกว่าจะหมดอายุ) —
        // แค่บันทึกไว้เป็น audit ว่าผู้ใช้กดออกจากระบบเอง ไม่ใช่การ "เตะ" session เหมือนเว็บหลัก
        if ($this->jti !== null) {
            (new CheckinLoginLogModel())->logLogout($this->jti);
        }
        self::json(['success' => true]);
    }

    // POST /auth/password/forgot — ส่งลิงก์รีเซ็ตรหัสผ่านไปที่อีเมลที่ลงทะเบียนไว้ใน Employee_Dempc_Roster
    // ตอบ EMAIL_NOT_FOUND ชัดเจนถ้าไม่เจออีเมลนี้ในระบบ (ตั้งใจไม่กัน enumeration — แอปนี้เป็นแอปภายในสำหรับ
    // พนักงานที่มีอยู่แล้วเท่านั้น ไม่มีหน้าสมัครสมาชิกให้คนนอกใช้ ความเสี่ยงจาก enumeration ต่ำกว่าเว็บทั่วไป
    // มาก ขณะที่ผู้ใช้จริงพิมพ์อีเมลผิด/ใช้อีเมลที่ไม่ได้ลงทะเบียนไว้บ่อยกว่า ควรรู้ทันทีว่าต้องแก้ไขอะไร
    // แทนที่จะรอกล่องจดหมายที่ไม่มีวันมา) ของเดิม (HR รีเซ็ตมือผ่าน CheckinAccountModel::resetByEmpcode())
    // ยังใช้ได้เป็น fallback ถ้าพนักงานเข้าไม่ถึงอีเมลตัวเอง
    public function requestPasswordReset(): void
    {
        $body = self::body();
        $email = trim((string) ($body['email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            self::error('กรุณากรอกอีเมลให้ถูกต้อง', 400, 'VALIDATION_ERROR');
        }

        $logModel = new CheckinLoginLogModel();
        $ip = self::clientIp();
        // กัน spam กดขอลิงก์รัว ๆ — ใช้ log เดียวกับ login() (นับรวมเป็น "ความพยายามยืนยันตัวตน" แบบเดียวกัน)
        $model = new CheckinAuthModel();
        $employee = $model->findByEmail($email);

        if ($employee === null) {
            self::error('ไม่พบอีเมลนี้ในระบบ กรุณาตรวจสอบอีเมลที่ลงทะเบียนไว้กับฝ่ายบุคคล (HR) อีกครั้ง', 404, 'EMAIL_NOT_FOUND');
        }

        $empcode = $employee['empcode'];
        // ไม่ enumeration แล้วก็จริง แต่ยังกัน spam กดขอลิงก์รัว ๆ ไว้เหมือนเดิม — บอกตรงๆ ว่าเกินจำนวนครั้ง
        // แทนที่จะเงียบแล้วไม่ส่ง (ผู้ใช้จะได้รู้ว่าทำไมอีเมลไม่มาสักที ไม่ใช่นึกว่าระบบพัง)
        self::checkLockout($logModel, $empcode, $ip, 'ขอลิงก์รีเซ็ตรหัสผ่านบ่อยเกินไป กรุณาลองใหม่อีกครั้งใน 15 นาที');

        try {
            $token = (new CheckinPasswordResetModel())->createToken($empcode, $ip);
            $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
            // โดเมนที่อนุญาตตั้งที่ config/app.php (ตัวเดียวกับ CORS) — Origin ปลอมที่ไม่อยู่ในรายการจะใช้โดเมนหลัก
            // (ตัวแรก) แทน กัน phishing ผ่านลิงก์ในอีเมลรีเซ็ตรหัสผ่าน
            $allowed = \Core\AppConfig::frontendOrigins();
            if ($allowed === []) throw new \RuntimeException('ยังไม่ได้ตั้ง frontend_origins ใน config/app.php');
            $base = in_array($origin, $allowed, true) ? $origin : $allowed[0];
            $link = $base . '/reset-password?token=' . urlencode($token);

            (new Mailer())->send(
                $email,
                'CheckInTime: คำขอตั้งรหัสผ่านใหม่',
                "สวัสดีค่ะ/ครับ\n\n"
                . "คุณเพิ่งกดขอตั้งค่ารหัสผ่านใหม่เมื่อสักครู่นี้ กรุณาคลิกที่ลิงก์ด้านล่างเพื่อเปลี่ยนรหัสผ่านของคุณ "
                . "(ลิงก์นี้ใช้ได้ภายใน 30 นาที)\n\n"
                . "{$link}\n\n"
                . "หากคุณไม่ได้กดขอตั้งรหัสผ่านใหม่ด้วยตนเอง หรือต้องการความช่วยเหลือเพิ่มเติม "
                . "กรุณาติดต่อฝ่ายบุคคล (HR) ของบริษัท\n\n"
                . "ขอบคุณค่ะ/ครับ\n"
                . "CheckInTime\n\n"
                . "——————————————\n"
                . "อีเมลนี้ถูกจัดส่งจากระบบแจ้งเตือนอัตโนมัติ ซึ่งไม่สามารถรับอีเมลตอบกลับได้"
            );
        } catch (\Throwable $e) {
            error_log('[AuthController::requestPasswordReset] ' . $e->getMessage());
            self::error('ไม่สามารถส่งอีเมลได้ในขณะนี้ กรุณาลองใหม่อีกครั้ง', 500, 'EMAIL_SEND_FAILED');
        }

        self::json(['success' => true, 'message' => 'ส่งลิงก์รีเซ็ตรหัสผ่านไปที่อีเมลของคุณแล้ว กรุณาตรวจสอบกล่องจดหมาย']);
    }

    // POST /auth/password/reset — ตั้งรหัสผ่านใหม่จาก token ที่ได้จากลิงก์ในอีเมล
    public function resetPassword(): void
    {
        $body = self::body();
        $token = trim((string) ($body['token'] ?? ''));
        $newPassword = (string) ($body['newPassword'] ?? '');

        if ($token === '' || $newPassword === '') {
            self::error('ข้อมูลไม่ครบถ้วน', 400, 'VALIDATION_ERROR');
        }
        if (mb_strlen($newPassword) < 8 || !preg_match('/[A-Za-z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword)) {
            self::error('รหัสผ่านใหม่ต้องมีอย่างน้อย 8 ตัวอักษร และมีทั้งตัวอักษรภาษาอังกฤษกับตัวเลข', 400, 'VALIDATION_ERROR');
        }

        $resetModel = new CheckinPasswordResetModel();
        $empcode = $resetModel->findValid($token);
        if ($empcode === null) {
            self::error('ลิงก์รีเซ็ตรหัสผ่านหมดอายุหรือถูกใช้ไปแล้ว กรุณาขอลิงก์ใหม่', 400, 'TOKEN_INVALID');
        }
        if ($newPassword === $empcode) {
            self::error('กรุณาตั้งรหัสผ่านใหม่ที่ไม่ใช่รหัสพนักงานของตัวเอง', 400, 'VALIDATION_ERROR');
        }

        (new CheckinAccountModel())->setPassword($empcode, $newPassword);
        $resetModel->consume($token);

        $employee = (new CheckinAuthModel())->findByEmpcode($empcode);
        if ($employee === null) {
            self::json(['success' => true]);
        }

        $logModel = new CheckinLoginLogModel();
        $this->issueSession($employee, $logModel, self::clientIp(), $_SERVER['HTTP_USER_AGENT'] ?? null);
    }

    public function consents(): void
    {
        $body = self::body();
        $policyVersion = trim((string) ($body['policyVersion'] ?? ''));
        $location = (bool) ($body['location'] ?? false);

        if ($policyVersion === '') {
            self::error('กรุณายอมรับนโยบายความเป็นส่วนตัว', 400, 'VALIDATION_ERROR');
        }

        $ip = self::clientIp();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        try {
            $consentModel = new CheckinConsentModel();
            $consentModel->record((string) $this->empcode, 'privacy_policy', 'granted', $policyVersion, $ip, $userAgent);
        // gps_location แยก step ต่างหาก — บันทึกเป็น "granted" เฉพาะตอนติ๊กยอมรับจริง ถ้าไม่ติ๊กก็ไม่ต้องเขียน
        // แถวอะไรเลย (ไม่มีแถว = ยังไม่เคยยินยอม ต่างจากการ "ถอน" ซึ่งต้องมีแถว action=revoked ชัดเจน)
            if ($location) {
                $consentModel->record((string) $this->empcode, 'gps_location', 'granted', null, $ip, $userAgent);
            }
        } catch (\Throwable $e) {
            error_log('[AuthController::consents] ' . $e->getMessage());
            self::error('ไม่สามารถบันทึกความยินยอมได้ กรุณาลองใหม่อีกครั้ง', 500, 'CONSENT_SAVE_FAILED');
        }

        $model = new CheckinAuthModel();
        $employee = $model->findByEmpcode((string) $this->empcode);
        if ($employee === null) {
            self::error('ไม่พบข้อมูลพนักงาน', 404, 'NOT_FOUND');
        }
        self::json($this->buildUserPayload($employee));
    }

    // โครงสร้างตาม docs/api-spec-for-backend.txt ข้อ 3.1 — position/department/phone/startDate ดึงจาก
    // CheckinAuthModel::getPersonalDetail() (เขียนแยกเป็นของ CheckInTime เอง ไม่พึ่ง
    // Models\Employee\EmployeeModel ของเว็บหลักแล้ว เพราะไฟล์นั้นลาก Models\Management\AttendanceModel.php
    // ตามมาด้วยทั้งที่ไม่เกี่ยวกัน — ดู comment ที่ getPersonalDetail()) ส่วน email, address,
    // emergencyContact มาจาก getContactFields() (Employee_Dempc_Roster)
    private function buildUserPayload(array $employee): array
    {
        $consent = (new CheckinConsentModel())->getSummary((string) $employee['empcode']);
        $authModel = new CheckinAuthModel();
        $contact = $authModel->getContactFields((string) $employee['empcode']);

        // รูปโปรไฟล์: ฝังเป็น base64 dataURL ตรงใน response (ไม่ใช่ URL ให้ไปโหลดต่อ) เพราะทุก endpoint ของ
        // CheckInTime ต้องมี JWT ซึ่ง <img src="..."> ธรรมดาส่ง header ไม่ได้ — ดู
        // CheckinAuthModel::getPhotoDataUrl()
        $avatarUrl = $authModel->getPhotoDataUrl((string) $employee['empcode']);

        $detail = $authModel->getPersonalDetail((string) $employee['empcode']);

        $hasEmergencyContact = !empty($contact['emergency_name']) || !empty($contact['emergency_relation']) || !empty($contact['emergency_phone']);

        return [
            'id'            => (int) $employee['id'],
            'employeeCode'  => $employee['empcode'],
            'email'         => $contact['email'] ?: null,
            'name'          => trim(($employee['empname'] ?? '') . ' ' . ($employee['emplname'] ?? '')),
            'position'      => $detail['Position'] ?: null,
            'department'    => $detail['Department'] ?: null,
            // หน่วยงาน (level 3 ในผังองค์กร — ใต้ฝ่าย/แผนก) มาจาก hrtime.dbo.DepartmentSub เหมือนกับที่
            // OrganizationModel/sync_employee_full.php ใช้ — getUserPersonalDetail() join มาให้แล้วเป็น
            // UNIT อยู่แล้ว ไม่ต้อง query เพิ่ม บางคนไม่มี unit สังกัด (เช่น office staff) จะได้ค่าว่าง → null
            'unit'          => $detail['UNIT'] ?: null,
            'phone'         => $detail['mobile'] ?: null,
            'startDate'     => $detail['startdate'] ?: null,
            'avatarUrl'     => $avatarUrl,
            'address'       => $contact['address'] ?: null,
            'emergencyContact' => $hasEmergencyContact ? [
                'name'     => $contact['emergency_name'] ?: null,
                'relation' => $contact['emergency_relation'] ?: null,
                'phone'    => $contact['emergency_phone'] ?: null,
            ] : null,
            'consent'       => $consent,
        ];
    }

    // PUT /me/profile — แก้ไขข้อมูลติดต่อของตัวเอง (email/address/emergencyContact/avatarUrl) ตาม
    // checkin/frontend/src/api/services/authService.js::updateProfile() — phone ไม่อยู่ในนี้เพราะดึงจาก
    // hrtime.dbo.employeesNew.mobile โดยตรง (ข้อมูล HR ดูแล แก้จากแอปนี้ไม่ได้)
    public function updateProfile(): void
    {
        $body = self::body();
        $empcode = (string) $this->empcode;
        $emergency = is_array($body['emergencyContact'] ?? null) ? $body['emergencyContact'] : [];

        // สร้าง array เฉพาะคีย์ที่ client ส่งมาจริง (ไม่ใส่คีย์ = ไม่แตะคอลัมน์นั้น) — กันกรณี client ส่ง
        // อัปเดตบางส่วน (เช่น แก้แค่รูปโปรไฟล์) แล้ว updateContactFields() ไปเคลียร์ email/address/
        // emergency_* ที่เหลือเป็น NULL ทิ้งหมดโดยไม่ตั้งใจ (email คือตัวเดียวที่ใช้ล็อกอิน ถ้าหายไปเงียบๆ
        // ผู้ใช้จะเข้าระบบไม่ได้เลย) — PersonalInfoView.vue ส่งฟอร์มเต็มอยู่แล้วตามปกติ แต่ backend ต้องไม่
        // พึ่งพฤติกรรมฝั่ง client เพียงอย่างเดียว
        $fields = [];
        if (array_key_exists('email', $body)) {
            $email = trim((string) $body['email']);
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                self::error('รูปแบบอีเมลไม่ถูกต้อง', 400, 'VALIDATION_ERROR');
            }
            $fields['email'] = $email;
        }
        if (array_key_exists('address', $body)) {
            $fields['address'] = trim((string) $body['address']);
        }
        if (array_key_exists('emergencyContact', $body)) {
            $fields['emergency_name'] = trim((string) ($emergency['name'] ?? ''));
            $fields['emergency_relation'] = trim((string) ($emergency['relation'] ?? ''));
            $fields['emergency_phone'] = trim((string) ($emergency['phone'] ?? ''));
        }

        $authModel = new CheckinAuthModel();
        try {
            if ($fields !== []) {
                $authModel->updateContactFields($empcode, $fields);
            }

            // avatarUrl: null = ลบรูป (ปุ่ม "ลบรูปโปรไฟล์"), dataURL ใหม่ = อัปโหลด/เปลี่ยนรูป, ไม่ส่งมาเลย = ไม่แตะรูปเดิม
            if (array_key_exists('avatarUrl', $body)) {
                if ($body['avatarUrl'] === null) {
                    $authModel->deletePhoto($empcode);
                } elseif (is_string($body['avatarUrl']) && str_starts_with($body['avatarUrl'], 'data:image/')) {
                    $authModel->savePhoto($empcode, $body['avatarUrl']);
                }
            }
        } catch (\InvalidArgumentException $e) {
            self::error($e->getMessage(), 400, 'VALIDATION_ERROR');
        } catch (\Throwable $e) {
            error_log('[AuthController::updateProfile] ' . $e->getMessage());
            self::error('ไม่สามารถบันทึกข้อมูลได้ในขณะนี้ กรุณาลองใหม่อีกครั้ง', 500, 'PROFILE_UPDATE_FAILED');
        }

        $employee = $authModel->findByEmpcode($empcode);
        if ($employee === null) {
            self::error('ไม่พบข้อมูลพนักงาน', 404, 'NOT_FOUND');
        }
        self::json($this->buildUserPayload($employee));
    }
}
