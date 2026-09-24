import axiosClient from '@/api/axiosClient'
import { mockCalendar } from '@/api/mock/mockServer'
import { USE_MOCK } from '@/utils/constants'

const httpCalendar = {
  /**
   * @param {{ month: 'YYYY-MM' }} params
   * @returns {Promise<{ month, days: Array<{ date, shift: { code, name, start, end } | null,
   *   holiday: { name } | null, leave: { id, leaveType, period, status } | null,
   *   attendance: 'on_time'|'late'|'incomplete'|'missing'|'working'|null, checkIn, checkOut }> }>}
   */
  getMonth: (params) => axiosClient.get('/calendar', { params }),
}

export const calendarService = USE_MOCK ? mockCalendar : httpCalendar
