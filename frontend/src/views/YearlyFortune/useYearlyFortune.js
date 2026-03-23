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
const aiLoading = ref(false)
const pointsCost = ref(50)
const aiPointsCost = ref(30)

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

// 禁用未来日期
const disabledDate = (time) => {
  return time.getTime() > Date.now()
}

// 加载积分配置
const loadPointsConfig = async () => {
  try {
    const response = await getFortunePointsCost()
    if (response.code === 200) {
      pointsCost.value = response.data.yearly || 50
      aiPointsCost.value = response.data.ai_analysis || 30
    }
  } catch (error) {
    console.error('加载积分配置失败:', error)
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

    if (response.code === 200) {
      result.value = response.data.fortune || response.data
      monthlyFortune.value = response.data.monthly || []
      aiAnalysis.value = response.data.aiAnalysis || ''
      
      ElMessage.success('解析成功！')
      
      // 扣除积分（实际应该在API成功后扣除）
      userPoints.value -= pointsCost.value
    } else {
      throw new Error(response.message || '解析失败')
    }
  } catch (error) {
    ElMessage.error('解析失败，请重试')
    console.error(error)
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

// 获取AI分析
const getAiAnalysis = async () => {
  if (!result.value) {
    ElMessage.warning('请先计算流年运势')
    return
  }

  // 检查积分
  if (userPoints.value < aiPointsCost.value) {
    ElMessage.warning(`积分不足，需要${aiPointsCost.value}积分`)
    return
  }

  aiLoading.value = true

  try {
    const response = await fetch('/api/ai/analyze', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        type: 'yearly_fortune',
        data: result.value,
        birthDateTime: birthDateTime.value,
        gender: gender.value
      })
    })
    const data = await response.json()

    if (data.code === 200) {
      aiAnalysis.value = data.data.analysis || data.data.content || ''
      // 更新每月运势（如果AI返回了更详细的每月分析）
      if (data.data.monthly) {
        monthlyFortune.value = data.data.monthly
      }
      ElMessage.success('AI分析完成')
      
      // 扣除积分
      userPoints.value -= aiPointsCost.value
    } else {
      throw new Error(data.message || '分析失败')
    }
  } catch (error) {
    ElMessage.error('AI分析失败，请重试')
    console.error(error)
  } finally {
    aiLoading.value = false
  }
}

return {
  // 状态数据
  birthDateTime, gender, calculating, result,
  monthlyFortune, aiAnalysis, aiLoading,
  pointsCost, aiPointsCost, fortuneCategories,

  // 方法
  disabledDate, handleCalculate, resetForm, getAiAnalysis,
}
} // end useYearlyFortune
