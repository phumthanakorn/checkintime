<template>
  <section>
    <h2 class="mb-2 px-1 text-[13px] font-semibold text-ink-muted">{{ title }}</h2>
    <div class="divide-y divide-slate-100 overflow-hidden rounded-2xl bg-card shadow-sm">
      <component
        :is="item.switch === undefined ? 'button' : 'label'"
        v-for="item in items"
        :key="item.key"
        :type="item.switch === undefined ? 'button' : undefined"
        class="flex w-full cursor-pointer items-center gap-3 px-4 py-3.5 text-left transition active:bg-slate-50"
        @click="item.switch === undefined && emit('select', item.key)"
      >
        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl" :class="item.bg">
          <v-icon :icon="item.icon" size="20" :class="item.color" />
        </span>

        <span class="min-w-0 flex-1">
          <span class="block text-sm font-medium text-ink">{{ item.label }}</span>
          <span v-if="item.description" class="block truncate text-xs text-ink-muted">{{ item.description }}</span>
        </span>

        <span v-if="item.value" class="font-display text-[13px] font-semibold text-ink-muted">{{ item.value }}</span>

        <!-- สวิตช์เปิด/ปิด -->
        <span v-if="item.switch !== undefined" class="relative inline-flex">
          <input
            type="checkbox"
            class="peer sr-only"
            :checked="item.switch"
            @change="emit('toggle', item.key, $event.target.checked)"
          />
          <span class="h-6 w-11 rounded-full bg-slate-200 transition peer-checked:bg-status-checkin" />
          <span
            class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition peer-checked:translate-x-5"
          />
        </span>
        <v-icon v-else icon="mdi-chevron-right" size="20" class="text-slate-300" />
      </component>
    </div>
  </section>
</template>

<script setup>
/**
 * item: {
 *   key, label, icon, bg, color,
 *   description?: string,  ข้อความรองใต้ชื่อเมนู
 *   value?: string,        ค่าที่แสดงด้านขวา
 *   switch?: boolean,      ถ้ากำหนด จะแสดงเป็นสวิตช์แทนลูกศร
 * }
 */
defineProps({
  title: { type: String, required: true },
  items: { type: Array, required: true },
})

const emit = defineEmits(['select', 'toggle'])
</script>
