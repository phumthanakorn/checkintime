<template>
  <!-- แถบเมนูลอย: แคปซูลมุมมน เว้นระยะจากขอบจอ มีเงา ไม่ติดขอบล่าง -->
  <nav
    class="fixed bottom-[calc(1rem+env(safe-area-inset-bottom))] left-1/2 z-10 grid w-[calc(100%-2rem)] max-w-[24rem] -translate-x-1/2 grid-cols-4 rounded-full border border-white/70 bg-card/90 px-2 shadow-[0_8px_30px_rgb(15_23_42/0.12)] backdrop-blur-md"
  >
    <!-- ไอคอน + ข้อความ แท็บที่เลือก = สีหลัก (เขียว) พร้อมเส้นใต้ -->
    <router-link
      v-for="item in items"
      :key="item.name"
      :to="{ name: item.name }"
      class="relative flex h-16 flex-col items-center justify-center gap-0.5 no-underline transition-colors duration-200"
      :class="isActive(item) ? 'text-status-checkin' : 'text-ink'"
      :aria-current="isActive(item) ? 'page' : undefined"
    >
      <AppIcon :name="item.icon" :size="24" />
      <span class="text-[11px] leading-tight" :class="isActive(item) ? 'font-semibold' : 'font-medium'">{{ item.label }}</span>
      <!-- เส้นใต้แท็บที่เลือก -->
      <span
        class="absolute bottom-1.5 h-[3px] rounded-full bg-status-checkin transition-all duration-200"
        :class="isActive(item) ? 'w-8 opacity-100' : 'w-0 opacity-0'"
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
