<template>
  <Teleport to="body">
    <Transition name="sheet-fade">
      <div
        v-if="modelValue"
        class="fixed inset-0 z-[2400] flex items-end justify-center bg-black/45"
        @click.self="close"
      >
        <Transition name="sheet-slide" appear>
          <div class="w-full max-w-md overflow-hidden rounded-t-[28px] bg-card pb-[env(safe-area-inset-bottom)] shadow-2xl">
            <div class="flex items-start justify-between gap-3 px-5 pb-2 pt-4">
              <div class="min-w-0">
                <p class="truncate text-base font-bold text-ink">{{ location?.name }}</p>
                <p class="text-xs text-ink-muted">
                  {{ location?.typeLabel }}<span v-if="distanceLabel"> · ห่างจากคุณ {{ distanceLabel }}</span>
                </p>
              </div>
              <button
                type="button"
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-ink-muted"
                aria-label="ปิด"
                @click="close"
              >
                <AppIcon name="x" :size="18" />
              </button>
            </div>

            <div class="h-72 w-full">
              <MapView v-if="location" :office="mapOffice" :user="user" />
            </div>

            <div class="p-5">
              <a
                :href="navigateUrl"
                target="_blank"
                rel="noopener"
                class="flex items-center justify-center gap-2 rounded-2xl bg-status-checkin px-4 py-3.5 text-sm font-semibold text-white shadow-sm active:scale-[0.99]"
              >
                <AppIcon name="navigation" :size="18" />
                นำทางไปที่นี่
              </a>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
/** Popup แผนที่ของสถานที่ลงเวลา 1 จุด — โชว์หมุดสถานที่ + ตำแหน่งปัจจุบันของผู้ใช้ (reuse MapView ตัวเดียว
 * กับที่ OutOfAreaView.vue ใช้อยู่แล้ว) เปิดตอนแตะป้าย "สถานที่ใกล้ที่สุด" บนการ์ดลงเวลา — แสดงให้ดูเฉยๆ
 * ไม่เกี่ยวกับการบังคับ/อนุญาตลงเวลาเลย */
import { computed } from 'vue'
import MapView from '@/components/common/MapView.vue'
import { distanceMeters } from '@/utils/geo'

const modelValue = defineModel({ type: Boolean, default: false })
const props = defineProps({
  /** { name, typeLabel, latitude, longitude, radiusMeters } */
  location: { type: Object, default: null },
  /** { lat, lng, accuracy } | null */
  user: { type: Object, default: null },
})

// หลายสถานที่ (เช่น office) ไม่ได้ตั้ง radiusMeters ไว้ — ใช้ 50ม. เป็นค่า default ให้วาดวงกลมบนแผนที่ได้เสมอ
const mapOffice = computed(() =>
  props.location
    ? {
        lat: props.location.latitude,
        lng: props.location.longitude,
        radiusMeters: props.location.radiusMeters || 50,
        name: props.location.name,
      }
    : null,
)

const distanceLabel = computed(() => {
  if (!props.user || !props.location) return null
  const m = distanceMeters(props.user, { lat: props.location.latitude, lng: props.location.longitude })
  return m < 1000 ? `${Math.round(m)} ม.` : `${(m / 1000).toFixed(1)} กม.`
})

const navigateUrl = computed(() =>
  props.location
    ? `https://www.google.com/maps/dir/?api=1&destination=${props.location.latitude},${props.location.longitude}`
    : '#',
)

function close() {
  modelValue.value = false
}
</script>

<style scoped>
.sheet-fade-enter-active,
.sheet-fade-leave-active {
  transition: opacity 0.2s ease;
}
.sheet-fade-enter-from,
.sheet-fade-leave-to {
  opacity: 0;
}

.sheet-slide-enter-active {
  transition: transform 0.28s cubic-bezier(0.22, 1, 0.36, 1);
}
.sheet-slide-leave-active {
  transition: transform 0.2s ease-in;
}
.sheet-slide-enter-from,
.sheet-slide-leave-to {
  transform: translateY(100%);
}

@media (prefers-reduced-motion: reduce) {
  .sheet-fade-enter-active,
  .sheet-fade-leave-active,
  .sheet-slide-enter-active,
  .sheet-slide-leave-active {
    transition-duration: 0.01s;
  }
}
</style>
