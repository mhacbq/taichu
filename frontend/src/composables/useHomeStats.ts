import { ref } from 'vue'
import { getHomeStats } from '../api'

export function useHomeStats() {
  const stats = ref([
    { number: '--', label: '服务用户', caption: '统计同步中', isLive: false },
    { number: '--', label: '分析次数', caption: '统计同步中', isLive: false },
    { number: '--', label: '好评率', caption: '统计同步中', isLive: false },
  ])
  const statsLoading = ref(true)
  const statsError = ref(false)
  const userCount = ref(null)

  const hasDisplayValue = (value: unknown) => 
    value !== undefined && value !== null && `${value}`.trim() !== ''

  const formatDisplayValue = (value: unknown) => {
    if (!hasDisplayValue(value)) return '--'
    
    if (typeof value === 'number') {
      return value.toLocaleString('zh-CN')
    }
    
    const numericValue = Number(value)
    return Number.isFinite(numericValue) ? numericValue.toLocaleString('zh-CN') : String(value)
  }

  const buildStats = (incomingStats: any[]) => {
    const fallbackStats = [
      { number: '--', label: '服务用户', icon: 'UserFilled', caption: '统计同步中', isLive: false },
      { number: '--', label: '分析次数', icon: 'DataLine', caption: '统计同步中', isLive: false },
      { number: '--', label: '好评率', icon: 'ChatLineRound', caption: '统计同步中', isLive: false },
    ]

    return fallbackStats.map((fallback, index) => {
      const item = incomingStats[index]
      if (!item) return fallback

      return {
        ...fallback,
        ...item,
        number: hasDisplayValue(item.number) ? formatDisplayValue(item.number) : '--',
        label: item.label || fallback.label,
        icon: item.icon || fallback.icon,
        caption: hasDisplayValue(item.number) ? '' : fallback.caption,
        isLive: hasDisplayValue(item.number),
      }
    })
  }

  const loadStats = async () => {
    statsLoading.value = true
    statsError.value = false

    try {
      const response = await getHomeStats()
      
      if (response.code !== 200) {
        throw new Error(response.message || '加载统计数据失败')
      }

      const incomingStats = Array.isArray(response.data?.stats) ? response.data.stats : []
      stats.value = buildStats(incomingStats)
      userCount.value = hasDisplayValue(response.data?.userCount) ? response.data.userCount : null

      if (!incomingStats.length && !hasDisplayValue(userCount.value)) {
        statsError.value = true
        stats.value = buildStats([])
      }
    } catch (error) {
      stats.value = buildStats([])
      userCount.value = null
      statsError.value = true
    } finally {
      statsLoading.value = false
    }
  }

  return {
    stats,
    statsLoading,
    statsError,
    userCount,
    formatDisplayValue,
    hasDisplayValue,
    loadStats,
  }
}
