import { ref, computed, onMounted, onUnmounted } from 'vue'

export function useGreeting() {
  const currentHour = ref(new Date().getHours())
  let greetingRefreshTimer = null

  const syncCurrentHour = () => {
    currentHour.value = new Date().getHours()
  }

  const scheduleGreetingRefresh = () => {
    syncCurrentHour()

    if (greetingRefreshTimer) {
      window.clearTimeout(greetingRefreshTimer)
    }

    const now = new Date()
    const nextHour = new Date(now)
    nextHour.setHours(now.getHours() + 1, 0, 0, 0)
    
    greetingRefreshTimer = window.setTimeout(() => {
      scheduleGreetingRefresh()
    }, Math.max(1000, nextHour.getTime() - now.getTime() + 1000))
  }

  const handleVisibilityChange = () => {
    if (document.visibilityState === 'visible') {
      scheduleGreetingRefresh()
    }
  }

  const greetingIcon = computed(() => {
    if (currentHour.value < 12) return 'morning'
    if (currentHour.value < 18) return 'afternoon'
    return 'evening'
  })

  const greetingText = computed(() => {
    if (currentHour.value < 12) return '早上好，愿你今天充满希望'
    if (currentHour.value < 18) return '下午好，愿你的努力都有收获'
    return '晚上好，愿你今夜好梦'
  })

  onMounted(() => {
    scheduleGreetingRefresh()
    document.addEventListener('visibilitychange', handleVisibilityChange)
  })

  onUnmounted(() => {
    document.removeEventListener('visibilitychange', handleVisibilityChange)
    if (greetingRefreshTimer) {
      window.clearTimeout(greetingRefreshTimer)
      greetingRefreshTimer = null
    }
  })

  return {
    greetingIcon,
    greetingText,
  }
}
