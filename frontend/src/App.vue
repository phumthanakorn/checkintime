<template>
  <v-app>
    <component :is="layout">
      <router-view />
    </component>
    <ToastNotification />
    <OfflineBanner />
  </v-app>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import MainLayout from '@/layouts/MainLayout.vue'
import AuthLayout from '@/layouts/AuthLayout.vue'
import BlankLayout from '@/layouts/BlankLayout.vue'
import ToastNotification from '@/components/feedback/ToastNotification.vue'
import OfflineBanner from '@/components/feedback/OfflineBanner.vue'

const layouts = {
  main: MainLayout,
  auth: AuthLayout,
  blank: BlankLayout,
}

const route = useRoute()
const layout = computed(() => layouts[route.meta.layout] || BlankLayout)
</script>
