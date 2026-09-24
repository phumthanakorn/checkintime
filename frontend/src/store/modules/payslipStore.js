import { defineStore } from 'pinia'
import { ref } from 'vue'
import { payslipService } from '@/api/services/payslipService'

export const usePayslipStore = defineStore('payslip', () => {
  const list = ref([])
  const current = ref(null)
  const loadingList = ref(false)
  const loadingDetail = ref(false)
  const cache = new Map() // เก็บสลิปที่เคยโหลด สลับเดือนไปมาได้ทันที

  async function fetchList() {
    loadingList.value = true
    try {
      list.value = await payslipService.getList()
    } finally {
      loadingList.value = false
    }
  }

  async function fetchDetail(month) {
    if (cache.has(month)) {
      current.value = cache.get(month)
      return current.value
    }
    loadingDetail.value = true
    try {
      current.value = await payslipService.getDetail(month)
      cache.set(month, current.value)
      return current.value
    } finally {
      loadingDetail.value = false
    }
  }

  function reset() {
    list.value = []
    current.value = null
    cache.clear()
  }

  return { list, current, loadingList, loadingDetail, fetchList, fetchDetail, reset }
})
