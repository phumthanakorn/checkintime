/**
 * ขอพิกัดปัจจุบันของผู้ใช้ คืนค่า null เมื่อเบราว์เซอร์ไม่รองรับหรือผู้ใช้ไม่อนุญาต
 * (การลงเวลายังทำต่อได้ ให้ backend เป็นผู้ตัดสินว่าบังคับใช้พิกัดหรือไม่)
 */
export function useGeolocation() {
  function getPosition(timeout = 8000) {
    return new Promise((resolve) => {
      if (!('geolocation' in navigator)) return resolve(null)

      navigator.geolocation.getCurrentPosition(
        ({ coords }) =>
          resolve({
            lat: coords.latitude,
            lng: coords.longitude,
            accuracy: Math.round(coords.accuracy),
          }),
        () => resolve(null),
        { enableHighAccuracy: true, timeout, maximumAge: 60000 },
      )
    })
  }

  return { getPosition }
}
