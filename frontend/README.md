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

- บัญชีทดลอง: `EMP-2569001` / `123456` (OTP ลืมรหัสผ่าน: `123456`)
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
│   │                         MonthSwitcher, SegmentedTabs, StatusBadge, AppIcon, AppDatePicker,
│   │                         DateRangeField, AppTimePicker, TimeField, AttachmentField, AttachmentList,
│   │                         TextField, PasswordField, PinPad, OtpInput, ToggleSwitch
│   ├── feedback/             LoadingDots, LoadingState, ToastNotification, EmptyState
│   └── layout/               AppHeader, AppBottomNav, PageHeader
└── modules/
    ├── auth/                 LoginForm | LoginView, ForgotPasswordView, PinLoginView
    ├── home/                 CheckInCard, AttendanceStats, RequestStatusList | HomeView
    ├── history/              HistorySummary, HistoryItem, HistoryDetailSheet | HistoryView
    ├── onboarding/           ConsentStep, LocationStep | OnboardingView (ยินยอม PDPA + ขอตำแหน่ง)
    ├── notifications/        NotificationItem | NotificationsView
    ├── time-fix/             TimeFixForm, TimeFixItem, TimeFixDetailSheet | TimeFixView
    ├── leave/                LeaveBalanceList, LeaveRequestItem, LeaveRequestForm,
    │                         LeaveDetailSheet | LeaveView
    ├── profile/              ProfileCard, MenuGroup | ProfileView (แท็บ "ฉัน"), PersonalInfoView,
    │                         ChangePasswordView, PinSettingsView, SettingsView, PrivacyView
    ├── payslip/              NetPayCard, PayslipSection | PayslipView (เข้าจากแท็บ "ฉัน")
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
| POST | `/auth/login/pin` `{ employeeCode, pin }` | เข้าสู่ระบบด้วย PIN |
| POST | `/auth/password/forgot` `{ username }` | ส่ง OTP → `{ maskedPhone, refCode }` |
| POST | `/auth/password/verify-otp` `{ username, otp }` | ยืนยัน OTP → `{ resetToken }` |
| POST | `/auth/password/reset` `{ resetToken, newPassword }` | ตั้งรหัสผ่านใหม่ |
| PUT | `/me/profile` `{ phone, email, address, emergencyContact, avatarUrl }` | แก้ไขข้อมูลส่วนตัว → user |
| POST | `/me/password` `{ currentPassword, newPassword }` | เปลี่ยนรหัสผ่าน |
| POST | `/me/consents` `{ policyVersion, location }` | ยินยอม PDPA (บังคับก่อนใช้งาน) → user |
| POST / DELETE | `/me/pin` `{ pin }` | ตั้ง / ปิด PIN (backend ต้องเก็บแบบ hash) |
| GET | `/attendance/today` | รายการวันนี้ หรือ `null` |
| POST | `/attendance/check-in` `{ location }` | เข้างาน |
| POST | `/attendance/check-out` `{ location }` | ออกงาน |
| GET | `/attendance/summary?month=YYYY-MM` | `{ leaveRemainingDays, lateCount, lateMinutes, otMinutes }` |
| GET | `/attendance/history?month=YYYY-MM` | ประวัติรายวัน + วันที่ขาดลงเวลา (`missing: true`) และ `fixStatus` ของแต่ละวัน |
| GET | `/leave/balances` | `[{ type, quota, used, pending, remaining }]` |
| GET | `/leave/requests` | รายการคำขอลา |
| POST | `/leave/requests` `{ leaveType, startDate, endDate, period, reason }` | ยื่นใบลา |
| POST | `/leave/requests/:id/cancel` | ยกเลิกคำขอที่รออนุมัติ |
| GET | `/time-fix/requests` | คำขอลงเวลาย้อนหลัง |
| POST | `/time-fix/requests` `{ date, fixType, checkIn, checkOut, reason }` | ขอลงเวลาย้อนหลัง (`fixType`: check_in / check_out / both) |
| POST | `/time-fix/requests/:id/cancel` | ยกเลิกคำขอที่รออนุมัติ |
| GET | `/payslips` | เดือนที่สลิปออกแล้ว `[{ month, netPay }]` |
| GET | `/payslips/:month` | รายละเอียดสลิป (รายได้, รายการหัก, ยอดสุทธิ, สรุปเวลาทำงาน) — 404 ถ้ายังไม่ออก |
| GET | `/notifications` | การแจ้งเตือน `[{ id, type, title, body, createdAt, read, link }]` |
| POST | `/notifications/:id/read` | อ่านแล้ว |
| POST | `/notifications/read-all` | อ่านทั้งหมด |
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

## ไอคอน

ใช้ [Phosphor Icons](https://phosphoricons.com) ผ่าน component กลาง `AppIcon` (ลงทะเบียนไว้ทั้งแอป ไม่ต้อง import)

```vue
<AppIcon name="calendar" :size="20" weight="duotone" class="text-metric-blue" />
```

| weight | ใช้ตรงไหน |
| --- | --- |
| `regular` (ค่าเริ่มต้น) | ไอคอนทั่วไป, แท็บที่ไม่ได้เลือก |
| `fill` | แท็บที่เลือกอยู่, สถานะที่ต้องการเน้น |
| `duotone` | ไอคอนในวงกลม/กล่องสีอ่อน (สถิติ, ประเภทการลา, เมนู) |
| `bold` | ไอคอนในปุ่มหลักของการ์ดลงเวลา |

เพิ่มไอคอนใหม่: ค้นชื่อที่ phosphoricons.com แล้ว import + ใส่ชื่อสั้นใน `ICONS` ของ [src/components/common/AppIcon.vue](src/components/common/AppIcon.vue)
