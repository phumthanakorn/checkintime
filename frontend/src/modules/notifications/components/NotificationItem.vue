<template>
  <button
    type="button"
    class="relative flex w-full items-start gap-3 rounded-2xl p-4 text-left shadow-sm transition active:scale-[0.99]"
    :class="notification.read ? 'bg-card' : 'bg-metric-blue/5 ring-1 ring-metric-blue/15'"
    @click="emit('select', notification)"
  >
    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full" :class="meta.bg">
      <AppIcon :name="meta.icon" :size="22" weight="duotone" :class="meta.color" />
    </span>

    <span class="min-w-0 flex-1 pr-3">
      <span class="block text-sm text-ink" :class="notification.read ? 'font-medium' : 'font-bold'">
        {{ notification.title }}
      </span>
      <span class="mt-0.5 line-clamp-2 block text-xs leading-relaxed text-ink-muted">{{ notification.body }}</span>
      <span class="mt-1.5 block text-[11px] text-ink-muted">{{ formatRelativeTime(notification.createdAt) }}</span>
    </span>

    <!-- จุดยังไม่อ่าน -->
    <span v-if="!notification.read" class="absolute right-4 top-5 h-2.5 w-2.5 rounded-full bg-metric-blue" aria-label="ยังไม่อ่าน" />
  </button>
</template>

<script setup>
import { computed } from 'vue'
import { NOTIFICATION_META, NOTIFICATION_TYPES } from '@/utils/constants'
import { formatRelativeTime } from '@/utils/formatters'

const props = defineProps({
  notification: { type: Object, required: true },
})

const emit = defineEmits(['select'])

const meta = computed(
  () => NOTIFICATION_META[props.notification.type] ?? NOTIFICATION_META[NOTIFICATION_TYPES.ANNOUNCEMENT],
)
</script>
