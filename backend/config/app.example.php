<?php
// ค่าตั้งของแอปที่ "ต่างกันตามสภาพแวดล้อม" (dev / production) — แก้ที่ไฟล์นี้ที่เดียว ไม่ต้องไล่แก้ในโค้ด
// ไฟล์นี้ถูก gitignore (เหมือน db.php/jwt.php/mail.php) แต่ละเครื่องมีของตัวเอง — ต้นแบบอยู่ที่ app.example.php
//
// ===== เมื่อได้โดเมน production จริงแล้ว ให้ทำตามนี้ =====
//   1) แก้ 'frontend_origins' ด้านล่าง: ใส่โดเมนจริงเป็นตัวแรก (https://... ไม่มี / ท้าย) แล้วลบของ dev ออก
//      (ถ้าเข้าใช้งานได้จากหลายโดเมน ใส่ได้หลายตัว)
//   2) frontend: แก้ VITE_API_BASE_URL ใน frontend/.env.production และ base ใน frontend/vite.config.js
//      ให้ตรง path จริงบนเซิร์ฟเวอร์ แล้ว npm run build ใหม่
//   3) สร้าง config/db.php, jwt.php (secret ใหม่ ไม่ใช้ของ dev), mail.php บนเครื่อง production เอง
//   4) รายการอื่นก่อนเปิดใช้งานจริง ดู docs/production-checklist.md
return [
    // โดเมนของหน้าเว็บ (frontend) ที่อนุญาต ใช้ 2 จุดพร้อมกัน:
    //   - CORS: เบราว์เซอร์จากโดเมนเหล่านี้เท่านั้นที่เรียก API ได้ (backend/router.php)
    //   - ลิงก์ในอีเมลรีเซ็ตรหัสผ่าน: ใช้ Origin ที่ตรงในรายการ ไม่ตรงจะใช้ตัวแรกสุด (AuthController)
    // **ตัวแรกคือโดเมนหลักที่ลิงก์อีเมลจะชี้ไป**
    'frontend_origins' => [
        // --- dev (ลบทิ้งตอนขึ้น production) ---
        'https://localhost:5173',
        'https://127.0.0.1:5173',
        'https://localhost:4173',
        'https://192.168.2.208:5173',   // IP เครื่อง dev ในวง LAN (ทดสอบจากมือถือ)
        'https://192.168.2.208:4173',
        // --- production (ยังไม่รู้โดเมน: ใส่ที่นี่ แล้วย้ายให้อยู่บนสุดของรายการ) ---
        // 'https://checkin.example.com',
    ],

    // Client ID ของ "OAuth 2.0 Client" ชนิด Web application ที่สร้างใน Google Cloud Console
    // (console.cloud.google.com -> APIs & Services -> Credentials) — ต้องเพิ่มทุกโดเมนใน frontend_origins
    // ด้านบนเป็น "Authorized JavaScript origins" ของ client นี้ด้วย ไม่งั้นปุ่ม Google จะ error
    // ค่านี้เป็น public id (ไม่ใช่ secret) ฝัง frontend ได้ปกติ แต่เก็บไว้ในนี้ที่เดียวกันกับโดเมนเพราะผูกกับ
    // environment เดียวกัน (dev/prod คนละ client ได้ ถ้าอยากแยก authorized origins ให้ชัดเจน)
    // ยังไม่ได้สร้าง client จริง — Google login endpoint (/auth/google) จะปิดใช้งานเองถ้าค่านี้ว่าง
    'google_client_id' => '',
];
