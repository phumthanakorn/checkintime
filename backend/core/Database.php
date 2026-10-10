<?php
namespace Core;

use PDO;

// โปรเจกต์นี้ (checkin เดี่ยว) ใช้ MySQL ในเครื่อง (ฐาน `checkin`) เป็นฐานข้อมูลเดียว — ของเดิม (คัดลอกมา
// จาก GBG-MANAGEMENT) มี method ต่อ MSSQL หลายฐาน + MySQL กลางข้ามเครือข่ายด้วย ตัดออกหมดแล้วตามที่ตกลงกัน
// ว่าโปรเจกต์นี้จะไม่เชื่อมฐานข้อมูลภายนอกอีกต่อไป เหลือ connection เดียวคือตัวนี้
class Database {
    private static ?PDO $connection = null;

    // Models ทั้งหมดเขียน query เป็น T-SQL (MSSQL syntax) มาแต่เดิม — PDO ตัวนี้ (LocalMysqlPdo) แปลเป็น
    // MySQL ให้อัตโนมัติตอน prepare/query/exec จึงไม่ต้องไล่เขียน Models ใหม่ทั้งหมด
    public static function connectLocalMysql(): PDO {
        if (self::$connection !== null) {
            return self::$connection;
        }

        $config = require dirname(__DIR__) . '/config/db.php';
        $c = $config['local_mysql'] ?? null;
        if (!$c) {
            throw new \RuntimeException("ยังไม่ได้ตั้งค่า config['local_mysql'] ใน config/db.php");
        }

        require_once __DIR__ . '/LocalMysqlPdo.php';
        $dsn = "mysql:host={$c['host']};port=" . ($c['port'] ?? 3306) . ";dbname={$c['database']};charset=utf8mb4";
        $pdo = new LocalMysqlPdo($dsn, $c['user'], $c['password'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_FOUND_ROWS   => true, // rowCount() = แถวที่ match (เหมือน MSSQL) ไม่ใช่แถวที่ค่าเปลี่ยน
        ]);
        $pdo->exec("SET time_zone = '+07:00'");
        return self::$connection = $pdo;
    }
}
