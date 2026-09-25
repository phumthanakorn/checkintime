<template>
  <nav
    class="fixed bottom-0 left-1/2 z-10 grid w-full max-w-md -translate-x-1/2 grid-cols-4 border-t border-slate-100 bg-card pb-[env(safe-area-inset-bottom)]"
  >
    <!-- ไอคอนอย่างเดียว (ไม่มีข้อความ) แท็บที่เลือก = ไอคอนทึบสีหลัก (เขียว) -->
    <router-link
      v-for="item in items"
      :key="item.name"
      :to="{ name: item.name }"
      class="flex h-14 items-center justify-center no-underline transition-colors duration-200"
      :class="isActive(item) ? 'text-status-checkin' : 'text-slate-400 hover:text-slate-500'"
      :aria-label="item.label"
      :aria-current="isActive(item) ? 'page' : undefined"
    >
      <AppIcon
        :name="item.icon"
        :size="26"
        :weight="isActive(item) ? 'fill' : 'regular'"
        class="transition-transform duration-200"
        :class="isActive(item) && 'scale-110'"
      />
    </router-link>
  </nav>
</template>

<script setup>
import { useRoute } from 'vue-router'

const route = useRoute()

const items = [
  { name: 'home', label: 'หน้าหลัก', icon: 'home' },
  { name: 'history', label: 'ประวัติ', icon: 'history' },
  { name: 'leave', label: 'การลา', icon: 'calendar' },
  { name: 'profile', label: 'ฉัน', icon: 'user' },
]

// หน้าย่อย (เช่น สลิป) ระบุแท็บแม่ผ่าน meta.tab
const isActive = (item) => route.name === item.name || route.meta.tab === item.name
</script>
