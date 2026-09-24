import axiosClient from '@/api/axiosClient'
import { mockLeave } from '@/api/mock/mockServer'
import { USE_MOCK } from '@/utils/constants'

const httpLeave = {
  /** @returns {Promise<Array<{ type, quota, used, pending, remaining }>>} */
  getBalances: () => axiosClient.get('/leave/balances'),
  getRequests: () => axiosClient.get('/leave/requests'),
  /** @param {{ leaveType, startDate, endDate, period, reason }} payload  วันที่เป็น 'YYYY-MM-DD' */
  createRequest: (payload) => axiosClient.post('/leave/requests', payload),
  cancelRequest: (id) => axiosClient.post(`/leave/requests/${id}/cancel`),
}

export const leaveService = USE_MOCK ? mockLeave : httpLeave
