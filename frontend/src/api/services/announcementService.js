import axiosClient from '@/api/axiosClient'

const httpAnnouncement = {
  /** @returns {Promise<{ id, category, title, summary, body: string[], author, publishedAt, requireAck, acknowledgedAt }>} */
  get: (id) => axiosClient.get(`/announcements/${id}`),
  acknowledge: (id) => axiosClient.post(`/announcements/${id}/acknowledge`),
}

export const announcementService = httpAnnouncement
