<template>
  <header class="flex items-center gap-3 pt-6">
    <router-link
      v-if="backTo"
      :to="backTo"
      class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-card text-ink no-underline shadow-sm"
      aria-label="ย้อนกลับ"
    >
      <AppIcon name="caret-left" :size="24" />
    </router-link>
    <div class="min-w-0 flex-1">
      <h1 class="truncate text-xl font-bold text-ink">{{ title }}</h1>
      <p v-if="subtitle" class="truncate text-[13px] text-ink-muted">{{ subtitle }}</p>
    </div>
    <slot name="actions" />
  </header>
</template>

<script setup>
defineProps({
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
  /** route ที่จะกลับไป (ไม่ระบุ = ไม่แสดงปุ่มย้อนกลับ) */
  backTo: { type: [String, Object], default: null },
})
</script>
