import { defineStore } from 'pinia'
import { ref } from 'vue'
import { leaveService } from '@/api/services/leaveService'

export const useLeaveStore = defineStore('leave', () => {
  const balances = ref([])
  const requests = ref([])
  const loading = ref(false)
  const submitting = ref(false)

  async function fetchAll() {
    loading.value = true
    try {
      const [balanceList, requestList] = await Promise.all([leaveService.getBalances(), leaveService.getRequests()])
      balances.value = balanceList
      requests.value = requestList
    } finally {
      loading.value = false
    }
  }

  async function createRequest(payload) {
    submitting.value = true
    try {
      const request = await leaveService.createRequest(payload)
      await fetchAll()
      return request
    } finally {
      submitting.value = false
    }
  }

  async function cancelRequest(id) {
    submitting.value = true
    try {
      await leaveService.cancelRequest(id)
      await fetchAll()
    } finally {
      submitting.value = false
    }
  }

  function reset() {
    balances.value = []
    requests.value = []
  }

  return { balances, requests, loading, submitting, fetchAll, createRequest, cancelRequest, reset }
})
