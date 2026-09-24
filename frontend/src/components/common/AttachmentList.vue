<template>
  <div v-if="files?.length">
    <p class="mb-2 flex items-center gap-1 text-xs font-medium text-ink-muted">
      <AppIcon name="paperclip" :size="14" />
      ไฟล์แนบ {{ files.length }} ไฟล์
    </p>
    <div class="grid grid-cols-4 gap-2">
      <button
        v-for="file in files"
        :key="file.id"
        type="button"
        class="aspect-square overflow-hidden rounded-xl border border-slate-200 bg-app-bg"
        :aria-label="`เปิดไฟล์ ${file.name}`"
        @click="open(file)"
      >
        <img v-if="isImage(file.type)" :src="file.dataUrl" :alt="file.name" class="h-full w-full object-cover" />
        <span v-else class="flex h-full flex-col items-center justify-center gap-0.5 p-1">
          <AppIcon name="file-pdf" :size="26" class="text-status-outside" />
          <span class="line-clamp-1 break-all text-[9px] text-ink">{{ file.name }}</span>
        </span>
      </button>
    </div>
  </div>

  <!-- ดูรูปเต็มจอ -->
  <v-dialog v-model="previewOpen" fullscreen transition="fade-transition">
    <div class="relative flex h-full items-center justify-center bg-ink/95 p-4" @click="previewOpen = false">
      <img v-if="preview" :src="preview.dataUrl" :alt="preview.name" class="max-h-full max-w-full rounded-lg object-contain" />
      <button
        type="button"
        class="absolute right-4 top-4 flex h-10 w-10 items-center justify-center rounded-full bg-white/15 text-white"
        aria-label="ปิด"
        @click="previewOpen = false"
      >
        <AppIcon name="x" />
      </button>
      <p v-if="preview" class="absolute inset-x-0 bottom-6 text-center text-xs text-white/70">{{ preview.name }}</p>
    </div>
  </v-dialog>
</template>

<script setup>
import { ref } from 'vue'
import { isImage, openDataUrl } from '@/utils/files'

defineProps({
  /** [{ id, name, type, size, dataUrl }] */
  files: { type: Array, default: () => [] },
})

const previewOpen = ref(false)
const preview = ref(null)

function open(file) {
  if (isImage(file.type)) {
    preview.value = file
    previewOpen.value = true
  } else {
    openDataUrl(file.dataUrl)
  }
}
</script>
