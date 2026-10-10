import axiosClient from '@/api/axiosClient'

const httpTimeFix = {
  /** @returns {Promise<Array<{ id, date, fixType, checkIn, checkOut, reason, status, reviewNote, createdAt }>>} */
  getRequests: () => axiosClient.get('/time-fix/requests'),
  /** @param {{ date: 'YYYY-MM-DD', fixType: 'check_in'|'check_out'|'both', checkIn?: 'HH:mm', checkOut?: 'HH:mm', reason }} payload */
  createRequest: (payload) => axiosClient.post('/time-fix/requests', payload),
  cancelRequest: (id) => axiosClient.post(`/time-fix/requests/${id}/cancel`),
  getAttachment: async (requestId, attachmentId) => {
    try {
      return await axiosClient.get(
        `/time-fix/requests/${requestId}/attachments/${attachmentId}`, { responseType: 'blob' },
      )
    } catch (error) {
      // JSON API errors arrive as Blob too when responseType is blob.
      if (error.data instanceof Blob) {
        try {
          const data = JSON.parse(await error.data.text())
          if (typeof data.message === 'string') error.message = data.message
        } catch { /* Keep the existing network/server message. */ }
      }
      throw error
    }
  },
}

export const timeFixService = httpTimeFix
