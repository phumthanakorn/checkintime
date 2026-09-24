import { defineStore } from 'pinia'
import { ref } from 'vue'
import { timeFixService } from '@/api/services/timeFixService'

export const useTimeFixStore = defineStore('timeFix', () => {
  const requests = ref([])
  const loading = ref(false)
  const submitting = ref(false)

  async function fetchAll() {
    loading.value = true
    try {
      requests.value = await timeFixService.getRequests()
    } finally {
      loading.value = false
    }
  }

  async function createRequest(payload) {
    submitting.value = true
    try {
      const request = await timeFixService.createRequest(payload)
      await fetchAll()
      return request
    } finally {
      submitting.value = false
    }
  }

  async function cancelRequest(id) {
    submitting.value = true
    try {
      await timeFixService.cancelRequest(id)
      await fetchAll()
    } finally {
      submitting.value = false
    }
  }

  function reset() {
    requests.value = []
  }

  return { requests, loading, submitting, fetchAll, createRequest, cancelRequest, reset }
})
