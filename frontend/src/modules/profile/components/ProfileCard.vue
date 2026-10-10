<template>
  <section class="overflow-hidden rounded-[28px] bg-card shadow-sm">
    <!-- ส่วนบน: พื้นหลังสีหลัก + รูปโปรไฟล์ -->
    <div class="relative bg-status-checkin px-5 pb-6 pt-6 text-white">
      <span class="pointer-events-none absolute -right-10 -top-12 h-44 w-44 rounded-full bg-white/10" />
      <span class="pointer-events-none absolute -bottom-16 -left-8 h-32 w-32 rounded-full bg-white/10" />

      <div class="relative flex items-center gap-4">
        <EmployeeAvatar :src="user?.avatarUrl" :name="user?.name || ''" :size="72" class="ring-4 ring-white/40" />

        <div class="min-w-0">
          <p class="truncate text-lg font-bold leading-snug">{{ user?.name }}</p>
          <p v-if="user?.employeeCode" class="mt-0.5 flex items-center gap-1 font-display text-xs text-white/70">
            <AppIcon name="id-card" :size="13" />
            {{ user.employeeCode }}
          </p>
          <span
            v-if="user?.position"
            class="mt-2 inline-flex items-center gap-1 rounded-full bg-white/20 px-2.5 py-0.5 font-display text-xs font-semibold"
          >
            <AppIcon name="briefcase" :size="14" />
            {{ user.position }}
          </span>
        </div>
      </div>
    </div>

    <!-- ส่วนล่าง: ข้อมูลสรุปแบบรายการ (เดิมเป็น grid แบ่งคอลัมน์ — ชื่อแผนกยาวแล้วถูกบีบจนดูรก เปลี่ยนเป็น
         รายการเต็มความกว้างแทน อ่านง่ายกว่าไม่ว่าชื่อแผนก/หน่วยงานจะยาวแค่ไหน) เอาเบอร์โทรออก ไปอยู่หน้า
         "ข้อมูลส่วนตัว" แทน — แถว "หน่วยงาน" แสดงเฉพาะคนที่มี unit สังกัดจริง (office staff บางคนไม่มี) -->
    <div class="divide-y divide-slate-100 px-5 py-1">
      <div v-for="info in infos" :key="info.label" class="flex items-start gap-3 py-3">
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-status-checkin/10 text-status-checkin">
          <AppIcon :name="info.icon" :size="16" />
        </span>
        <div class="min-w-0 flex-1">
          <p class="text-xs text-ink-muted">{{ info.label }}</p>
          <p class="text-sm font-semibold leading-snug text-ink">{{ info.value }}</p>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import EmployeeAvatar from '@/components/common/EmployeeAvatar.vue'
import { computed } from 'vue'
import { formatTenure } from '@/utils/formatters'

const props = defineProps({
  user: { type: Object, default: null },
})


const infos = computed(() => {
  const list = [{ label: 'แผนก', value: props.user?.department || '-', icon: 'bank' }]
  if (props.user?.unit) {
    list.push({ label: 'หน่วยงาน', value: props.user.unit, icon: 'users' })
  }
  list.push({ label: 'อายุงาน', value: formatTenure(props.user?.startDate), icon: 'calendar' })
  return list
})
</script>
