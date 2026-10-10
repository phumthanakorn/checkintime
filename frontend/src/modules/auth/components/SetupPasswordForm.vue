<template>
  <form class="space-y-4" novalidate @submit.prevent="handleSubmit">
    <Transition name="fade" mode="out-in">
      <!-- สำเร็จแล้ว: โชว์ผลลัพธ์สั้นๆ ก่อนพาเข้าระบบ ไม่ให้หน้าเปลี่ยนไปเงียบๆ จนไม่รู้ว่าบันทึกสำเร็จหรือยัง -->
      <div v-if="done" key="done" class="success-panel">
        <div class="success-ring">
          <AppIcon name="check" :size="38" weight="bold" />
        </div>
        <p class="mt-4 text-lg font-bold text-ink">ตั้งรหัสผ่านสำเร็จ</p>
        <p class="mt-1 text-sm text-ink-muted">กำลังพาคุณเข้าสู่ระบบ...</p>
        <div class="success-bar"><span /></div>
      </div>

      <div v-else key="fields" class="space-y-4">
        <div class="welcome rise" style="--d: 0ms">
          <span class="welcome-icon"><AppIcon name="shield-check" :size="26" weight="duotone" /></span>
          <div class="min-w-0">
            <p class="text-sm font-bold text-ink">ยินดีต้อนรับ! ตั้งรหัสผ่านใหม่</p>
            <p class="mt-0.5 text-xs leading-snug text-ink-muted">
              รหัสพนักงาน <span class="font-semibold text-ink">{{ empcode }}</span>
              · ใช้เข้าสู่ระบบครั้งถัดไป
            </p>
          </div>
        </div>

        <div class="rise" :class="{ shake }" style="--d: 80ms">
          <label for="newPassword" class="mb-2 block text-sm font-medium text-ink">รหัสผ่านใหม่</label>
          <div class="field" :class="{ 'field--error': errors.newPassword }">
            <AppIcon name="lock-open" :size="20" class="text-slate-400" />
            <input
              id="newPassword"
              v-model="form.newPassword"
              :type="showNew ? 'text' : 'password'"
              autocomplete="new-password"
              placeholder="อย่างน้อย 8 ตัวอักษร"
            />
            <button
              type="button"
              class="text-slate-400"
              :aria-label="showNew ? 'ซ่อนรหัสผ่านใหม่' : 'แสดงรหัสผ่านใหม่'"
              @click="showNew = !showNew"
            >
              <AppIcon :name="showNew ? 'eye-slash' : 'eye'" :size="20" />
            </button>
          </div>
          <p v-if="errors.newPassword" class="mt-1 text-xs text-red-500">{{ errors.newPassword }}</p>

          <!-- ความแข็งแรงของรหัสผ่าน: 4 ช่อง เติมสีตามคะแนน -->
          <div v-if="form.newPassword" class="strength" aria-live="polite">
            <div class="strength-bars">
              <span
                v-for="i in 4"
                :key="i"
                :style="{ background: i <= strength.score ? strength.color : undefined }"
              />
            </div>
            <span class="strength-label" :style="{ color: strength.color }">{{ strength.label }}</span>
          </div>
        </div>

        <div class="rise" :class="{ shake }" style="--d: 160ms">
          <label for="confirmPassword" class="mb-2 block text-sm font-medium text-ink">ยืนยันรหัสผ่านใหม่</label>
          <div class="field" :class="{ 'field--error': confirmMessage, 'field--ok': matches }">
            <AppIcon name="lock-open" :size="20" class="text-slate-400" />
            <input
              id="confirmPassword"
              v-model="form.confirmPassword"
              :type="showConfirm ? 'text' : 'password'"
              autocomplete="new-password"
              placeholder="กรอกรหัสผ่านใหม่อีกครั้ง"
            />
            <Transition name="pop">
              <span v-if="matches" class="match-badge" aria-label="รหัสผ่านตรงกัน">
                <AppIcon name="check" :size="14" weight="bold" />
              </span>
            </Transition>
            <button
              type="button"
              class="text-slate-400"
              :aria-label="showConfirm ? 'ซ่อนการยืนยันรหัสผ่าน' : 'แสดงการยืนยันรหัสผ่าน'"
              @click="showConfirm = !showConfirm"
            >
              <AppIcon :name="showConfirm ? 'eye-slash' : 'eye'" :size="20" />
            </button>
          </div>
          <!-- เตือนทันทีที่พิมพ์ไม่ตรง (ไม่ต้องรอกดบันทึก) แสดงใต้ช่องยืนยันเสมอ -->
          <p v-if="confirmMessage" class="mt-1 text-xs text-red-500" role="alert">{{ confirmMessage }}</p>
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="login-submit rise flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-status-checkin font-semibold text-white shadow-lg shadow-status-checkin/30 transition hover:brightness-95 disabled:opacity-70"
          style="--d: 240ms"
        >
          <LoadingDots v-if="loading" />
          <template v-else>
            ตั้งรหัสผ่านและเข้าสู่ระบบ
            <AppIcon name="arrow-right" :size="20" />
          </template>
        </button>

        <button
          type="button"
          class="rise w-full text-center text-sm font-medium text-ink-muted"
          style="--d: 300ms"
          :disabled="loading"
          @click="emit('back')"
        >
          กลับไปหน้าเข้าสู่ระบบ
        </button>
      </div>
    </Transition>
  </form>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'

