<?php
namespace Core;

class Model
{
    // โปรเจกต์นี้ (checkin เดี่ยว แยกจาก GBG-MANAGEMENT แล้ว) ใช้ MySQL ในเครื่อง (ฐาน `checkin`) เป็นฐาน
    // ข้อมูลเดียวเท่านั้น — ไม่เชื่อมต่อ MSSQL/เซิร์ฟเวอร์ภายนอกใดๆ อีกต่อไป (ของเดิมที่คัดลอกมาจาก
    // GBG-MANAGEMENT เคยต่อ MSSQL หลายฐาน + MySQL กลางข้ามเครือข่าย ตัดออกทั้งหมดแล้ว)
    protected \PDO $db;

    // alias ของ $db เฉยๆ — Models เดิม (คัดลอกมาจาก GBG-MANAGEMENT) อ้างชื่อ property พวกนี้ตรงๆ เช่น
    // DempcLocationModel::gbgDb() ใช้ $this->dbGbgDataTest ?? $this->dbGbgData — คงไว้กันพัง ไม่ต้องไล่แก้
    // ทุกจุด ทุกตัวชี้ไปฐานเดียวกันหมด
    protected \PDO $dbHrtime;
    protected \PDO $dbHrtimeTest;
    protected \PDO $dbGbgData;
    protected \PDO $dbGbgDataTest;

    public function __construct()
    {
        require_once __DIR__ . '/Database.php';
        $this->db = Database::connectLocalMysql();
        $this->dbHrtime = $this->dbHrtimeTest = $this->dbGbgData = $this->dbGbgDataTest = $this->db;
    }

    /**
     * ฐานข้อมูลหลักของระบบ (MySQL ในเครื่อง) — ของเดิมเรียกว่า "GBG_Data" เพราะพึ่งฐาน MSSQL ของเว็บหลัก
     * ตอนนี้เป็นฐานเดียว ไม่แยก prod/test แล้ว คงชื่อ method นี้ไว้กัน Models เดิมพังหมด
     */
    protected function gbgDataForCurrentEnvironment(): ?\PDO
    {
        return $this->db;
    }

    protected function clientIp(): ?string
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return trim($_SERVER['HTTP_CLIENT_IP']);
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        }
        return $_SERVER['REMOTE_ADDR'] ?? null;
    }
}
