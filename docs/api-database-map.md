# CheckInTime API ↔ ตารางฐานข้อมูล

สรุปว่า endpoint แต่ละเส้นใน `backend/` ใช้ตาราง/แหล่งข้อมูลไหนบ้าง (อัปเดตล่าสุด: ดูวันที่ commit ไฟล์นี้)

> **อัปเดต:** โปรเจกต์นี้แยกออกมาจาก GBG-MANAGEMENT เป็นโปรเจกต์เดี่ยวแล้ว ฐานข้อมูลหลักเปลี่ยนจาก MSSQL
> (`GBG_Data` + `hrtime`) มาเป็น **MySQL/MariaDB ในเครื่อง ฐานชื่อ `checkin`** ฐานเดียว — ไม่เชื่อมต่อ MSSQL
> หรือเซิร์ฟเวอร์ภายนอกใดๆ อีกต่อไป (ดู [`backend/core/Model.php`](../backend/core/Model.php),
> [`backend/core/Database.php`](../backend/core/Database.php)) โครงสร้างตารางอยู่ที่
> [`database/schema.mysql.sql`](../database/schema.mysql.sql)
>
> ตาราง `hrtime.dbo.employeesNew` และตารางอื่นๆ ของฐาน HR กลางเดิม ถูกจำลองเป็นตารางเปล่าในฐาน `checkin`
> เดียวกัน (`employeesNew`, `DeptSub`, `DepartmentSub`, `positions`) — ต้อง import ข้อมูลพนักงานเข้าไปเอง
> (ยังไม่มีการ sync อัตโนมัติจาก HR กลางจริง — ดู "ของที่ยังค้างอยู่" ด้านล่าง)
>
> โค้ดใน Models ส่วนใหญ่ยังเขียน SQL เป็น T-SQL (MSSQL syntax, `dbo.`, `TOP n`, `SYSDATETIME()` ฯลฯ) ของเดิม
> — [`backend/core/LocalMysqlPdo.php`](../backend/core/LocalMysqlPdo.php) แปลเป็น MySQL ให้อัตโนมัติตอนรัน
> จึงไม่ต้องเขียน Models ใหม่ทั้งหมด

ไฟล์รูปภาพ (ไม่ใช่ตาราง) เก็บนอก webroot ที่ `storage/` (ที่ root ของโปรเจกต์ ไม่ใช่ `checkin/storage/` แบบเดิม
เพราะตอนนี้ `checkin/` คือ root ของโปรเจกต์เองแล้ว)

## Auth (`AuthController`)

| Endpoint | ตาราง/แหล่งข้อมูล |
|---|---|
| `POST /auth/login` | `Employee_Dempc_Roster` (resolve email→empcode), `Employee_Checkin_Account` (ตรวจรหัสผ่าน), `employeesNew`, `Employee_User_Permissions`, `Employee_CheckinTime_Login_Log` (log ทุกครั้ง) |
| `POST /auth/setup-password` | เหมือน login + `Employee_Checkin_Account` (เขียนรหัสผ่านใหม่) |
| `POST /auth/password/forgot` | `Employee_Dempc_Roster` (resolve email), `Employee_Checkin_Password_Reset` (สร้าง token) — ส่งอีเมลจริงผ่าน SMTP (`config/mail.php`) |
| `POST /auth/password/reset` | `Employee_Checkin_Password_Reset` (ตรวจ/ใช้ token), `Employee_Checkin_Account` (เขียนรหัสผ่านใหม่) |
| `GET /auth/me` | `Employee_Dempc_Roster`, `employeesNew` (join `DeptSub`/`DepartmentSub`/`positions`), `Employee_Checkin_Consent` |
| `POST /auth/logout` | `Employee_CheckinTime_Login_Log` (update logout) |
| `POST /me/consents` | `Employee_Checkin_Consent` |
| `POST /me/password` | `Employee_Checkin_Account`, `Employee_CheckinTime_Login_Log` |
| `POST /me/password/verify` | `Employee_Checkin_Account`, `Employee_CheckinTime_Login_Log` |
| `PUT /me/profile` | `Employee_Dempc_Roster` (คอลัมน์ `email`/`address`/`emergency_name`/`emergency_relation`/`emergency_phone`/`profile_photo_path` — อัปเดตเฉพาะคอลัมน์ที่ request ส่งมาจริง ไม่เขียนทับทั้งแถว) + ไฟล์จริงใน `storage/profile-photos/` |

## Attendance (`AttendanceController`)

| Endpoint | ตาราง/แหล่งข้อมูล |
|---|---|
| `GET /attendance/today` | `Employee_CheckinTime_Attendance_Log` (fallback `Employee_Dempc_TimeAttendance` ของระบบเก่า) |
| `GET /attendance/photo` | `Employee_CheckinTime_Attendance_Log` + ไฟล์ใน `storage/photos/` |
| `POST /attendance/check-in` | `Employee_CheckinTime_Attendance_Log` + เขียนไฟล์ `storage/photos/` |
| `POST /attendance/check-out` | `Employee_CheckinTime_Attendance_Log` + เขียนไฟล์ `storage/photos/` |
| `GET /attendance/history` | **ยังไม่ต่อจริง** — มี TODO, frontend ยังไม่เรียกใช้ |
| `GET /attendance/summary` | **ยังไม่ต่อจริง** — ยอดลาคงเหลือ hardcode เป็น 0 |

## Calendar (`CalendarController`)

| Endpoint | ตาราง/แหล่งข้อมูล |
|---|---|
| `GET /calendar` | `Employee_CheckinTime_Attendance_Log` (ผ่าน `CheckinAttendanceLogModel`) — ยังไม่มีระบบกะ/วันหยุด/ใบลามาต่อ จึงคืนค่า null หลายวัน |

