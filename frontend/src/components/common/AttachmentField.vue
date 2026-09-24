<template>
  <div>
    <div class="mb-2 flex items-baseline justify-between">
      <span class="text-sm font-medium text-ink">
        {{ label }}
        <span class="font-normal text-ink-muted">(ไม่บังคับ)</span>
      </span>
      <span class="font-display text-xs text-ink-muted">{{ model.length }}/{{ max }}</span>
    </div>

    <div class="grid grid-cols-3 gap-2">
      <!-- ไฟล์ที่แนบแล้ว -->
      <div
        v-for="(file, index) in model"
        :key="file.id"
        class="relative aspect-square overflow-hidden rounded-2xl border border-slate-200 bg-app-bg"
      >
        <img v-if="isImage(file.type)" :src="file.dataUrl" :alt="file.name" class="h-full w-full object-cover" />
        <div v-else class="flex h-full flex-col items-center justify-center gap-1 p-2 text-center">
          <v-icon icon="mdi-file-pdf-box" size="32" class="text-status-outside" />
          <span class="line-clamp-2 break-all text-[10px] leading-tight text-ink">{{ file.name }}</span>
        </div>
        <span class="absolute bottom-1 left-1 rounded-full bg-ink/60 px-1.5 py-0.5 font-display text-[9px] text-white">
          {{ formatFileSize(file.size) }}
        </span>
        <button
          type="button"
          class="absolute right-1 top-1 flex h-6 w-6 items-center justify-center rounded-full bg-ink/70 text-white"
          :aria-label="`ลบไฟล์ ${file.name}`"
          @click="remove(index)"
        >
          <v-icon icon="mdi-close" size="14" />
        </button>
      </div>

      <!-- ปุ่มเพิ่มไฟล์ -->
      <label
        v-if="model.length < max"
        class="flex aspect-square cursor-pointer flex-col items-center justify-center gap-1 rounded-2xl border-2 border-dashed border-slate-200 bg-app-bg text-ink-muted transition hover:border-status-checkin hover:text-status-checkin"
        :class="processing && 'pointer-events-none opacity-60'"
      >
        <v-progress-circular v-if="processing" indeterminate size="22" width="2" />
        <template v-else>
          <v-icon icon="mdi-paperclip" size="24" />
          <span class="text-[11px] font-medium">แนบไฟล์</span>
        </template>
        <input
          ref="inputRef"
          type="file"
          class="sr-only"
          :accept="accept"
          :multiple="max - model.length > 1"
          @change="onSelect"
        />
      </label>
    </div>

    <p class="mt-1.5 text-[11px] text-ink-muted">
      {{ hint || `รูปภาพหรือ PDF ไม่เกิน ${maxSizeMb} MB ต่อไฟล์ · ถ่ายรูปจากกล้องมือถือได้` }}
    </p>
    <p v-if="error" class="mt-1 flex items-center gap-1 text-xs text-status-outside">
      <v-icon icon="mdi-alert-circle-outline" size="14" />
      {{ error }}
    </p>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { compressImage, dataUrlSize, formatFileSize, isImage, readAsDataUrl } from '@/utils/files'

/** [{ id, name, type, size, dataUrl }] */
const model = defineModel({ type: Array, default: () => [] })

const props = defineProps({
  label: { type: String, default: 'แนบไฟล์หลักฐาน' },
  hint: { type: String, default: '' },
  max: { type: Number, default: 3 },
  maxSizeMb: { type: Number, default: 5 },
  accept: { type: String, default: 'image/*,application/pdf' },
})

const inputRef = ref()
const processing = ref(false)
const error = ref('')
let nextId = 0

async function onSelect(event) {
  const files = [...event.target.files].slice(0, props.max - model.value.length)
  event.target.value = '' // เลือกไฟล์เดิมซ้ำได้
  if (!files.length) return

  error.value = ''
  processing.value = true
  const added = []
  try {
    for (const file of files) {
      const allowed = isImage(file.type) || file.type === 'application/pdf'
      if (!allowed) {
        error.value = `ไม่รองรับไฟล์ ${file.name} (รองรับเฉพาะรูปภาพและ PDF)`
        continue
      }
      if (file.size > props.maxSizeMb * 1024 * 1024) {
        error.value = `${file.name} ใหญ่เกิน ${props.maxSizeMb} MB`
        continue
      }
      const dataUrl = isImage(file.type) ? await compressImage(file) : await readAsDataUrl(file)
      added.push({
        id: `${Date.now()}-${nextId++}`,
        name: file.name,
        type: dataUrl.startsWith('data:image/jpeg') ? 'image/jpeg' : file.type,
        size: dataUrlSize(dataUrl),
        dataUrl,
      })
    }
  } catch (e) {
    error.value = e.message
  } finally {
    processing.value = false
  }
  if (added.length) model.value = [...model.value, ...added]
}

function remove(index) {
  model.value = model.value.filter((_, i) => i !== index)
  error.value = ''
}
</script>
