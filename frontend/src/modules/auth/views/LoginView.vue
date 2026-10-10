<template>
  <!-- หัวหน้า: สลับข้อความตามโหมด (login / ตั้งรหัสผ่านครั้งแรก) พร้อม fade ให้รู้ว่าหน้าเปลี่ยนแล้ว -->
  <Transition name="head" mode="out-in">
    <div :key="needsSetupEmpcode ? 'setup' : 'login'" class="flex flex-col items-center">
      <div
        class="mb-5 flex h-24 w-24 items-center justify-center bg-white"
        :class="{ 'logo-hop': needsSetupEmpcode }"
        style="border-radius:22px; box-shadow:0 8px 24px rgba(15,23,42,0.08)"
      >
        <img :src="logoUrl" alt="CheckInTime" class="object-contain" width="80" height="80" style="width:80px;height:80px" />
      </div>
      <h1 class="text-2xl font-bold text-ink">{{ needsSetupEmpcode ? 'ตั้งรหัสผ่านใหม่' : 'เข้าสู่ระบบ' }}</h1>
      <p class="mt-1 text-[13px] text-ink-muted">
        {{ needsSetupEmpcode ? 'ขั้นตอนสุดท้ายก่อนเริ่มใช้งาน' : 'ระบบลงเวลาทำงานของพนักงาน' }}
      </p>
    </div>
  </Transition>

  <div class="mt-6 overflow-hidden rounded-3xl bg-card p-5 shadow-sm">
    <Transition name="swap" mode="out-in">
    <!-- ตั้งรหัสผ่านครั้งแรกต้องยืนยันด้วยอีเมลที่ลงทะเบียนไว้ด้วย (กันคนรู้แค่รหัสพนักงานมายึดบัญชีคนอื่น) —
    ตอนนี้ login บังคับกรอกอีเมลเท่านั้นแล้ว (ตัด empcode-as-username ออก ดู AuthController::login()) ทุกครั้ง
    ที่เข้าถึงจุดนี้จึงมี needsSetupEmail เสมอ ไม่ต้องมีหน้า "ยังไม่มีอีเมล" แยกอีกแล้ว -->
    <SetupPasswordForm
      v-if="needsSetupEmpcode"
      key="setup"
      :empcode="needsSetupEmpcode"
      :loading="loading"
      :done="setupDone"
      @submit="handleSetupPassword"
      @back="resetToLogin"
    />
    <div v-else key="login">
      <LoginForm
        :loading="loading"
        :initial-username="rememberedUsername"
        @submit="handleLogin"
        @forgot="router.push({ name: 'forgot-password' })"
      />

      <div class="my-5 flex items-center gap-3 text-xs text-slate-400">
        <span class="h-px flex-1 bg-slate-200" />
        หรือ
        <span class="h-px flex-1 bg-slate-200" />
      </div>

      <!-- ปุ่มสไตล์ของเราเอง มองเห็น แต่กดไม่ได้ตรงๆ (pointer-events:none) — ปุ่มจริงของ Google อยู่ใน
      overlay ด้านบนแบบโปร่งใส คลิกตรงไหนก็ไปโดนปุ่มจริงเสมอ (ดู useGoogleSignIn.js) -->
      <div ref="googleBtnWrap" class="relative h-10 w-full">
        <button class="google-btn pointer-events-none absolute inset-0 w-full" type="button" tabindex="-1">
          <svg class="google-icon" width="18" height="18" viewBox="0 0 48 48" aria-hidden="true">
            <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z" />
            <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z" />
            <path fill="#FBBC05" d="M10.53 28.59A14.4 14.4 0 0 1 9.75 24c0-1.59.27-3.13.78-4.59l-7.98-6.19A23.9 23.9 0 0 0 0 24c0 3.87.93 7.53 2.56 10.78l7.97-6.19z" />
            <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.91-5.8l-7.73-6c-2.15 1.45-4.92 2.3-8.18 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z" />
          </svg>
          <span>ลงชื่อเข้าใช้ด้วย Google</span>
        </button>
        <div ref="googleOverlay" class="absolute inset-0 overflow-hidden opacity-0" aria-hidden="true" />
      </div>
    </div>
    </Transition>
  </div>

  <div
    class="mt-6 flex items-center justify-center gap-2 rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-2 text-center text-xs text-emerald-700"
  >
    <AppIcon name="shield-check" :size="16" />
    ระบบปลอดภัยตามมาตรฐานความปลอดภัยข้อมูลพนักงาน (PDPA)
  </div>

  <p class="mt-4 text-center text-xs text-slate-500">
    พบปัญหาการเข้าสู่ระบบ? ติดต่อฝ่ายบุคคล (HR)
    <span class="font-semibold text-slate-800">{{ HR_CONTACT_PHONE }}</span>
  </p>
  <p class="mt-4 text-center text-xs text-slate-400">{{ APP_NAME }} v{{ APP_VERSION }}</p>

</template>

<script setup>
const logoUrl = import.meta.env.BASE_URL + 'LogoTimeCheck.png'
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import LoginForm from '../components/LoginForm.vue'
import SetupPasswordForm from '../components/SetupPasswordForm.vue'
import { useAuth } from '@/composables/useAuth'
import { useGoogleSignIn } from '@/composables/useGoogleSignIn'
import { useNotification } from '@/composables/useNotification'
import { APP_NAME, APP_VERSION, HR_CONTACT_PHONE, STORAGE_KEYS } from '@/utils/constants'

