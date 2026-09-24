import axiosClient from '@/api/axiosClient'
import { mockAnnouncement } from '@/api/mock/mockServer'
import { USE_MOCK } from '@/utils/constants'

const httpAnnouncement = {
  /** @returns {Promise<{ id, category, title, summary, body: string[], author, publishedAt, requireAck, acknowledgedAt }>} */
  get: (id) => axiosClient.get(`/announcements/${id}`),
  acknowledge: (id) => axiosClient.post(`/announcements/${id}/acknowledge`),
}

export const announcementService = USE_MOCK ? mockAnnouncement : httpAnnouncement
