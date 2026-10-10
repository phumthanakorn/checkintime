-- Schema สำหรับทดสอบในเครื่อง (MySQL, ฐาน `checkin`) — รวมตารางที่ backend แตะ และข้อมูลทดสอบ 1 คน
-- ตาราง hrtime.* เป็นแบบย่อ (เฉพาะคอลัมน์ที่โค้ดใช้) ใช้แทนฐาน HR กลางตอน dev เท่านั้น
-- รัน: mysql -uroot checkin < database/schema.mysql.sql
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS employeesNew (
  empid INT AUTO_INCREMENT PRIMARY KEY, empcode VARCHAR(20) NOT NULL, empname VARCHAR(100), emplname VARCHAR(100),
  workstatus INT NOT NULL DEFAULT 1, startdate DATE NULL, DeptSubcode VARCHAR(20) NULL,
  DepartmentSub INT NULL, posicode VARCHAR(20) NULL, KEY (empcode)
);
CREATE TABLE IF NOT EXISTS DeptSub (DeptSubcode VARCHAR(20) PRIMARY KEY, DeptSub VARCHAR(100));
CREATE TABLE IF NOT EXISTS DepartmentSub (ID INT PRIMARY KEY, Description VARCHAR(100));
CREATE TABLE IF NOT EXISTS positions (posicode VARCHAR(20) PRIMARY KEY, description VARCHAR(100));

CREATE TABLE IF NOT EXISTS Employee_Dempc_Roster (
  empcode VARCHAR(20) PRIMARY KEY, email VARCHAR(150) NULL, phone VARCHAR(30) NULL, address VARCHAR(500) NULL,
  emergency_name VARCHAR(100) NULL, emergency_relation VARCHAR(50) NULL, emergency_phone VARCHAR(30) NULL,
  profile_photo_path VARCHAR(255) NULL, UNIQUE KEY (email)
);
CREATE TABLE IF NOT EXISTS Employee_User_Permissions (id INT AUTO_INCREMENT PRIMARY KEY, empcode VARCHAR(20), active TINYINT DEFAULT 1);

CREATE TABLE IF NOT EXISTS Employee_Checkin_Account (
  empcode VARCHAR(20) PRIMARY KEY, password_hash VARCHAR(255) NULL, status TINYINT NOT NULL DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NULL
);
CREATE TABLE IF NOT EXISTS Employee_Checkin_Password_Reset (
  id INT AUTO_INCREMENT PRIMARY KEY, empcode VARCHAR(20) NOT NULL, token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL, used_at DATETIME NULL, ip VARCHAR(45) NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY (token_hash)
);
CREATE TABLE IF NOT EXISTS Employee_Checkin_Consent (
  id INT AUTO_INCREMENT PRIMARY KEY, empcode VARCHAR(20) NOT NULL,
  consent_type ENUM('privacy_policy','gps_location') NOT NULL, action ENUM('granted','revoked') NOT NULL,
  policy_version VARCHAR(20) NULL, ip_address VARCHAR(45) NULL, user_agent VARCHAR(500) NULL,
  created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6), KEY (empcode, consent_type, created_at)
);
CREATE TABLE IF NOT EXISTS Employee_CheckinTime_Login_Log (
  id INT AUTO_INCREMENT PRIMARY KEY, empcode VARCHAR(20) NOT NULL, department VARCHAR(100) NULL,
  status VARCHAR(20) NOT NULL, fail_reason VARCHAR(100) NULL, ip_address VARCHAR(45) NULL, user_agent VARCHAR(500) NULL,
  session_token VARCHAR(255) NULL, login_at DATETIME NOT NULL, expires_at DATETIME NULL,
  is_logout TINYINT NOT NULL DEFAULT 0, logout_at DATETIME NULL, KEY (empcode, login_at), KEY (session_token)
);
CREATE TABLE IF NOT EXISTS Employee_CheckinTime_Notification (
  id INT AUTO_INCREMENT PRIMARY KEY, empcode VARCHAR(20) NOT NULL, notification_type VARCHAR(30) NULL,
  reference_type VARCHAR(30) NULL, reference_id VARCHAR(50) NULL, title VARCHAR(255) NULL, body TEXT NULL,
  is_read TINYINT NOT NULL DEFAULT 0, read_at DATETIME NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, KEY (empcode)
);

CREATE TABLE IF NOT EXISTS Master_Dempc_Location (
  location_id INT PRIMARY KEY, location_name VARCHAR(150) NOT NULL, location_type VARCHAR(20) NOT NULL, empcode VARCHAR(20) NULL,
  is_active TINYINT DEFAULT 1, allow_edit_mobile TINYINT DEFAULT 0, allow_gps_checkin TINYINT DEFAULT 1, allow_gps_job TINYINT DEFAULT 0,
  latitude DECIMAL(10,7) NULL, longitude DECIMAL(10,7) NULL, radius_meters INT NULL,
  allow_beacon_checkin TINYINT DEFAULT 0, beacon_id VARCHAR(100) NULL,
  created_by VARCHAR(50) NULL, created_at DATETIME NULL, updated_by VARCHAR(50) NULL, updated_at DATETIME NULL
);

