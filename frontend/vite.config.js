import { fileURLToPath, URL } from 'node:url'
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vuetify from 'vite-plugin-vuetify'
import tailwindcss from '@tailwindcss/vite'
import basicSsl from '@vitejs/plugin-basic-ssl'
import { VitePWA } from 'vite-plugin-pwa'

export default defineConfig(({ command }) => ({
  // ตอน dev (`vite dev`) ต้องเป็น '/' เสมอ เพราะ dev server เสิร์ฟที่ root ของ localhost:5173 ตรงๆ — ตอน
  // build (`vite build`) ต้องตั้งให้ตรงกับ path จริงบน prod ที่จะวางไฟล์ static ไว้ (nginx: alias
  // /var/www/intra/GBG-MANAGEMENT/checkin/frontend/dist/ ที่ /GBG-MANAGEMENT/checkin/) ไม่งั้น asset
  // (JS/CSS/รูป) จะอ้าง path ผิดเป็น root ("/assets/...") แทนที่จะเป็น ("/GBG-MANAGEMENT/checkin/assets/...")
  // router.js ก็อ่านค่านี้ต่อเองผ่าน import.meta.env.BASE_URL (createWebHistory(import.meta.env.BASE_URL))
  // ไม่ต้องตั้งซ้ำที่อื่น
  base: command === 'build' ? '/checkin/' : '/',
  // basicSsl ออก cert self-signed อัตโนมัติ (เก็บไว้ใน node_modules/.vite-plugin-basic-ssl) ให้ dev server
  // เป็น HTTPS — ต้องมีเพื่อให้ getUserMedia() (เปิดกล้องถ่ายยืนยันตัวตน) ใช้ได้ตอนเข้าผ่าน IP วง LAN จากมือถือ
  // (เบราว์เซอร์มือถือถือว่า http://<LAN-IP> ไม่ใช่ secure context เลยบล็อกกล้อง มีแค่ localhost/https เท่านั้น
  // ที่ผ่าน) เปิดครั้งแรกบนมือถือเบราว์เซอร์จะเตือน "ใบรับรองไม่น่าเชื่อถือ" ให้กด "ดำเนินการต่อ/Advanced"
  // ผ่านไปได้เลย เป็นเรื่องปกติของ self-signed cert ไม่ใช่ปัญหาจริง
  plugins: [
    vue(),
    vuetify({ autoImport: true }),
    tailwindcss(),
    basicSsl(),
    // PWA — ทำให้ "เพิ่มลงหน้าจอหลัก" บนมือถือได้ เปิดแบบเต็มจอเหมือนแอปจริง โดยไม่ต้องเขียนแอปแยก ใช้โค้ด
    // Vue เดิมทั้งหมด — precache เฉพาะไฟล์ static (JS/CSS/รูป/ไอคอน) เท่านั้น "ไม่" cache response จาก
    // /api/checkin หรือ /checkin/backend/ เด็ดขาด เพราะเป็นข้อมูลลงเวลา/ส่วนตัวที่เปลี่ยนตลอดเวลาและต้องมี
    // JWT ถึงจะเห็น ถ้า cache ไว้เสี่ยงโชว์ข้อมูลเก่า/ข้อมูลคนอื่นที่เคย login ไว้ก่อนในเครื่องเดียวกัน
    VitePWA({
      registerType: 'autoUpdate', // อัปเดต service worker เองตอนมีเวอร์ชันใหม่ ไม่ต้องให้ผู้ใช้ถอนแอปเก่าออกก่อน
      includeAssets: ['favicon.svg', 'LogoTimeCheck.png'],
      manifest: {
        name: 'CheckInTime',
        short_name: 'CheckInTime',
        description: 'ระบบลงเวลาทำงานของพนักงาน',
        // base ตอน build เป็น '/checkin/' เสมอ (ดูค่า base ด้านบน) — start_url/scope ต้องตรงกัน ไม่งั้นเปิด
        // จากหน้าจอหลักแล้วหลุด scope ของ service worker
        start_url: '/checkin/',
        scope: '/checkin/',
        display: 'standalone', // เปิดเต็มจอ ไม่มี address bar เหมือนแอปจริง
        background_color: '#fff7ed',
        theme_color: '#f97316',
        orientation: 'portrait',
        lang: 'th',
        icons: [
          { src: 'pwa-192.png', sizes: '192x192', type: 'image/png' },
          { src: 'pwa-512.png', sizes: '512x512', type: 'image/png' },
          { src: 'pwa-maskable-512.png', sizes: '512x512', type: 'image/png', purpose: 'maskable' },
        ],
      },
      workbox: {
        // precache เฉพาะไฟล์ build ของหน้าเว็บเอง — ไม่ตั้ง runtimeCaching ให้ /api/checkin หรือ
        // /checkin/backend/ เลย (ดู comment บนสุดของ VitePWA() นี้)
        globPatterns: ['**/*.{js,css,html,svg,png,ico}'],
      },
    }),
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  server: {
    port: 5173,
    // host: true = bind 0.0.0.0 แทน localhost อย่างเดียว ให้เครื่องอื่นในวง LAN เดียวกัน (เช่นมือถือ) เข้าผ่าน
    // IP เครื่องนี้ได้ (เช่น https://192.168.2.208:5173) ไว้ทดสอบ UI จริงบนมือถือ
    host: true,
    proxy: {
      // ส่งต่อ /api/checkin ไปยัง backend จริง (ใช้เรียก backend จริง) — สำคัญกว่าแค่ความสะดวก: ตอนนี้
      // หน้าเว็บเป็น HTTPS (จาก basicSsl ข้างบน) แต่ backend จริง (XAMPP/Apache) ยังเป็น HTTP ธรรมดา ถ้า axios
      // ยิงตรงไป http://... จากหน้า HTTPS เบราว์เซอร์จะบล็อกเป็น "mixed content" — ส่งผ่าน proxy นี้แทน ตัว
      // request จริงไป backend เกิดขึ้นฝั่ง Vite (node process คุยกับ Apache เอง) ไม่ใช่จากเบราว์เซอร์โดยตรง
      // เลยไม่ติด mixed-content/CORS เลย ไม่ว่าจะเข้าเว็บผ่าน localhost หรือ IP วง LAN จากมือถือก็ตาม
      '/api/checkin': {
        target: 'http://localhost', // Apache ของ XAMPP ฟังพอร์ต 80 ปกติ (ตั้งใจใช้ตรงนี้ ไม่ตั้ง vhost แยก)
        changeOrigin: true,
        // xfwd: true ให้ proxy แปะ header X-Forwarded-For (IP จริงของเบราว์เซอร์ที่ยิงเข้ามา — มือถือ ไม่ใช่
        // เครื่อง dev) ติดไปกับ request ที่ส่งต่อให้ Apache ด้วย — ฝั่ง backend อ่านผ่าน
        // AbstractApiController::clientIp() (เช็ค X-Forwarded-For ก่อน REMOTE_ADDR) ถึงจะได้ IP มือถือจริง
        // ไม่ใช่ IP เครื่อง dev เอง
        xfwd: true,
        rewrite: (path) => path.replace(/^\/api\/checkin/, '/checkin/backend/router.php'),
      },
    },
  },
}))
