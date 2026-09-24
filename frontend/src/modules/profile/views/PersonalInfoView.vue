<template>
  <div class="space-y-5">
    <PageHeader title="ข้อมูลส่วนตัว" :back-to="{ name: 'profile' }" />

    <!-- รูปโปรไฟล์ -->
    <section class="flex flex-col items-center">
      <div class="relative">
        <img
          v-if="form.avatarUrl"
          :src="form.avatarUrl"
          alt="รูปโปรไฟล์"
          class="h-24 w-24 rounded-full object-cover ring-4 ring-card shadow-md"
        />
        <span
          v-else
          class="flex h-24 w-24 items-center justify-center rounded-full bg-status-checkin/10 text-3xl font-bold text-status-checkin ring-4 ring-card shadow-md"
        >
          {{ getInitials(user?.name) }}
        </span>
        <label
          class="absolute -bottom-1 -right-1 flex h-9 w-9 cursor-pointer items-center justify-center rounded-full bg-status-checkin text-white shadow-md ring-2 ring-card"
          aria-label="เปลี่ยนรูปโปรไฟล์"
        >
          <LoadingDots v-if="processingAvatar" size="sm" />
          <AppIcon v-else name="camera" :size="18" weight="fill" />
          <input type="file" accept="image/*" class="sr-only" @change="onAvatarSelect" />
        </label>
      </div>
      <button
        v-if="form.avatarUrl"
        type="button"
        class="mt-3 text-xs font-medium text-status-outside"
        @click="form.avatarUrl = null"
      >
        ลบรูปโปรไฟล์
      </button>
    </section>

    <!-- ข้อมูลพนักงาน (แก้ไขโดย HR) -->
    <section class="rounded-3xl bg-card p-4 shadow-sm">
      <h2 class="mb-1 flex items-center justify-between text-base font-bold text-ink">
        ข้อมูลพนักงาน
        <span class="flex items-center gap-1 text-[11px] font-normal text-ink-muted">
          <AppIcon name="lock" :size="13" /> แก้ไขได้โดยฝ่ายบุคคล
        </span>
      </h2>
      <dl class="divide-y divide-slate-100">
        <div v-for="row in employeeRows" :key="row.label" class="flex justify-between gap-4 py-2.5 text-sm">
          <dt class="text-ink-muted">{{ row.label }}</dt>
          <dd class="text-right font-medium text-ink" :class="row.display && 'font-display'">{{ row.value }}</dd>
        </div>
      </dl>
    </section>

    <!-- ข้อมูลติดต่อ -->
    <section class="space-y-4 rounded-3xl bg-card p-4 shadow-sm">
      <h2 class="text-base font-bold text-ink">ข้อมูลติดต่อ</h2>
      <TextField
        v-model="form.phone"
        label="เบอร์โทรศัพท์"
        icon="phone"
        inputmode="tel"
        placeholder="08x-xxx-xxxx"
        required
        :error="errors.phone"
      />
      <TextField
        v-model="form.email"
        label="อีเมล"
        icon="envelope"
        inputmode="email"
        placeholder="name@company.com"
        :error="errors.email"
      />
      <TextField v-model="form.address" label="ที่อยู่ปัจจุบัน" icon="address" multiline :rows="2" optional />
    </section>

    <!-- ผู้ติดต่อฉุกเฉิน -->
    <section class="space-y-4 rounded-3xl bg-card p-4 shadow-sm">
      <h2 class="flex items-center gap-2 text-base font-bold text-ink">
        ผู้ติดต่อกรณีฉุกเฉิน
      </h2>
      <TextField v-model="form.emergencyName" label="ชื่อ-สกุล" icon="user" optional />
      <div class="grid grid-cols-2 gap-3">
        <TextField v-model="form.emergencyRelation" label="ความสัมพันธ์" icon="users" placeholder="เช่น มารดา" />
        <TextField
          v-model="form.emergencyPhone"
          label="เบอร์โทร"
          icon="phone"
          inputmode="tel"
          :error="errors.emergencyPhone"
        />
      </div>
    </section>

    <button
      type="button"
      class="flex h-12 w-full items-center justify-center gap-2 rounded-2xl bg-status-checkin font-semibold text-white shadow-lg shadow-status-checkin/30 transition disabled:opacity-50 disabled:shadow-none"
      :disabled="!dirty || saving"
      @click="save"
    >
      <LoadingDots v-if="saving" />
      <template v-else>บันทึกการเปลี่ยนแปลง</template>
    </button>
  </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import TextField from '@/components/common/TextField.vue'
