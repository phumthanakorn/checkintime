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
      <v-btn icon="mdi-close" variant="text" size="small" @click="app.removeToast(current.id)" />
    </template>
  </v-snackbar>
</template>

<script setup>
import { computed } from 'vue'
import { useAppStore } from '@/store'

const app = useAppStore()
const current = computed(() => app.toasts[0])
</script>
