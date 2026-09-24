# CheckInTime

ระบบลงเวลาเข้า-ออกงานสำหรับพนักงาน (Mobile Web — เปิดผ่าน LINE ได้ในอนาคต)

**ลองใช้งาน:** https://phumthanakorn.github.io/checkintime/ (บัญชีทดลอง `EMP-2569001` / `123456`)

| โฟลเดอร์ | รายละเอียด |
| --- | --- |
| [`frontend/`](frontend/) | Vue 3 + Vite + Pinia + Vuetify + Tailwind CSS 4 — ดูวิธีรันใน [frontend/README.md](frontend/README.md) |

## เริ่มต้นใช้งาน

```bash
cd frontend
npm install
npm run dev
```

เปิด http://localhost:5173 แล้วเข้าสู่ระบบด้วยบัญชีทดลอง `EMP-2569001` / `123456`

## Deploy

ทุกครั้งที่ push เข้า `main` GitHub Actions ([.github/workflows/deploy-pages.yml](.github/workflows/deploy-pages.yml)) จะ build `frontend/` แล้ว deploy ขึ้น GitHub Pages อัตโนมัติ
