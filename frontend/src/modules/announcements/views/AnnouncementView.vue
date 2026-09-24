<template>
  <div class="space-y-4">
    <PageHeader title="ประกาศ" :back-to="{ name: 'notifications' }" />

    <LoadingState v-if="loading" />

    <EmptyState
      v-else-if="error"
      icon="megaphone"
      title="ไม่พบประกาศ"
      :description="error"
    />

    <template v-else-if="item">
      <article class="overflow-hidden rounded-3xl bg-card shadow-sm">
        <!-- หัวประกาศ -->
        <header class="relative bg-metric-cyan/10 px-5 pb-5 pt-6">
          <span class="pointer-events-none absolute -right-8 -top-8 h-32 w-32 rounded-full bg-metric-cyan/10" />
          <span class="relative inline-flex items-center gap-1 rounded-full bg-card px-2.5 py-1 text-[11px] font-semibold text-metric-cyan shadow-sm">
            <AppIcon name="megaphone" :size="14" weight="duotone" />
            {{ item.category }}
          </span>
          <h1 class="relative mt-3 text-xl font-bold leading-snug text-ink">{{ item.title }}</h1>
          <p class="relative mt-2 flex items-center gap-1.5 text-xs text-ink-muted">
            <AppIcon name="user" :size="14" />
            {{ item.author }}
            <span aria-hidden="true">·</span>
            {{ formatDayMonth(item.publishedAt, true) }} {{ formatClock(item.publishedAt) }} น.
          </p>
        </header>

        <!-- เนื้อหา -->
        <div class="space-y-3 px-5 py-5">
          <p v-for="(paragraph, index) in item.body" :key="index" class="text-sm leading-relaxed text-ink">
            {{ paragraph }}
          </p>
          <AttachmentList v-if="item.attachments?.length" :files="item.attachments" class="pt-2" />
        </div>
      </article>

      <!-- รับทราบ -->
      <template v-if="item.requireAck">
        <p
          v-if="item.acknowledgedAt"
          class="flex items-center justify-center gap-2 rounded-2xl bg-status-checkin/10 py-3.5 text-sm font-semibold text-status-checkin"
        >
          <AppIcon name="seal-check" :size="20" weight="fill" />
          รับทราบแล้วเมื่อ {{ formatDayMonth(item.acknowledgedAt, true) }} {{ formatClock(item.acknowledgedAt) }} น.
        </p>
        <button
          v-else
          type="button"
          class="flex h-12 w-full items-center justify-center gap-2 rounded-2xl bg-status-checkin font-semibold text-white shadow-lg shadow-status-checkin/30 disabled:opacity-60"
          :disabled="acknowledging"
          @click="acknowledge"
        >
          <LoadingDots v-if="acknowledging" />
          <template v-else>
            <AppIcon name="check" :size="20" weight="bold" />
            รับทราบประกาศ
          </template>
        </button>
        <p v-if="!item.acknowledgedAt" class="text-center text-[11px] text-ink-muted">
          ประกาศนี้ต้องการให้พนักงานกดรับทราบ
        </p>
      </template>
    </template>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import PageHeader from '@/components/layout/PageHeader.vue'
import LoadingState from '@/components/feedback/LoadingState.vue'
import EmptyState from '@/components/feedback/EmptyState.vue'
import AttachmentList from '@/components/common/AttachmentList.vue'
import { announcementService } from '@/api/services/announcementService'
import { useNotification } from '@/composables/useNotification'
import { formatClock, formatDayMonth } from '@/utils/formatters'

const route = useRoute()
const notify = useNotification()

const item = ref(null)
const loading = ref(true)
const error = ref('')
const acknowledging = ref(false)

onMounted(async () => {
  try {
    item.value = await announcementService.get(route.params.id)
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
})

async function acknowledge() {
  acknowledging.value = true
  try {
    item.value = await announcementService.acknowledge(item.value.id)
    notify.success('รับทราบประกาศแล้ว')
  } catch (e) {
    notify.error(e.message)
  } finally {
    acknowledging.value = false
  }
}
</script>
