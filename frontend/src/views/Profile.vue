<template>
  <div class="profile-page">
    <div class="container">
      <div class="profile-hero">
        <BackButton />
        <div class="profile-hero-content">
          <div class="profile-hero-avatar">
            <img v-if="userInfo.avatar" :src="userInfo.avatar" alt="用户头像" class="profile-hero-avatar-img">
            <span v-else class="profile-hero-avatar-placeholder">{{ userInfo.nickname?.[0] || '用' }}</span>
          </div>
          <div class="profile-hero-info">
            <h1 class="profile-hero-name">{{ userInfo.nickname || '欢迎回来' }}</h1>
            <p class="profile-hero-sub">
              <span class="profile-hero-id">ID: {{ userInfo.id || '--' }}</span>
              <span class="profile-hero-divider">·</span>
              <span class="profile-hero-points-badge">
                <el-icon><Coin /></el-icon>
                {{ pointsBalance }} 积分
              </span>
            </p>
          </div>
        </div>
      </div>
      
      <!-- 签到卡片 -->
      <CheckinCard />

      <div class="profile-layout">
        <!-- 左侧边栏 -->
        <aside class="profile-sidebar">
          <!-- 用户信息卡片 -->
          <div class="sidebar-card user-card card-hover">
            <div class="user-stats">
              <div class="stat-item">
                <span class="stat-value">{{ pointsBalance }}</span>
                <span class="stat-label">积分</span>
              </div>
              <div class="stat-item">
                <span class="stat-value">{{ baziCount }}</span>
                <span class="stat-label">排盘</span>
              </div>
              <div class="stat-item">
                <span class="stat-value">{{ tarotCount }}</span>
                <span class="stat-label">占卜</span>
              </div>
              <div class="stat-item">
                <span class="stat-value">{{ liuyaoCount }}</span>
                <span class="stat-label">六爻</span>
              </div>
              <div class="stat-item">
                <span class="stat-value">{{ hehunCount }}</span>
                <span class="stat-label">合婚</span>
              </div>
            </div>
            
            <!-- 积分等级 -->
            <div class="level-section">
              <div class="level-header">
                <span class="level-title">积分等级</span>
                <span class="level-name">{{ pointsLevelName }}</span>
              </div>
              <el-progress 
                :percentage="pointsPercentage" 
                :format="pointsProgressFormat"
                :color="pointsProgressColor"
                :stroke-width="10"
                class="level-progress"
              />
              <div class="level-footer">
                <span>还需 {{ pointsToNextLevel }} 积分升级</span>
                <router-link to="/recharge" class="recharge-btn">充值</router-link>
              </div>
            </div>
          </div>

          <!-- 积分获取攻略 -->
          <div class="sidebar-card guide-card card-hover">
            <h4><el-icon><Coin /></el-icon> 积分获取攻略</h4>
            <div class="guide-list">
              <div class="guide-item" v-for="method in pointsMethods" :key="method.id">
                <div class="guide-icon">
                  <el-icon v-if="method.icon === 'calendar'"><Calendar /></el-icon>
                  <el-icon v-else-if="method.icon === 'present'"><Present /></el-icon>
                  <el-icon v-else-if="method.icon === 'user'"><UserFilled /></el-icon>
                  <el-icon v-else-if="method.icon === 'share'"><Share /></el-icon>
                  <el-icon v-else-if="method.icon === 'chat'"><ChatDotRound /></el-icon>
                </div>
                <div class="guide-content">
                  <span class="guide-name">{{ method.name }}</span>
                  <span class="guide-reward">+{{ method.points }}</span>
                </div>
                <el-button 
                  v-if="method.action" 
                  type="primary" 
                  size="small"
                  text
                  @click="handleMethodAction(method)"
                >
                  {{ method.actionText }}
                </el-button>
              </div>
            </div>
          </div>

          <!-- 邀请好友 -->
          <div class="sidebar-card invite-card card-hover">
            <h4><el-icon><Present /></el-icon> 邀请好友</h4>
            <p class="invite-text">每邀请一位好友，双方各得 <strong>20积分</strong></p>
            <div class="invite-actions">
              <el-button type="primary" @click="copyInviteCode" class="invite-btn-main">
                <el-icon><DocumentCopy /></el-icon> 复制邀请码
              </el-button>
              <el-button @click="copyInviteLink" size="small">
                <el-icon><Link /></el-icon> 分享链接
              </el-button>
            </div>
            <div class="invite-stats">
              <div class="invite-stat">
                <span class="stat-num">{{ inviteCount }}</span>
                <span class="stat-txt">已邀请</span>
              </div>
              <div class="invite-stat">
                <span class="stat-num">{{ invitePoints }}</span>
                <span class="stat-txt">获积分</span>
              </div>
            </div>
          </div>
        </aside>

        <!-- 右侧主区域 -->
        <main class="profile-main">
          <!-- 历史记录 -->
          <div class="main-card history-card card-hover">
            <el-tabs v-model="activeHistoryTab" class="custom-tabs">
              <el-tab-pane label="八字排盘" name="bazi">
                <AsyncState :status="baziStatus" loadingText="正在加载排盘记录..." @retry="loadBaziHistory">
                  <div class="history-grid" v-if="baziHistory.length > 0">
                    <div v-for="record in baziHistory" :key="record.id" class="history-record">
                      <div class="record-header">
                        <span class="record-time">{{ formatTime(record.createdAt) }}</span>
                        <span class="record-birth">{{ formatDate(record.birthDate) }} · {{ record.gender === 'male' ? '男' : '女' }}</span>
                      </div>
                      <div class="record-content">
                        <span class="bazi-p">{{ record.yearGan }}{{ record.yearZhi }}</span>
                        <span class="bazi-p">{{ record.monthGan }}{{ record.monthZhi }}</span>
                        <span class="bazi-p highlight">{{ record.dayGan }}{{ record.dayZhi }}</span>
                        <span class="bazi-p">{{ record.hourGan }}{{ record.hourZhi }}</span>
                      </div>
                      <el-button type="primary" size="small" @click="viewDetail(record)">查看详情</el-button>
                    </div>
                  </div>
                  <el-empty v-else description="暂无排盘记录" />
                  <div class="pagination-wrapper" v-if="baziTotal > baziPageSize">
                    <el-pagination
                      v-model:current-page="baziCurrentPage"
                      v-model:page-size="baziPageSize"
                      :total="baziTotal"
                      layout="prev, pager, next"
                      @current-change="loadBaziHistory"
                    />
                  </div>
                </AsyncState>
              </el-tab-pane>

              <el-tab-pane label="塔罗占卜" name="tarot">
                <AsyncState :status="tarotStatus" loadingText="正在加载占卜记录..." @retry="loadTarotHistory">
                  <div class="history-grid" v-if="tarotHistory.length > 0">
                    <div v-for="(record, index) in tarotHistory" :key="index" class="history-record">
                      <div class="record-header">
                        <span class="record-time">{{ formatTime(record.date) }}</span>
                        <span class="record-type">{{ record.spreadName }}</span>
                      </div>
                      <div class="record-content">
                        <div class="tarot-cards-mini">
                          <span v-for="(card, cidx) in record.cards.slice(0, 3)" :key="cidx" class="card-emoji">
                            {{ card.emoji }}<small v-if="card.reversed">逆</small>
                          </span>
                          <span v-if="record.cards.length > 3" class="more">+{{ record.cards.length - 3 }}</span>
                        </div>
                      </div>
                      <el-button type="primary" size="small" @click="viewTarotDetail(record)">查看详情</el-button>
                    </div>
                  </div>
                  <el-empty v-else description="暂无占卜记录" />
                </AsyncState>
              </el-tab-pane>

              <el-tab-pane label="六爻占卜" name="liuyao">
                <AsyncState :status="liuyaoStatus" loadingText="正在加载六爻记录..." @retry="loadLiuyaoHistory">
                  <div class="history-grid" v-if="liuyaoHistory.length > 0">
                    <div v-for="record in liuyaoHistory" :key="record.id" class="history-record">
                      <div class="record-header">
                        <span class="record-time">{{ formatTime(record.created_at) }}</span>
                        <span class="record-type">{{ record.method_name || '铜钱起卦' }}</span>
                      </div>
                      <div class="record-content">
                        <span class="record-question">{{ record.question || '六爻占卜' }}</span>
                      </div>
                      <router-link to="/liuyao" class="el-button el-button--primary el-button--small">查看详情</router-link>
                    </div>
                  </div>
                  <el-empty v-else description="暂无六爻记录" />
                </AsyncState>
              </el-tab-pane>

              <el-tab-pane label="八字合婚" name="hehun">
                <AsyncState :status="hehunStatus" loadingText="正在加载合婚记录..." @retry="loadHehunHistory">
                  <div class="history-grid" v-if="hehunHistory.length > 0">
                    <div v-for="record in hehunHistory" :key="record.id" class="history-record">
                      <div class="record-header">
                        <span class="record-time">{{ formatTime(record.created_at) }}</span>
                        <span v-if="record.total_score" class="record-score">得分：{{ record.total_score }}</span>
                      </div>
                      <div class="record-content">
                        <span class="record-couple">{{ record.male_name || '男方' }} × {{ record.female_name || '女方' }}</span>
                      </div>
                      <router-link to="/hehun" class="el-button el-button--primary el-button--small">查看详情</router-link>
                    </div>
                  </div>
                  <el-empty v-else description="暂无合婚记录" />
                </AsyncState>
              </el-tab-pane>
            </el-tabs>
          </div>

          <!-- 积分明细 -->
          <div class="main-card points-card card-hover">
            <div class="card-header">
              <h3>积分明细</h3>
            </div>
            <div class="points-list" v-if="pointsHistory.length > 0">
              <div v-for="record in pointsHistory" :key="record.id" class="points-row">
                <div class="points-left">
                  <span class="points-action">{{ record.action }}</span>
                  <span class="points-time">{{ formatTime(record.createdAt) }}</span>
                </div>
                <span class="points-amount" :class="{ positive: record.points > 0, negative: record.points < 0 }">
                  {{ record.points > 0 ? '+' : '' }}{{ record.points }}
                </span>
              </div>
            </div>
            <el-empty v-else description="暂无积分记录" />
          </div>

          <!-- 反馈建议 -->
          <div id="feedback-card" class="main-card feedback-card card-hover">
            <h3>反馈建议</h3>
            <div v-if="feedbackEnabled" class="feedback-form">
              <el-input
                v-model="feedbackContent"
                type="textarea"
                :rows="4"
                placeholder="请输入您的意见或建议..."
                class="feedback-input"
              />
              <el-input
                v-model="feedbackContact"
                placeholder="手机号或邮箱（选填）"
                class="feedback-contact"
              />
              <el-button type="primary" @click="submitFeedbackForm" :loading="feedbackLoading">
                提交反馈
              </el-button>
            </div>
            <el-empty v-else description="反馈功能暂时关闭" />
          </div>

          <!-- 帮助与设置 -->
          <div class="main-card help-card card-hover">
            <h3><el-icon><List /></el-icon> 帮助与设置</h3>
            <div class="help-list">
              <div class="help-link" @click="restartTourGuide">
                <el-icon><DocumentCopy /></el-icon>
                <span>重新查看引导</span>
                <el-icon><ArrowRight /></el-icon>
              </div>
            </div>
          </div>
        </main>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { getUserInfo, getPointsBalance, getPointsHistory, getBaziHistory, getTarotHistory, getLiuyaoHistory, getHehunHistory, submitFeedback, getClientConfig } from '../api'
