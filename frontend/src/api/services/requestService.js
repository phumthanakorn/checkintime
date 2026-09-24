import axiosClient from '@/api/axiosClient'
import { mockRequest } from '@/api/mock/mockServer'
import { USE_MOCK } from '@/utils/constants'

const httpRequest = {
  /** @returns {Promise<Array<{ type: 'leave' | 'advance', pendingCount: number }>>} */
  getStatusSummary: () => axiosClient.get('/requests/status-summary'),
}

export const requestService = USE_MOCK ? mockRequest : httpRequest
