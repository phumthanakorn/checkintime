<template>
  <Teleport to="body">
    <Transition name="camera-fade">
      <div
        v-if="modelValue"
        class="fixed inset-0 z-[2500] flex flex-col bg-black"
        role="dialog"
        aria-modal="true"
        :aria-label="title"
      >
        <!-- หัว: ปิด + ชื่อหน้าจอ -->
        <div class="flex items-center justify-between px-4 pb-3 pt-[calc(1rem+env(safe-area-inset-top))] text-white">
          <button
            type="button"
            class="flex h-9 w-9 items-center justify-center rounded-full bg-white/15"
            aria-label="ปิด"
            @click="close"
          >
            <AppIcon name="x" :size="20" />
          </button>
          <p class="text-sm font-semibold">{{ title }}</p>
          <span class="w-9" aria-hidden="true" />
        </div>

        <!-- ช่องมองภาพ -->
        <div class="relative flex flex-1 items-center justify-center overflow-hidden">
          <video v-show="!photo && !error" ref="videoEl" class="h-full w-full object-cover" autoplay playsinline muted />
          <img v-if="photo" :src="photo" class="h-full w-full object-cover" alt="ภาพที่ถ่ายไว้" />

          <!-- กรอบวงกลมจัดหน้า (เฉพาะตอนยังไม่ถ่าย) -->
          <div v-if="!photo && !error" class="pointer-events-none absolute inset-0 flex items-center justify-center">
            <div
              class="h-72 w-72 max-w-[72vw] rounded-full border-4 border-white/70"
              style="box-shadow: 0 0 0 2000px rgb(0 0 0 / 0.55)"
            />
          </div>

          <p v-if="error" class="absolute inset-x-6 top-1/2 -translate-y-1/2 rounded-2xl bg-white/10 p-5 text-center text-sm text-white">
            {{ error }}
          </p>
        </div>

        <!-- ปุ่มควบคุม -->
        <div class="flex items-center justify-center gap-5 px-6 pb-[calc(1.75rem+env(safe-area-inset-bottom))] pt-5">
          <template v-if="!photo && !error">
            <button type="button" class="shutter-btn" :disabled="!ready" aria-label="ถ่ายภาพ" @click="capture">
              <span class="shutter-btn__ring" />
            </button>
          </template>
          <template v-else-if="photo">
            <button
              type="button"
              class="flex items-center gap-2 rounded-full bg-white/15 px-5 py-3 text-sm font-semibold text-white active:bg-white/25"
              @click="retake"
            >
              <AppIcon name="reset" :size="18" />
              ถ่ายใหม่
            </button>
            <button
              type="button"
              class="flex items-center gap-2 rounded-full bg-status-checkin px-6 py-3 text-sm font-semibold text-white active:scale-[0.97]"
              @click="confirm"
            >
              <AppIcon name="check" :size="18" />
              ยืนยัน
            </button>
          </template>
          <template v-else>
            <button
              type="button"
              class="flex items-center gap-2 rounded-full bg-white/15 px-5 py-3 text-sm font-semibold text-white active:bg-white/25"
              @click="startCamera"
            >
              <AppIcon name="reset" :size="18" />
              ลองอีกครั้ง
            </button>
          </template>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
/**
 * ถ่ายเซลฟี่ยืนยันตัวตนก่อนลงเวลา — เปิดกล้องหน้า (facingMode: 'user') ในหน้าจอเต็ม ให้ถ่าย/ถ่ายใหม่/ยืนยัน
 * ก่อน emit('confirm', photoDataUrl) กลับไปให้ผู้เรียกไปแนบกับ check-in/check-out จริง — ตัวนี้แค่จัดการ
 * กล้อง+ภาพเท่านั้น ไม่ยุ่งกับ logic ลงเวลาเลย
 */
import { nextTick, onBeforeUnmount, ref, watch } from 'vue'

const modelValue = defineModel({ type: Boolean, default: false })
defineProps({
  title: { type: String, default: 'ถ่ายภาพยืนยันตัวตน' },
})
const emit = defineEmits(['confirm'])

const videoEl = ref(null)
const photo = ref(null)
const ready = ref(false)
const error = ref(null)
let stream = null

