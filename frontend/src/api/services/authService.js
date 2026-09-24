import axiosClient from '@/api/axiosClient'
import { mockAuth } from '@/api/mock/mockServer'
import { USE_MOCK } from '@/utils/constants'

const httpAuth = {
  /** @returns {Promise<{ token: string, user: object }>} */
  login: (payload) => axiosClient.post('/auth/login', payload),
  me: () => axiosClient.get('/auth/me'),
  logout: () => axiosClient.post('/auth/logout'),
}

export const authService = USE_MOCK ? mockAuth : httpAuth