const props = defineProps({
  empcode: { type: String, required: true },
  loading: { type: Boolean, default: false },
  // true หลังตั้งรหัสผ่านสำเร็จ — สลับไปโชว์หน้า "สำเร็จ" ก่อนที่ผู้เรียกจะพาเข้าระบบ
  done: { type: Boolean, default: false },
})

const emit = defineEmits(['submit', 'back'])

// ปุ่มตาแยกกันคนละช่อง (ดู/ซ่อนรหัสผ่านใหม่ กับช่องยืนยัน ไม่ผูกกัน)
const showNew = ref(false)
const showConfirm = ref(false)
const shake = ref(false)
const form = reactive({ newPassword: '', confirmPassword: '' })
const errors = reactive({ newPassword: '', confirmPassword: '' })

const matches = computed(() => form.confirmPassword !== '' && form.confirmPassword === form.newPassword)
const mismatch = computed(() => form.confirmPassword !== '' && form.confirmPassword !== form.newPassword)
const confirmMessage = computed(() => errors.confirmPassword || (mismatch.value ? 'รหัสผ่านไม่ตรงกัน' : ''))

// พิมพ์แก้แล้วเคลียร์ error เก่าของช่องนั้นทันที ไม่ปล่อยข้อความแดงค้างหลังแก้ถูกแล้ว
watch(() => form.newPassword, () => { errors.newPassword = '' })
watch(() => form.confirmPassword, () => { errors.confirmPassword = '' })

const strength = computed(() => {
  const p = form.newPassword
  if (p.length < 8) return { score: 1, label: 'สั้นเกินไป', color: '#ef4444' }

  let points = 0
  if (p.length >= 12) points++
  if (/[a-z]/.test(p) && /[A-Z]/.test(p)) points++
  if (/\d/.test(p)) points++
  if (/[^A-Za-z0-9]/.test(p)) points++

  if (points <= 1) return { score: 2, label: 'พอใช้', color: '#f97316' }
  if (points === 2) return { score: 3, label: 'ดี', color: '#84cc16' }
  return { score: 4, label: 'แข็งแรง', color: '#16a34a' }
})

// ตรวจเบื้องต้นฝั่ง client ให้ตรงกับกฎที่ backend บังคับอยู่แล้ว (AuthController::setupPassword()) — backend
// ยัง validate ซ้ำเสมอ ฝั่งนี้แค่กันกรอกผิดแล้วไม่ต้องรอ round-trip ไปเซิร์ฟเวอร์ก่อนรู้ผล
function validate() {
  errors.newPassword = ''
  errors.confirmPassword = ''

  if (form.newPassword.length < 8) {
    errors.newPassword = 'รหัสผ่านใหม่ต้องมีอย่างน้อย 8 ตัวอักษร'
  } else if (form.newPassword === props.empcode) {
    errors.newPassword = 'กรุณาตั้งรหัสผ่านใหม่ที่ไม่ใช่รหัสพนักงานของตัวเอง'
  }

  if (form.confirmPassword !== form.newPassword) {
    errors.confirmPassword = 'รหัสผ่านไม่ตรงกัน'
  }

  return !errors.newPassword && !errors.confirmPassword
}

function handleSubmit() {
  if (validate()) {
    emit('submit', { newPassword: form.newPassword })
    return
  }
  shake.value = false
  requestAnimationFrame(() => {
    shake.value = true
    setTimeout(() => (shake.value = false), 450)
  })
}
</script>

<style scoped>
.login-submit { border:0; border-radius:0.75rem !important; }

.field {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  height: 3rem;
  padding: 0 1rem;
  border: 1px solid #e2e8f0;
  border-radius: 0.75rem;
  background: #f8fafc;
  transition: border-color 0.15s, box-shadow 0.15s;
}