## Time Fix (`TimeFixController`)

| Endpoint | ตาราง/แหล่งข้อมูล |
|---|---|
| `GET /time-fix/requests` | `Employee_Attendance_TimeFix_Request` |
| `GET /time-fix/requests/{id}/attachments/{id}` | `Employee_Attendance_TimeFix_Request` (คอลัมน์ `attachments_json`) + ไฟล์จริงใน `storage/timefix/` |
| `POST /time-fix/requests` | `Employee_Attendance_TimeFix_Request`, อ่าน `Employee_CheckinTime_Attendance_Log` ประกอบ |
| `POST /time-fix/requests/{id}/cancel` | `Employee_Attendance_TimeFix_Request` |

## Leave (`LeaveController`)

| Endpoint | สถานะ |
|---|---|
| `GET /leave/balances` | **Skeleton** — ไม่มีตารางจริง มี TODO "ต่อกับ Model การลาเดิม" |
| `GET /leave/requests` | **Skeleton** |
| `POST /leave/requests` | ตอบ `501 NOT_IMPLEMENTED` ตรงๆ |
| `POST /leave/requests/{id}/cancel` | ตอบ `501 NOT_IMPLEMENTED` ตรงๆ |

## Location (`LocationController`)

| Endpoint | ตาราง |
|---|---|
| `GET /locations` | `Master_Dempc_Location` (ผ่าน `DempcLocationModel`) — GET ใช้งานได้จริง แต่ยังไม่ต่อเข้ากับ geofence ตอน check-in จริง (ตั้งใจปล่อยฟรีไว้ก่อน — `VITE_ENFORCE_GEOFENCE=false`) |

## Notifications (`NotificationController`)

| Endpoint | ตาราง |
|---|---|
| `GET /notifications` | `Employee_CheckinTime_Notification` |
| `POST /notifications/read-all` | `Employee_CheckinTime_Notification` |
| `POST /notifications/{id}/read` | `Employee_CheckinTime_Notification` |

## Requests summary (`RequestsController`)

| Endpoint | ตาราง |
|---|---|
| `GET /requests/status-summary` | นับจาก `Employee_Attendance_TimeFix_Request` เท่านั้น (leave เป็น skeleton เลยไม่มีให้นับ) |

## Simulation (`SimulationController`) — เครื่องมือ dev/test

| Endpoint | ตาราง/แหล่งข้อมูล |
|---|---|
| `POST /simulate/check-in-log` | ไม่แตะฐานข้อมูล — เขียน log ลงไฟล์ `storage/simulated_attendance_log.json` ตรงๆ |

## ตารางทั้งหมดที่ระบบนี้แตะ (ฐาน MySQL `checkin` ฐานเดียว)

**เขียน/อ่าน (ของ CheckInTime เอง):**
`Employee_Checkin_Account`, `Employee_Checkin_Password_Reset`, `Employee_Checkin_Consent`,
`Employee_CheckinTime_Login_Log`, `Employee_CheckinTime_Attendance_Log`,
`Employee_CheckinTime_Notification`, `Employee_Attendance_TimeFix_Request`, `Master_Dempc_Location`

**เขียนบางคอลัมน์ (ตารางร่วมกับระบบ Dempc เดิม):**
`Employee_Dempc_Roster` (เขียนเฉพาะคอลัมน์ self-service: `email`, `address`, `emergency_*`,
`profile_photo_path` — ไม่แตะคอลัมน์ที่ HR import มา)

**อ่านอย่างเดียว (legacy, ของระบบ Dempc เดิม):**
`Employee_Dempc_TimeAttendance`, `Employee_User_Permissions`

**อ่านอย่างเดียว (จำลองฐาน HR กลางเดิม — ตอนนี้อยู่ในฐาน `checkin` เดียวกัน ไม่ใช่คนละเซิร์ฟเวอร์แล้ว):**
`employeesNew`, `DeptSub`, `DepartmentSub`, `positions`

**ไฟล์ (ไม่ใช่ตาราง DB, อยู่ที่ root โปรเจกต์):**
`storage/photos/` (รูป check-in/check-out), `storage/profile-photos/` (รูปโปรไฟล์),
`storage/timefix/` (ไฟล์แนบคำขอลงเวลาย้อนหลัง), `storage/simulated_attendance_log.json` (log เครื่องมือ dev)

**ยังไม่มีตารางจริง (รอทำ):** ระบบลางาน (Leave)

**ตัดออกจากระบบแล้ว:** ฟีเจอร์ "เบิกเงินล่วงหน้า" (advance request) — ไม่มีแผนทำแล้ว ไม่มีแม้แต่ skeleton

## ของที่ยังค้างอยู่ (ไม่เกี่ยวกับตารางโดยตรง แต่กระทบการใช้งานจริง)

- **ข้อมูลพนักงานจาก HR กลาง** — ตอนนี้มีแค่พนักงานที่ import มือเข้า `employeesNew`/`Employee_Dempc_Roster`
  เท่านั้น (ดู [`file/`](../file/) และ [`database/schema.mysql.sql`](../database/schema.mysql.sql))
  ยังไม่มีทาง sync ข้อมูลพนักงานทั้งบริษัทอัตโนมัติ ต้องตัดสินใจก่อนว่าจะ: (1) ต่อข้ามเครือข่ายไปหา hrtime
  จริงตรงๆ (2) sync ข้อมูลเป็นระยะ หรือ (3) ให้เว็บหลัก (GBG-MANAGEMENT) เปิด API ให้เรียก
- **Google login** — ยังไม่เชื่อมต่อ
- **Apache/production deploy** — ยังทดสอบแค่ dev (`localhost`, Apache พอร์ต 80 + Vite dev proxy) ยังไม่มี
  vhost/nginx config สำหรับ production จริง
