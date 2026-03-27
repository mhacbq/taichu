<template>
  <div class="profile-page">
    <div class="container">
      <!-- 个人信息头部 -->
      <div class="profile-hero" v-if="profileStatus !== 'loading'">
        <div class="hero-main">
          <div class="hero-avatar">
            <img v-if="userInfo.avatar" :src="userInfo.avatar" alt="用户头像">
            <span v-else class="avatar-fallback">{{ userInfo.nickname?.[0] || '用' }}</span>
            <div v-if="isVip" class="vip-crown">👑</div>
          </div>
          <div class="hero-info">
            <div class="info-header">
              <h2 class="nickname">{{ userInfo.nickname || '欢迎回来' }}</h2>
              <span class="uid">ID: {{ userInfo.id || '--' }}</span>
            </div>
            <div class="level-section">
              <div class="level-badge">
                <el-icon><Medal /></el-icon>
                <span>{{ pointsLevelName }}</span>
              </div>
              <div class="level-progress">
                <div class="progress-bar">
                  <div class="progress-fill" :style="{ width: pointsPercentage + '%' }"></div>
                </div>
                <span class="progress-text">{{ pointsToNextLevel > 0 ? `再${pointsToNextLevel}分升级` : '已达最高等级' }}</span>
              </div>
            </div>
          </div>
        </div>
        <div class="hero-points">
          <div class="points-card-mini">
            <div class="points-icon-bg">
              <el-icon><Coin /></el-icon>
            </div>
            <div class="points-content">
              <span class="points-number">{{ pointsBalance }}</span>
              <span class="points-label">我的积分</span>
            </div>
            <router-link to="/recharge" class="recharge-btn">
              <el-icon><Plus /></el-icon>
            </router-link>
          </div>
          <div v-if="isVip" class="vip-expire">
            <el-icon><Calendar /></el-icon>
            <span>VIP 到期: {{ vipExpireTime }}</span>
          </div>
        </div>
      </div>

      <!-- 加载骨架屏 -->
      <div v-if="profileStatus === 'loading'" class="profile-skeleton">
        <div class="skeleton-hero"></div>
        <div class="skeleton-stats"></div>
        <div class="skeleton-grid"></div>
        <div class="skeleton-history"></div>
      </div>

      <div v-else class="profile-content">
        <!-- 快捷统计 -->
        <div class="stats-bar">
          <div class="stat-box" v-for="stat in quickStats" :key="stat.key" @click="$router.push(stat.path)">
            <span class="stat-num">{{ stat.value }}</span>
            <span class="stat-name">{{ stat.label }}</span>
          </div>
        </div>

        <!-- 功能入口网格 -->
        <div class="feature-section">
          <h3 class="section-title">常用功能</h3>
          <div class="feature-grid">
            <div 
              class="feature-item" 
              :class="{ 'checked': item.checked, 'loading': item.loading }"
              v-for="item in featureList" 
              :key="item.key"
              @click="handleFeatureClick(item)"
            >
              <div class="feature-icon-box" :class="item.key">
                <el-icon v-if="!item.loading"><component :is="item.icon" /></el-icon>
                <el-icon v-else class="is-loading"><Loading /></el-icon>
              </div>
              <span class="feature-name">{{ item.title }}</span>
              <span class="feature-badge" v-if="item.badge">{{ item.badge }}</span>
            </div>
          </div>
        </div>

        <!-- 历史记录 -->
        <div class="history-section">
          <div class="section-header">
            <h3 class="section-title">历史记录</h3>
            <div class="history-tabs">
              <button 
                v-for="tab in historyTabs" 
                :key="tab.key"
                :class="['tab-btn', { active: activeHistoryTab === tab.key }]"
                @click="activeHistoryTab = tab.key"
              >
                <el-icon><component :is="tab.icon" /></el-icon>
                <span>{{ tab.label }}</span>
              </button>
            </div>
          </div>
          
          <div class="history-content">
            <AsyncState 
              :status="currentHistoryStatus" 
              :loadingText="'加载中...'" 
              @retry="loadCurrentHistory"
            >
              <template v-if="currentHistoryList.length > 0">
                <div class="history-list">
                  <div 
                    v-for="record in currentHistoryList.slice(0, 6)" 
                    :key="record.id || record.date" 
                    class="history-card"
                    @click="viewCurrentDetail(record)"
                  >
                    <div class="card-icon" :class="activeHistoryTab">
                      <el-icon v-if="activeHistoryTab === 'bazi'"><Clock /></el-icon>
                      <span v-else-if="activeHistoryTab === 'tarot'">🃏</span>
                      <span v-else-if="activeHistoryTab === 'liuyao'">☯</span>
                      <span v-else>💕</span>
                    </div>
                    <div class="card-body">
                      <h4 class="card-title">{{ getRecordTitle(record) }}</h4>
                      <p class="card-subtitle">{{ getRecordSubtitle(record) }}</p>
                    </div>
                    <div class="card-meta">
                      <span class="card-time">{{ formatTime(record.createdAt || record.created_at || record.date) }}</span>
                      <el-icon class="card-arrow"><ArrowRight /></el-icon>
                    </div>
                  </div>
                </div>
                <div v-if="currentHistoryList.length > 6" class="view-all" @click="goToHistoryPage">
                  查看全部 {{ currentHistoryList.length }} 条记录
                  <el-icon><ArrowRight /></el-icon>
                </div>
              </template>
              <div v-else class="empty-history">
                <el-empty :description="`暂无${currentTabLabel}记录`">
                  <el-button type="primary" @click="goToHistoryPage">去体验</el-button>
                </el-empty>
              </div>
            </AsyncState>
          </div>
        </div>

        <!-- 底部两栏 -->
        <div class="bottom-section">
          <!-- 积分明细 -->
          <div class="points-detail">
            <div class="section-header compact">
              <h3 class="section-title">积分明细</h3>
              <router-link to="/recharge" class="link-more">
                去充值 <el-icon><ArrowRight /></el-icon>
              </router-link>
            </div>
            <div class="points-list" v-if="pointsHistory.length > 0">
              <div 
                v-for="record in pointsHistory.slice(0, 5)" 
                :key="record.id" 
                class="points-row"
              >
                <div class="row-info">
                  <span class="row-action">{{ record.business_label || record.action || '积分变动' }}</span>
                  <span class="row-time">{{ formatTime(record.created_at || record.createdAt) }}</span>
                </div>
                <span class="row-amount" :class="{ plus: record.points > 0, minus: record.points < 0 }">
                  {{ record.points > 0 ? '+' : '' }}{{ record.points }}
                </span>
              </div>
            </div>
            <el-empty v-else description="暂无积分记录" :image-size="80" />
          </div>

          <!-- 邀请好友 -->
          <div class="invite-section">
            <div class="section-header compact">
              <h3 class="section-title">邀请好友</h3>
            </div>
            <div class="invite-content">
              <p class="invite-text">每邀请一位好友消费，双方各得 <strong>{{ invitePointsReward }} 积分</strong></p>
              <div class="invite-data">
                <div class="data-item">
                  <span class="data-value">{{ inviteCount }}</span>
                  <span class="data-label">已邀请</span>
                </div>
                <div class="data-item">
                  <span class="data-value">{{ inviteSuccessCount }}</span>
                  <span class="data-label">已消费</span>
                </div>
                <div class="data-item highlight">
                  <span class="data-value">{{ invitePoints }}</span>
                  <span class="data-label">获积分</span>
                </div>
              </div>
              <div class="invite-actions">
                <el-button type="primary" @click="copyInviteCode" class="btn-main">
                  <el-icon><DocumentCopy /></el-icon> 复制邀请码
                </el-button>
                <el-button @click="copyInviteLink">
                  <el-icon><Share /></el-icon> 分享链接
                </el-button>
              </div>
            </div>
          </div>
        </div>

        <!-- 更多功能 -->
        <div class="more-section">
          <h3 class="section-title">更多</h3>
          <div class="more-list">
            <div class="more-item" @click="restartTourGuide">
              <div class="item-left">
                <div class="item-icon guide">
                  <el-icon><Guide /></el-icon>
                </div>
                <span>重新查看引导</span>
              </div>
              <el-icon><ArrowRight /></el-icon>
            </div>
            <div class="more-item" @click="showFeedback = true">
              <div class="item-left">
                <div class="item-icon feedback">
                  <el-icon><ChatDotRound /></el-icon>
                </div>
                <span>反馈建议</span>
              </div>
              <el-icon><ArrowRight /></el-icon>
            </div>
            <div class="more-item" @click="handleLogout">
              <div class="item-left">
                <div class="item-icon logout">
                  <el-icon><SwitchButton /></el-icon>
                </div>
                <span>退出登录</span>
              </div>
              <el-icon><ArrowRight /></el-icon>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 反馈弹窗 -->
    <el-dialog
      v-model="showFeedback"
      title="反馈建议"
      width="460px"
      :close-on-click-modal="false"
      class="feedback-dialog"
    >
      <div class="feedback-form">
        <el-input
          v-model="feedbackContent"
          type="textarea"
          :rows="4"
          placeholder="请输入您的意见或建议，我们会认真听取..."
        />
        <el-input
          v-model="feedbackContact"
          placeholder="手机号或邮箱（选填，方便我们联系您）"
          class="feedback-contact"
        />
      </div>
      <template #footer>
        <el-button @click="showFeedback = false">取消</el-button>
        <el-button type="primary" @click="submitFeedbackForm" :loading="feedbackLoading">
          提交反馈
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { 
  UserFilled, Coin, Trophy, ArrowRight, Clock, 
  Present, DocumentCopy, Share, Guide, 
  ChatDotRound, SwitchButton, Plus, Calendar,
  Star, Link, Loading, Medal, Compass,
  Magic, HeartFilled
} from '@element-plus/icons-vue'
import AsyncState from '../../components/AsyncState.vue'
import { useProfile } from './useProfile'

