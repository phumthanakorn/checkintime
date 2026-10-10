import axiosClient from '@/api/axiosClient'

const httpRequest = {
  /** @returns {Promise<Array<{ type: 'leave' | 'time_fix', pendingCount: number }>>} */
  getStatusSummary: () => axiosClient.get('/requests/status-summary'),
}

export const requestService = httpRequest
