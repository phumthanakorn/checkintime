<template>
  <div class="space-y-5">
    <PageHeader title="การลา" :subtitle="`วันลาคงเหลือปี ${new Date().getFullYear() + 543}`">
      <template #actions>
        <button
          type="button"
          class="flex h-10 items-center gap-1 rounded-full bg-status-checkin px-4 text-sm font-semibold text-white shadow-md shadow-status-checkin/30"
          @click="openForm"
        >
          <v-icon icon="mdi-plus" size="18" />
          ยื่นใบลา
        </button>
      </template>
    </PageHeader>

    <LeaveBalanceList :balances="leave.balances" />

    <section class="space-y-3">
      <h2 class="text-base font-bold text-ink">คำขอลาของฉัน</h2>
      <SegmentedTabs v-model="filter" :options="filterOptions" />

      <LoadingSpinner v-if="leave.loading && !leave.requests.length" text="กำลังโหลด..." />

      <EmptyState
        v-else-if="!filteredRequests.length"
        icon="mdi-calendar-check-outline"
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
  </div>

  <AppBottomSheet v-model="formOpen" title="ยื่นใบลา" :persistent="leave.submitting">
    <LeaveRequestForm ref="formRef" :balances="leave.balances" @submit="submitRequest" />
    <template #footer>
      <button
        type="submit"
        form="leave-request-form"
        class="flex h-12 w-full items-center justify-center gap-2 rounded-2xl bg-status-checkin font-semibold text-white shadow-lg shadow-status-checkin/30 disabled:opacity-70"
        :disabled="leave.submitting"
      >
        <v-progress-circular v-if="leave.submitting" indeterminate size="20" width="2" />
        <template v-else>
          ส่งคำขอลา
          <v-icon icon="mdi-send" size="18" />
        </template>
      </button>
    </template>
  </AppBottomSheet>

  <LeaveDetailSheet v-model="detailOpen" :request="selected" :loading="leave.submitting" @cancel="confirmCancel = true" />

  <ConfirmModal
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
import LoadingSpinner from '@/components/feedback/LoadingSpinner.vue'
import EmptyState from '@/components/feedback/EmptyState.vue'
import LeaveBalanceList from '../components/LeaveBalanceList.vue'
import LeaveRequestItem from '../components/LeaveRequestItem.vue'
import LeaveRequestForm from '../components/LeaveRequestForm.vue'
import LeaveDetailSheet from '../components/LeaveDetailSheet.vue'
import { useLeaveStore } from '@/store'
import { useNotification } from '@/composables/useNotification'
import { LEAVE_STATUS } from '@/utils/constants'

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
  try {
    await leave.fetchAll()
  } catch (error) {
    notify.error(error.message)
  }
})

function openForm() {
  formRef.value?.reset()
  formOpen.value = true
}

function openDetail(request) {
  selected.value = request
  detailOpen.value = true
}

async function submitRequest(payload) {
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
