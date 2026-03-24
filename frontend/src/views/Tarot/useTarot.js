import { ref, onMounted, onUnmounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { drawTarot, interpretTarot, aiAnalyzeTarot, getPointsBalance, saveTarotRecord, setTarotPublic, getClientConfig } from '../../api'
import html2canvas from 'html2canvas'
import { trackPageView, trackEvent, trackSubmit, trackError } from '../../utils/tracker'

export function useTarot() {
// 客户端配置
const clientConfig = ref(null)

// 积分消耗配置（从接口动态获取）
const tarotCost = computed(() => clientConfig.value?.points?.costs?.tarot || 5)

// 路由实例
const router = useRouter()




const spreads = [
  { id: 'single', name: '单张牌', icon: 'card', description: '简单直接，适合快速解答' },
  { id: 'three', name: '三张牌', icon: 'cards', description: '过去、现在、未来' },
  { id: 'celtic', name: '凯尔特十字', icon: 'magic', description: '深度分析，全面解读' },
]

// 问题引导话题
const questionTopics = [
  { id: 'career', name: '工作事业', icon: 'briefcase' },
  { id: 'love', name: '感情关系', icon: 'heart' },
  { id: 'growth', name: '个人成长', icon: 'star' },
  { id: 'decision', name: '选择困难', icon: 'question' },
  { id: 'relation', name: '人际关系', icon: 'users' },
]

// 问题模板
const questionTemplates = {
  career: [
    '我是否应该接受这份新工作offer？',
    '现在是我换工作的合适时机吗？',
    '我应该如何提升自己在工作中的表现？',
    '我的职业发展方向应该是什么？',
    '如何应对目前工作中的困难和挑战？',
    '我应该创业还是继续打工？',
  ],
  love: [
    '这段感情是否值得我继续投入？',
    '我应该主动表白还是再等等看？',
    '如何改善我和伴侣之间的关系？',
    '我什么时候能遇到合适的人？',
    '前任还会回到我身边吗？',
    '我应该放下这段感情吗？',
  ],
  growth: [
    '我该如何克服目前的迷茫状态？',
    '我最大的优势和需要改进的地方是什么？',
    '如何实现我今年的目标？',
    '我应该学习什么新技能？',
    '如何建立更好的自信心？',
    '我该如何找到人生的方向？',
  ],
  decision: [
    '我应该选择A还是B？',
    '现在采取行动是好时机吗？',
    '我应该继续坚持还是及时止损？',
    '如何做出不会后悔的决定？',
    '我应该听从内心还是理性分析？',
  ],
  relation: [
    '如何改善我和家人之间的关系？',
    '我应该如何与朋友沟通这个问题？',
    '这个人是否值得我信任？',
    '如何处理与同事的冲突？',
    '我该如何扩大自己的社交圈？',
  ],
}

const selectedSpread = ref('three')
const question = ref('')
const loading = ref(false)
const pointsLoading = ref(false)
const pointsError = ref(false)
const cards = ref([])
const interpretation = ref('')
const currentPoints = ref(null)
const cardDetailVisible = ref(false)
const selectedCard = ref(null)
const selectedTopic = ref('')
const questionGuideExpanded = ref(true)
const flowError = ref(null)
const submittedQuestion = ref('')
const submittedSpread = ref('')
const aiAnalysisLoading = ref(false)
const aiAnalysisResult = ref('')
const aiAnalysisCost = ref(10)


const getSpreadName = (spreadType) => {
  return spreads.find((spread) => spread.id === spreadType)?.name || '当前牌阵'
}

const isResultLocked = computed(() => cards.value.length > 0)
const isTarotContextLocked = computed(() => loading.value || isResultLocked.value)
const submittedQuestionDisplay = computed(() => submittedQuestion.value || question.value.trim())
const submittedSpreadName = computed(() => getSpreadName(displayedSpread.value))


const reportUiError = (action, error, userMessage = '') => {

  if (userMessage) {
    ElMessage.error(userMessage)
  }
}

const pointsDisplayText = computed(() => {
  if (pointsLoading.value) {
    return '加载中...'
  }

  if (currentPoints.value === null || currentPoints.value === undefined) {
    return '暂未获取'
  }

  return currentPoints.value
})

const canDrawTarot = computed(() => {
  if (isResultLocked.value || !question.value.trim() || pointsLoading.value || loading.value) {
    return false
  }

  if (currentPoints.value === null || currentPoints.value === undefined) {
    return true
  }

  return currentPoints.value >= 5
})



const setFlowError = (stage, message) => {
  flowError.value = {
    stage,
    message,
  }
}

const clearFlowError = () => {
  flowError.value = null
}

const refreshPoints = async ({ silent = false } = {}) => {
  pointsLoading.value = true
  pointsError.value = false

  try {
    const response = await getPointsBalance()
    if (response.code === 200) {
      currentPoints.value = response.data.balance
      pointsError.value = false
      return true
    }

    currentPoints.value = null
    pointsError.value = true
    if (!silent) {
      ElMessage.warning(response.message || '获取积分失败，请稍后重试')
    }
  } catch (error) {
    currentPoints.value = null
    pointsError.value = true
    reportUiError('获取积分失败', error, silent ? '' : '获取积分失败，请稍后重试')
  } finally {
    pointsLoading.value = false
  }

  return false
}


const currentTemplates = computed(() => {
  return selectedTopic.value ? questionTemplates[selectedTopic.value] : []
})

const showQuestionGuide = computed(() => cards.value.length === 0 && (!question.value || questionGuideExpanded.value))

const toggleQuestionGuide = () => {
  questionGuideExpanded.value = !questionGuideExpanded.value
}

const selectSpread = (spreadId) => {
  if (isTarotContextLocked.value) {
    return
  }

  selectedSpread.value = spreadId
}

const displayedSpread = computed(() => submittedSpread.value || selectedSpread.value)


const flowErrorTitle = computed(() => {

  if (!flowError.value) return ''
  return flowError.value.stage === 'interpret' ? '牌面已抽出，但解读未完成' : '抽牌流程暂时中断'
})

const flowErrorDescription = computed(() => {
  if (!flowError.value) return ''

  return flowError.value.stage === 'interpret'
    ? `最近失败原因：${flowError.value.message}。您可以直接重试解读，无需重新抽牌。`
    : `最近失败原因：${flowError.value.message}。请稍后重试，系统会重新发起抽牌流程。`
})

const flowErrorActionText = computed(() => {
  if (!flowError.value) return '重试'
  return flowError.value.stage === 'interpret' ? '重试解读' : '重试抽牌'
})

const interpretationState = computed(() => {
  if (interpretation.value) {
    return 'ready'
  }

  if (loading.value && cards.value.length > 0 && !flowError.value) {
    return 'loading'
  }

  if (flowError.value?.stage === 'interpret') {
    return 'error'
  }

  return 'idle'
})

const spreadPositionLabels = {

  single: ['今日指引'],
  three: ['过去', '现在', '未来'],
  celtic: [
    '当前状态',
    '障碍/挑战',
    '潜意识/基础',
    '过去影响',
    '目标可能',
    '近期发展',
    '你的态度',
    '外部环境',
    '希望/恐惧',
    '最终走向',
  ],
}

const getPositionLabel = (spreadType, index) => {
  const labels = spreadPositionLabels[spreadType] || []
  return labels[index] || `第${index + 1}张`
}

const selectTopic = (topicId) => {
  selectedTopic.value = topicId
  questionGuideExpanded.value = true
}

const selectQuestion = (template) => {
  if (isTarotContextLocked.value) {
    return
  }

  question.value = template
  questionGuideExpanded.value = false
}

const getCurrentTarotQuestion = () => submittedQuestion.value || question.value.trim()
const getCurrentTarotSpread = () => submittedSpread.value || selectedSpread.value
const getCurrentCardNamesText = () => cards.value.map((card) => `${card.name}${card.reversed ? '(逆位)' : '(正位)'}`).join('、')

const buildTarotShareSummary = (shareUrl) => {
  const spreadName = getSpreadName(getCurrentTarotSpread())
  return `我在太初命理完成了一次${spreadName}塔罗占卜\n` +
    '这次先分享摘要与链接，问题全文和牌面详情未写入消息正文\n' +
    `查看详情：${shareUrl}`
}

const buildTarotShareDetailText = (shareUrl) => {
  const cardNames = getCurrentCardNamesText()
  return `我在太初命理进行了塔罗占卜\n` +
    `问题：${getCurrentTarotQuestion()}\n` +
    `抽到的牌：${cardNames}\n` +
    `查看详情：${shareUrl}`
}

const resolveTarotShareText = async (shareUrl) => {
  try {
    await ElMessageBox.confirm(
      '默认只分享摘要与链接。若你确实需要把“问题全文 + 牌面详情”一起发出，请再次确认。',
      '是否附带详细内容',
      {
        confirmButtonText: '附带问题与牌面',
        cancelButtonText: '仅分享摘要',
        distinguishCancelAndClose: true,
        type: 'warning',
      }
    )

    return buildTarotShareDetailText(shareUrl)
  } catch (actionOrError) {
    if (actionOrError === 'cancel' || actionOrError === 'close' || actionOrError?.name === 'AbortError') {
      return buildTarotShareSummary(shareUrl)
    }

    ElMessage.error('分享内容设置失败，请稍后重试')
    return null
  }
}

const getCardDetailAriaLabel = (card, index) => {

  const positionLabel = getPositionLabel(displayedSpread.value, index)
  const directionLabel = card.reversed ? '逆位' : '正位'
  return `查看${positionLabel}${card.name}${directionLabel}的详细解读`
}



// 塔罗牌详细解读数据
const cardDetailedMeanings = {
  '愚者': {
    upright: '新的开始，充满潜力和可能性，勇敢地迈出第一步',
    reversed: '缺乏计划，过于冲动，需要更多思考和准备',
    advice: '相信自己的直觉，但也要脚踏实地'
  },
  '魔术师': {
    upright: '创造力爆发，拥有实现目标的资源和能力',
    reversed: '技能未充分发挥，可能存在欺骗或操纵',
    advice: '运用你的才能，相信自己有实现梦想的能力'
  },
  '女祭司': {
    upright: '倾听内心的声音，直觉会给你正确的指引',
    reversed: '忽视直觉，过于依赖理性分析',
    advice: '静心冥想，答案就在你心中'
  },
  '皇后': {
    upright: '丰饶与滋养，享受生活中的美好事物',
    reversed: '过度放纵，缺乏安全感',
    advice: '关爱自己，同时也关爱身边的人'
  },
  '皇帝': {
    upright: '建立秩序和结构，展现领导力',
    reversed: '专横跋扈，过度控制',
    advice: '建立稳定的框架，但不要过于僵化'
  },
  '教皇': {
    upright: '遵循传统智慧，寻求精神指导',
    reversed: '打破传统，走自己的路',
    advice: '尊重传统，但也要有自己的判断'
  },
  '恋人': {
    upright: '和谐的关系，重要的选择，爱情的到来',
    reversed: '关系失衡，选择困难',
    advice: '跟随内心，做出真诚的选择'
  },
  '战车': {
    upright: '意志坚定，克服困难，取得胜利',
    reversed: '方向不明，力量分散',
    advice: '保持专注，控制好自己的情绪'
  },
  '力量': {
    upright: '内在的力量，以柔克刚，耐心与勇气',
    reversed: '失去信心，被恐惧支配',
    advice: '相信自己的力量，温柔而坚定地面对挑战'
  },
  '隐者': {
    upright: '内省沉思，寻求内在智慧，独处静修',
    reversed: '孤立自闭，孤独寂寞，拒绝指引',
    advice: '花时间反思，找到内心的光芒'
  },
  '命运之轮': {
    upright: '命运的转折，周期的变化，把握机会',
    reversed: '抗拒变化，错失良机',
    advice: '顺应变化，把握命运的转机'
  },
  '正义': {
    upright: '公正公平，因果报应，理性决策',
    reversed: '不公正，逃避责任',
    advice: '做出公正的决定，承担相应的责任'
  },
  '倒吊人': {
    upright: '牺牲与等待，新的视角，耐心',
    reversed: '抗拒改变，无谓的牺牲',
    advice: '换个角度看问题，有时等待是最好的选择'
  },
  '死神': {
    upright: '结束与转变，新的开始，放下过去',
    reversed: '抗拒结束，停滞不前',
    advice: '接受变化，结束是为了更好的开始'
  },
  '节制': {
    upright: '平衡与调和，耐心，中庸之道',
    reversed: '极端，失衡，过度',
    advice: '保持平衡，避免走极端'
  },
  '恶魔': {
    upright: '物质诱惑，束缚，上瘾',
    reversed: '挣脱束缚，重获自由',
    advice: '认识到束缚你的东西，勇敢挣脱'
  },
  '塔': {
    upright: '突然的变化，觉醒，打破旧有模式',
    reversed: '避免改变，内在的崩溃',
    advice: '接受必要的改变，从中学习成长'
  },
  '星星': {
    upright: '希望与灵感，宁静，信心的恢复',
    reversed: '失去信心，绝望',
    advice: '保持希望，相信美好的未来'
  },
  '月亮': {
    upright: '幻觉与恐惧，潜意识，直觉',
    reversed: '真相大白，恐惧消散',
    advice: '面对内心的恐惧，寻找真相'
  },
  '太阳': {
    upright: '快乐与成功，活力，积极能量',
    reversed: '暂时的阴霾，失去信心',
    advice: '保持乐观，阳光总在风雨后'
  },
  '审判': {
    upright: '重生与觉醒，宽恕，新的开始',
    reversed: '自我怀疑，逃避审判',
    advice: '接受自己的过去，勇敢地开始新篇章'
  },
  '世界': {
    upright: '完成与成就，圆满，旅行的结束',
    reversed: '未完成，延迟，缺乏closure',
    advice: '庆祝你的成就，准备开始新的旅程'
  }
}

// 获取当前积分
const loadPoints = async () => {
  await refreshPoints()
}

// 加载客户端配置
const loadClientConfig = async () => {
  try {
    const response = await getClientConfig()
    if (response.code === 200) {
      clientConfig.value = response.data
    }
  } catch (error) {
    console.error('加载客户端配置失败:', error)
  }
}

const interpretCurrentCards = async () => {
  const interpretQuestion = getCurrentTarotQuestion()
  const interpretResponse = await interpretTarot({
    cards: cards.value,
    question: interpretQuestion,
  })

  if (interpretResponse.code === 200) {
    interpretation.value = interpretResponse.data.interpretation
    clearFlowError()
    ElMessage.success('抽牌成功')
    return true
  }

  setFlowError('interpret', interpretResponse.message || '解读失败')
  ElMessage.error(interpretResponse.message || '解读失败')
  if (interpretResponse.code === 403) {
    await loadPoints()
  }

  return false
}

// AI 分析塔罗牌
const performAiAnalysis = async () => {
  if (aiAnalysisLoading.value) return

  if (currentPoints.value < aiAnalysisCost.value) {
    ElMessageBox.confirm(
      `AI 深度分析需要 ${aiAnalysisCost.value} 积分，当前积分不足，是否前往充值？`,
      '积分不足',
      {
        confirmButtonText: '去充值',
        cancelButtonText: '取消',
        type: 'warning',
      }
    ).then(() => {
      router.push('/recharge')
    }).catch(() => {})
    return
  }

  try {
    await ElMessageBox.confirm(
      `AI 深度分析将消耗 ${aiAnalysisCost.value} 积分，为您提供更深入的解读和个性化建议，确认继续吗？`,
      '确认 AI 分析',
      {
        confirmButtonText: '开始分析',
        cancelButtonText: '取消',
        type: 'info',
      }
    )
  } catch {
    return
  }

  aiAnalysisLoading.value = true
  try {
    const response = await aiAnalyzeTarot({
      cards: cards.value,
      question: getCurrentTarotQuestion(),
      spread: getCurrentTarotSpread(),
      interpretation: interpretation.value,
    })

    if (response.code === 200) {
      aiAnalysisResult.value = response.data.analysis
      ElMessage.success('AI 分析完成')
      await loadPoints()
      trackEvent('tarot_ai_analysis_success', {
        spread: getCurrentTarotSpread(),
        cards_count: cards.value.length,
      })
    } else {
      ElMessage.error(response.message || 'AI 分析失败')
      if (response.code === 403) {
        await loadPoints()
      }
      trackError('tarot_ai_analysis_failed', {
        code: response.code,
        message: response.message,
      })
    }
  } catch (error) {
    ElMessage.error('AI 分析请求失败')
    trackError('tarot_ai_analysis_error', error)
  } finally {
    aiAnalysisLoading.value = false
  }
}


// 显示确认对话框
const showConfirm = async () => {
  if (!question.value.trim()) {
    ElMessage.warning('请输入您想咨询的问题')
    return
  }

  if (pointsError.value || currentPoints.value === null || currentPoints.value === undefined) {
    const recovered = await refreshPoints()
    if (!recovered) {
      ElMessage.warning('积分同步失败，请先重新获取积分')
      return
    }
  }

  if (currentPoints.value === null || currentPoints.value === undefined) {
    ElMessage.warning('积分状态同步中，请稍后再试')
    return
  }

  if (currentPoints.value < 5) {
    ElMessageBox.confirm(
      '当前积分不足，是否前往签到或充值获取积分？',
      '积分不足',
      {
        confirmButtonText: '去获取积分',
        cancelButtonText: '取消',
        type: 'warning',
      }
    ).then(() => {
      router.push('/profile')
    }).catch(() => {})
    return
  }

  try {
    await ElMessageBox.confirm(
      `本次占卜将消耗 ${tarotCost.value} 积分，确认继续吗？`,
      '确认占卜',
      {
        confirmButtonText: '确认',
        cancelButtonText: '取消',
        type: 'info',
      }
    )
    await drawCards()
  } catch {
    // 用户取消
  }
}


const drawCards = async () => {
  const lockedQuestion = question.value.trim()
  const lockedSpread = selectedSpread.value

  loading.value = true
  try {
    const drawResponse = await drawTarot({
      spread: lockedSpread,
      question: lockedQuestion,
    })

    if (drawResponse.code === 200) {
      trackSubmit('tarot_draw', true, { spread: lockedSpread })
      cards.value = drawResponse.data.cards
      interpretation.value = ''
      submittedQuestion.value = lockedQuestion
      submittedSpread.value = lockedSpread
      question.value = lockedQuestion
      selectedSpread.value = lockedSpread
      savedRecordId.value = null
      savedShareCode.value = null
      sharePublicConfirmed.value = false

      const remainingPoints = Number(drawResponse.data.remaining_points)

      if (Number.isFinite(remainingPoints)) {
        currentPoints.value = remainingPoints
        window.dispatchEvent(new CustomEvent('points-updated', {
          detail: {
            balance: remainingPoints,
          },
        }))
      }

      clearFlowError()

      await interpretCurrentCards()
      return
    }


    setFlowError('draw', drawResponse.message || '抽牌失败')
    ElMessage.error(drawResponse.message || '抽牌失败')
    if (drawResponse.code === 403) {
      await loadPoints()
    }
  } catch (error) {
    setFlowError('draw', '网络错误，请稍后重试')
    reportUiError('抽牌失败', error, '网络错误，请稍后重试')
  } finally {
    loading.value = false
  }
}

const retryLastAction = async () => {
  if (!flowError.value) {
    return
  }

  if (flowError.value.stage === 'interpret' && cards.value.length > 0) {
    loading.value = true
    try {
      await interpretCurrentCards()
    } catch (error) {
      setFlowError('interpret', '网络错误，请稍后重试')
      reportUiError('重试塔罗解读失败', error, '网络错误，请稍后重试')
    } finally {
      loading.value = false
    }
    return
  }

  await drawCards()
}

const handlePointsUpdated = () => {
  refreshPoints({ silent: true })
}

onMounted(() => {
  trackPageView('tarot')
  loadPoints()
  loadClientConfig()
  window.addEventListener('points-updated', handlePointsUpdated)
})

onUnmounted(() => {
  window.removeEventListener('points-updated', handlePointsUpdated)
})


// 当前保存的记录信息
const savedRecordId = ref(null)
const savedShareCode = ref(null)
const sharePublicConfirmed = ref(false)

// 保存塔罗结果到后端
const saveTarotResult = async () => {
  if (savedRecordId.value) {
    ElMessage.info('已经保存过了')
    return true
  }
  
  try {
    const response = await saveTarotRecord({
      spread_type: getCurrentTarotSpread(),
      question: getCurrentTarotQuestion(),
      cards: cards.value,
      interpretation: interpretation.value,
      ai_analysis: ''
    })


    if (response.code === 200) {
      savedRecordId.value = response.data.record_id
      savedShareCode.value = response.data.share_code
      sharePublicConfirmed.value = false
      window.dispatchEvent(new CustomEvent('tarot-history-updated', {
        detail: {
          recordId: savedRecordId.value,
        },
      }))
      ElMessage.success('保存成功，已同步到云端塔罗历史，可在个人中心查看')
      return true
    }

    ElMessage.error(response.message || '保存失败')
  } catch (error) {
    reportUiError('保存塔罗记录失败', error, '保存失败，请稍后重试')
  }

  return false
}


const updateTarotShareVisibility = async (isPublic, { silent = false } = {}) => {
  if (!savedRecordId.value) {
    return false
  }

  const errorMessage = isPublic ? '分享链接准备失败，请稍后重试' : '恢复私密状态失败，请稍后重试'

  try {
    const response = await setTarotPublic({ id: savedRecordId.value, is_public: isPublic })
    if (response.code === 200) {
      savedShareCode.value = response.data.share_code || savedShareCode.value
      sharePublicConfirmed.value = isPublic
      return true
    }

    if (!silent) {
      ElMessage.error(response.message || errorMessage)
    }
  } catch (error) {
    reportUiError(
      isPublic ? '准备塔罗分享链接失败' : '恢复塔罗私密状态失败',
      error,
      silent ? '' : errorMessage
    )
  }

  return false
}

const ensureTarotShareReady = async () => {
  if (!savedRecordId.value) {
    return { ready: false, exposedNow: false }
  }

  if (sharePublicConfirmed.value && savedShareCode.value) {
    return { ready: true, exposedNow: false }
  }

  try {
    await ElMessageBox.confirm(
      '分享前需要先将本次塔罗记录设为公开，仅持有链接的人可以查看。若你稍后取消系统分享，我们会自动恢复为私密状态。',
      '确认公开分享',
      {
        confirmButtonText: '确认公开并分享',
        cancelButtonText: '先不分享',
        distinguishCancelAndClose: true,
        type: 'warning',
      }
    )
  } catch (actionOrError) {
    if (actionOrError === 'cancel' || actionOrError === 'close' || actionOrError?.name === 'AbortError') {
      return { ready: false, exposedNow: false }
    }

    ElMessage.error('分享确认失败，请稍后重试')
    return { ready: false, exposedNow: false }
  }

  const ready = await updateTarotShareVisibility(true)
  return { ready, exposedNow: ready }
}

// 分享塔罗结果
const shareTarotResult = async () => {
  // 如果没有保存过，先保存
  if (!savedRecordId.value) {
    const saved = await saveTarotResult()
    if (!saved) {
      return
    }
  }

  
  if (!savedRecordId.value) {
    return
  }

  const { ready: shareReady, exposedNow } = await ensureTarotShareReady()
  if (!shareReady || !savedShareCode.value) {
    return
  }

  // 生成分享链接
  const shareUrl = `${window.location.origin}/tarot/share/${savedShareCode.value}`
  const shareText = await resolveTarotShareText(shareUrl)
  if (!shareText) {
    if (exposedNow) {
      await updateTarotShareVisibility(false)
    }
    return
  }

  if (navigator.share) {

    try {
      await navigator.share({
        title: '我的塔罗占卜结果',
        text: shareText,
        url: shareUrl
      })
    } catch (error) {
      if (exposedNow) {
        const reverted = await updateTarotShareVisibility(false)
        if (reverted && error?.name === 'AbortError') {
          ElMessage.info('已取消分享，本次记录仍保持私密')
        }
      }

      if (error?.name !== 'AbortError') {
        reportUiError('系统分享失败', error, '分享失败，请稍后重试')
      }
    }

    return
  }

  try {
    await navigator.clipboard.writeText(shareText)
    ElMessage.success('分享链接已复制到剪贴板')
  } catch (error) {
    if (exposedNow) {
      await updateTarotShareVisibility(false)
    }
    reportUiError('复制分享内容失败', error, '复制失败，请手动复制')
  }
}




// 重置占卜
const resetTarot = () => {
  cards.value = []
  interpretation.value = ''
  question.value = ''
  submittedQuestion.value = ''
  submittedSpread.value = ''
  selectedTopic.value = ''
  questionGuideExpanded.value = true
  savedRecordId.value = null
  savedShareCode.value = null
  sharePublicConfirmed.value = false
  clearFlowError()
}

const shouldShowTarotRechargeAction = computed(() => Number.isFinite(currentPoints.value) && currentPoints.value < 5)

const tarotResultHighlights = computed(() => {
  const highlights = [
    {
      key: 'spread',
      label: `牌阵：${submittedSpreadName.value}`,
    },
    {
      key: 'record',
      label: savedRecordId.value ? '已同步到云端记录' : '可先保存到云端记录',
      tone: savedRecordId.value ? 'success' : 'warning',
    },
    {
      key: 'share',
      label: sharePublicConfirmed.value ? '当前已允许分享' : '默认仍为私密状态',
    },
  ]

  if (flowError.value?.stage === 'interpret') {
    highlights.push({
      key: 'retry-interpret',
      label: '解读中断后可直接重试，无需重新抽牌',
      tone: 'danger',
    })
  }

  return highlights
})

const tarotResultActions = computed(() => {
  return [
    {
      key: 'save',
      label: savedRecordId.value ? '已保存到云端' : '保存记录',
      type: 'primary',
      disabled: !interpretation.value || Boolean(savedRecordId.value),
      onClick: saveTarotResult,
    },
    {
      key: 'share',
      label: '分享结果',
      onClick: shareTarotResult,
      disabled: !interpretation.value,
    },
    {
      key: 'download',
      label: '保存为图片',
      onClick: downloadAsImage,
      disabled: !interpretation.value,
    },
    {
      key: 'history',
      label: '查看我的记录',
      to: '/profile',
    },
    shouldShowTarotRechargeAction.value
      ? {
          key: 'recharge',
          label: '去充值 / 补积分',
          plain: true,
          to: '/recharge',
        }
      : null,
    {
      key: 'retry',
      label: '重新占卜',
      plain: true,
      onClick: resetTarot,
    },
  ].filter(Boolean)
})

const tarotRelatedRecommendations = computed(() => {
  return [
    {
      key: 'daily',
      title: '去看今日运势',
      description: '把这次塔罗结论和当天整体节奏放在一起看，更容易判断要不要立刻行动。',
      to: '/daily',
      badge: '相关推荐',
    },
    {
      key: 'bazi',
      title: '补一版八字排盘',
      description: '如果你想把短期问题放回长期命盘里再核对，可以继续看八字主线。',
      to: '/bazi',
      badge: '继续深入',
    },
  ]
})


// 显示卡片详情

const showCardDetail = (card, index = 0) => {
  const currentSpread = getCurrentTarotSpread()
  selectedCard.value = {
    ...card,
    positionIndex: index,
    positionLabel: getPositionLabel(currentSpread, index),
    spreadType: currentSpread,
    spreadName: getSpreadName(currentSpread),
  }
  cardDetailVisible.value = true
}



// 获取卡片详细含义
const getCardDetailedMeaning = (card) => {
  const meaning = cardDetailedMeanings[card.name]
  if (!meaning) return card.meaning
  return card.reversed ? meaning.reversed : meaning.upright
}

// 获取卡片建议
const getCardAdvice = (card) => {
  const meaning = cardDetailedMeanings[card.name]
  if (!meaning) return '相信直觉，找到属于你的答案'
  return meaning.advice
}

// 保存为图片
const downloadAsImage = async () => {
  const resultElement = document.querySelector('.cards-result')
  if (!resultElement) return

  try {
    const canvas = await html2canvas(resultElement, {
      useCORS: true,
      scale: 2,
      backgroundColor: '#fffdf8'
    })
    
    const link = document.createElement('a')
    link.download = `塔罗占卜结果_${new Date().getTime()}.png`
    link.href = canvas.toDataURL('image/png')
    link.click()
    
    ElMessage.success('图片保存成功')
  } catch (error) {
    reportUiError('保存图片失败', error, '保存图片失败，请稍后重试')
  }
}

return {
  // 状态数据
  tarotCost, spreads, questionTopics, selectedSpread,
  question, loading, pointsLoading, pointsError,
  cards, interpretation, currentPoints,
  cardDetailVisible, selectedCard, selectedTopic,
  questionGuideExpanded, flowError,
  aiAnalysisLoading, aiAnalysisResult, aiAnalysisCost,

  // 计算属性
  isResultLocked, isTarotContextLocked,
  submittedQuestionDisplay, submittedSpreadName,
  pointsDisplayText, canDrawTarot,
  currentTemplates, showQuestionGuide,
  displayedSpread,
  flowErrorTitle, flowErrorDescription, flowErrorActionText,
  interpretationState,
  shouldShowTarotRechargeAction,
  tarotResultHighlights, tarotResultActions, tarotRelatedRecommendations,

  // 方法
  toggleQuestionGuide, selectSpread,
  getPositionLabel, selectTopic, selectQuestion,
  getCardDetailAriaLabel,
  loadPoints, performAiAnalysis, retryLastAction,
  resetTarot, showCardDetail, showConfirm,
  getCardDetailedMeaning, getCardAdvice,
}
} // end useTarot
