// เข้าสู่ระบบด้วย Google Identity Services (GSI) — โหลด script ของ Google แบบ lazy (แค่ตอนเปิดหน้า login
// จริงๆ ไม่ต้องโหลดทุกหน้า) แล้ว render "ปุ่มจริงของ Google" (บังคับตามเงื่อนไขการใช้งานของ Google ต้องใช้
// ปุ่มที่ Google render เอง ปุ่ม custom ของเราเองกดไม่ได้โดยตรง) ซ้อนทับปุ่มสไตล์ของเราแบบโปร่งใส
// (opacity: 0) ผู้ใช้เห็นแค่ปุ่มของเรา แต่คลิกจริงไปโดนปุ่มของ Google ข้างใต้ — เทคนิคมาตรฐานที่ Google เอง
// แนะนำไว้สำหรับเคสที่อยากคุม UI เองแต่ยังใช้ปุ่ม sign-in ที่ compliant อยู่
import { ref } from 'vue'

const GSI_SCRIPT_SRC = 'https://accounts.google.com/gsi/client'
let scriptPromise = null

function loadGsiScript() {
  if (window.google?.accounts?.id) return Promise.resolve()
  if (scriptPromise) return scriptPromise
  scriptPromise = new Promise((resolve, reject) => {
    const script = document.createElement('script')
    script.src = GSI_SCRIPT_SRC
    script.async = true
    script.defer = true
    script.onload = () => resolve()
    script.onerror = () => reject(new Error('โหลดสคริปต์ Google ไม่สำเร็จ'))
    document.head.appendChild(script)
  })
  return scriptPromise
}

export function useGoogleSignIn() {
  const ready = ref(false)
  const error = ref('')

  /**
   * โหลด GSI แล้วซ่อนปุ่มจริงของ Google ไว้ใน overlayEl (ต้องเป็น div ว่างที่ครอบปุ่ม custom ของเราพอดี
   * ด้วย CSS: position:absolute; inset:0; opacity:0 — ปุ่ม custom ที่มองเห็นต้องมี pointer-events:none
   * เพื่อให้คลิกทะลุไปโดนปุ่มจริงข้างใต้)
   * @param {HTMLElement} overlayEl
   * @param {(credential: string) => void} onCredential - เรียกเมื่อผู้ใช้เลือกบัญชี Google สำเร็จ
   */
  async function mount(overlayEl, onCredential) {
    const clientId = import.meta.env.VITE_GOOGLE_CLIENT_ID
    if (!clientId) {
      error.value = 'ยังไม่ได้ตั้งค่า VITE_GOOGLE_CLIENT_ID'
      return
    }
    try {
      await loadGsiScript()
      window.google.accounts.id.initialize({
        client_id: clientId,
        callback: (response) => onCredential(response.credential),
        // ยังไม่ login มาก่อน เลยยังไม่รู้ว่าอีเมลนี้มีบัญชีในระบบไหม — ให้ backend เป็นคนตัดสิน ไม่กรอง
        // ด้วย client_id เดียวจำกัดโดเมนอีเมลที่นี่ (ระบบนี้รองรับหลายโดเมนอีเมลตามที่ HR ลงทะเบียนไว้)
      })
      window.google.accounts.id.renderButton(overlayEl, {
        type: 'standard',
        theme: 'outline',
        size: 'large',
        width: overlayEl.offsetWidth || 320,
      })
      ready.value = true
    } catch (e) {
      error.value = e.message || 'โหลด Google Sign-In ไม่สำเร็จ'
    }
  }

  return { ready, error, mount }
}
