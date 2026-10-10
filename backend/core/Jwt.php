<?php
declare(strict_types=1);

namespace Core;

// JWT แบบ HS256 ขั้นต่ำสุด — เขียนเองแทนที่จะพึ่ง library ภายนอก (ไม่มี composer.json/firebase/php-jwt
// ติดตั้งอยู่ในโปรเจกต์นี้) ใช้เฉพาะ encode()/decode() เท่าที่ api/checkin ต้องการ ไม่รองรับ algorithm อื่น
// หรือ claim พิเศษใดๆ เกินจำเป็น
class Jwt
{
    public static function encode(array $payload, string $secret, int $ttlSeconds): string
    {
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];
        $payload['iat'] = time();
        $payload['exp'] = time() + $ttlSeconds;

        $segments = [
            self::base64UrlEncode(json_encode($header, JSON_UNESCAPED_UNICODE)),
            self::base64UrlEncode(json_encode($payload, JSON_UNESCAPED_UNICODE)),
        ];
        $signingInput = implode('.', $segments);
        $signature = hash_hmac('sha256', $signingInput, $secret, true);
        $segments[] = self::base64UrlEncode($signature);

        return implode('.', $segments);
    }

    // คืน payload (array) ถ้า token ถูกต้องและยังไม่หมดอายุ, null ถ้าไม่ผ่าน (ลายเซ็นไม่ตรง/parse ไม่ได้/หมดอายุ)
    public static function decode(string $token, string $secret): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;
        [$headerB64, $payloadB64, $sigB64] = $parts;

        $signingInput = "$headerB64.$payloadB64";
        $expectedSig = hash_hmac('sha256', $signingInput, $secret, true);
        $actualSig = self::base64UrlDecode($sigB64);
        if ($actualSig === false || !hash_equals($expectedSig, $actualSig)) return null;

        $payloadJson = self::base64UrlDecode($payloadB64);
        if ($payloadJson === false) return null;
        $payload = json_decode($payloadJson, true);
        if (!is_array($payload)) return null;

        if (!isset($payload['exp']) || time() >= (int) $payload['exp']) return null;

        return $payload;
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string|false
    {
        $padded = str_pad($data, strlen($data) % 4 === 0 ? strlen($data) : strlen($data) + (4 - strlen($data) % 4), '=');
        return base64_decode(strtr($padded, '-_', '+/'), true);
    }
}