const { login, googleLogin, completeSetup } = useAuth()
const notify = useNotification()
const router = useRouter()
const { error: googleError, mount: mountGoogleButton } = useGoogleSignIn()

const loading = ref(false)
const rememberedUsername = localStorage.getItem(STORAGE_KEYS.REMEMBER_USERNAME) || ''
// ไม่ว่างเมื่อไหร่ = เข้าสู่ระบบครั้งแรก (ยังไม่เคยตั้งรหัสผ่าน หรือ HR รีเซ็ตให้) สลับไปโชว์
// SetupPasswordForm แทน LoginForm โดยไม่ต้องเปลี่ยนหน้า (ยังอยู่ใน card เดิม)
const needsSetupEmpcode = ref('')
// อีเมลที่ resolve มาจาก login ด้วยอีเมล (ถ้า login ด้วยรหัสพนักงานเฉยๆ จะว่าง) — ส่งไปยืนยันตัวตนอีกชั้นตอน
// ตั้งรหัสผ่านครั้งแรก ไม่ต้องให้พิมพ์ซ้ำ เพราะ backend ตรวจตอน resolve ไปแล้ว (ดู AuthController::login())
const needsSetupEmail = ref('')
// true หลังตั้งรหัสผ่านสำเร็จ — ให้ SetupPasswordForm โชว์หน้า "สำเร็จ" สั้นๆ ก่อนพาเข้าระบบ
const setupDone = ref(false)
const SETUP_SUCCESS_DELAY_MS = 1100

async function handleLogin({ username, password, remember }) {
  loading.value = true
  try {
    const result = await login({ username, password })
    if (result?.needsSetup) {
      needsSetupEmpcode.value = result.empcode
      needsSetupEmail.value = result.email || ''
      return
    }
    if (remember) localStorage.setItem(STORAGE_KEYS.REMEMBER_USERNAME, username)
    else localStorage.removeItem(STORAGE_KEYS.REMEMBER_USERNAME)
  } catch (error) {
    notify.error(error.message)
  } finally {
    loading.value = false
  }
}

async function handleSetupPassword({ newPassword }) {
  loading.value = true
  try {
    await completeSetup(
      {
        empcode: needsSetupEmpcode.value,
        email: needsSetupEmail.value,
        password: needsSetupEmpcode.value,
        newPassword,
      },
      {
        onSuccess: async () => {
          setupDone.value = true
          await new Promise((resolve) => setTimeout(resolve, SETUP_SUCCESS_DELAY_MS))
        },
      },
    )
  } catch (error) {
    notify.error(error.message)
  } finally {
    loading.value = false
  }
}

function resetToLogin() {
  needsSetupEmpcode.value = ''
  needsSetupEmail.value = ''
}

const googleOverlay = ref(null)
const googleBtnWrap = ref(null)

onMounted(() => {
  // ปุ่ม Google ไม่โชว์ตอนอยู่หน้า "ตั้งรหัสผ่านใหม่" (needsSetupEmpcode) เพราะ el ยังไม่ mount — ไม่เป็นไร
  // เพราะ v-if สลับไปแสดง LoginForm ก่อนอยู่แล้วตอนเปิดหน้านี้ครั้งแรก (googleOverlay ว่างแค่ตอนโหมด setup)
  if (!googleOverlay.value || needsSetupEmpcode.value) return
  mountGoogleButton(googleOverlay.value, handleGoogleCredential)
})

async function handleGoogleCredential(credential) {
  loading.value = true
  try {
    const result = await googleLogin(credential)
    if (result?.needsSetup) {
      needsSetupEmpcode.value = result.empcode
      needsSetupEmail.value = result.email || ''
    }
  } catch (error) {
    notify.error(error.message)
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
/* สลับระหว่างฟอร์ม login กับฟอร์มตั้งรหัสผ่าน: ออกซ้าย เข้าขวา */
.swap-enter-active, .swap-leave-active { transition: opacity 0.26s ease, transform 0.26s ease; }
.swap-enter-from { opacity: 0; transform: translateX(28px); }
.swap-leave-to { opacity: 0; transform: translateX(-28px); }

.head-enter-active, .head-leave-active { transition: opacity 0.2s ease, transform 0.2s ease; }
.head-enter-from { opacity: 0; transform: translateY(-8px); }
.head-leave-to { opacity: 0; transform: translateY(8px); }

.logo-hop { animation: logo-hop 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) 0.15s both; }

@keyframes logo-hop {
  0% { transform: scale(1); }
  40% { transform: scale(1.12) rotate(-4deg); }
  100% { transform: scale(1) rotate(0); }
}

@media (prefers-reduced-motion: reduce) {
  .swap-enter-active, .swap-leave-active, .head-enter-active, .head-leave-active { transition: none; }
  .logo-hop { animation: none; }
}

.google-btn {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  height: 40px;
  padding: 0 42px;
  border: 1px solid #d5deed;
  border-radius: 4px;
  background: #f8fbff;
  font-size: 14px;
  font-weight: 400;
  color: #183153;
  transition: background 0.15s;
}

.google-icon {
  position: absolute;
  left: 14px;
}

.google-btn:hover {
  background: #edf4ff;
}

.google-btn:focus-visible {
  outline: 2px solid #4285f4;
  outline-offset: 2px;
}
</style>
