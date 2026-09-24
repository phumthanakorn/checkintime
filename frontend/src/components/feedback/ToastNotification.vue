<template>
  <!-- key ทำให้ snackbar สร้างใหม่ต่อ toast แต่ละอัน เพื่อเริ่มนับเวลาใหม่ -->
  <v-snackbar
    v-if="current"
    :key="current.id"
    :model-value="true"
    :color="current.type"
    :timeout="current.timeout"
    location="top"
    @update:model-value="(open) => !open && app.removeToast(current.id)"
  >
    {{ current.message }}
    <template #actions>
      <button
        type="button"
        class="flex h-8 w-8 items-center justify-center rounded-full text-white/90 hover:bg-white/15"
        aria-label="ปิด"
        @click="app.removeToast(current.id)"
      >
        <AppIcon name="x" :size="18" />
      </button>
    </template>
  </v-snackbar>
</template>

<script setup>
import { computed } from 'vue'
import { useAppStore } from '@/store'

const app = useAppStore()
const current = computed(() => app.toasts[0])
</script>