const {
  // 状态
  userInfo, pointsBalance, baziCount, tarotCount, liuyaoCount, hehunCount,
  pointsHistory, baziHistory, tarotHistory, liuyaoHistory, hehunHistory,
  feedbackContent, feedbackContact, feedbackLoading,
  activeHistoryTab, isVip, vipExpireTime,
  profileStatus,
  baziStatus, tarotStatus, liuyaoStatus, hehunStatus,
  inviteCount, invitePoints, inviteSuccessCount, inviteLink,
  checkinStatus, checkinLoading,

  // 计算属性
  pointsLevelName, pointsPercentage, pointsToNextLevel,

  // 方法
  formatTime, formatDate,
  restartTourGuide,
  loadBaziHistory, loadTarotHistory, loadLiuyaoHistory, loadHehunHistory,
  submitFeedbackForm,
  viewDetail, viewTarotDetail, viewLiuyaoDetail, viewHehunDetail,
  copyInviteCode, copyInviteLink, handleLogout, doCheckin,
} = useProfile()

// 邀请奖励积分（从配置读取，这里用默认值）
const invitePointsReward = ref(20)

// 历史记录标签
const historyTabs = [
  { key: 'bazi', label: '八字排盘', icon: Clock },
  { key: 'tarot', label: '塔罗占卜', icon: Magic },
  { key: 'liuyao', label: '六爻占卜', icon: Compass },
  { key: 'hehun', label: '八字合婚', icon: HeartFilled },
]

