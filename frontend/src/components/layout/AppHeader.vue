<template>
  <header class="flex items-center gap-3 px-4 pb-4 pt-6">
    <v-menu location="bottom start">
      <template #activator="{ props }">
        <button v-bind="props" class="shrink-0 rounded-full" aria-label="เมนูผู้ใช้">
          <img
            v-if="user?.avatarUrl"
            :src="user.avatarUrl"
            :alt="user.name"
            class="h-11 w-11 rounded-full object-cover"
          />
          <span
            v-else
            class="flex h-11 w-11 items-center justify-center rounded-full bg-emerald-100 text-sm font-semibold text-emerald-700"
          >
            {{ getInitials(user?.name) }}
          </span>
        </button>
      </template>
      <v-list density="compact" min-width="180">
        <v-list-item title="ออกจากระบบ" @click="logout">
          <template #prepend>
            <AppIcon name="sign-out" class="mr-3 text-ink-muted" />
          </template>
        </v-list-item>
      </v-list>
    </v-menu>

    <div class="min-w-0 flex-1 leading-tight">
      <p class="text-[13px] text-ink-muted">สวัสดี</p>
      <p class="truncate text-lg font-bold leading-snug text-ink">{{ user?.name }}</p>
      <p class="truncate text-[13px] text-ink-muted">{{ user?.position }}</p>
    </div>

    <router-link
      :to="{ name: 'notifications' }"
      class="relative flex h-10 w-10 items-center justify-center rounded-full bg-card text-ink no-underline shadow-sm"
      :aria-label="notifications.unreadCount ? `การแจ้งเตือน ยังไม่อ่าน ${notifications.unreadCount} รายการ` : 'การแจ้งเตือน'"
    >
      <AppIcon name="bell" :size="22" :weight="notifications.unreadCount ? 'fill' : 'regular'" />
      <span
        v-if="notifications.unreadCount"
        class="absolute -right-0.5 -top-0.5 flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-status-outside px-1 font-display text-[10px] font-bold text-white ring-2 ring-app-bg"
      >
        {{ notifications.unreadCount > 9 ? '9+' : notifications.unreadCount }}
      </span>
    </router-link>
  </header>
</template>

<script setup>
import { onMounted } from 'vue'
import { useNotificationStore } from '@/store'
import { useAuth } from '@/composables/useAuth'
import { getInitials } from '@/utils/formatters'

const { user, logout } = useAuth()
const notifications = useNotificationStore()

// โหลดครั้งแรกเพื่อแสดงตัวเลขบนกระดิ่ง (ไม่แจ้ง error เพราะไม่ใช่ข้อมูลหลักของหน้า)
onMounted(() => {
  if (!notifications.loaded) notifications.fetchAll().catch(() => {})
})
</script>
