import axiosClient from '@/api/axiosClient'

const httpLocation = {
  /** @returns {Promise<Array<{ id, name, type, typeLabel, latitude, longitude, radiusMeters, allowGpsCheckin, allowBeaconCheckin, beaconId }>>} */
  list: () => axiosClient.get('/locations'),
}

export const locationService = httpLocation