// 当前标签标签名
const currentTabLabel = computed(() => {
  const tab = historyTabs.find(t => t.key === activeHistoryTab.value)
  return tab?.label || ''
})

// 当前历史记录状态
const currentHistoryStatus = computed(() => {
  const statusMap = { bazi: baziStatus, tarot: tarotStatus, liuyao: liuyaoStatus, hehun: hehunStatus }
  return statusMap[activeHistoryTab.value]?.value || 'loading'
})

// 当前历史记录列表
const currentHistoryList = computed(() => {
  const listMap = { bazi: baziHistory, tarot: tarotHistory, liuyao: liuyaoHistory, hehun: hehunHistory }
  return listMap[activeHistoryTab.value]?.value || []
})

// 加载当前历史记录
const loadCurrentHistory = () => {
  const loaders = {
    bazi: loadBaziHistory,
    tarot: loadTarotHistory,
    liuyao: loadLiuyaoHistory,
    hehun: loadHehunHistory
  }
  loaders[activeHistoryTab.value]?.()
}

// 查看当前详情
const viewCurrentDetail = (record) => {
  const viewers = {
    bazi: viewDetail,
    tarot: viewTarotDetail,
    liuyao: viewLiuyaoDetail,
    hehun: viewHehunDetail
  }
  viewers[activeHistoryTab.value]?.(record)
}

