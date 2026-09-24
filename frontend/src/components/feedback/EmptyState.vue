<template>
  <div class="flex flex-col items-center justify-center rounded-3xl bg-card px-6 py-14 text-center shadow-sm">
    <span class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100">
      <AppIcon :name="icon" :size="28" weight="duotone" class="text-slate-400" />
    </span>
    <p class="font-semibold text-ink">{{ title }}</p>
    <p v-if="description" class="mt-1 text-sm text-ink-muted">{{ description }}</p>
    <slot />
  </div>
</template>

<script setup>
defineProps({
  icon: { type: String, default: 'tray' },
  title: { type: String, required: true },
  description: { type: String, default: '' },
})
</script>
