import axiosClient from '@/api/axiosClient'
import { mockPayslip } from '@/api/mock/mockServer'
import { USE_MOCK } from '@/utils/constants'

const httpPayslip = {
  /** @returns {Promise<Array<{ month: 'YYYY-MM', netPay: number }>>} เดือนที่สลิปออกแล้ว (ล่าสุดก่อน) */
  getList: () => axiosClient.get('/payslips'),
  /**
   * @returns {Promise<{ month, payDate, employee, bank, earnings: Array<{ label, amount, note? }>,
   *   deductions: Array<{ label, amount, note? }>, totalEarnings, totalDeductions, netPay, attendance }>}
   */
  getDetail: (month) => axiosClient.get(`/payslips/${month}`),
}

export const payslipService = USE_MOCK ? mockPayslip : httpPayslip