.field:focus-within {
  border-color: #fd7e14;
  box-shadow: 0 0 0 3px rgb(253 126 20 / 0.15);
  background: #fff;
}

.field--error {
  border-color: #ef4444;
}

.field--ok {
  border-color: #16a34a;
}

.field input {
  flex: 1;
  min-width: 0;
  outline: none;
  background: transparent;
  font-size: 0.875rem;
  color: #1e293b;
}

.field input::placeholder {
  color: #94a3b8;
}

/* แบนเนอร์ต้อนรับ */
.welcome {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem;
  border-radius: 1rem;
  background: rgb(253 126 20 / 0.1);
}

.welcome-icon {
  position: relative;
  display: flex;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
  width: 2.75rem;
  height: 2.75rem;
  border-radius: 999px;
  background: #fd7e14;
  color: #fff;
  animation: icon-pop 0.55s cubic-bezier(0.34, 1.56, 0.64, 1) 0.1s both;
}

.welcome-icon::after {
  content: '';
  position: absolute;
  inset: 0;
  border-radius: inherit;
  border: 2px solid #fd7e14;
  animation: ring 1.8s ease-out 0.6s infinite;
}

/* ไล่เข้าทีละส่วน (stagger) — กำหนด delay ผ่าน --d ที่แต่ละ element */
.rise {
  animation: rise 0.45s cubic-bezier(0.22, 1, 0.36, 1) both;
  animation-delay: var(--d, 0ms);
}

/* ความแข็งแรงรหัสผ่าน */
.strength {
  display: flex;
  align-items: center;
  gap: 0.625rem;
  margin-top: 0.5rem;
}

.strength-bars {
  display: flex;
  flex: 1;
  gap: 0.25rem;
}

.strength-bars span {
  flex: 1;
  height: 0.25rem;
  border-radius: 999px;
  background: #e2e8f0;
  transition: background 0.25s ease;
}

.strength-label {
  min-width: 3.75rem;
  text-align: right;
  font-size: 0.6875rem;
  font-weight: 600;
  transition: color 0.25s ease;
}

/* เครื่องหมายถูกเมื่อรหัสผ่านตรงกัน */
.match-badge {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 1.375rem;
  height: 1.375rem;
  border-radius: 999px;
  background: #16a34a;
  color: #fff;
}

.pop-enter-active { transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.2s; }
.pop-leave-active { transition: transform 0.15s ease, opacity 0.15s; }
.pop-enter-from, .pop-leave-to { transform: scale(0); opacity: 0; }

.shake { animation: shake 0.42s ease; }

/* หน้าสำเร็จ */
.success-panel {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 1.5rem 0 1rem;
}

.success-ring {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 5rem;
  height: 5rem;
  border-radius: 999px;
  background: #16a34a;
  color: #fff;
  box-shadow: 0 0 0 0 rgb(22 163 74 / 0.45);
  animation: icon-pop 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) both, success-pulse 1.1s ease-out 0.3s;
}

.success-bar {
  width: 70%;
  height: 0.25rem;
  margin-top: 1.25rem;
  overflow: hidden;
  border-radius: 999px;
  background: #e2e8f0;
}

.success-bar span {
  display: block;
  height: 100%;
  border-radius: inherit;
  background: #16a34a;
  animation: fill 1.1s linear both;
}

.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease, transform 0.2s ease; }
.fade-enter-from { opacity: 0; transform: translateY(8px); }
.fade-leave-to { opacity: 0; transform: translateY(-8px); }

@keyframes rise {
  from { opacity: 0; transform: translateY(14px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes icon-pop {
  from { opacity: 0; transform: scale(0.3) rotate(-20deg); }
  to { opacity: 1; transform: scale(1) rotate(0); }
}

@keyframes ring {
  from { opacity: 0.55; transform: scale(1); }
  to { opacity: 0; transform: scale(1.6); }
}

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  20% { transform: translateX(-6px); }
  40% { transform: translateX(6px); }
  60% { transform: translateX(-4px); }
  80% { transform: translateX(4px); }
}

@keyframes success-pulse {
  to { box-shadow: 0 0 0 22px rgb(22 163 74 / 0); }
}

@keyframes fill {
  from { width: 0; }
  to { width: 100%; }
}

@media (prefers-reduced-motion: reduce) {
  .rise, .welcome-icon, .welcome-icon::after, .shake, .success-ring, .success-bar span {
    animation: none !important;
  }
  .fade-enter-active, .fade-leave-active, .pop-enter-active, .pop-leave-active { transition: none; }
}
</style>
