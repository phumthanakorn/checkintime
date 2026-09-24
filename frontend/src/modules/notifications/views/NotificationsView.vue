<template>
  <div class="space-y-4">
    <PageHeader title="การแจ้งเตือน" :back-to="{ name: 'home' }">
      <template #actions>
        <button
          v-if="store.unreadCount"
          type="button"
          class="flex h-10 items-center gap-1 rounded-full bg-card px-3.5 text-xs font-semibold text-ink shadow-sm"
          @click="markAllRead"
        >
          <AppIcon name="checks" :size="18" class="text-status-checkin" />
          อ่านทั้งหมด
        </button>
      </template>
    </PageHeader>

    <SegmentedTabs v-model="filter" :options="filterOptions" />

    <LoadingState v-if="store.loading && !store.loaded" />

    <EmptyState
      v-else-if="!filtered.length"
      icon="bell-slash"
      :title="filter === 'unread' ? 'อ่านครบทุกรายการแล้ว' : 'ยังไม่มีการแจ้งเตือน'"
      description="เมื่อมีความเคลื่อนไหว เช่น ใบลาได้รับอนุมัติ จะแจ้งให้ทราบที่นี่"
    />

    <template v-else>
      <section v-for="group in groups" :key="group.label" class="space-y-2.5">
        <h2 class="px-1 pt-1 text-[13px] font-semibold text-ink-muted">{{ group.label }}</h2>
        <NotificationItem v-for="item in group.items" :key="item.id" :notification="item" @select="open" />
      </section>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import PageHeader from '@/components/layout/PageHeader.vue'
import SegmentedTabs from '@/components/common/SegmentedTabs.vue'
import LoadingState from '@/components/feedback/LoadingState.vue'
import EmptyState from '@/components/feedback/EmptyState.vue'
import NotificationItem from '../components/NotificationItem.vue'
import { useNotificationStore } from '@/store'
import { useNotification } from '@/composables/useNotification'
import { toDateKey } from '@/utils/formatters'

const store = useNotificationStore()
const toast = useNotification()
const router = useRouter()

const filter = ref('all')

const filterOptions = computed(() => [
  { value: 'all', label: 'ทั้งหมด' },
  { value: 'unread', label: 'ยังไม่อ่าน', count: store.unreadCount },
])

const filtered = computed(() => (filter.value === 'unread' ? store.items.filter((n) => !n.read) : store.items))

// แบ่งกลุ่ม วันนี้ / เมื่อวาน / ก่อนหน้านี้
const groups = computed(() => {
  const today = toDateKey()
  const yesterday = toDateKey(new Date(Date.now() - 86400000))
  const buckets = [
    { label: 'วันนี้', items: [] },
    { label: 'เมื่อวาน', items: [] },
    { label: 'ก่อนหน้านี้', items: [] },
  ]
  for (const item of filtered.value) {
    const day = toDateKey(item.createdAt)
    buckets[day === today ? 0 : day === yesterday ? 1 : 2].items.push(item)
  }
  return buckets.filter((b) => b.items.length)
})

onMounted(async () => {
  try {
    await store.fetchAll()
  } catch (error) {
    toast.error(error.message)
  }
})

function open(item) {
  store.markRead(item.id)
  if (item.link) router.push(item.link)
}

async function markAllRead() {
  try {
    await store.markAllRead()
    toast.success('อ่านการแจ้งเตือนทั้งหมดแล้ว')
  } catch (error) {
    toast.error(error.message)
  }
}
</script>
