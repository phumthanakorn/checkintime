<template>
  <section class="relative overflow-hidden rounded-[28px] bg-status-checkin p-5 text-white shadow-lg shadow-status-checkin/30">
    <span class="pointer-events-none absolute -right-12 -top-12 h-44 w-44 rounded-full bg-white/10" />
    <span class="pointer-events-none absolute -bottom-16 right-16 h-32 w-32 rounded-full bg-white/10" />

    <div class="relative">
      <div class="flex items-center justify-between">
        <p class="text-[13px] text-white/85">ยอดเงินได้สุทธิ · {{ formatMonth(slip.month) }}</p>
        <button
          type="button"
          class="flex h-8 w-8 items-center justify-center rounded-full bg-white/15"
          :aria-label="hidden ? 'แสดงยอดเงิน' : 'ซ่อนยอดเงิน'"
          @click="hidden = !hidden"
        >
          <AppIcon :name="hidden ? 'eye-slash' : 'eye'" :size="18" />
        </button>
      </div>

      <p class="mt-1 font-display text-4xl font-extrabold tracking-tight tabular-nums">
        <span class="mr-1 text-2xl font-bold opacity-80">฿</span>{{ hidden ? '••••••' : formatMoney(slip.netPay) }}
      </p>

      <div class="mt-4 grid grid-cols-2 gap-2">
        <div class="rounded-2xl bg-white/15 px-3 py-2.5">
          <p class="flex items-center gap-1 text-[11px] text-white/85">
            <AppIcon name="trend-up" :size="14" /> รายได้รวม
          </p>
          <p class="font-display text-base font-bold tabular-nums">{{ hidden ? '••••' : formatMoney(slip.totalEarnings) }}</p>
        </div>
        <div class="rounded-2xl bg-white/15 px-3 py-2.5">
          <p class="flex items-center gap-1 text-[11px] text-white/85">
            <AppIcon name="trend-down" :size="14" /> รายการหัก
          </p>
          <p class="font-display text-base font-bold tabular-nums">{{ hidden ? '••••' : formatMoney(slip.totalDeductions) }}</p>
        </div>
      </div>

      <p class="mt-3 flex items-center gap-1.5 text-[11px] text-white/85">
        <AppIcon name="bank" :size="14" />
        จ่ายวันที่ {{ formatDayMonth(slip.payDate, true) }} · {{ slip.bank.name }}
        <span class="font-display">{{ slip.bank.account }}</span>
      </p>
    </div>
  </section>
</template>

<script setup>
import { formatDayMonth, formatMoney, formatMonth } from '@/utils/formatters'

/** ซ่อน/แสดงยอดเงิน (จำค่าไว้ในเครื่อง) */
const hidden = defineModel('hidden', { type: Boolean, default: false })

defineProps({
  slip: { type: Object, required: true },
})
</script>
