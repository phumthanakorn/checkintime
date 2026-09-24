import axiosClient from '@/api/axiosClient'
import { mockNotification } from '@/api/mock/mockServer'
import { USE_MOCK } from '@/utils/constants'

const httpNotification = {
  /** @returns {Promise<Array<{ id, type, title, body, createdAt, read, link: { name, query? } | null }>>} */
  getAll: () => axiosClient.get('/notifications'),
  markRead: (id) => axiosClient.post(`/notifications/${id}/read`),
  markAllRead: () => axiosClient.post('/notifications/read-all'),
}

export const notificationService = USE_MOCK ? mockNotification : httpNotification
