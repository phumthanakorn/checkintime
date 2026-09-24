import { defineStore } from 'pinia'
import { ref } from 'vue'

let toastId = 0

export const useAppStore = defineStore('app', () => {
  // null = ให้ Vuetify เลือกเองตามขนาดจอ (เปิดบนจอใหญ่ ปิดบนมือถือ)
  const drawer = ref(null)
  const toasts = ref([])

  function toggleDrawer() {
    drawer.value = !drawer.value
  }

  function pushToast({ type = 'info', message, timeout = 3000 }) {
    toasts.value.push({ id: ++toastId, type, message, timeout })
  }

  function removeToast(id) {
    toasts.value = toasts.value.filter((t) => t.id !== id)
  }

  return { drawer, toasts, toggleDrawer, pushToast, removeToast }
})
