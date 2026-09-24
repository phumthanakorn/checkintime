import axiosClient from '@/api/axiosClient'
import { mockTimeFix } from '@/api/mock/mockServer'
import { USE_MOCK } from '@/utils/constants'

const httpTimeFix = {
  /** @returns {Promise<Array<{ id, date, fixType, checkIn, checkOut, reason, status, reviewNote, createdAt }>>} */
  getRequests: () => axiosClient.get('/time-fix/requests'),
  /** @param {{ date: 'YYYY-MM-DD', fixType: 'check_in'|'check_out'|'both', checkIn?: 'HH:mm', checkOut?: 'HH:mm', reason }} payload */
  createRequest: (payload) => axiosClient.post('/time-fix/requests', payload),
  cancelRequest: (id) => axiosClient.post(`/time-fix/requests/${id}/cancel`),
}

export const timeFixService = USE_MOCK ? mockTimeFix : httpTimeFix
