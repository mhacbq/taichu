import { ref, computed, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { useUserPoints } from '../../composables/useUserPoints'
import { getFortunePointsCost, getYearlyFortune } from '../../api'

export function useYearlyFortune() {
const { userPoints, isLoggedIn, loadUserPoints } = useUserPoints()

// 表单数据
const birthDateTime = ref('')
const gender = ref('male')
const calculating = ref(false)
const result = ref(null)
const monthlyFortune = ref([])
const aiAnalysis = ref('')
const pointsCost = ref(50)

// 四大运势分类
const fortuneCategories = [
  {
    key: 'career',
    title: '事业运势',
    icon: 'Briefcase',
    defaultSummary: '今年事业运势平稳发展，建议抓住机遇，稳步前进。'
  },
  {
    key: 'wealth',
    title: '财富运势',
    icon: 'Coin',
    defaultSummary: '财运整体尚可，正财稳定，偏财需谨慎。'
  },
  {
    key: 'love',
    title: '感情运势',
    icon: 'Heart',
    defaultSummary: '感情生活有望改善，单身者有机会遇见良缘。'
  },
  {
    key: 'health',
    title: '健康运势',
    icon: 'Operation',
    defaultSummary: '注意劳逸结合，保持良好的作息习惯。'
  }
]

// 当前年份及天干地支
const currentYear = new Date().getFullYear()
const chineseYearName = computed(() => {
  const tianGan = ['庚', '辛', '壬', '癸', '甲', '乙', '丙', '丁', '戊', '己']
  const diZhi = ['申', '酉', '戌', '亥', '子', '丑', '寅', '卯', '辰', '巳', '午', '未']
  return tianGan[(currentYear - 4) % 10] + diZhi[(currentYear - 4) % 12] + '年'
})

// 禁用未来日期
const disabledDate = (time) => {
  return time.getTime() > Date.now()
}

// 加载积分配置
const loadPointsConfig = async () => {
  try {
    const response = await getFortunePointsCost()
    if (response.code === 0) {
      pointsCost.value = response.data.yearly_fortune || response.data.yearly || 50
    }
  } catch {
    // 加载失败时使用默认积分值，静默处理
  }
}

// 组件挂载时加载积分和配置
onMounted(() => {
  loadUserPoints()
  loadPointsConfig()
})

// 计算流年运势
const handleCalculate = async () => {
  if (!birthDateTime.value) {
    ElMessage.warning('请选择出生日期和时间')
    return
  }

  // 检查登录状态
  if (!isLoggedIn.value) {
    ElMessage.warning('请先登录')
    return
  }

  // 检查积分
  if (userPoints.value === null) {
    await loadUserPoints()
  }

  if (userPoints.value < pointsCost.value) {
    ElMessage.warning(`积分不足，需要${pointsCost.value}积分`)
    return
  }

  calculating.value = true

  try {
    const response = await getYearlyFortune({
      birthDateTime: birthDateTime.value,
      gender: gender.value
    })

    if (response.code === 0) {
      result.value = response.data
      // 新提示词返回 monthly 数组，直接使用；兼容旧格式
      monthlyFortune.value = response.data.monthly || []
      // 后端返回字段为 overall，包含 AI 深度解读内容
      aiAnalysis.value = response.data.overall || ''
      
      ElMessage.success('解析成功！')
      
      // 直接用后端返回的扣后余额，彻底消除价格缓存不一致问题
      if (response.data.remaining_points !== undefined) {
        userPoints.value = response.data.remaining_points
      } else if (response.data.points_cost !== undefined) {
        userPoints.value -= response.data.points_cost
      } else {
        await loadUserPoints()
      }
      // 同步最新价格到本地缓存
      if (response.data.points_cost !== undefined) {
        pointsCost.value = response.data.points_cost
      }
    } else {
      throw new Error(response.message || '解析失败')
    }
  } catch (error) {
    // 积分不足时刷新配置和余额，确保提示的积分数与最新价格一致
    if (error.response?.status === 402 || error.message?.includes('积分不足')) {
      await Promise.all([loadPointsConfig(), loadUserPoints()])
      ElMessage.warning(`积分不足，需要${pointsCost.value}积分`)
    } else {
      ElMessage.error('解析失败，请重试')
    }
  } finally {
    calculating.value = false
  }
}

// 重置表单
const resetForm = () => {
  birthDateTime.value = ''
  gender.value = 'male'
  result.value = null
  monthlyFortune.value = []
  aiAnalysis.value = ''
}

// 获取分类运势文本（后端返回字符串，key映射）
const categoryKeyMap = {
  career: 'career',
  wealth: 'wealth',
  love: 'relationship',
  health: 'health',
}

const getCategoryText = (key) => {
  const backendKey = categoryKeyMap[key] || key
  return result.value?.[backendKey] || ''
}

// 获取分类评分（优先使用后端返回的真实分项评分）
const getCategoryScore = (key) => {
  const scoreKeyMap = {
    career: 'career_score',
    wealth: 'wealth_score',
    love: 'love_score',
    health: 'health_score',
  }
  const scoreKey = scoreKeyMap[key]
  if (scoreKey && result.value?.[scoreKey]) {
    return result.value[scoreKey]
  }
  // 兜底：用总分估算（旧缓存数据兼容）
  const base = result.value?.score || 75
  const offsets = { career: 0, wealth: -3, love: 2, health: -1 }
  return Math.min(100, Math.max(1, base + (offsets[key] || 0)))
}



return {
  // 状态数据
  birthDateTime, gender, calculating, result,
  monthlyFortune, aiAnalysis,
  pointsCost, fortuneCategories,
  currentYear, chineseYearName,

  // 方法
  disabledDate, handleCalculate, resetForm,
  getCategoryText, getCategoryScore,
}
} // end useYearlyFortune
