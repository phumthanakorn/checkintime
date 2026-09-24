<template>
  <v-main>
    <!-- กรอบแอปมือถือ: บนจอใหญ่จะแสดงเป็นคอลัมน์กลางจอ -->
    <div class="relative mx-auto flex min-h-screen w-full max-w-md flex-col bg-app-bg shadow-sm">
      <AppHeader v-if="!route.meta.hideHeader" />
      <div class="flex-1 px-4 pb-28">
        <slot />
      </div>
      <AppBottomNav />
    </div>
  </v-main>
</template>

<script setup>
import { useRoute } from 'vue-router'
import AppHeader from '@/components/layout/AppHeader.vue'
import AppBottomNav from '@/components/layout/AppBottomNav.vue'

const route = useRoute()
</script>
