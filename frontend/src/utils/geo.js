const EARTH_RADIUS_METERS = 6371000
const toRad = (deg) => (deg * Math.PI) / 180

/** ระยะทางระหว่าง 2 พิกัด (เมตร) ด้วยสูตร haversine */
export function distanceMeters(a, b) {
  const dLat = toRad(b.lat - a.lat)
  const dLng = toRad(b.lng - a.lng)
  const h =
    Math.sin(dLat / 2) ** 2 + Math.cos(toRad(a.lat)) * Math.cos(toRad(b.lat)) * Math.sin(dLng / 2) ** 2
  return 2 * EARTH_RADIUS_METERS * Math.asin(Math.sqrt(h))
}

export function isInsideArea(position, area) {
  if (!position) return false
  return distanceMeters(position, area) <= area.radiusMeters + (position.accuracy || 0)
}
