import { ref, computed } from 'vue'

const STORAGE_KEY = 'taichu_tour_completed'
const STORAGE_VERSION = 'v1' // 引导版本，用于升级时重新触发

export function useTourGuide() {
  const showTour = ref(false)
  const currentStep = ref(0)

  // 检查是否已完成引导
  const hasCompletedTour = computed(() => {
    try {
      const stored = localStorage.getItem(STORAGE_KEY)
      if (!stored) return false
      const data = JSON.parse(stored)
      return data.version === STORAGE_VERSION && data.completed
    } catch {
      return false
    }
  })

  // 标记引导完成
  const markTourCompleted = () => {
    localStorage.setItem(STORAGE_KEY, JSON.stringify({
      version: STORAGE_VERSION,
      completed: true,
      completedAt: Date.now()
    }))
    showTour.value = false
  }

  // 重置引导状态（用于测试或重新引导）
  const resetTour = () => {
    localStorage.removeItem(STORAGE_KEY)
    currentStep.value = 0
  }

  // 开始引导
  const startTour = () => {
    currentStep.value = 0
    showTour.value = true
  }

  // 引导步骤配置
  const tourSteps = [
    {
      target: '#tour-home-features',
      title: '欢迎来到太初命理',
      description: '我们提供专业的八字排盘、塔罗占卜、每日运势等命理服务，助您洞察运势，规划人生。',
      placement: 'bottom'
    },
    {
      target: '#tour-bazi',
      title: '八字排盘',
      description: '输入出生信息，自动排盘并给出专业解读，包含运势分析、性格特点等内容。',
      placement: 'bottom'
    },
    {
      target: '#tour-recharge',
      title: '积分充值',
      description: '充值积分可解锁高级功能，享受更详细、更精准的命理解读服务。',
      placement: 'bottom'
    },
    {
      target: '#tour-profile',
      title: '个人中心',
      description: '查看历史记录、每日签到领积分，管理你的命理体验进度。',
      placement: 'bottom'
    }
  ]

  return {
    showTour,
    currentStep,
    hasCompletedTour,
    tourSteps,
    markTourCompleted,
    resetTour,
    startTour
  }
}