CREATE TABLE IF NOT EXISTS Employee_CheckinTime_Attendance_Log (
  id INT AUTO_INCREMENT PRIMARY KEY, empcode VARCHAR(20) NOT NULL, work_date DATE NOT NULL,
  check_in DATETIME NULL, check_in_lat DOUBLE NULL, check_in_lng DOUBLE NULL, check_in_accuracy_m DOUBLE NULL,
  check_in_nearest_location_id INT NULL, check_in_nearest_distance_m DOUBLE NULL, check_in_device VARCHAR(500) NULL,
  check_in_ip VARCHAR(45) NULL, check_in_photo_path VARCHAR(255) NULL,
  check_out DATETIME NULL, check_out_lat DOUBLE NULL, check_out_lng DOUBLE NULL, check_out_accuracy_m DOUBLE NULL,
  check_out_nearest_location_id INT NULL, check_out_nearest_distance_m DOUBLE NULL, check_out_device VARCHAR(500) NULL,
  check_out_ip VARCHAR(45) NULL, check_out_photo_path VARCHAR(255) NULL,
  approved_check_in DATETIME NULL, approved_check_out DATETIME NULL,
  approved_check_in_request_id INT NULL, approved_check_out_request_id INT NULL,
  late_minutes INT NOT NULL DEFAULT 0, ot_minutes INT NOT NULL DEFAULT 0, work_minutes INT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NULL, UNIQUE KEY (empcode, work_date)
);
CREATE TABLE IF NOT EXISTS Employee_Attendance_TimeFix_Request (
  id INT AUTO_INCREMENT PRIMARY KEY, empcode VARCHAR(20) NOT NULL, work_date DATE NOT NULL, attendance_log_id INT NULL,
  original_check_in DATETIME NULL, original_check_out DATETIME NULL, requested_check_in DATETIME NULL, requested_check_out DATETIME NULL,
  reason TEXT NULL, status VARCHAR(20) NOT NULL DEFAULT 'pending', created_by VARCHAR(50) NULL, attachments_json TEXT NULL,
  reviewed_by VARCHAR(50) NULL, reviewed_at DATETIME NULL, review_note TEXT NULL,
  cancelled_by VARCHAR(50) NULL, cancelled_at DATETIME NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NULL, KEY (empcode, work_date),
  -- กัน pending ซ้ำวันเดียวกันที่ระดับ DB (MySQL ไม่มี filtered unique index: status<>'pending' ได้ NULL ซึ่ง UNIQUE ยอมซ้ำ)
  pending_key VARCHAR(40) GENERATED ALWAYS AS (IF(status = 'pending', CONCAT(empcode, '|', work_date), NULL)) STORED,
  UNIQUE KEY uq_timefix_pending_per_day (pending_key)
);
CREATE TABLE IF NOT EXISTS Employee_Dempc_TimeAttendance (
  id INT AUTO_INCREMENT PRIMARY KEY, empcode VARCHAR(20) NOT NULL, work_date DATE NOT NULL,
  actual_in VARCHAR(8) NULL, actual_out VARCHAR(8) NULL, checkin_source VARCHAR(40) NULL, checkout_source VARCHAR(40) NULL,
  checkin_timefix_request_id INT NULL, checkout_timefix_request_id INT NULL, is_manual TINYINT DEFAULT 0,
  checkin_location VARCHAR(150) NULL, checkin_dist_m DOUBLE NULL, checkin_method VARCHAR(30) NULL,
  checkout_location VARCHAR(150) NULL, checkout_dist_m DOUBLE NULL,
  updated_by VARCHAR(50) NULL, updated_at DATETIME NULL, UNIQUE KEY (empcode, work_date)
);

-- ข้อมูลทดสอบ: login ด้วยอีเมล test@example.com รหัสผ่านชั่วคราว = 9999 (จะถูกพาไปตั้งรหัสใหม่)
INSERT IGNORE INTO DeptSub VALUES ('D01','ฝ่ายทดสอบ');
INSERT IGNORE INTO DepartmentSub VALUES (1,'หน่วยทดสอบ');
INSERT IGNORE INTO positions VALUES ('P01','เจ้าหน้าที่');
INSERT INTO employeesNew (empcode, empname, emplname, workstatus, startdate, DeptSubcode, DepartmentSub, posicode)
  SELECT '9999','ทดสอบ','ระบบ',1,'2024-01-01','D01',1,'P01' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM employeesNew WHERE empcode='9999');
INSERT IGNORE INTO Employee_Dempc_Roster (empcode, email, phone) VALUES ('9999','test@example.com','0800000000');
INSERT IGNORE INTO Master_Dempc_Location (location_id, location_name, location_type, is_active, allow_gps_checkin, latitude, longitude, radius_meters)
  VALUES (1,'สำนักงานใหญ่','office',1,1,13.7563,100.5018,200);
