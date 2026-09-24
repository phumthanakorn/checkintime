<template>
  <nav
    class="fixed bottom-0 left-1/2 z-10 grid w-full max-w-md -translate-x-1/2 grid-cols-4 border-t border-slate-100 bg-card pb-[env(safe-area-inset-bottom)]"
  >
    <router-link
      v-for="item in items"
      :key="item.name"
      :to="{ name: item.name }"
      class="flex flex-col items-center gap-1 py-3 text-[11px] font-medium no-underline transition-colors"
      :class="isActive(item) ? 'font-semibold text-metric-blue' : 'text-ink-muted'"
    >
      <v-icon :icon="isActive(item) ? item.activeIcon : item.icon" size="22" />
      {{ item.label }}
    </router-link>
  </nav>
</template>

<script setup>
import { useRoute } from 'vue-router'

const route = useRoute()

const items = [
  { name: 'home', label: 'หน้าหลัก', icon: 'mdi-home-outline', activeIcon: 'mdi-home' },
  { name: 'history', label: 'ประวัติ', icon: 'mdi-history', activeIcon: 'mdi-history' },
  { name: 'leave', label: 'การลา', icon: 'mdi-calendar-blank-outline', activeIcon: 'mdi-calendar-blank' },
  { name: 'profile', label: 'ฉัน', icon: 'mdi-account-circle-outline', activeIcon: 'mdi-account-circle' },
]

// หน้าย่อย (เช่น สลิป) ระบุแท็บแม่ผ่าน meta.tab
const isActive = (item) => route.name === item.name || route.meta.tab === item.name
</script>
