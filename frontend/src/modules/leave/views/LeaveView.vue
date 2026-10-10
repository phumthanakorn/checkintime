<template>
  <div class="space-y-5">
    <PageHeader title="การลา" :subtitle="LEAVE_ENABLED ? `วันลาคงเหลือปี ${new Date().getFullYear() + 543}` : undefined">
      <template v-if="LEAVE_ENABLED" #actions>
        <button
          type="button"
          class="flex h-10 items-center gap-1 rounded-full bg-status-checkin px-4 text-sm font-semibold text-white shadow-md shadow-status-checkin/30"
          @click="openForm"
        >
          <AppIcon name="plus" :size="18" />
          ยื่นใบลา
        </button>
      </template>
    </PageHeader>

    <section v-if="!LEAVE_ENABLED" class="flex min-h-[280px] flex-col items-center justify-center rounded-3xl bg-card px-6 py-10 text-center shadow-sm" role="status">
      <span class="mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-status-checkin/10 text-status-checkin">
        <AppIcon name="calendar-check" :size="32" weight="duotone" />
      </span>
      <h2 class="text-base font-bold text-ink">การลาผ่านแอปยังไม่เปิดใช้งาน</h2>
      <p class="mt-3 text-sm leading-relaxed text-ink-muted">ขณะนี้กรุณายื่นลาผ่านช่องทางเดิม<br />หากมีข้อสงสัย กรุณาติดต่อฝ่ายบุคคล (HR)</p>
      <span class="mt-5 rounded-full bg-slate-100 px-3 py-1.5 text-xs text-ink-muted">รอเปิดให้บริการ</span>
    </section>

    <template v-else>
    <LeaveBalanceList :balances="leave.balances" />

    <section class="space-y-3">
      <h2 class="text-base font-bold text-ink">คำขอลาของฉัน</h2>
      <SegmentedTabs v-model="filter" :options="filterOptions" />

      <LoadingState v-if="leave.loading && !leave.requests.length" />

      <EmptyState
        v-else-if="!filteredRequests.length"
        icon="calendar-check"
        title="ไม่มีคำขอลา"
        description="กด “ยื่นใบลา” เพื่อส่งคำขอใหม่"
      />

      <div v-else class="space-y-2.5">
        <LeaveRequestItem
          v-for="request in filteredRequests"
          :key="request.id"
          :request="request"
          @select="openDetail"
        />
      </div>
    </section>
    </template>
  </div>

  <AppBottomSheet v-if="LEAVE_ENABLED" v-model="formOpen" title="ยื่นใบลา" :persistent="leave.submitting">
    <LeaveRequestForm ref="formRef" :balances="leave.balances" @submit="submitRequest" />
    <template #footer>
      <button
        type="submit"
        form="leave-request-form"
        class="flex h-12 w-full items-center justify-center gap-2 rounded-2xl bg-status-checkin font-semibold text-white shadow-lg shadow-status-checkin/30 disabled:opacity-70"
        :disabled="leave.submitting"
      >
        <LoadingDots v-if="leave.submitting" />
        <template v-else>
          ส่งคำขอลา
          <AppIcon name="send" :size="18" />
        </template>
      </button>
    </template>
  </AppBottomSheet>

  <LeaveDetailSheet v-if="LEAVE_ENABLED" v-model="detailOpen" :request="selected" :loading="leave.submitting" @cancel="confirmCancel = true" />

  <ConfirmModal
    v-if="LEAVE_ENABLED"
    v-model="confirmCancel"
    title="ยกเลิกคำขอลา"
    message="ต้องการยกเลิกคำขอลานี้ใช่หรือไม่?"
    confirm-text="ยกเลิกคำขอ"
    cancel-text="ไม่ใช่"
    color="error"
    :loading="leave.submitting"
    @confirm="cancelRequest"
  />
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import SegmentedTabs from '@/components/common/SegmentedTabs.vue'
import AppBottomSheet from '@/components/common/AppBottomSheet.vue'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import LoadingState from '@/components/feedback/LoadingState.vue'
import EmptyState from '@/components/feedback/EmptyState.vue'
import LeaveBalanceList from '../components/LeaveBalanceList.vue'
import LeaveRequestItem from '../components/LeaveRequestItem.vue'
import LeaveRequestForm from '../components/LeaveRequestForm.vue'
import LeaveDetailSheet from '../components/LeaveDetailSheet.vue'
import { useLeaveStore } from '@/store'
import { useNotification } from '@/composables/useNotification'
import { LEAVE_ENABLED, LEAVE_STATUS } from '@/utils/constants'

const leave = useLeaveStore()
const notify = useNotification()

const filter = ref('all')
const formOpen = ref(false)
const formRef = ref()
const detailOpen = ref(false)
const confirmCancel = ref(false)
const selected = ref(null)

const countOf = (status) => leave.requests.filter((r) => r.status === status).length

const filterOptions = computed(() => [
  { value: 'all', label: 'ทั้งหมด' },
  { value: LEAVE_STATUS.PENDING, label: 'รออนุมัติ', count: countOf(LEAVE_STATUS.PENDING) },
  { value: LEAVE_STATUS.APPROVED, label: 'อนุมัติ' },
  { value: LEAVE_STATUS.REJECTED, label: 'ไม่อนุมัติ' },
])

const filteredRequests = computed(() =>
  filter.value === 'all' ? leave.requests : leave.requests.filter((r) => r.status === filter.value),
)

onMounted(async () => {
  if (!LEAVE_ENABLED) return
  try {
    await leave.fetchAll()
  } catch (error) {
    notify.error(error.message)
  }
})

function openForm() {
  if (!LEAVE_ENABLED) return
  formRef.value?.reset()
  formOpen.value = true
}

function openDetail(request) {
  selected.value = request
  detailOpen.value = true
}

async function submitRequest(payload) {
  if (!LEAVE_ENABLED) return
  try {
    await leave.createRequest(payload)
    formOpen.value = false
    filter.value = LEAVE_STATUS.PENDING
    notify.success('ส่งคำขอลาเรียบร้อย รอหัวหน้าอนุมัติ')
  } catch (error) {
    notify.error(error.message)
  }
}

async function cancelRequest() {
  if (!LEAVE_ENABLED) return
  try {
    await leave.cancelRequest(selected.value.id)
    confirmCancel.value = false
    detailOpen.value = false
    notify.success('ยกเลิกคำขอลาแล้ว')
  } catch (error) {
    notify.error(error.message)
  }
}
</script>
