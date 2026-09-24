import { computed, ref } from 'vue'
import { useGeolocation } from './useGeolocation'
import { ENFORCE_GEOFENCE, OFFICE_LOCATION } from '@/utils/constants'
import { isInsideArea } from '@/utils/geo'

/**
 * ตรวจว่าผู้ใช้อยู่ในพื้นที่ที่อนุญาตให้ลงเวลาหรือไม่
 * ถ้า VITE_ENFORCE_GEOFENCE=false จะถือว่าอยู่ในพื้นที่เสมอ (แต่ยังส่งพิกัดไปให้ backend ถ้ามี)
 */
export function useGeofence() {
  const { getPosition } = useGeolocation()
  const position = ref(null)
  const checking = ref(false)

  const inside = computed(() => !ENFORCE_GEOFENCE || isInsideArea(position.value, OFFICE_LOCATION))

  async function refresh() {
    checking.value = true
    try {
      position.value = await getPosition()
      return position.value
    } finally {
      checking.value = false
    }
  }

  return { position, inside, checking, refresh }
}
