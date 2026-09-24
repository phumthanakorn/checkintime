<template>
  <v-bottom-sheet v-model="open" :persistent="persistent" scrollable>
    <div class="mx-auto flex max-h-[88vh] w-full max-w-md flex-col rounded-t-[28px] bg-card">
      <!-- แถบจับด้านบน -->
      <div class="flex justify-center pt-3">
        <span class="h-1.5 w-10 rounded-full bg-slate-200" />
      </div>

      <div class="flex items-center justify-between px-5 pb-2 pt-3">
        <h2 class="text-base font-bold text-ink">{{ title }}</h2>
        <button
          type="button"
          class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-ink-muted"
          aria-label="ปิด"
          @click="open = false"
        >
          <v-icon icon="mdi-close" size="18" />
        </button>
      </div>

      <div class="flex-1 overflow-y-auto px-5 pb-4">
        <slot />
      </div>

      <div v-if="$slots.footer" class="border-t border-slate-100 px-5 pb-[calc(1rem+env(safe-area-inset-bottom))] pt-3">
        <slot name="footer" />
      </div>
    </div>
  </v-bottom-sheet>
</template>

<script setup>
const open = defineModel({ type: Boolean, default: false })

defineProps({
  title: { type: String, required: true },
  persistent: { type: Boolean, default: false },
})
</script>
