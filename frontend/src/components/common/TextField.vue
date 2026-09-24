<template>
  <div>
    <label v-if="label" :for="id" class="mb-2 block text-sm font-medium text-ink">
      {{ label }}
      <span v-if="required" class="text-status-outside">*</span>
      <span v-else-if="optional" class="font-normal text-ink-muted">(ไม่บังคับ)</span>
    </label>

    <div class="field" :class="{ 'field--error': error, 'field--readonly': readonly, 'field--multiline': multiline }">
      <AppIcon v-if="icon" :name="icon" :size="20" class="text-slate-400" :class="multiline && 'mt-3'" />
      <textarea
        v-if="multiline"
        :id="id"
        v-model="model"
        :rows="rows"
        :placeholder="placeholder"
        :maxlength="maxlength"
        :readonly="readonly"
      />
      <input
        v-else
        :id="id"
        v-model="model"
        :type="type"
        :placeholder="placeholder"
        :autocomplete="autocomplete"
        :inputmode="inputmode"
        :maxlength="maxlength"
        :readonly="readonly"
      />
      <slot name="append" />
    </div>

    <p v-if="error" class="mt-1 flex items-center gap-1 text-xs text-status-outside">
      <AppIcon name="warning-circle" :size="14" />
      {{ error }}
    </p>
    <p v-else-if="hint" class="mt-1 text-[11px] text-ink-muted">{{ hint }}</p>
  </div>
</template>

<script setup>
import { useId } from 'vue'

const model = defineModel({ type: String, default: '' })

defineProps({
  label: { type: String, default: '' },
  type: { type: String, default: 'text' },
  icon: { type: String, default: '' },
  placeholder: { type: String, default: '' },
  error: { type: String, default: '' },
  hint: { type: String, default: '' },
  required: { type: Boolean, default: false },
  optional: { type: Boolean, default: false },
  readonly: { type: Boolean, default: false },
  multiline: { type: Boolean, default: false },
  rows: { type: Number, default: 3 },
  autocomplete: { type: String, default: undefined },
  inputmode: { type: String, default: undefined },
  maxlength: { type: [Number, String], default: undefined },
})

const id = useId()
</script>

<style scoped>
.field {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  min-height: 3rem;
  padding: 0 1rem;
  border: 1px solid #e2e8f0;
  border-radius: 0.75rem;
  background: #f8faff;
  transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
}

.field--multiline {
  align-items: flex-start;
}

.field:focus-within {
  border-color: #10b981;
  box-shadow: 0 0 0 3px rgb(16 185 129 / 0.15);
  background: #fff;
}

.field--error {
  border-color: #f95738;
}

.field--readonly {
  background: #f1f5f9;
}

.field input,
.field textarea {
  flex: 1;
  min-width: 0;
  outline: none;
  background: transparent;
  font-size: 0.875rem;
  color: #0f172a;
}

.field textarea {
  resize: none;
  padding: 0.75rem 0;
}

.field input::placeholder,
.field textarea::placeholder {
  color: #94a3b8;
}

.field--readonly input {
  color: #64748b;
}
</style>
