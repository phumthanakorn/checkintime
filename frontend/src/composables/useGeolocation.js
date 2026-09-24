/**
 * ขอพิกัดปัจจุบันของผู้ใช้
 *  - getPosition(): คืนพิกัด หรือ null (ใช้ตอนลงเวลา ไม่สนสาเหตุ)
 *  - requestPosition(): คืน { position, status } เพื่อแยกว่า "ผู้ใช้ปฏิเสธ" หรือ "หาตำแหน่งไม่ได้"
 *  - getPermissionState(): 'granted' | 'denied' | 'prompt' | 'unsupported' (ไม่เด้งถามผู้ใช้)
 */
export function useGeolocation() {
  /**
   * @param {number} timeout
   * @param {number} maximumAge ยอมใช้ตำแหน่งที่แคชไว้ไม่เกินกี่ ms (0 = ขอใหม่เสมอ เช่น ตอนกด "ตรวจอีกครั้ง")
   */
  function requestPosition(timeout = 10000, maximumAge = 0) {
    return new Promise((resolve) => {
      if (!('geolocation' in navigator)) return resolve({ position: null, status: 'unsupported' })

      navigator.geolocation.getCurrentPosition(
        ({ coords }) =>
          resolve({
            position: { lat: coords.latitude, lng: coords.longitude, accuracy: Math.round(coords.accuracy) },
            status: 'granted',
          }),
        (error) =>
          resolve({
            position: null,
            // 1 = PERMISSION_DENIED, 2 = POSITION_UNAVAILABLE, 3 = TIMEOUT
            status: error.code === 1 ? 'denied' : error.code === 3 ? 'timeout' : 'unavailable',
          }),
        { enableHighAccuracy: true, timeout, maximumAge },
      )
    })
  }

  async function getPosition(timeout = 8000) {
    // ตอนลงเวลา ยอมใช้ตำแหน่งอายุไม่เกิน 30 วินาที เพื่อให้กดได้เร็ว
    return (await requestPosition(timeout, 30000)).position
  }

  async function getPermissionState() {
    if (!('geolocation' in navigator)) return 'unsupported'
    try {
      const result = await navigator.permissions?.query({ name: 'geolocation' })
      return result?.state ?? 'prompt'
    } catch {
      // Safari รุ่นเก่า / เบราว์เซอร์ในแอปบางตัวไม่รองรับ permissions API
      return 'prompt'
    }
  }

  return { getPosition, requestPosition, getPermissionState }
}
