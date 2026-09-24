# CheckInTime — Frontend (ฝั่งพนักงาน)

แอปลงเวลาเข้า-ออกงานสำหรับพนักงาน ออกแบบสำหรับมือถือ (บนจอใหญ่จะแสดงเป็นคอลัมน์กลางจอ)

**Stack:** Vue 3 + Vite + Pinia + Vue Router + Vuetify + Tailwind CSS 4 + Axios

## เริ่มใช้งาน

```bash
npm install
npm run dev      # http://localhost:5173
npm run build
```

โหมด mock (ค่าเริ่มต้น) เก็บข้อมูลใน localStorage ใช้ทดลองได้ทันทีโดยไม่ต้องมี backend

- บัญชีทดลอง: `EMP-2569001` / `123456`
- ล้างข้อมูลทดลอง: ลบ localStorage key `cit_mock_db`

## ตั้งค่า (.env)

| ตัวแปร | ความหมาย |
| --- | --- |
| `VITE_API_BASE_URL` | base URL ของ API (ค่าเริ่มต้น `/api` และ dev server จะ proxy ไป `http://localhost:3000`) |
| `VITE_USE_MOCK` | `true` = ใช้ mock, `false` = เรียก backend จริง |
| `VITE_ENFORCE_GEOFENCE` | `true` = ลงเวลาได้เฉพาะในพื้นที่ที่กำหนด |
| `VITE_OFFICE_LAT` / `VITE_OFFICE_LNG` / `VITE_OFFICE_RADIUS` | พิกัดสำนักงานและรัศมี (เมตร) |

## โครงสร้าง

```
src/
├── main.js, App.vue          จุดเริ่มต้น / เลือก layout ตาม route.meta.layout
├── api/
│   ├── axiosClient.js        axios กลาง (แนบ token, จัดการ 401, แปลง error)
│   ├── services/             authService, attendanceService, leaveService, requestService
│   └── mock/mockServer.js    mock API (สลับด้วย VITE_USE_MOCK)
├── assets/css/               tailwind.css, main.css
├── plugins/                  pinia.js, vuetify.js
├── router/                   index.js (guard), routes.js (รายการ route)
├── store/modules/            appStore (toast), authStore, attendanceStore, leaveStore
├── composables/              useAuth, useNotification, useGeolocation, useGeofence, useNow
├── utils/                    constants, formatters, dates, validators, geo
├── layouts/                  AuthLayout, MainLayout (header + bottom nav), BlankLayout
├── components/
│   ├── common/               AppButton, ConfirmModal, AppBottomSheet, DateField,
│   │                         MonthSwitcher, SegmentedTabs, StatusBadge
│   ├── feedback/             LoadingSpinner, ToastNotification, EmptyState
│   └── layout/               AppHeader, AppBottomNav, PageHeader
└── modules/
    ├── auth/                 LoginForm | LoginView
    ├── home/                 CheckInCard, AttendanceStats, RequestStatusList | HomeView
    ├── history/              HistorySummary, HistoryItem, HistoryDetailSheet | HistoryView
    ├── leave/                LeaveBalanceList, LeaveRequestItem, LeaveRequestForm,
    │                         LeaveDetailSheet | LeaveView
    ├── profile/              ProfileCard, MenuGroup | ProfileView (แท็บ "ฉัน")
    ├── payslip/              PayslipView   (โครง รอพัฒนา, เข้าจากแท็บ "ฉัน")
    └── system/               NotFoundView
```

## สถานะการ์ดลงเวลา (หน้าหลัก)

| สถานะ | สี | ปุ่ม |
| --- | --- | --- |
| `ready` ยังไม่เข้างาน | เขียว | กดเข้างาน |
| `working` เข้างานแล้ว | ส้ม | กดออกงาน (มีหน้ายืนยัน) |
| `done` ลงเวลาครบแล้ว | เทาเข้ม | เช็คอินครบแล้วสำหรับวันนี้ |
| `out_of_area` นอกพื้นที่ | แดงส้ม | อยู่นอกพื้นที่ (กดไม่ได้) |

## API ที่ frontend คาดหวัง (เมื่อ VITE_USE_MOCK=false)

| Method | Path | ใช้ทำอะไร |
| --- | --- | --- |
| POST | `/auth/login` `{ username, password }` | คืน `{ token, user }` |
| GET | `/auth/me` | ข้อมูลผู้ใช้ |
| POST | `/auth/logout` | ออกจากระบบ |
| GET | `/attendance/today` | รายการวันนี้ หรือ `null` |
| POST | `/attendance/check-in` `{ location }` | เข้างาน |
| POST | `/attendance/check-out` `{ location }` | ออกงาน |
| GET | `/attendance/summary?month=YYYY-MM` | `{ leaveRemainingDays, lateCount, lateMinutes, otMinutes }` |
| GET | `/attendance/history?month=YYYY-MM` | ประวัติ |
| GET | `/leave/balances` | `[{ type, quota, used, pending, remaining }]` |
| GET | `/leave/requests` | รายการคำขอลา |
| POST | `/leave/requests` `{ leaveType, startDate, endDate, period, reason }` | ยื่นใบลา |
| POST | `/leave/requests/:id/cancel` | ยกเลิกคำขอที่รออนุมัติ |
| GET | `/requests/status-summary` | `[{ type: 'leave' \| 'advance', pendingCount }]` |

## Design tokens (PromptChex Design Master v1.0.0)

กำหนดไว้ที่ `src/assets/css/tailwind.css` (`@theme`) ใช้เป็น class ของ Tailwind ได้ทันที

| Token | ค่า | ตัวอย่าง class |
| --- | --- | --- |
| status-checkin | #10B981 | `bg-status-checkin` |
| status-checkout | #F59E0B | `bg-status-checkout` |
| status-outside | #F95738 | `bg-status-outside` |
| status-done | #334155 | `bg-status-done` |
| app-bg / card | #F8FAFF / #FFFFFF | `bg-app-bg`, `bg-card` |
| ink / ink-muted | #0F172A / #64748B | `text-ink`, `text-ink-muted` |
| metric-blue / metric-cyan | #3B82F6 / #06B6D4 | `text-metric-blue` |
| font-sans / font-display | Prompt / Plus Jakarta Sans | `font-display` (เวลา ตัวเลขสถิติ) |

Radii: การ์ดหลักและการ์ดสถิติ `rounded-[28px]`, ปุ่มและรายการ `rounded-2xl`, ไอคอนและรูปโปรไฟล์ `rounded-full`
