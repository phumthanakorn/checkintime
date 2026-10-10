import { computed, ref } from 'vue'
import { useGeolocation } from './useGeolocation'
import { locationService } from '@/api/services/locationService'
import { ENFORCE_GEOFENCE, OFFICE_LOCATION } from '@/utils/constants'
import { distanceMeters, isInsideArea } from '@/utils/geo'

/**
 * ตรวจว่าผู้ใช้อยู่ในพื้นที่ที่อนุญาตให้ลงเวลาหรือไม่ (ยังอิง OFFICE_LOCATION เดิมเหมือนก่อน — ไม่แตะ
 * logic บังคับ/เปิดกั้นตรงนี้ ตามที่ขอไว้ว่า "ไม่ต้องฟิกซ์ว่าบังคับให้ลงที่ไหน")
 * ถ้า VITE_ENFORCE_GEOFENCE=false จะถือว่าอยู่ในพื้นที่เสมอ (แต่ยังส่งพิกัดไปให้ backend ถ้ามี)
 *
 * เพิ่ม: ดึงรายชื่อ "สถานที่ลงเวลา" จริงทั้งหมด (GET /locations) มาหาตัวที่ใกล้ตำแหน่งปัจจุบันที่สุด
 * เอาไว้ "แสดง" ในการ์ดลงเวลาเฉยๆ (เหมือนแอปอ้างอิงที่ส่งมา — โชว์ชื่อสถานที่ใกล้สุดก่อนกดลงเวลา)
 * ไม่ได้ใช้ผลนี้ไปตัดสินว่า inside/บล็อกการลงเวลาเลย เป็นแค่ข้อมูลอ้างอิงให้ผู้ใช้เห็น
 */
export function useGeofence() {
  const { getPosition } = useGeolocation()
  const position = ref(null)
  const checking = ref(false)
  const locations = ref([])

  const inside = computed(() => !ENFORCE_GEOFENCE || isInsideArea(position.value, OFFICE_LOCATION))

  // สถานที่ลงเวลาที่ใกล้ตำแหน่งปัจจุบันที่สุด (เฉพาะที่เปิด allowGpsCheckin ไว้) + ระยะห่าง (เมตร)
  const nearest = computed(() => {
    if (!position.value || locations.value.length === 0) return null
    const candidates = locations.value.filter((loc) => loc.allowGpsCheckin)
    if (candidates.length === 0) return null
    return candidates.reduce((closest, loc) => {
      const d = distanceMeters(position.value, { lat: loc.latitude, lng: loc.longitude })
      return !closest || d < closest.distance ? { location: loc, distance: d } : closest
    }, null)
  })

  async function fetchLocations() {
    try {
      locations.value = await locationService.list()
    } catch {
      // ดึงรายชื่อสถานที่ไม่สำเร็จก็ไม่ต้อง block การลงเวลา — แค่ไม่มีป้ายชื่อสถานที่ให้โชว์
      locations.value = []
    }
  }

  async function refresh() {
    checking.value = true
    try {
      position.value = await getPosition()
      if (locations.value.length === 0) await fetchLocations()
      return position.value
    } finally {
      checking.value = false
    }
  }

  return { position, inside, checking, locations, nearest, refresh }
}
