/** 1536000 -> '1.5 MB' */
export function formatFileSize(bytes = 0) {
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${Math.round(bytes / 1024)} KB`
  return `${(bytes / 1024 / 1024).toFixed(1)} MB`
}

export const isImage = (type = '') => type.startsWith('image/')

export function readAsDataUrl(file) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader()
    reader.onload = () => resolve(reader.result)
    reader.onerror = () => reject(new Error('อ่านไฟล์ไม่สำเร็จ'))
    reader.readAsDataURL(file)
  })
}

/**
 * ย่อรูปให้ด้านยาวไม่เกิน maxSide px แล้วแปลงเป็น JPEG
 * (รูปจากกล้องมือถือมักใหญ่หลาย MB ย่อแล้วเหลือราว 100–300 KB)
 */
export async function compressImage(file, { maxSide = 1600, quality = 0.8 } = {}) {
  const dataUrl = await readAsDataUrl(file)
  const image = await new Promise((resolve, reject) => {
    const img = new Image()
    img.onload = () => resolve(img)
    img.onerror = () => reject(new Error('ไฟล์รูปภาพไม่ถูกต้อง'))
    img.src = dataUrl
  })

  const scale = Math.min(1, maxSide / Math.max(image.width, image.height))
  const canvas = document.createElement('canvas')
  canvas.width = Math.round(image.width * scale)
  canvas.height = Math.round(image.height * scale)
  const ctx = canvas.getContext('2d')
  ctx.fillStyle = '#fff' // รูป PNG โปร่งใส -> พื้นขาว
  ctx.fillRect(0, 0, canvas.width, canvas.height)
  ctx.drawImage(image, 0, 0, canvas.width, canvas.height)

  const compressed = canvas.toDataURL('image/jpeg', quality)
  // ถ้าย่อแล้วใหญ่กว่าเดิม (รูปเล็กอยู่แล้ว) ใช้ไฟล์เดิม
  return compressed.length < dataUrl.length ? compressed : dataUrl
}

/** ขนาดจริง (bytes) ของ data URL แบบ base64 */
export function dataUrlSize(dataUrl) {
  const base64 = dataUrl.split(',')[1] || ''
  return Math.floor((base64.length * 3) / 4) - (base64.endsWith('==') ? 2 : base64.endsWith('=') ? 1 : 0)
}

/** เปิดไฟล์ (data URL) ในแท็บใหม่ เช่น PDF */
export async function openDataUrl(dataUrl) {
  const blob = await (await fetch(dataUrl)).blob()
  const url = URL.createObjectURL(blob)
  window.open(url, '_blank', 'noopener')
  setTimeout(() => URL.revokeObjectURL(url), 60_000)
}
