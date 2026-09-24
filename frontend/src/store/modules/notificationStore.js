import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { notificationService } from '@/api/services/notificationService'

export const useNotificationStore = defineStore('notification', () => {
  const items = ref([])
  const loading = ref(false)
  const loaded = ref(false)

  const unreadCount = computed(() => items.value.filter((n) => !n.read).length)

  async function fetchAll() {
    loading.value = true
    try {
      items.value = await notificationService.getAll()
      loaded.value = true
    } finally {
      loading.value = false
    }
  }

  async function markRead(id) {
    const item = items.value.find((n) => n.id === id)
    if (!item || item.read) return
    item.read = true // อัปเดตหน้าจอก่อน ไม่ต้องรอ API
    try {
      await notificationService.markRead(id)
    } catch {
      item.read = false
    }
  }

  async function markAllRead() {
    const unread = items.value.filter((n) => !n.read)
    unread.forEach((n) => (n.read = true))
    try {
      await notificationService.markAllRead()
    } catch (error) {
      unread.forEach((n) => (n.read = false))
      throw error
    }
  }

  function reset() {
    items.value = []
    loaded.value = false
  }

  return { items, loading, loaded, unreadCount, fetchAll, markRead, markAllRead, reset }
})
