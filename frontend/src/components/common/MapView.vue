<template>
  <div ref="mapEl" class="map-view h-full w-full" role="img" :aria-label="ariaLabel" />
</template>

<script setup>
/**
 * แผนที่แสดงออฟฟิศ (พร้อมรัศมีที่ลงเวลาได้) และตำแหน่งของผู้ใช้
 * ใช้ Leaflet + แผนที่ OpenStreetMap
 * หมายเหตุ: เมื่อใช้งานจริงจำนวนมาก ควรเปลี่ยนผู้ให้บริการแผนที่ (เช่น Longdo / Google Maps / MapTiler)
 *          ตามเงื่อนไขการใช้งานของ OpenStreetMap
 */
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

const props = defineProps({
  /** { lat, lng, radiusMeters, name } | null — ไม่ใส่ (null) = โหมดโชว์แค่จุดเดียว ไม่มีรัศมี/เส้นประ (ใช้
   * ตอนย้อนดูพิกัดการสแกนในอดีตที่ไม่มีพิกัดของ "ออฟฟิศ" อ้างอิง มีแต่ชื่อ+ระยะห่างเท่านั้น) */
  office: { type: Object, default: null },
  /** { lat, lng, accuracy } | null */
  user: { type: Object, default: null },
})

const mapEl = ref()
let map = null
let userLayer = null

const ariaLabel = computed(() => {
  if (!props.office) return 'แผนที่แสดงตำแหน่งที่บันทึกไว้'
  return props.user ? `แผนที่แสดงตำแหน่งของคุณและ${props.office.name}` : `แผนที่แสดงที่ตั้ง${props.office.name}`
})

const pin = (className, html) =>
  L.divIcon({ className: '', html: `<span class="${className}">${html}</span>`, iconSize: [36, 36], iconAnchor: [18, 18] })

function drawUser() {
  userLayer?.remove()
  if (!props.user) {
    if (props.office) map.setView([props.office.lat, props.office.lng], 16)
    return
  }
  const userPoint = [props.user.lat, props.user.lng]
  const layers = [
    // วงความแม่นยำของ GPS
    L.circle(userPoint, {
      radius: Math.max(props.user.accuracy || 0, 10),
      color: '#dc2626',
      weight: 1,
      fillOpacity: 0.08,
    }),
    L.marker(userPoint, { icon: pin('map-pin map-pin--user', ''), zIndexOffset: 1000 }),
  ]

  if (!props.office) {
    userLayer = L.layerGroup(layers).addTo(map)
    map.setView(userPoint, 17)
    return
  }

  const officePoint = [props.office.lat, props.office.lng]
  // เส้นประจากผู้ใช้ถึงออฟฟิศ — มีแค่ตอนมีจุดออฟฟิศอ้างอิงเท่านั้น
  layers.push(L.polyline([userPoint, officePoint], { color: '#334155', weight: 2, dashArray: '6 8', opacity: 0.7 }))
  userLayer = L.layerGroup(layers).addTo(map)
  map.fitBounds(L.latLngBounds([userPoint, officePoint]).pad(0.35), { maxZoom: 17 })
}

onMounted(() => {
  map = L.map(mapEl.value, { zoomControl: false, attributionControl: true })
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '© OpenStreetMap',
  }).addTo(map)
  L.control.zoom({ position: 'bottomright' }).addTo(map)

  if (props.office) {
    const officePoint = [props.office.lat, props.office.lng]
    // พื้นที่ลงเวลาได้ + หมุดออฟฟิศ
    L.circle(officePoint, {
      radius: props.office.radiusMeters,
      color: '#fd7e14',
      weight: 2,
      fillColor: '#fd7e14',
      fillOpacity: 0.15,
    }).addTo(map)
    L.marker(officePoint, { icon: pin('map-pin map-pin--office', '🏢') }).addTo(map)
  }

  drawUser()
})

watch(() => props.user, () => map && drawUser(), { deep: true })

onBeforeUnmount(() => map?.remove())
</script>

<style>
/* หมุดบนแผนที่ (divIcon อยู่นอก scope ของ component จึงไม่ใช้ scoped) */
.map-pin {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 9999px;
  border: 3px solid #fff;
  box-shadow: 0 4px 10px rgb(15 23 42 / 0.25);
  font-size: 16px;
}

.map-pin--office {
  background: #fd7e14;
}

.map-pin--user {
  width: 22px;
  height: 22px;
  margin: 7px;
  background: #dc2626;
  animation: map-pulse 1.8s ease-out infinite;
}

@keyframes map-pulse {
  0% {
    box-shadow: 0 0 0 0 rgb(220 38 38 / 0.5);
  }
  100% {
    box-shadow: 0 0 0 18px rgb(220 38 38 / 0);
  }
}

.map-view .leaflet-control-attribution {
  font-size: 9px;
}
</style>