async function startCamera() {
  error.value = null
  photo.value = null
  ready.value = false
  try {
    stream = await navigator.mediaDevices.getUserMedia({
      // ไม่กำหนด width/height ไว้เลย — ให้กล้องแต่ละเครื่องให้ความละเอียดเริ่มต้นของตัวเองมา (เหมือนแอปกล้อง
      // ปกติ) ไม่บีบให้ต่ำลงเทียมๆ ตามที่ตกลงกัน
      video: { facingMode: 'user' },
      audio: false,
    })
    await nextTick()
    if (!videoEl.value) return
    videoEl.value.srcObject = stream
    ready.value = true
  } catch (e) {
    error.value =
      e?.name === 'NotAllowedError'
        ? 'ไม่ได้รับอนุญาตให้ใช้กล้อง — กรุณาอนุญาตการใช้กล้องในเบราว์เซอร์แล้วลองใหม่'
        : 'เปิดกล้องไม่สำเร็จ กรุณาลองใหม่อีกครั้ง'
  }
}

function stopCamera() {
  stream?.getTracks().forEach((track) => track.stop())
  stream = null
  ready.value = false
}

// พรีวิว (วิดีโอตอนเปิดกล้อง) ใช้ความละเอียดเต็มของเครื่องเสมอ ไม่แตะ — แต่ "ภาพที่บันทึกจริง" ถูกจำกัดด้าน
// ยาวสุดไว้ที่ค่านี้ กันไฟล์ใหญ่เกินจำเป็น (กล้องมือถือสมัยนี้ถ่ายได้หลาย MP ทั้งที่แค่ต้องดูออกว่าเป็นใคร) ถ้า
// กล้องเครื่องไหนความละเอียดต่ำกว่านี้อยู่แล้วก็ไม่ upscale ขึ้นมา (ใช้ Math.min)
const MAX_SAVE_SIZE = 720
const JPEG_QUALITY = 0.8

function capture() {
  if (!videoEl.value) return
  // ครอปเป็นสี่เหลี่ยมจัตุรัสตรงกลางจาก video ตามความละเอียดจริงของกล้องเครื่องนั้นก่อน แล้วค่อย resize ลงมาที่
  // MAX_SAVE_SIZE ในขั้นตอนเดียวกันผ่าน drawImage (destination size ต่างจาก source size ได้)
  const cropSize = Math.min(videoEl.value.videoWidth, videoEl.value.videoHeight)
  const sx = (videoEl.value.videoWidth - cropSize) / 2
  const sy = (videoEl.value.videoHeight - cropSize) / 2
  const outputSize = Math.min(cropSize, MAX_SAVE_SIZE)

  const canvas = document.createElement('canvas')
  canvas.width = outputSize
  canvas.height = outputSize
  const ctx = canvas.getContext('2d')
  ctx.translate(outputSize, 0)
  ctx.scale(-1, 1) // กระจกภาพกลับด้าน ให้ตรงกับที่เห็นบนจอ (preview ถูก mirror ด้วย CSS ไม่ได้ ก็เลย flip ตรงนี้แทน)
  ctx.drawImage(videoEl.value, sx, sy, cropSize, cropSize, 0, 0, outputSize, outputSize)
  photo.value = canvas.toDataURL('image/jpeg', JPEG_QUALITY)
  stopCamera() // หยุดกล้องทันทีหลังถ่าย ประหยัดแบตระหว่างดูภาพ/ตัดสินใจ
}

function retake() {
  startCamera()
}

function confirm() {
  emit('confirm', photo.value)
  modelValue.value = false
}

function close() {
  modelValue.value = false
}

watch(modelValue, (open) => {
  if (open) startCamera()
  else stopCamera()
})

onBeforeUnmount(stopCamera)
</script>

<style scoped>
video {
  transform: scaleX(-1); /* พรีวิวกระจก ให้เหมือนส่องกระจกตอนถ่ายเซลฟี่ */
}

.shutter-btn {
  display: flex;
  height: 72px;
  width: 72px;
  align-items: center;
  justify-content: center;
  border-radius: 9999px;
  border: 4px solid rgb(255 255 255 / 0.85);
  background: transparent;
  transition: transform 0.1s ease;
}
.shutter-btn:active {
  transform: scale(0.92);
}
.shutter-btn:disabled {
  opacity: 0.4;
}
.shutter-btn__ring {
  height: 56px;
  width: 56px;
  border-radius: 9999px;
  background: #fff;
}

.camera-fade-enter-active,
.camera-fade-leave-active {
  transition: opacity 0.2s ease;
}
.camera-fade-enter-from,
.camera-fade-leave-to {
  opacity: 0;
}

@media (prefers-reduced-motion: reduce) {
  .camera-fade-enter-active,
  .camera-fade-leave-active {
    transition-duration: 0.01s;
  }
}
</style>
