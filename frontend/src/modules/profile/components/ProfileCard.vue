<template>
  <section class="overflow-hidden rounded-[28px] bg-card shadow-sm">
    <!-- ส่วนบน: พื้นหลังสีหลัก + รูปโปรไฟล์ -->
    <div class="relative bg-status-checkin px-5 pb-6 pt-6 text-white">
      <span class="pointer-events-none absolute -right-10 -top-12 h-44 w-44 rounded-full bg-white/10" />
      <span class="pointer-events-none absolute -bottom-16 -left-8 h-32 w-32 rounded-full bg-white/10" />

      <div class="relative flex items-center gap-4">
        <img
          v-if="user?.avatarUrl"
          :src="user.avatarUrl"
          :alt="user.name"
          class="h-[72px] w-[72px] shrink-0 rounded-full object-cover ring-4 ring-white/40"
        />
        <span
          v-else
          class="flex h-[72px] w-[72px] shrink-0 items-center justify-center rounded-full bg-card text-2xl font-bold text-status-checkin ring-4 ring-white/40"
        >
          {{ getInitials(user?.name) }}
        </span>

        <div class="min-w-0">
          <p class="truncate text-lg font-bold leading-snug">{{ user?.name }}</p>
          <p class="truncate text-[13px] text-white/85">{{ user?.position }}</p>
          <span
            class="mt-2 inline-flex items-center gap-1 rounded-full bg-white/20 px-2.5 py-0.5 font-display text-xs font-semibold"
          >
            <AppIcon name="id-card" :size="14" />
            {{ user?.employeeCode }}
          </span>
        </div>
      </div>
    </div>

    <!-- ส่วนล่าง: ข้อมูลสรุป 3 ช่อง -->
    <div class="grid grid-cols-3 divide-x divide-slate-100 py-4">
      <div v-for="info in infos" :key="info.label" class="flex flex-col items-center gap-0.5 px-2 text-center">
        <span class="text-xs text-ink-muted">{{ info.label }}</span>
        <span class="text-sm font-semibold text-ink">{{ info.value }}</span>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue'
import { formatTenure, getInitials } from '@/utils/formatters'

const props = defineProps({
  user: { type: Object, default: null },
})

const infos = computed(() => [
  { label: 'แผนก', value: props.user?.department || '-' },
  { label: 'อายุงาน', value: formatTenure(props.user?.startDate) },
  { label: 'เบอร์โทร', value: props.user?.phone || '-' },
])
</script>
