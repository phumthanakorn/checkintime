<template>
  <!-- แสดงเมื่อผู้ใช้ปิดการเข้าถึงตำแหน่งไว้ (ลงเวลาในแอปไม่ได้) -->
  <router-link
    v-if="denied"
    :to="{ name: 'onboarding', query: { step: 'location' } }"
    class="flex items-center gap-3 rounded-2xl bg-status-outside/10 p-3.5 no-underline"
  >
    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-status-outside text-white">
      <AppIcon name="map-pin" :size="20" weight="fill" />
    </span>
    <span class="min-w-0 flex-1">
      <span class="block text-sm font-semibold text-ink">ยังไม่ได้อนุญาตตำแหน่ง</span>
      <span class="block text-xs text-ink-muted">ต้องเปิดตำแหน่งก่อนจึงจะลงเวลาได้ แตะเพื่อดูวิธีเปิด</span>
    </span>
    <AppIcon name="caret-right" class="text-status-outside" />
  </router-link>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useGeolocation } from '@/composables/useGeolocation'

const { getPermissionState } = useGeolocation()
const denied = ref(false)

onMounted(async () => {
  denied.value = (await getPermissionState()) === 'denied'
})
</script>
