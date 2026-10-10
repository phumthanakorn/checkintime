<template>
  <v-app>
    <component :is="layout">
      <router-view v-slot="{ Component }">
        <Transition name="page">
          <component :is="Component" :key="route.fullPath" />
        </Transition>
      </router-view>
    </component>
    <ToastNotification />
    <OfflineBanner />
  </v-app>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/store'
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
const auth = useAuthStore()
const layout = computed(() => layouts[route.meta.layout] || BlankLayout)

const ACCESS_CHECK_INTERVAL_MS = 60 * 1000
let accessCheckTimer = null
let accessCheckPromise = null

function checkCurrentAccess() {
  if (!auth.token || document.visibilityState === 'hidden') return Promise.resolve()
  if (accessCheckPromise) return accessCheckPromise

  accessCheckPromise = auth.refreshUser()
    .catch(() => {
    
    })
    .finally(() => {
      accessCheckPromise = null
    })

  return accessCheckPromise
}

function handleVisibilityChange() {
  if (document.visibilityState === 'visible') checkCurrentAccess()
}

function handleWindowFocus() {
  checkCurrentAccess()
}

watch(
  () => route.fullPath,
  () => checkCurrentAccess(),
  { immediate: true },
)

onMounted(() => {
  document.addEventListener('visibilitychange', handleVisibilityChange)
  window.addEventListener('focus', handleWindowFocus)
  accessCheckTimer = window.setInterval(checkCurrentAccess, ACCESS_CHECK_INTERVAL_MS)
})

onBeforeUnmount(() => {
  document.removeEventListener('visibilitychange', handleVisibilityChange)
  window.removeEventListener('focus', handleWindowFocus)
  if (accessCheckTimer) window.clearInterval(accessCheckTimer)
})
</script>

<style>
.page-enter-active,
.page-leave-active {
  transition: opacity 0.22s ease, transform 0.22s ease;
}
.page-enter-from {
  opacity: 0;
  transform: translateY(10px);
}
.page-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}

.page-leave-active {
  position: absolute;
  width: 100%;
}

@media (prefers-reduced-motion: reduce) {
  .page-enter-active,
  .page-leave-active {
    transition-duration: 0.01s;
  }
}
</style>
