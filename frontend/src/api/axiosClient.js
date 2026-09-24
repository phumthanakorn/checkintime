import axios from 'axios'
import { STORAGE_KEYS } from '@/utils/constants'

const axiosClient = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || '/api',
  timeout: 15000,
  headers: { 'Content-Type': 'application/json' },
})

axiosClient.interceptors.request.use((config) => {
  const token = localStorage.getItem(STORAGE_KEYS.TOKEN)
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

axiosClient.interceptors.response.use(
  (response) => response.data,
  (error) => {
    const status = error.response?.status

    if (status === 401) {
      localStorage.removeItem(STORAGE_KEYS.TOKEN)
      localStorage.removeItem(STORAGE_KEYS.USER)
      // BASE_URL = '/' ตอน dev หรือ '/<repo>/' บน GitHub Pages
      const base = import.meta.env.BASE_URL
      const currentPath = window.location.pathname.slice(base.length - 1)
      if (currentPath !== '/login') {
        window.location.href = `${base}login?redirect=${encodeURIComponent(currentPath)}`
      }
    }

    const message =
      error.response?.data?.message ||
      (error.code === 'ECONNABORTED' ? 'หมดเวลาการเชื่อมต่อ กรุณาลองใหม่' : 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้')

    const normalized = new Error(message)
    normalized.status = status
    normalized.data = error.response?.data
    return Promise.reject(normalized)
  },
)

export default axiosClient
