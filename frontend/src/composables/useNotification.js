import { useAppStore } from '@/store'

export function useNotification() {
  const app = useAppStore()
  const notify = (type) => (message, timeout) => app.pushToast({ type, message, timeout })

  return {
    success: notify('success'),
    error: notify('error'),
    warning: notify('warning'),
    info: notify('info'),
  }
}
