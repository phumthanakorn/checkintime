<template>
  <div class="space-y-4">
    <PageHeader title="ลงเวลาไม่ได้" subtitle="อยู่นอกพื้นที่ที่อนุญาตให้ลงเวลา" :back-to="{ name: 'home' }" />

    <!-- แผนที่ -->
    <section class="relative h-64 overflow-hidden rounded-3xl bg-slate-100 shadow-sm">
      <MapView :office="OFFICE_LOCATION" :user="position" />
      <div
        v-if="checking"
        class="absolute inset-0 z-[500] flex items-center justify-center bg-card/60 backdrop-blur-[1px]"
      >
        <LoadingDots size="lg" class="text-status-checkin" />
      </div>
    </section>

    <!-- สรุประยะทาง -->
    <section class="rounded-3xl bg-card p-4 shadow-sm">
      <template v-if="position">
        <div class="flex items-center gap-3">
          <span
            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl"
            :class="inside ? 'bg-status-checkin/10' : 'bg-status-outside/10'"
          >
            <AppIcon
              :name="inside ? 'check' : 'map-pin'"
              :size="26"
              weight="duotone"
              :class="inside ? 'text-status-checkin' : 'text-status-outside'"
            />
          </span>
          <div class="min-w-0 flex-1">
            <p class="text-xs text-ink-muted">ระยะห่างจาก{{ OFFICE_LOCATION.name }}</p>
            <p class="font-display text-2xl font-bold" :class="inside ? 'text-status-checkin' : 'text-status-outside'">
              {{ formatDistance(distance) }}
            </p>
          </div>
        </div>
        <div class="mt-3 grid grid-cols-2 gap-2 text-center">
          <div class="rounded-2xl bg-app-bg px-3 py-2.5">
            <p class="text-[11px] text-ink-muted">ต้องอยู่ในรัศมี</p>
            <p class="font-display text-sm font-semibold text-ink">{{ OFFICE_LOCATION.radiusMeters }} ม.</p>
          </div>
          <div class="rounded-2xl bg-app-bg px-3 py-2.5">
            <p class="text-[11px] text-ink-muted">ความแม่นยำ GPS</p>
            <p class="font-display text-sm font-semibold" :class="lowAccuracy ? 'text-status-checkout' : 'text-ink'">
              ±{{ position.accuracy }} ม.
            </p>
          </div>
        </div>
        <p v-if="inside" class="mt-3 text-center text-sm font-medium text-status-checkin">
          ตอนนี้คุณอยู่ในพื้นที่แล้ว กลับไปกดลงเวลาได้เลย
        </p>
      </template>

      <div v-else-if="!checking" class="flex items-start gap-3">
        <AppIcon name="warning" :size="24" weight="duotone" class="text-status-checkout" />
        <div class="text-sm">
          <p class="font-semibold text-ink">หาตำแหน่งของคุณไม่ได้</p>
          <p class="text-xs text-ink-muted">ตรวจว่าเปิดตำแหน่ง / GPS แล้ว และอนุญาตให้แอปใช้ตำแหน่ง</p>
          <router-link
            :to="{ name: 'onboarding', query: { step: 'location' } }"
            class="mt-1 inline-block text-xs font-semibold text-status-checkin no-underline"
          >
            ดูวิธีเปิดตำแหน่ง ›
          </router-link>
        </div>
      </div>
    </section>

    <!-- สิ่งที่ทำได้ -->
    <button
      type="button"
      class="flex h-12 w-full items-center justify-center gap-2 rounded-2xl font-semibold text-white shadow-lg transition disabled:opacity-60"
      :class="inside ? 'bg-status-checkin shadow-status-checkin/30' : 'bg-ink shadow-ink/20'"
      :disabled="checking"
      @click="inside ? router.replace({ name: 'home' }) : refresh()"
    >
      <AppIcon :name="inside ? 'arrow-right' : 'crosshair'" :size="20" />
      {{ inside ? 'กลับไปลงเวลา' : 'ตรวจตำแหน่งอีกครั้ง' }}
    </button>

    <div v-if="!inside" class="divide-y divide-slate-100 overflow-hidden rounded-2xl bg-card shadow-sm">
      <a :href="directionsUrl" target="_blank" rel="noopener" class="option-row no-underline">
        <span class="icon bg-metric-blue/10"><AppIcon name="navigation" weight="duotone" class="text-metric-blue" /></span>
        <span class="min-w-0 flex-1">
          <span class="block text-sm font-medium text-ink">นำทางไป{{ OFFICE_LOCATION.name }}</span>
          <span class="block text-xs text-ink-muted">เปิดเส้นทางใน Google Maps</span>
        </span>
        <AppIcon name="caret-right" class="text-slate-300" />
      </a>
      <router-link
        :to="{ name: 'time-fix', query: { date: today, type: 'check_in' } }"
        class="option-row no-underline"
      >
        <span class="icon bg-violet-50"><AppIcon name="clock-edit" weight="duotone" class="text-violet-500" /></span>
        <span class="min-w-0 flex-1">
          <span class="block text-sm font-medium text-ink">ทำงานนอกสถานที่?</span>
          <span class="block text-xs text-ink-muted">ส่งคำขอลงเวลาให้หัวหน้าอนุมัติแทน</span>
        </span>
        <AppIcon name="caret-right" class="text-slate-300" />
      </router-link>
    </div>

    <!-- เคล็ดลับเมื่อ GPS คลาดเคลื่อน -->
    <section v-if="!inside" class="rounded-2xl bg-metric-blue/10 p-4 text-xs leading-relaxed text-metric-blue">
      <p class="mb-1 flex items-center gap-1 font-semibold"><AppIcon name="info" :size="16" /> อยู่ที่ออฟฟิศแล้วแต่ยังขึ้นนอกพื้นที่?</p>
      <ul class="list-disc space-y-0.5 pl-5">
        <li>ขยับไปใกล้หน้าต่างหรือที่โล่ง ให้ GPS จับสัญญาณได้ดีขึ้น</li>
        <li>เปิดโหมดตำแหน่ง “ความแม่นยำสูง” ในการตั้งค่าโทรศัพท์</li>
        <li>รอสักครู่แล้วกด “ตรวจตำแหน่งอีกครั้ง”</li>
      </ul>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import PageHeader from '@/components/layout/PageHeader.vue'
