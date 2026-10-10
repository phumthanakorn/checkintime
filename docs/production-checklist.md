# Production checklist

สิ่งที่ต้องทำ/แก้ตอนขึ้น production จริง รวมไว้ที่เดียว (ตอนนี้ยังไม่มีโดเมนที่ชัดเจน — ทำเมื่อได้ข้อมูลแล้ว)

## ข้อมูลที่ต้องรู้ก่อน (ยังไม่ทราบ)

- [ ] โดเมนจริง เช่น `https://checkin.<บริษัท>.com`
- [ ] เซิร์ฟเวอร์: Apache หรือ nginx / Linux หรือ Windows (ของเดิมบน prod เป็น nginx)
- [ ] ฐาน MySQL บน prod อยู่เครื่องเดียวกับเว็บ หรือแยกเครื่อง
- [ ] path ที่จะวางแอป (root โดเมน หรือใต้ path เช่น `/checkin/`)

## จุดที่ต้องแก้ (โดเมนผูกอยู่ที่นี่)

| ไฟล์ | แก้อะไร |
|---|---|
| `backend/config/app.php` | **`frontend_origins`** ใส่โดเมนจริงเป็นตัวแรก ลบของ dev (localhost / 192.168.2.208) ออก — ใช้ทั้ง CORS และลิงก์ในอีเมลรีเซ็ตรหัสผ่าน (ที่เดียวพอ) |
| `frontend/.env.production` | `VITE_API_BASE_URL` ให้ตรง path ของ API บน prod |
| `frontend/vite.config.js` | `base` ตอน build (ตอนนี้ `/checkin/`) ถ้าวางที่ root โดเมนให้เป็น `/` |
| `.htaccess` (ถ้าใช้ Apache) | `RewriteBase` ให้ตรง path จริง · ถ้าใช้ nginx ใช้ `try_files $uri /index.html` แทน |
| `frontend/.env.production` | ค่า geofence `VITE_OFFICE_*` ตอนนี้เป็นค่า placeholder — ตั้งพิกัดสำนักงานจริงถ้าจะบังคับ geofence |
| Google Cloud Console → Credentials | เพิ่มโดเมน production เป็น **Authorized JavaScript origins** ของ OAuth Client (ตัวเดียวกับที่ใช้ dev หรือสร้างใหม่แยกก็ได้) ไม่งั้นปุ่ม "เข้าสู่ระบบด้วย Google" จะ error บน prod |

หลังแก้ฝั่ง frontend ต้อง `npm run build` ใหม่ แล้วคัดลอก `frontend/dist/*` ไปวาง

## สร้างบนเครื่อง production เอง (ไม่อยู่ใน git)

- [ ] `backend/config/db.php` — ข้อมูลเชื่อมฐานจริง (key `local_mysql`) · สร้างฐานจาก `database/schema.mysql.sql`
- [ ] `backend/config/jwt.php` — **secret ใหม่** ไม่ใช้ร่วมกับ dev
- [ ] `backend/config/mail.php` — SMTP จริง
- [ ] `backend/config/app.php` — ดูตารางด้านบน (คัดลอกจาก `app.example.php`)

## ความปลอดภัย / ความเรียบร้อย

- [ ] HTTPS จริง (กล้อง + GPS บนมือถือใช้ได้เฉพาะ HTTPS)
- [ ] `storage/` เขียนได้โดย PHP แต่ **ห้ามเข้าถึงผ่านเว็บตรง** (รูปถ่ายพนักงาน/ไฟล์แนบ — เสิร์ฟผ่าน API ที่เช็ค JWT เท่านั้น)
- [ ] `backend/config/` และ `backend/logs/` ห้ามเข้าถึงผ่านเว็บ
- [ ] ปิด endpoint เครื่องมือ dev `POST /simulate/check-in-log` (route ใน `backend/router.php`)
- [ ] ลบพนักงานทดสอบ (empcode 9999) และข้อมูลทดสอบ
- [ ] PHP: `display_errors=Off`, เปิด opcache
- [ ] MySQL: user เฉพาะแอป (ไม่ใช้ root ไม่มีรหัสผ่านแบบ dev)
- [ ] นำข้อมูลพนักงานจริงเข้า (ยังไม่ได้ทำ — ดู `docs/api-database-map.md` หัวข้อ "ของที่ยังค้างอยู่")
- [ ] ทดสอบ flow เต็มบน prod: ลืมรหัสผ่าน (ลิงก์ในอีเมลต้องชี้โดเมนจริง), check-in/out พร้อมรูป, time-fix พร้อมไฟล์แนบ, เข้าสู่ระบบด้วย Google

## ความปลอดภัยระดับเครือข่าย (เพิ่มเติมจากที่แอปกันไว้แล้ว — `/auth/login` และ `/auth/password/forgot`
เปิดสาธารณะโดยดีไซน์ เหมือนหน้า login ของเว็บทั่วไป แอปกัน brute-force/SQLi/path-traversal/ปลอมไฟล์แนบไว้
แล้วในโค้ด แต่ชั้นเครือข่ายข้างล่างนี้ยังไม่มี ควรตั้งที่ web server/CDN ไม่ต้องแก้โค้ดแอป)

- [ ] **Rate limit ระดับ IP** หน้า web server หรือผ่าน Cloudflare (ฟรี) — กันคนยิง `/auth/login` รัวๆ ด้วยอีเมลคนละตัวเป็นพันครั้ง
      (lockout ในแอปนับต่อ 1 บัญชีเท่านั้น ไม่กันการสแกนข้าม IP)
- [ ] **HSTS** (`Strict-Transport-Security` header) ให้เบราว์เซอร์บังคับ HTTPS เสมอหลังเข้าครั้งแรก
- [ ] **Security headers ระดับเว็บ** (`X-Frame-Options`, `X-Content-Type-Options`, CSP ของหน้าเว็บหลัก — ตอนนี้มี CSP
      เฉพาะตอน serve ไฟล์รูป/ไฟล์แนบเท่านั้น ไม่ใช่ทั้งเว็บ)
- [ ] **แจ้งเตือนเมื่อมีความพยายามเจาะผิดปกติ** — ตอนนี้ log อยู่ใน `Employee_CheckinTime_Login_Log` เฉยๆ ไม่มีระบบแจ้งเตือนอัตโนมัติถ้า
      FAILED เยอะผิดปกติในช่วงเวลาสั้นๆ

## ที่ตั้งใจพักไว้ (ไม่ใช่ blocker)

ระบบลางาน · shift/วันหยุดในปฏิทิน · บังคับ geofence