// 获取记录标题
const getRecordTitle = (record) => {
  switch (activeHistoryTab.value) {
    case 'bazi':
      return `${formatDate(record.birthDate)} · ${record.gender === 'male' ? '男' : '女'}`
    case 'tarot':
      return record.spreadName || '塔罗占卜'
    case 'liuyao':
      return record.question || '六爻占卜'
    case 'hehun':
      return `${record.male_name || '男方'} × ${record.female_name || '女方'}`
    default:
      return '记录'
  }
}

// 获取记录副标题
const getRecordSubtitle = (record) => {
  switch (activeHistoryTab.value) {
    case 'bazi':
      return `${record.yearGan}${record.yearZhi} ${record.dayGan}${record.dayZhi}`
    case 'tarot':
      return `${record.cards?.length || 0}张牌 · ${record.cards?.[0]?.name || ''}`
    case 'liuyao':
      return record.method_name || '铜钱起卦'
    case 'hehun':
      return record.total_score ? `${record.total_score}分` : '八字合婚'
    default:
      return ''
  }
}

// 跳转到历史记录页面
const goToHistoryPage = () => {
  const pathMap = {
    bazi: '/bazi',
    tarot: '/tarot',
    liuyao: '/liuyao',
    hehun: '/hehun'
  }
  window.location.href = pathMap[activeHistoryTab.value] || '/'
}

// 快捷统计
const quickStats = computed(() => [
  { key: 'bazi', value: baziCount.value, label: '八字排盘', path: '/bazi' },
  { key: 'tarot', value: tarotCount.value, label: '塔罗占卜', path: '/tarot' },
  { key: 'liuyao', value: liuyaoCount.value, label: '六爻占卜', path: '/liuyao' },
  { key: 'hehun', value: hehunCount.value, label: '八字合婚', path: '/hehun' },
])

// 功能列表
const featureList = computed(() => [
  { 
    key: 'checkin', 
    title: checkinStatus.value.today_checkin ? '已签到' : '每日签到', 
    icon: Calendar,
    checked: checkinStatus.value.today_checkin,
    loading: checkinLoading.value,
    badge: checkinStatus.value.today_checkin ? `连续${checkinStatus.value.consecutive_days}天` : null
  },
  { key: 'vip', title: isVip.value ? 'VIP会员' : '开通VIP', icon: Trophy },
  { key: 'points', title: '积分攻略', icon: Star },
  { key: 'invite', title: '邀请好友', icon: Link, badge: inviteCount.value > 0 ? `${inviteCount.value}人` : null },
  { key: 'bazi', title: '八字排盘', icon: Clock },
  { key: 'tarot', title: '塔罗占卜', icon: Magic },
  { key: 'liuyao', title: '六爻占卜', icon: Compass },
  { key: 'hehun', title: '八字合婚', icon: HeartFilled },
])

// 处理功能点击
const handleFeatureClick = async (item) => {
  const paths = {
    vip: '/vip',
    bazi: '/bazi',
    tarot: '/tarot',
    liuyao: '/liuyao',
    hehun: '/hehun'
  }
  
  if (item.key === 'checkin') {
    await doCheckin()
  } else if (item.key === 'points') {
    ElMessage.info('积分攻略：每日签到+10分，邀请好友+50分，充值更划算！')
  } else if (item.key === 'invite') {
    copyInviteCode()
  } else if (paths[item.key]) {
    window.location.href = paths[item.key]
  }
}

// 控制反馈弹窗
const showFeedback = ref(false)
</script>

<style scoped>
@import './style.css';
</style>
