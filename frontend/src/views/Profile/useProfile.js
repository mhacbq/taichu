import { computed, ref, onMounted, onUnmounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { getUserInfo, getPointsBalance, getPointsHistory, getBaziHistory, getTarotHistory, getLiuyaoHistory, getHehunHistory, submitFeedback, getClientConfig, getMyInvites, getPointsRules, dailyCheckin, getCheckinStatus } from '../../api'
import { useTourGuide } from '../../composables/useTourGuide'
import { formatTime, formatDate } from '../../utils/format'





export function useProfile() {
const router = useRouter()

// 新用户引导
const { resetTour, startTour } = useTourGuide()

const restartTourGuide = () => {
  resetTour()
  nextTick(() => {
    router.push('/')
    setTimeout(() => {
      startTour()
    }, 500)
  })
}

const userInfo = ref({})
const pointsBalance = ref(0)
const baziCount = ref(0)
const tarotCount = ref(0)
const pointsHistory = ref([])
const baziHistory = ref([])
const tarotHistory = ref([])
const liuyaoHistory = ref([])
const hehunHistory = ref([])
const liuyaoCount = ref(0)
const hehunCount = ref(0)
const feedbackContent = ref('')
const feedbackContact = ref('')
const feedbackLoading = ref(false)
const feedbackEnabled = ref(true)
const activeHistoryTab = ref('bazi')

// 生日相关
const userBirthDate = ref('')

// 签到相关
const checkinStatus = ref({
  today_checkin: false,
  consecutive_days: 0
})
const checkinLoading = ref(false)

// 状态管理
const profileStatus = ref('loading')
const profileError = ref(null)
const baziStatus = ref('loading')
const tarotStatus = ref('loading')
const liuyaoStatus = ref('loading')
const hehunStatus = ref('loading')

// 分页相关
const baziCurrentPage = ref(1)
const baziPageSize = ref(5)
const baziTotal = ref(0)

// 积分等级相关
const pointsLevels = [
  { name: '初学乍练', min: 0, max: 100, color: '#909399' },
  { name: '略有小成', min: 101, max: 500, color: '#67C23A' },
  { name: '融会贯通', min: 501, max: 2000, color: '#E6A23C' },
  { name: '登峰造极', min: 2001, max: 10000, color: '#F56C6C' },
  { name: '返璞归真', min: 10001, max: Infinity, color: '#D4AF37' }
]

const currentPointsLevel = computed(() => {
  return pointsLevels.find(level => pointsBalance.value >= level.min && pointsBalance.value <= level.max) || pointsLevels[0]
})

const pointsLevelName = computed(() => currentPointsLevel.value.name)

// VIP 相关
const isVip = computed(() => userInfo.value?.is_vip || false)
const vipExpireTime = computed(() => userInfo.value?.vip_expire_time || '')

const pointsPercentage = computed(() => {
  const level = currentPointsLevel.value
  if (level.max === Infinity) return 100
  const range = level.max - level.min
  const current = pointsBalance.value - level.min
  return Math.min(100, Math.max(0, (current / range) * 100))
})

const pointsProgressColor = computed(() => currentPointsLevel.value.color)

const pointsToNextLevel = computed(() => {
  const level = currentPointsLevel.value
  if (level.max === Infinity) return 0
  return level.max - pointsBalance.value + 1
})

const pointsProgressFormat = (percentage) => {
  return `${pointsBalance.value} / ${currentPointsLevel.value.max === Infinity ? '∞' : currentPointsLevel.value.max}`
}

// 积分获取方式（从后端动态加载）
const pointsMethodsRaw = ref([])
const pointsMethods = computed(() => {
  const list = pointsMethodsRaw.value.length > 0
    ? pointsMethodsRaw.value
    : [] // 加载中时为空
  return feedbackEnabled.value
    ? list
    : list.filter(m => m.action !== 'feedback')
})

// 邀请记录相关
const inviteRecords = ref([])
const inviteRecordsTotal = ref(0)
const inviteSuccessCount = ref(0)
const inviteRecordsPage = ref(1)
const inviteRecordsLoading = ref(false)


// 邀请相关
const inviteCode = ref('')
const inviteCount = ref(0)
const invitePoints = ref(0)
const inviteLink = ref('')

const truncateClientMessage = (value, maxLength = 160) => {
  const text = typeof value === 'string' ? value.trim() : ''
  if (!text) {
    return ''
  }

  return text.length > maxLength ? `${text.slice(0, maxLength)}...` : text
}

const sanitizeProfileErrorMessage = (error) => {
  const message = typeof error?.message === 'string' ? error.message : String(error ?? '')
  return truncateClientMessage(message) || 'unknown'
}

const reportProfileError = (action, error, extra = {}) => {
  if (!import.meta.env.DEV) {
    return
  }

  console.error('[Profile]', {
    action,
    error_type: error?.name || typeof error,
    message: sanitizeProfileErrorMessage(error),
    ...extra
  })
}

const resolveFeedbackEnabled = (config = {}) => {
  const featureConfig = config?.features?.feedback
  if (featureConfig && typeof featureConfig === 'object' && 'enabled' in featureConfig) {
    return Boolean(featureConfig.enabled)
  }

  if (typeof featureConfig === 'boolean') {
    return featureConfig
  }

  return true
}

const syncClientFeatureConfig = async () => {
  try {
    const response = await getClientConfig()
    if (response?.code === 0) {
      feedbackEnabled.value = resolveFeedbackEnabled(response.data)
      if (!feedbackEnabled.value) {
        feedbackContent.value = ''
        feedbackContact.value = ''
      }
      return
    }
  } catch (error) {
    reportProfileError('load_client_config_failed', error)
  }

  feedbackEnabled.value = true
}

const safeReadArrayFromStorage = (key) => {

  try {
    const rawValue = localStorage.getItem(key)
    if (!rawValue) {
      return []
    }

    const parsedValue = JSON.parse(rawValue)
    return Array.isArray(parsedValue) ? parsedValue : []
  } catch (error) {
    reportProfileError('read_storage_failed', error, { storage_key: key })
    return []
  }
}

const normalizeTarotCard = (card) => {
  const source = card && typeof card === 'object' ? card : {}

  return {
    ...source,
    name: typeof source.name === 'string' && source.name.trim() ? source.name.trim() : '未知牌',
    reversed: Boolean(source.reversed),
    emoji: typeof source.emoji === 'string' && source.emoji.trim() ? source.emoji : '🃏'
  }
}

const normalizeTarotRecord = (record, index = 0) => {
  const source = record && typeof record === 'object' ? record : {}
  const cards = Array.isArray(source.cards) ? source.cards.map(normalizeTarotCard) : []
  const spreadNameMap = {
    single: '单牌占卜',
    three: '三张牌阵',
    celtic: '凯尔特十字',
    relationship: '关系牌阵',
  }

  return {
    ...source,
    question: typeof source.question === 'string' && source.question.trim()
      ? source.question.trim()
      : `塔罗记录 ${index + 1}`,
    cards,
    date: source.date || source.created_at || '',
    spreadName: source.spread_name || spreadNameMap[source.spread_type] || '塔罗占卜',
  }
}


const getTarotCards = (record) => {
  return Array.isArray(record?.cards) ? record.cards : []
}

const loadPointsRules = async () => {
  try {
    const res = await getPointsRules()
    if (res.code === 0 && Array.isArray(res.data?.tasks)) {
      pointsMethodsRaw.value = res.data.tasks.map((item, idx) => ({
        id: idx + 1,
        icon: item.icon || 'gift',
        name: item.name,
        desc: '',
        points: item.points,
        action: item.action || null,
        actionText: item.actionText || '',
      }))
    } else {
      // 后端返回失败时，显示默认积分获取规则
      pointsMethodsRaw.value = [
        { id: 1, icon: 'calendar', name: '每日签到', points: 10, action: 'checkin', actionText: '去签到' },
        { id: 2, icon: 'share', name: '邀请好友', points: 50, action: 'invite', actionText: '去邀请' },
        { id: 3, icon: 'chat', name: '提交反馈', points: 20, action: 'feedback', actionText: '去反馈' },
        { id: 4, icon: 'star', name: '完成新手任务', points: 100, action: 'task', actionText: '去完成' },
        { id: 5, icon: 'book', name: '使用八字排盘', points: 5, action: 'bazi', actionText: '去排盘' }
      ]
    }
  } catch (e) {
    // 加载失败时显示默认规则，确保用户能看到积分获取方式
    pointsMethodsRaw.value = [
      { id: 1, icon: 'calendar', name: '每日签到', points: 10, action: 'checkin', actionText: '去签到' },
      { id: 2, icon: 'share', name: '邀请好友', points: 50, action: 'invite', actionText: '去邀请' },
      { id: 3, icon: 'chat', name: '提交反馈', points: 20, action: 'feedback', actionText: '去反馈' },
      { id: 4, icon: 'star', name: '完成新手任务', points: 100, action: 'task', actionText: '去完成' },
      { id: 5, icon: 'book', name: '使用八字排盘', points: 5, action: 'bazi', actionText: '去排盘' }
    ]
  }
}

const loadInviteRecords = async (page = 1) => {
  inviteRecordsLoading.value = true
  try {
    const res = await getMyInvites({ page, limit: 10 })
    if (res.code === 0) {
      inviteRecords.value = res.data?.list || []
      inviteRecordsTotal.value = res.data?.total || 0
      inviteSuccessCount.value = res.data?.success_count || 0
      inviteRecordsPage.value = page
    }
  } catch (e) {
    // 静默处理
  } finally {
    inviteRecordsLoading.value = false
  }
}

const loadUserData = async () => {
  profileStatus.value = 'loading'
  try {
    const [userRes, pointsRes, historyRes] = await Promise.all([
      getUserInfo(),
      getPointsBalance(),
      getPointsHistory(),
    ])
    
    if (userRes.code === 0) {
      userInfo.value = userRes.data
      // 使用后端返回的邀请码和统计
      inviteCode.value = userRes.data.invite_code || ''
      inviteCount.value = userRes.data.invite_count || 0
      invitePoints.value = userRes.data.invite_points || 0
      // 生成邀请链接
      inviteLink.value = `${window.location.origin}/login?invite_code=${inviteCode.value}`
    }
    
    if (pointsRes.code === 0) {
      pointsBalance.value = pointsRes.data.balance
      baziCount.value = pointsRes.data.baziCount || 0
      tarotCount.value = pointsRes.data.tarotCount || 0
    }
    
    if (historyRes.code === 0) {
      pointsHistory.value = historyRes.data?.list || historyRes.data || []
    }

    await syncClientFeatureConfig()

    // 并行加载积分规则和邀请记录
    loadPointsRules()
    loadInviteRecords()
    
    // 加载排盘历史
    await loadBaziHistory()
    
    // 加载塔罗历史
    await loadTarotHistory()
    // 加载六爻历史
    await loadLiuyaoHistory()
    // 加载合婚历史
    await loadHehunHistory()

    // 所有数据加载完成，更新状态
    profileStatus.value = 'success'
  } catch (error) {
    profileStatus.value = 'error'
    reportProfileError('load_user_data_failed', error)
  }
}


// 加载排盘历史（后端分页）
const loadBaziHistory = async () => {
  baziStatus.value = 'loading'
  try {
    const baziRes = await getBaziHistory({
      page: baziCurrentPage.value,
      page_size: baziPageSize.value
    })
    if (baziRes.code === 0) {
      baziHistory.value = baziRes.data.list || []
      baziTotal.value = baziRes.data.pagination?.total || 0
      baziStatus.value = baziHistory.value.length > 0 ? 'success' : 'empty'
    } else {
      baziStatus.value = 'error'
    }
  } catch (error) {
    baziStatus.value = 'error'
    reportProfileError('load_bazi_history_failed', error, {
      page: baziCurrentPage.value,
      page_size: baziPageSize.value
    })
  }
}

// 加载塔罗历史
const loadTarotHistory = async () => {
  tarotStatus.value = 'loading'
  try {
    const response = await getTarotHistory({ page: 1, page_size: 10 })
    if (response.code === 0) {
      const list = Array.isArray(response.data?.list) ? response.data.list : []
      tarotHistory.value = list.map(normalizeTarotRecord)
      tarotStatus.value = tarotHistory.value.length > 0 ? 'success' : 'empty'
      return
    }

    tarotHistory.value = []
    tarotStatus.value = 'error'
    ElMessage.warning(response.message || '获取塔罗历史失败，请稍后重试')
  } catch (error) {
    tarotHistory.value = []
    tarotStatus.value = 'error'
    reportProfileError('load_tarot_history_failed', error)
  }
}

// 加载六爻历史
const loadLiuyaoHistory = async () => {
  liuyaoStatus.value = 'loading'
  try {
    const response = await getLiuyaoHistory({ page: 1, page_size: 10 })
    if (response.code === 0) {
      liuyaoHistory.value = response.data?.list || []
      liuyaoCount.value = response.data?.pagination?.total || liuyaoHistory.value.length
      liuyaoStatus.value = liuyaoHistory.value.length > 0 ? 'success' : 'empty'
    } else {
      liuyaoStatus.value = 'error'
    }
  } catch (error) {
    reportProfileError('load_liuyao_history_failed', error)
    liuyaoHistory.value = []
    liuyaoStatus.value = 'error'
  }
}

// 加载合婚历史
const loadHehunHistory = async () => {
  hehunStatus.value = 'loading'
  try {
    const response = await getHehunHistory({ page: 1, page_size: 10 })
    if (response.code === 0) {
      hehunHistory.value = response.data?.list || []
      hehunCount.value = response.data?.pagination?.total || hehunHistory.value.length
      hehunStatus.value = hehunHistory.value.length > 0 ? 'success' : 'empty'
    } else {
      hehunStatus.value = 'error'
    }
  } catch (error) {
    reportProfileError('load_hehun_history_failed', error)
    hehunHistory.value = []
    hehunStatus.value = 'error'
  }
}

const handleTarotHistoryUpdated = () => {
  loadTarotHistory()
}



const submitFeedbackForm = async () => {
  if (!feedbackEnabled.value) {
    ElMessage.warning('反馈功能暂时关闭')
    return
  }

  const content = feedbackContent.value.trim()
  if (!content || content.length < 5) {
    ElMessage.warning('反馈内容不能少于5个字符')
    return
  }

  if (content.length > 5000) {
    ElMessage.warning('反馈内容不能超过5000个字符')
    return
  }

  const contact = feedbackContact.value.trim()
  if (contact && !validateContact(contact)) {
    ElMessage.warning('请填写有效的邮箱地址或手机号码')
    return
  }


  feedbackLoading.value = true
  try {
    const response = await submitFeedback({
      content: content,
      type: 'suggestion',
      contact: contact,
    })

    if (response.code === 0) {
      ElMessage.success('反馈提交成功，感谢您的建议！')
      feedbackContent.value = ''
      feedbackContact.value = ''
    } else {
      ElMessage.error(response.message || '提交失败')
    }
  } catch (error) {
    ElMessage.error('网络错误，请稍后重试')
    reportProfileError('submit_feedback_failed', error)
  } finally {
    feedbackLoading.value = false
  }
}

/**
 * 验证联系方式（邮箱或手机号）
 */
const validateContact = (contact) => {
  if (!contact) return true

  // 邮箱格式验证
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  if (emailRegex.test(contact)) {
    return true
  }

  // 中国大陆手机号验证
  const phoneRegex = /^1[3-9]\d{9}$/
  if (phoneRegex.test(contact)) {
    return true
  }

  return false
}

const loadUserBirthDate = () => {
  const savedBirthDate = localStorage.getItem('user_birth_date')
  if (savedBirthDate) {
    userBirthDate.value = savedBirthDate
  }
}

const saveBirthDate = async (value) => {
  if (!value) {
    ElMessage.warning('请选择出生日期')
    return
  }

  try {
    // 保存到本地存储
    localStorage.setItem('user_birth_date', value)
    userBirthDate.value = value
    ElMessage.success('出生日期设置成功')
    
    // 这里可以添加调用后端API的代码
    // await updateUserBirthDate(value)
  } catch (err) {
    ElMessage.error('设置失败，请重试')
  }
}

// 获取签到状态
const loadCheckinStatus = async () => {
  try {
    const res = await getCheckinStatus()
    if (res.code === 0) {
      checkinStatus.value = {
        today_checkin: res.data?.today_checkin || false,
        consecutive_days: res.data?.consecutive_days || 0
      }
    }
  } catch (error) {
    console.error('获取签到状态失败:', error)
  }
}

// 执行签到
const doCheckin = async () => {
  if (checkinLoading.value) return
  if (checkinStatus.value.today_checkin) {
    ElMessage.info('今天已经签到过了，明天再来吧！')
    return
  }

  checkinLoading.value = true
  try {
    const res = await dailyCheckin()
    if (res.code === 0) {
      const data = res.data
      // 更新签到状态
      checkinStatus.value.today_checkin = true
      checkinStatus.value.consecutive_days = data.consecutive_days || 0
      // 更新积分余额
      pointsBalance.value = data.total_points || pointsBalance.value
      // 显示成功消息
      const message = data.bonus_points > 0
        ? `签到成功！获得 ${data.points_earned} 积分（连续${data.consecutive_days}天额外奖励${data.bonus_points}积分）`
        : `签到成功！获得 ${data.points_earned} 积分`
      ElMessage.success(message)
      // 刷新积分历史
      await loadPointsHistory()
    } else {
      ElMessage.error(res.message || '签到失败，请稍后重试')
    }
  } catch (error) {
    console.error('签到失败:', error)
    ElMessage.error('签到失败，请稍后重试')
  } finally {
    checkinLoading.value = false
  }
}

const viewDetail = (record) => {
  if (!record?.id) {
    ElMessage.warning('记录数据异常，无法查看详情')
    return
  }
  router.push(`/bazi?record_id=${record.id}`)
}

const viewLiuyaoDetail = (record) => {
  if (!record?.id) {
    ElMessage.warning('记录数据异常，无法查看详情')
    return
  }
  router.push(`/liuyao?record_id=${record.id}`)
}

const viewHehunDetail = (record) => {
  if (!record?.id) {
    ElMessage.warning('记录数据异常，无法查看详情')
    return
  }
  router.push(`/hehun?record_id=${record.id}`)
}

const viewTarotDetail = (record) => {
  if (!record?.id) {
    ElMessage.warning('记录数据异常，无法查看详情')
    return
  }
  // 跳转到塔罗结果页
  router.push(`/tarot?record_id=${record.id}`)
}


// 处理积分获取方式点击
const handleMethodAction = (method) => {
  switch (method.action) {
    case 'checkin':
      window.scrollTo({ top: 0, behavior: 'smooth' })
      break
    case 'invite':
      document.querySelector('.invite-section')?.scrollIntoView({ behavior: 'smooth' })
      break
    case 'feedback':
      document.querySelector('.feedback-section')?.scrollIntoView({ behavior: 'smooth' })
      break
  }
}

// 复制邀请码
const copyInviteCode = () => {
  if (!inviteCode.value) {
    ElMessage.warning('邀请码加载中，请稍后再试')
    return
  }
  navigator.clipboard.writeText(inviteCode.value).then(() => {
    ElMessage.success('邀请码已复制到剪贴板')
  }).catch(() => {
    ElMessage.error('复制失败，请手动复制')
  })
}

// 复制邀请链接
const copyInviteLink = () => {
  if (!inviteLink.value) {
    ElMessage.warning('链接生成中，请稍后再试')
    return
  }
  navigator.clipboard.writeText(inviteLink.value).then(() => {
    ElMessage.success('邀请链接已复制到剪贴板')
  }).catch(() => {
    ElMessage.error('复制失败，请手动复制')
  })
}

// 分享到微信
const shareToWechat = () => {
  const shareText = `我在用「太初命理」进行八字排盘和运势分析，非常准确！

使用我的邀请码【${inviteCode.value}】注册，我们双方都能获得20积分奖励！

快来试试吧
${inviteLink.value}`

  if (navigator.share) {
    navigator.share({
      title: '邀请你使用太初命理',
      text: shareText
    })
  } else {
    navigator.clipboard.writeText(shareText).then(() => {
      ElMessage.success('分享内容已复制，请粘贴到微信发送给好友')
    }).catch(() => {
      ElMessage.error('复制失败，请手动复制')
    })
  }
}

onMounted(() => {
  loadUserData()
  loadUserBirthDate()
  loadCheckinStatus()
  window.addEventListener('tarot-history-updated', handleTarotHistoryUpdated)
})


onUnmounted(() => {
  window.removeEventListener('tarot-history-updated', handleTarotHistoryUpdated)
})

return {
  // 状态
  userInfo, pointsBalance, baziCount, tarotCount, liuyaoCount, hehunCount,
  pointsHistory, baziHistory, tarotHistory, liuyaoHistory, hehunHistory,
  feedbackContent, feedbackContact, feedbackLoading, feedbackEnabled,
  activeHistoryTab, userBirthDate,
  profileStatus, profileError,
  baziStatus, tarotStatus, liuyaoStatus, hehunStatus,
  baziCurrentPage, baziPageSize, baziTotal,
  inviteCode, inviteCount, invitePoints, inviteLink,
  inviteRecords, inviteRecordsTotal, inviteSuccessCount,
  inviteRecordsPage, inviteRecordsLoading,
  checkinStatus, checkinLoading,

  // 计算属性
  currentPointsLevel, pointsLevelName, pointsPercentage,
  pointsProgressColor, pointsToNextLevel, pointsMethods,
  isVip, vipExpireTime,

  // 常量
  pointsLevels,

  // 方法
  pointsProgressFormat, formatTime, formatDate,
  restartTourGuide,
  loadBaziHistory, loadTarotHistory, loadLiuyaoHistory, loadHehunHistory,
  loadInviteRecords,
  submitFeedbackForm, saveBirthDate,
  viewDetail, viewTarotDetail, viewLiuyaoDetail, viewHehunDetail,
  handleMethodAction, copyInviteCode, copyInviteLink, shareToWechat,
  loadCheckinStatus, doCheckin,
}
} // end useProfile
