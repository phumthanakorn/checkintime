<template>
  <div class="flex min-h-screen flex-col items-center justify-center gap-3 bg-app-bg p-6 text-center">
    <p class="text-6xl font-bold font-display text-status-checkin">404</p>
    <p class="text-slate-600">ไม่พบหน้าที่คุณต้องการ</p>
    <router-link
      :to="{ name: 'home' }"
      class="mt-2 rounded-xl bg-status-checkin px-5 py-2.5 text-sm font-semibold text-white no-underline"
    >
      กลับหน้าหลัก
    </router-link>
  </div>
</template>