import MapView from '@/components/common/MapView.vue'
import { useGeolocation } from '@/composables/useGeolocation'
import { OFFICE_LOCATION } from '@/utils/constants'
import { toDateKey } from '@/utils/formatters'
import { distanceMeters } from '@/utils/geo'

const route = useRoute()
const router = useRouter()
const { requestPosition } = useGeolocation()

const position = ref(null)
const checking = ref(true)
const today = toDateKey()

const distance = computed(() => (position.value ? distanceMeters(position.value, OFFICE_LOCATION) : null))
// ถือว่าอยู่ในพื้นที่ถ้าระยะ ≤ รัศมี + ความคลาดเคลื่อน GPS (เกณฑ์เดียวกับ utils/geo isInsideArea)
const inside = computed(
  () => distance.value != null && distance.value <= OFFICE_LOCATION.radiusMeters + (position.value?.accuracy || 0),
)
const lowAccuracy = computed(() => (position.value?.accuracy ?? 0) > 100)

const directionsUrl = computed(
  () => `https://www.google.com/maps/dir/?api=1&destination=${OFFICE_LOCATION.lat},${OFFICE_LOCATION.lng}`,
)

function formatDistance(meters) {
  if (meters == null) return '-'
  return meters >= 1000 ? `${(meters / 1000).toFixed(1)} กม.` : `${Math.round(meters)} ม.`
}

async function refresh() {
  checking.value = true
  // โหมดตัวอย่าง (?demo=1): จำลองว่าอยู่ห่างออฟฟิศ ~1.2 กม.
  if (route.query.demo) {
    await new Promise((r) => setTimeout(r, 400))
    position.value = { lat: OFFICE_LOCATION.lat + 0.0085, lng: OFFICE_LOCATION.lng + 0.0068, accuracy: 25 }
  } else {
    position.value = (await requestPosition()).position
  }
  checking.value = false
}

onMounted(refresh)
</script>

<style scoped>
.option-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.875rem 1rem;
}

.icon {
  display: flex;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 0.75rem;
}
</style>
