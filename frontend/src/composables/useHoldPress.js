import { onBeforeUnmount, ref } from 'vue'

/**
 * กดค้างเพื่อยืนยัน: เรียก onComplete เมื่อกดค้างครบ duration ms
 * ปล่อยก่อนครบจะยกเลิก คืน progress (0–1) ไว้ใช้วาดวงแหวน
 */
export function useHoldPress(onComplete, { duration = 1000, vibrate = 40 } = {}) {
  const progress = ref(0)
  const holding = ref(false)
  let frame = null
  let startedAt = 0

  function tick(now) {
    progress.value = Math.min(1, (now - startedAt) / duration)
    if (progress.value < 1) {
      frame = requestAnimationFrame(tick)
      return
    }
    frame = null
    holding.value = false
    try {
      navigator.vibrate?.(vibrate) // Android สั่นเบา ๆ (iOS ไม่รองรับ)
    } catch {
      // ไม่รองรับก็ข้ามไป
    }
    onComplete()
  }

  function begin() {
    if (holding.value) return
    holding.value = true
    startedAt = performance.now()
    frame = requestAnimationFrame(tick)
  }

  function cancel() {
    if (frame) cancelAnimationFrame(frame)
    frame = null
    if (holding.value) {
      holding.value = false
      progress.value = 0
    }
  }

  function reset() {
    cancel()
    progress.value = 0
  }

  onBeforeUnmount(cancel)

  return { progress, holding, begin, cancel, reset }
}