import { useAuthStore } from '@/store'
import { useNotification } from '@/composables/useNotification'
import { compressImage } from '@/utils/files'
import { formatDayMonth, formatTenure, getInitials } from '@/utils/formatters'
import { isEmail, isPhone } from '@/utils/validators'

const auth = useAuthStore()
const notify = useNotification()

const user = computed(() => auth.user)
const saving = ref(false)
const processingAvatar = ref(false)
const errors = reactive({ phone: '', email: '', emergencyPhone: '' })

const fromUser = (u) => ({
  avatarUrl: u?.avatarUrl ?? null,
  phone: u?.phone ?? '',
  email: u?.email ?? '',
  address: u?.address ?? '',
  emergencyName: u?.emergencyContact?.name ?? '',
  emergencyRelation: u?.emergencyContact?.relation ?? '',
  emergencyPhone: u?.emergencyContact?.phone ?? '',
})

const original = ref(fromUser(user.value))
const form = reactive({ ...original.value })
const dirty = computed(() => Object.keys(form).some((k) => form[k] !== original.value[k]))

const employeeRows = computed(() => [
  { label: 'ชื่อ-สกุล', value: user.value?.name },
  { label: 'รหัสพนักงาน', value: user.value?.employeeCode, display: true },
  { label: 'ตำแหน่ง', value: user.value?.position },
  { label: 'แผนก', value: user.value?.department || '-' },
  {
    label: 'วันเริ่มงาน',
    value: user.value?.startDate
      ? `${formatDayMonth(user.value.startDate, true)} (${formatTenure(user.value.startDate)})`
      : '-',
  },
])

async function onAvatarSelect(event) {
  const file = event.target.files[0]
  event.target.value = ''
  if (!file) return
  if (!file.type.startsWith('image/')) {
    notify.error('กรุณาเลือกไฟล์รูปภาพ')
    return
  }
  processingAvatar.value = true
  try {
    form.avatarUrl = await compressImage(file, { maxSide: 400, quality: 0.85 })
  } catch (error) {
    notify.error(error.message)
  } finally {
    processingAvatar.value = false
  }
}

function validate() {
  errors.phone = !form.phone.trim() ? 'กรุณากรอกเบอร์โทรศัพท์' : isPhone(form.phone) ? '' : 'รูปแบบเบอร์โทรไม่ถูกต้อง'
  errors.email = form.email && !isEmail(form.email) ? 'รูปแบบอีเมลไม่ถูกต้อง' : ''
  errors.emergencyPhone = form.emergencyPhone && !isPhone(form.emergencyPhone) ? 'รูปแบบเบอร์โทรไม่ถูกต้อง' : ''
  return !errors.phone && !errors.email && !errors.emergencyPhone
}

async function save() {
  if (!validate()) return
  saving.value = true
  try {
    const updated = await auth.updateProfile({
      phone: form.phone,
      email: form.email,
      address: form.address,
      avatarUrl: form.avatarUrl,
      emergencyContact: {
        name: form.emergencyName.trim(),
        relation: form.emergencyRelation.trim(),
        phone: form.emergencyPhone.trim(),
      },
    })
    original.value = fromUser(updated)
    Object.assign(form, original.value)
    notify.success('บันทึกข้อมูลเรียบร้อย')
  } catch (error) {
    notify.error(error.message)
  } finally {
    saving.value = false
  }
}
</script>
