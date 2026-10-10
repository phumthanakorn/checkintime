import axiosClient from '@/api/axiosClient'

const httpNotification = {
  /** @returns {Promise<Array<{ id, type, title, body, createdAt, read, link: { name, query? } | null }>>} */
  getAll: () => axiosClient.get('/notifications'),
  markRead: (id) => axiosClient.post(`/notifications/${id}/read`),
  markAllRead: () => axiosClient.post('/notifications/read-all'),
}

export const notificationService = httpNotification