import { useTourGuide } from '../composables/useTourGuide'
import { formatTime, formatDate } from '../utils/format'
import CheckinCard from '../components/CheckinCard.vue'
import BackButton from '../components/BackButton.vue'
import AsyncState from '../components/AsyncState.vue'
import { Coin, Present, UserFilled, ChatDotRound, DocumentCopy, Share, Link, List, Calendar, ArrowRight } from '@element-plus/icons-vue'

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

// 积分获取方式
const pointMethodDefinitions = [
  { id: 1, icon: 'calendar', name: '每日签到', desc: '每天签到领积分', points: 5, action: 'checkin', actionText: '去签到' },
  { id: 2, icon: 'present', name: '新手礼包', desc: '新用户注册奖励', points: 100, action: null, actionText: '' },
  { id: 3, icon: 'user', name: '邀请好友', desc: '每邀请一位好友', points: 20, action: 'invite', actionText: '去邀请' },
  { id: 4, icon: 'share', name: '分享结果', desc: '分享排盘或占卜结果', points: 5, action: null, actionText: '' },
  { id: 5, icon: 'chat', name: '提交反馈', desc: '提交有价值的建议', points: 10, action: 'feedback', actionText: '去反馈' }
]
const pointsMethods = computed(() => (
  feedbackEnabled.value
    ? pointMethodDefinitions
    : pointMethodDefinitions.filter(method => method.action !== 'feedback')
))


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
    if (response?.code === 200) {
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

const loadUserData = async () => {

  try {
    const [userRes, pointsRes, historyRes] = await Promise.all([
      getUserInfo(),
      getPointsBalance(),
      getPointsHistory(),
    ])
    
    if (userRes.code === 200) {
      userInfo.value = userRes.data
      // 使用后端返回的邀请码和统计
      inviteCode.value = userRes.data.invite_code || ''
      inviteCount.value = userRes.data.invite_count || 0
      invitePoints.value = userRes.data.invite_points || 0
      // 生成邀请链接
      inviteLink.value = `${window.location.origin}/login?invite_code=${inviteCode.value}`
    }
    
    if (pointsRes.code === 200) {
      pointsBalance.value = pointsRes.data.balance
      baziCount.value = pointsRes.data.baziCount || 0
      tarotCount.value = pointsRes.data.tarotCount || 0
    }
    
    if (historyRes.code === 200) {
      pointsHistory.value = historyRes.data || []
    }

    await syncClientFeatureConfig()
    
    // 加载排盘历史
    await loadBaziHistory()
    
    // 加载塔罗历史
    await loadTarotHistory()
    // 加载六爻历史
    await loadLiuyaoHistory()
    // 加载合婚历史
    await loadHehunHistory()
  } catch (error) {


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
    if (baziRes.code === 200) {
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
    if (response.code === 200) {
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
    if (response.code === 200) {
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
    if (response.code === 200) {
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

  if (!feedbackContent.value.trim()) {
    ElMessage.warning('请输入反馈内容')
    return
  }

  
  feedbackLoading.value = true
  try {
    const response = await submitFeedback({
      content: feedbackContent.value,
      type: 'suggestion',
      contact: feedbackContact.value,
    })
    
    if (response.code === 200) {
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

const viewDetail = (record) => {
  // 显示排盘详情弹窗
  ElMessage.info('八字详情：' + record.yearGan + record.yearZhi + ' ' + 
    record.monthGan + record.monthZhi + ' ' + 
    record.dayGan + record.dayZhi + ' ' + 
    record.hourGan + record.hourZhi)
}

const viewTarotDetail = (record) => {
  const cards = getTarotCards(record)
  if (!cards.length) {
    ElMessage.warning('该记录缺少塔罗牌数据')
    return
  }

  const cardNames = cards.map(card => `${card.name}${card.reversed ? '(逆位)' : '(正位)'}`).join('、')
  ElMessage.info(`塔罗牌：${cardNames}`)
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
  window.addEventListener('tarot-history-updated', handleTarotHistoryUpdated)
})

onUnmounted(() => {
  window.removeEventListener('tarot-history-updated', handleTarotHistoryUpdated)
})

</script>

<style scoped>
.profile-page {
  padding: 60px 0;
  min-height: 100vh;
}

.profile-hero {
  display: flex;
  align-items: center;
  gap: 20px;
  margin-bottom: 36px;
  padding: 28px 32px;
  background: linear-gradient(135deg, rgba(184, 134, 11, 0.08), rgba(212, 175, 55, 0.04));
  border: 2px solid rgba(212, 175, 55, 0.2);
  border-radius: 20px;
  box-shadow: 0 4px 20px rgba(212, 175, 55, 0.1);
}

.profile-hero-content {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 20px;
}

.profile-hero-avatar {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  overflow: hidden;
  background: linear-gradient(135deg, #B8860B, #D4AF37);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  border: 2px solid rgba(212, 175, 55, 0.4);
}

.profile-hero-avatar-img { width: 100%; height: 100%; object-fit: cover; }

.profile-hero-avatar-placeholder {
  font-size: 24px;
  font-weight: 700;
  color: #fff;
}

.profile-hero-name {
  font-size: 22px;
  font-weight: 700;
  color: #333;
  margin: 0 0 6px;
}

.profile-hero-sub {
  display: flex;
  align-items: center;
  gap: 12px;
  margin: 0;
  font-size: 14px;
}

.profile-hero-id { color: #999; font-weight: 500; }
.profile-hero-divider { color: #e0e0e0; }

.profile-hero-points-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px;
  background: linear-gradient(135deg, rgba(212, 175, 55, 0.12), rgba(212, 175, 55, 0.06));
  border-radius: 20px;
  color: #D4AF37;
  font-weight: 700;
  font-size: 13px;
}

/* 新布局 */
.profile-layout {
  max-width: 960px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: 320px 1fr;
  gap: 28px;
}

/* 侧边栏 */
.profile-sidebar {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.sidebar-card {
  background: #fff;
  border-radius: 16px;
  padding: 24px;
  border: 1px solid #f0f0f0;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
}

.sidebar-card h4 {
  margin: 0 0 18px;
  font-size: 16px;
  font-weight: 700;
  color: #333;
  display: flex;
  align-items: center;
  gap: 8px;
}

.card-hover {
  transition: all 0.3s ease;
}

.card-hover:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
  border-color: rgba(212, 175, 55, 0.3);
}

/* 用户统计卡片 */
.user-card {
  padding: 20px;
}

.user-stats {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 8px;
  margin-bottom: 20px;
}

.stat-item {
  text-align: center;
  padding: 12px 4px;
  background: linear-gradient(135deg, #fafafa, #f5f5f5);
  border-radius: 12px;
  border: 1px solid #e8e8e8;
}

.stat-value {
  display: block;
  font-size: 20px;
  font-weight: 700;
  color: #D4AF37;
  margin-bottom: 4px;
}

.stat-label {
  font-size: 11px;
  color: #999;
}

/* 积分等级 */
.level-section {
  padding-top: 18px;
  border-top: 2px dashed #f0f0f0;
}

.level-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.level-title {
  font-size: 13px;
  color: #666;
}

.level-name {
  font-size: 14px;
  font-weight: 700;
  color: #D4AF37;
}

.level-progress {
  margin-bottom: 10px;
}

.level-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 12px;
  color: #999;
}

.recharge-btn {
  color: #D4AF37;
  text-decoration: none;
  font-weight: 600;
  padding: 4px 12px;
  background: rgba(212, 175, 55, 0.1);
  border-radius: 12px;
  transition: all 0.3s ease;
}

.recharge-btn:hover {
  background: rgba(212, 175, 55, 0.2);
}

/* 积分获取攻略 */
.guide-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.guide-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  background: #fafafa;
  border-radius: 10px;
  transition: all 0.3s ease;
}

.guide-item:hover {
  background: rgba(212, 175, 55, 0.05);
}

.guide-icon {
  width: 36px;
  height: 36px;
  background: linear-gradient(135deg, rgba(212, 175, 55, 0.1), rgba(212, 175, 55, 0.05));
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #D4AF37;
  flex-shrink: 0;
}

.guide-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.guide-name {
  font-size: 13px;
  font-weight: 600;
  color: #333;
}

.guide-reward {
  font-size: 12px;
  color: #D4AF37;
  font-weight: 700;
}

/* 邀请卡片 */
.invite-text {
  font-size: 13px;
  color: #666;
  margin-bottom: 16px;
  text-align: center;
}

.invite-text strong {
  color: #D4AF37;
}

.invite-actions {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-bottom: 16px;
}

.invite-btn-main {
  width: 100%;
}

.invite-stats {
  display: flex;
  justify-content: space-around;
  padding-top: 16px;
  border-top: 2px dashed #f0f0f0;
}

.invite-stat {
  text-align: center;
}

.stat-num {
  display: block;
  font-size: 24px;
  font-weight: 700;
  color: #D4AF37;
}

.stat-txt {
  font-size: 12px;
  color: #999;
}

/* 主内容区 */
.profile-main {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.main-card {
  background: #fff;
  border-radius: 16px;
  padding: 24px;
  border: 1px solid #f0f0f0;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
}

.main-card h3 {
  margin: 0 0 18px;
  font-size: 18px;
  font-weight: 700;
  color: #333;
}

.card-header {
  margin-bottom: 18px;
}

/* Tab样式 */
:deep(.custom-tabs .el-tabs__nav-wrap::after) {
  height: 2px;
  background: #f0f0f0;
}

:deep(.custom-tabs .el-tabs__item) {
  font-size: 15px;
  color: #666;
  padding: 0 20px;
  font-weight: 600;
}

:deep(.custom-tabs .el-tabs__item.is-active) {
  color: #D4AF37;
}

:deep(.custom-tabs .el-tabs__active-bar) {
  background: #D4AF37;
  height: 3px;
}

/* 历史记录网格 */
.history-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 16px;
}

.history-record {
  background: #fafafa;
  border: 1px solid #e8e8e8;
  border-radius: 12px;
  padding: 16px;
  transition: all 0.3s ease;
}

.history-record:hover {
  background: rgba(212, 175, 55, 0.03);
  border-color: rgba(212, 175, 55, 0.2);
  transform: translateY(-2px);
}

.record-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.record-time {
  font-size: 12px;
  color: #999;
}

.record-birth,
.record-type,
.record-score {
  font-size: 12px;
  color: #666;
  background: #fff;
  padding: 3px 8px;
  border-radius: 6px;
  border: 1px solid #e8e8e8;
}

.record-score {
  color: #D4AF37;
  border-color: rgba(212, 175, 55, 0.3);
}

.record-content {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
  flex-wrap: wrap;
}

.bazi-p {
  font-size: 14px;
  font-weight: 600;
  color: #666;
  padding: 4px 8px;
  background: #fff;
  border-radius: 6px;
  border: 1px solid #e8e8e8;
}

.bazi-p.highlight {
  color: #D4AF37;
  border-color: rgba(212, 175, 55, 0.3);
  background: rgba(212, 175, 55, 0.05);
}

.tarot-cards-mini {
  display: flex;
  gap: 6px;
  align-items: center;
}

.card-emoji {
  font-size: 20px;
}

.card-emoji small {
  font-size: 8px;
  color: #D4AF37;
  position: absolute;
  margin-left: -8px;
  margin-top: 10px;
}

.more {
  font-size: 12px;
  color: #999;
  margin-left: 4px;
}

.record-question {
  font-size: 13px;
  color: #666;
  line-height: 1.4;
}

.record-couple {
  font-size: 14px;
  font-weight: 600;
  color: #333;
}

/* 积分明细 */
.points-list {
  max-height: 300px;
  overflow-y: auto;
}

.points-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 14px 0;
  border-bottom: 1px solid #f0f0f0;
  transition: all 0.3s ease;
}

.points-row:hover {
  background: rgba(212, 175, 55, 0.02);
  padding: 14px 12px;
  margin: 0 -12px;
  border-radius: 8px;
}

.points-row:last-child {
  border-bottom: none;
}

.points-left {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.points-action {
  font-weight: 600;
  font-size: 14px;
  color: #333;
}

.points-time {
  font-size: 12px;
  color: #999;
}

.points-amount {
  font-weight: 700;
  font-size: 16px;
  padding: 4px 10px;
  border-radius: 8px;
}

.points-amount.positive {
  color: #67c23a;
  background: rgba(103, 194, 58, 0.1);
}

.points-amount.negative {
  color: #f56c6c;
  background: rgba(245, 108, 108, 0.1);
}

/* 反馈表单 */
.feedback-form {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.feedback-input,
.feedback-contact {
  width: 100%;
}

/* 帮助链接 */
.help-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.help-link {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px;
  background: #fafafa;
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 14px;
  color: #333;
}

.help-link:hover {
  background: rgba(212, 175, 55, 0.05);
  color: #D4AF37;
  transform: translateX(4px);
}

.help-link .el-icon {
  color: #D4AF37;
}

/* 分页 */
.pagination-wrapper {
  margin-top: 20px;
  display: flex;
  justify-content: center;
}

:deep(.el-pagination) {
  --el-pagination-bg-color: transparent;
}

:deep(.el-pagination .btn-prev),
:deep(.el-pagination .btn-next),
:deep(.el-pagination .number) {
  background: #fafafa;
  border-radius: 6px;
  border: 1px solid #e8e8e8;
}

:deep(.el-pagination .number.active) {
  background: #D4AF37;
  color: #fff;
  border-color: #D4AF37;
}

/* 响应式 */
@media (max-width: 768px) {
  .profile-layout {
    grid-template-columns: 1fr;
    gap: 20px;
  }

  .profile-hero {
    flex-direction: column;
    text-align: center;
    padding: 24px 20px;
  }

  .profile-hero-content {
    flex-direction: column;
    gap: 12px;
  }

  .profile-hero-sub {
    justify-content: center;
  }

  .user-stats {
    grid-template-columns: repeat(3, 1fr);
  }

  .history-grid {
    grid-template-columns: 1fr;
  }

  .sidebar-card {
    padding: 20px;
  }

  .main-card {
    padding: 20px;
  }
}
</style>
