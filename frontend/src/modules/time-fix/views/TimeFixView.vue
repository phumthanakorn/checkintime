<template>
  <div class="space-y-5">
    <PageHeader title="ขอลงเวลาย้อนหลัง" subtitle="กรณีลืมลงเวลาเข้า-ออกงาน" :back-to="{ name: 'history' }">
      <template #actions>
        <button
          type="button"
          class="flex h-10 items-center gap-1 rounded-full bg-status-checkin px-4 text-sm font-semibold text-white shadow-md shadow-status-checkin/30"
          @click="openForm()"
        >
          <v-icon icon="mdi-plus" size="18" />
          ขอลงเวลา
        </button>
      </template>
    </PageHeader>

    <div class="flex items-start gap-3 rounded-2xl bg-card p-4 shadow-sm">
      <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-violet-50">
        <v-icon icon="mdi-clock-edit-outline" size="20" class="text-violet-500" />
      </span>
      <p class="text-xs leading-relaxed text-ink-muted">
        ลืมกดเข้างานหรือออกงาน? ส่งคำขอพร้อมเวลาจริงและเหตุผล หัวหน้าจะเป็นผู้อนุมัติ
        <span class="font-semibold text-ink">ขอย้อนหลังได้ไม่เกิน {{ TIME_FIX_MAX_DAYS_BACK }} วัน</span>
      </p>
    </div>

    <section class="space-y-3">
      <h2 class="text-base font-bold text-ink">คำขอของฉัน</h2>
      <SegmentedTabs v-model="filter" :options="filterOptions" />

      <LoadingSpinner v-if="timeFix.loading && !timeFix.requests.length" text="กำลังโหลด..." />

      <EmptyState
        v-else-if="!filteredRequests.length"
        icon="mdi-clock-check-outline"
        title="ไม่มีคำขอ"
        description="กด “ขอลงเวลา” เมื่อลืมลงเวลาเข้า-ออกงาน"
      />

      <div v-else class="space-y-2.5">
        <TimeFixItem v-for="request in filteredRequests" :key="request.id" :request="request" @select="openDetail" />
      </div>
    </section>
  </div>

  <AppBottomSheet v-model="formOpen" title="ขอลงเวลาย้อนหลัง" :persistent="timeFix.submitting">
    <!-- key เปลี่ยนทุกครั้งที่เปิด = ฟอร์มใหม่ พร้อมค่าเริ่มต้น -->
    <TimeFixForm :key="formKey" :initial="formInitial" @submit="submitRequest" />
    <template #footer>
      <button
        type="submit"
        form="time-fix-form"
        class="flex h-12 w-full items-center justify-center gap-2 rounded-2xl bg-status-checkin font-semibold text-white shadow-lg shadow-status-checkin/30 disabled:opacity-70"
        :disabled="timeFix.submitting"
      >
        <v-progress-circular v-if="timeFix.submitting" indeterminate size="20" width="2" />
        <template v-else>
          ส่งคำขอ
          <v-icon icon="mdi-send" size="18" />
        </template>
      </button>
    </template>
  </AppBottomSheet>

  <TimeFixDetailSheet
    v-model="detailOpen"
    :request="selected"
    :loading="timeFix.submitting"
    @cancel="confirmCancel = true"
  />

  <ConfirmModal
    v-model="confirmCancel"
    title="ยกเลิกคำขอ"
    message="ต้องการยกเลิกคำขอลงเวลาย้อนหลังนี้ใช่หรือไม่?"
    confirm-text="ยกเลิกคำขอ"
    cancel-text="ไม่ใช่"
    color="error"
    :loading="timeFix.submitting"
    @confirm="cancelRequest"
  />
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import PageHeader from '@/components/layout/PageHeader.vue'
import SegmentedTabs from '@/components/common/SegmentedTabs.vue'
import AppBottomSheet from '@/components/common/AppBottomSheet.vue'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import LoadingSpinner from '@/components/feedback/LoadingSpinner.vue'
import EmptyState from '@/components/feedback/EmptyState.vue'
import TimeFixForm from '../components/TimeFixForm.vue'
import TimeFixItem from '../components/TimeFixItem.vue'
import TimeFixDetailSheet from '../components/TimeFixDetailSheet.vue'
import { useTimeFixStore } from '@/store'
import { useNotification } from '@/composables/useNotification'
import { REQUEST_STATUS, TIME_FIX_MAX_DAYS_BACK, TIME_FIX_TYPES } from '@/utils/constants'

const timeFix = useTimeFixStore()
const notify = useNotification()
const route = useRoute()
const router = useRouter()

const filter = ref('all')
const formOpen = ref(false)
const formKey = ref(0)
const formInitial = ref({})
const detailOpen = ref(false)
const confirmCancel = ref(false)
const selected = ref(null)

const countOf = (status) => timeFix.requests.filter((r) => r.status === status).length

const filterOptions = computed(() => [
  { value: 'all', label: 'ทั้งหมด' },
  { value: REQUEST_STATUS.PENDING, label: 'รออนุมัติ', count: countOf(REQUEST_STATUS.PENDING) },
  { value: REQUEST_STATUS.APPROVED, label: 'อนุมัติ' },
  { value: REQUEST_STATUS.REJECTED, label: 'ไม่อนุมัติ' },
])

const filteredRequests = computed(() =>
  filter.value === 'all' ? timeFix.requests : timeFix.requests.filter((r) => r.status === filter.value),
)

onMounted(async () => {
  try {
    await timeFix.fetchAll()
  } catch (error) {
    notify.error(error.message)
  }

  // มาจากหน้าประวัติ: /time-fix?date=YYYY-MM-DD&type=check_out -> เปิดฟอร์มพร้อมเติมค่า
  const { date, type } = route.query
  if (typeof date === 'string') {
    openForm({ date, fixType: Object.values(TIME_FIX_TYPES).includes(type) ? type : TIME_FIX_TYPES.BOTH })
    router.replace({ query: {} })
  }
})

function openForm(values = {}) {
  formInitial.value = values
  formKey.value++
  formOpen.value = true
}

function openDetail(request) {
  selected.value = request
  detailOpen.value = true
}

async function submitRequest(payload) {
  try {
    await timeFix.createRequest(payload)
    formOpen.value = false
    filter.value = REQUEST_STATUS.PENDING
    notify.success('ส่งคำขอเรียบร้อย รอหัวหน้าอนุมัติ')
  } catch (error) {
    notify.error(error.message)
  }
}

async function cancelRequest() {
  try {
    await timeFix.cancelRequest(selected.value.id)
    confirmCancel.value = false
    detailOpen.value = false
    notify.success('ยกเลิกคำขอแล้ว')
  } catch (error) {
    notify.error(error.message)
  }
}
</script>
