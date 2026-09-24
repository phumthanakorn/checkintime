<template>
  <div>
    <!-- แถบขั้นตอน -->
    <div v-if="!locationOnly" class="mb-8 flex justify-center gap-1.5" aria-hidden="true">
      <span
        v-for="i in 3"
        :key="i"
        class="h-1.5 rounded-full transition-all duration-300"
        :class="i <= stepIndex ? 'w-8 bg-status-checkin' : 'w-4 bg-slate-200'"
      />
    </div>

    <Transition name="step" mode="out-in">
      <ConsentStep v-if="step === 'consent'" key="consent" :loading="saving" @accept="acceptConsent" />

      <LocationStep
        v-else-if="step === 'location'"
        key="location"
        :skip-label="locationOnly ? 'กลับหน้าหลัก' : 'ข้ามไปก่อน'"
        @done="onLocationDone"
      />

      <!-- เสร็จสิ้น -->
      <div v-else key="done" class="pt-6 text-center">
        <span class="mx-auto mb-5 flex h-20 w-20 items-center justify-center rounded-full bg-status-checkin/10">
          <AppIcon name="rocket" :size="40" weight="duotone" class="text-status-checkin" />
        </span>
        <h1 class="text-2xl font-bold text-ink">พร้อมใช้งานแล้ว!</h1>
        <p class="mx-auto mt-1 max-w-[300px] text-[13px] text-ink-muted">
          สวัสดีคุณ{{ firstName }} เริ่มลงเวลาเข้างานได้เลยที่หน้าหลัก
        </p>
        <p
          v-if="!locationGranted"
          class="mt-5 flex items-start gap-2 rounded-2xl bg-status-checkout/10 p-3 text-left text-xs leading-relaxed text-amber-700"
        >
          <AppIcon name="warning" :size="16" />
          ยังไม่ได้อนุญาตตำแหน่ง คุณจะเห็นแถบเตือนที่หน้าหลักจนกว่าจะเปิด
        </p>
        <button
          type="button"
          class="mt-8 flex h-12 w-full items-center justify-center gap-2 rounded-2xl bg-status-checkin font-semibold text-white shadow-lg shadow-status-checkin/30"
          @click="router.replace({ name: 'home' })"
        >
          ไปหน้าหลัก
          <AppIcon name="arrow-right" :size="20" />
        </button>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import ConsentStep from '../components/ConsentStep.vue'
import LocationStep from '../components/LocationStep.vue'
import { useAuthStore } from '@/store'
import { useNotification } from '@/composables/useNotification'
import { PDPA_POLICY_VERSION } from '@/utils/constants'

const auth = useAuthStore()
const notify = useNotification()
const route = useRoute()
const router = useRouter()

// /onboarding?step=location -> มาจากแถบเตือนในหน้าหลัก ให้แสดงเฉพาะขั้นตำแหน่ง
const locationOnly = route.query.step === 'location'
const hasConsent = auth.user?.consent?.policyVersion === PDPA_POLICY_VERSION

const step = ref(locationOnly || hasConsent ? 'location' : 'consent')
const saving = ref(false)
const locationGranted = ref(false)

const stepIndex = computed(() => ({ consent: 1, location: 2, done: 3 })[step.value])
const firstName = computed(() => auth.user?.name?.replace(/^(นาย|นางสาว|นาง)\s*/, '').split(' ')[0] ?? '')

async function acceptConsent({ location }) {
  saving.value = true
  try {
    await auth.acceptConsent({ policyVersion: PDPA_POLICY_VERSION, location })
    step.value = 'location'
  } catch (error) {
    notify.error(error.message)
  } finally {
    saving.value = false
  }
}

function onLocationDone({ granted }) {
  locationGranted.value = granted
  if (locationOnly) router.replace({ name: 'home' })
  else step.value = 'done'
}
</script>

<style scoped>
.step-enter-active,
.step-leave-active {
  transition:
    opacity 0.25s ease,
    transform 0.25s ease;
}

.step-enter-from {
  opacity: 0;
  transform: translateX(24px);
}

.step-leave-to {
  opacity: 0;
  transform: translateX(-24px);
}
</style>
