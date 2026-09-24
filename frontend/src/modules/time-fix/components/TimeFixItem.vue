<template>
  <button
    type="button"
    class="flex w-full items-start gap-3 rounded-2xl bg-card p-4 text-left shadow-sm transition active:scale-[0.99]"
    @click="emit('select', request)"
  >
    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-violet-50">
      <AppIcon name="clock-edit" :size="22" weight="duotone" class="text-violet-500" />
    </span>

    <span class="min-w-0 flex-1">
      <span class="flex items-start justify-between gap-2">
        <span class="text-sm font-semibold text-ink">{{ TIME_FIX_TYPE_LABELS[request.fixType] }}</span>
        <StatusBadge :label="status.label" :tone="status.tone" />
      </span>
      <span class="mt-0.5 flex items-center gap-1 text-xs text-ink">
        <AppIcon name="calendar" :size="14" class="text-ink-muted" />
        {{ formatThaiDate(request.date) }}
      </span>
      <span class="mt-1 flex items-center gap-3 text-xs">
        <span v-if="request.checkIn" class="flex items-center gap-1">
          <AppIcon name="sign-in" :size="14" class="text-status-checkin" />
          <span class="font-display font-semibold text-ink">{{ request.checkIn }}</span>
        </span>
        <span v-if="request.checkOut" class="flex items-center gap-1">
          <AppIcon name="sign-out" :size="14" class="text-status-checkout" />
          <span class="font-display font-semibold text-ink">{{ request.checkOut }}</span>
        </span>
        <span class="truncate text-ink-muted">· {{ request.reason }}</span>
        <span v-if="request.attachments?.length" class="flex shrink-0 items-center gap-0.5 text-ink-muted">
          <AppIcon name="paperclip" :size="13" />{{ request.attachments.length }}
        </span>
      </span>
    </span>
  </button>
</template>

<script setup>
import { computed } from 'vue'
import StatusBadge from '@/components/common/StatusBadge.vue'
import { REQUEST_STATUS_META, TIME_FIX_TYPE_LABELS } from '@/utils/constants'
import { formatThaiDate } from '@/utils/formatters'

const props = defineProps({
  request: { type: Object, required: true },
})

const emit = defineEmits(['select'])

const status = computed(() => REQUEST_STATUS_META[props.request.status])
</script>
