<template>
  <div v-if="files?.length">
    <p class="mb-2 flex items-center gap-1 text-xs font-medium text-ink-muted">
      <AppIcon name="paperclip" :size="14" />ไฟล์แนบ {{ files.length }} ไฟล์
    </p>
    <button v-for="file in files" :key="file.id" type="button"
      class="flex w-full items-center gap-3 rounded-xl border border-slate-200 bg-app-bg p-3 text-left disabled:opacity-60"
      :disabled="loading" :aria-label="`เปิดไฟล์ ${file.name}`" @click="openFile(file)">
      <AppIcon :name="file.type?.startsWith('image/') ? 'image' : 'file-pdf'" :size="26" class="shrink-0 text-status-checkin" />
      <span class="min-w-0 flex-1">
        <span class="block truncate text-sm text-ink">{{ file.name }}</span>
        <span class="text-xs text-ink-muted">{{ formatFileSize(file.size) }} · {{ loading ? 'กำลังโหลด…' : 'กดเพื่อเปิดหลักฐาน' }}</span>
      </span>
      <AppIcon name="arrow-square-out" :size="18" />
    </button>
    <p v-if="error" role="alert" class="mt-2 text-xs text-status-outside">{{ error }}</p>
  </div>

  <v-dialog v-model="previewOpen" max-width="900" @after-leave="clearPreview">
    <div class="rounded-2xl bg-card p-4">
      <div class="mb-3 flex items-center justify-between gap-3">
        <p class="min-w-0 truncate text-sm font-semibold text-ink">{{ preview?.name }}</p>
        <button type="button" aria-label="ปิดไฟล์แนบ" @click="previewOpen = false"><AppIcon name="x" :size="24" /></button>
      </div>
      <img v-if="preview?.type?.startsWith('image/')" :src="previewUrl" :alt="preview.name" class="max-h-[75vh] w-full rounded-lg object-contain" />
      <p v-else class="mb-3 text-sm text-ink-muted">ไฟล์ PDF พร้อมเปิดดูหรือดาวน์โหลด</p>
      <div class="mt-3 flex flex-wrap gap-3">
        <a v-if="previewUrl" :href="previewUrl" :download="preview?.name" class="rounded-xl bg-status-checkin px-4 py-2 text-sm font-semibold text-white">ดาวน์โหลดไฟล์</a>
        <a v-if="preview?.type === 'application/pdf'" :href="previewUrl" target="_blank" rel="noopener noreferrer" class="rounded-xl border border-slate-200 px-4 py-2 text-sm text-ink">เปิด PDF</a>
      </div>
    </div>
  </v-dialog>
</template>

<script setup>
import { onBeforeUnmount, ref, watch } from 'vue'
import { timeFixService } from '@/api/services/timeFixService'
import { formatFileSize } from '@/utils/files'

const props = defineProps({
  requestId: { type: [Number, String], required: true },
  files: { type: Array, default: () => [] },
})
const loading = ref(false)
const error = ref('')
const previewOpen = ref(false)
const preview = ref(null)
const previewUrl = ref('')
let ownedUrl = ''
let generation = 0

function clearPreview() {
  if (ownedUrl) URL.revokeObjectURL(ownedUrl)
  ownedUrl = ''
  previewUrl.value = ''
  preview.value = null
}

async function openFile(file) {
  const current = ++generation
  loading.value = true
  error.value = ''
  clearPreview()
  try {
    const url = URL.createObjectURL(await timeFixService.getAttachment(props.requestId, file.id))
    if (current !== generation) {
      URL.revokeObjectURL(url)
      return
    }
    ownedUrl = url
    previewUrl.value = url
    preview.value = file
    previewOpen.value = true
  } catch (e) {
    if (current === generation) error.value = e.message || 'ไม่สามารถเปิดไฟล์แนบได้'
  } finally {
    if (current === generation) loading.value = false
  }
}

watch(() => props.requestId, () => {
  generation++
  loading.value = false
  error.value = ''
  previewOpen.value = false
  clearPreview()
})
onBeforeUnmount(() => { generation++; clearPreview() })
</script>
