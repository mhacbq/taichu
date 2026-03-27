<template>
  <div class="profile-page">
    <div class="container">
      <PageHeroHeader
        title="个人中心"
        subtitle="管理您的账户信息、查看历史记录和积分明细"
        :icon="UserFilled"
      />

      <!-- 加载骨架屏 -->
      <div v-if="profileStatus === 'loading'" class="profile-skeleton">
        <div class="skeleton-status"></div>
        <div class="skeleton-grid">
          <div class="skeleton-card" v-for="i in 4" :key="i"></div>
        </div>
        <div class="skeleton-history"></div>
      </div>

      <div v-else class="profile-content">
        <!-- 用户信息卡片 -->
        <div class="user-status card card-hover">
          <div class="status-header">
            <div class="status-avatar">
              <img v-if="userInfo.avatar" :src="userInfo.avatar" alt="用户头像" class="avatar-img">
              <span v-else class="avatar-placeholder">{{ userInfo.nickname?.[0] || '用' }}</span>
            </div>
            <div class="status-info">
              <h3>{{ userInfo.nickname || '欢迎回来' }}</h3>
              <p class="status-id">ID: {{ userInfo.id || '--' }}</p>
              <div class="status-badges">
                <span class="level-badge">{{ pointsLevelName }}</span>
                <span v-if="isVip" class="vip-badge">
                  <el-icon><Trophy /></el-icon> VIP
                </span>
              </div>
            </div>
            <div class="status-points">
              <div class="points-display">
                <el-icon class="points-icon"><Coin /></el-icon>
                <div class="points-info">
                  <span class="points-value">{{ pointsBalance }}</span>
                  <span class="points-label">当前积分</span>
                </div>
              </div>
              <router-link to="/recharge" class="recharge-link">
                <el-button type="primary" size="small">
                  <el-icon><Plus /></el-icon> 充值
                </el-button>
              </router-link>
            </div>
          </div>
          
          <!-- 快捷统计 -->
          <div class="quick-stats">
            <div class="stat-item" v-for="stat in quickStats" :key="stat.key">
              <span class="stat-value">{{ stat.value }}</span>
              <span class="stat-label">{{ stat.label }}</span>
            </div>
          </div>
        </div>

        <!-- 功能卡片网格 -->
        <div class="feature-grid">
          <div 
            class="feature-card card card-hover" 
            :class="{ 'checkin-checked': card.checked, 'checkin-loading': card.key === 'checkin' && checkinLoading }"
            v-for="card in featureCards" 
            :key="card.key"
            @click="handleCardClick(card)"
          >
            <div class="feature-icon-wrapper">
              <el-icon class="feature-icon"><component :is="card.icon" /></el-icon>
            </div>
            <h4 class="feature-title">{{ card.title }}</h4>
            <p class="feature-desc">{{ card.desc }}</p>
            <div class="feature-action">
              <span v-if="card.key === 'checkin' && checkinLoading">签到中...</span>
              <span v-else-if="card.checked">已完成</span>
              <span v-else>查看详情</span>
              <el-icon v-if="!(card.key === 'checkin' && checkinLoading)"><ArrowRight /></el-icon>
              <el-icon v-else class="is-loading"><Loading /></el-icon>
            </div>
          </div>
        </div>

        <!-- 历史记录卡片 -->
        <div class="history-section card card-hover">
          <div class="section-header">
            <h3><el-icon><Clock /></el-icon> 历史记录</h3>
            <el-radio-group v-model="activeHistoryTab" size="small">
              <el-radio-button label="bazi">八字排盘</el-radio-button>
              <el-radio-button label="tarot">塔罗占卜</el-radio-button>
              <el-radio-button label="liuyao">六爻占卜</el-radio-button>
              <el-radio-button label="hehun">八字合婚</el-radio-button>
            </el-radio-group>
          </div>
          
          <div class="history-content">
            <!-- 八字排盘 -->
            <div v-show="activeHistoryTab === 'bazi'" class="history-list">
              <AsyncState :status="baziStatus" loadingText="正在加载排盘记录..." @retry="loadBaziHistory">
                <template v-if="baziHistory.length > 0">
                  <div 
                    v-for="record in baziHistory.slice(0, 5)" 
                    :key="record.id" 
                    class="history-item"
                    @click="viewDetail(record)"
                  >
                    <div class="item-icon bazi-icon">
                      <el-icon><Clock /></el-icon>
                    </div>
                    <div class="item-main">
                      <span class="item-title">{{ formatDate(record.birthDate) }} · {{ record.gender === 'male' ? '男' : '女' }}</span>
                      <span class="item-subtitle">{{ record.yearGan }}{{ record.yearZhi }} {{ record.dayGan }}{{ record.dayZhi }}</span>
                    </div>
                    <div class="item-meta">
                      <span class="item-time">{{ formatTime(record.createdAt) }}</span>
                      <el-icon class="item-arrow"><ArrowRight /></el-icon>
                    </div>
                  </div>
                  <div v-if="baziHistory.length > 5" class="view-more" @click="$router.push('/bazi')">
                    查看全部 {{ baziHistory.length }} 条记录 <el-icon><ArrowRight /></el-icon>
                  </div>
                </template>
                <el-empty v-else description="暂无排盘记录">
                  <el-button type="primary" size="small" @click="$router.push('/bazi')">去排盘</el-button>
                </el-empty>
              </AsyncState>
            </div>

            <!-- 塔罗占卜 -->
            <div v-show="activeHistoryTab === 'tarot'" class="history-list">
              <AsyncState :status="tarotStatus" loadingText="正在加载占卜记录..." @retry="loadTarotHistory">
                <template v-if="tarotHistory.length > 0">
                  <div 
                    v-for="(record, index) in tarotHistory.slice(0, 5)" 
                    :key="index" 
                    class="history-item"
                    @click="viewTarotDetail(record)"
                  >
                    <div class="item-icon tarot-icon">
                      <span class="icon-emoji">🃏</span>
                    </div>
                    <div class="item-main">
                      <span class="item-title">{{ record.spreadName }}</span>
                      <span class="item-subtitle">{{ record.cards?.length || 0 }}张牌 · {{ record.cards?.[0]?.name }}{{ record.cards?.[0]?.reversed ? '(逆)' : '' }}</span>
                    </div>
                    <div class="item-meta">
                      <span class="item-time">{{ formatTime(record.date) }}</span>
                      <el-icon class="item-arrow"><ArrowRight /></el-icon>
                    </div>
                  </div>
                  <div v-if="tarotHistory.length > 5" class="view-more" @click="$router.push('/tarot')">
                    查看全部 {{ tarotHistory.length }} 条记录 <el-icon><ArrowRight /></el-icon>
                  </div>
                </template>
                <el-empty v-else description="暂无占卜记录">
                  <el-button type="primary" size="small" @click="$router.push('/tarot')">去占卜</el-button>
                </el-empty>
              </AsyncState>
            </div>

            <!-- 六爻占卜 -->
            <div v-show="activeHistoryTab === 'liuyao'" class="history-list">
              <AsyncState :status="liuyaoStatus" loadingText="正在加载六爻记录..." @retry="loadLiuyaoHistory">
                <template v-if="liuyaoHistory.length > 0">
                  <div 
                    v-for="record in liuyaoHistory.slice(0, 5)" 
                    :key="record.id" 
                    class="history-item"
                    @click="viewLiuyaoDetail(record)"
                  >
                    <div class="item-icon liuyao-icon">
                      <span class="icon-emoji">☯</span>
                    </div>
                    <div class="item-main">
                      <span class="item-title">{{ record.question || '六爻占卜' }}</span>
                      <span class="item-subtitle">{{ record.method_name || '铜钱起卦' }}</span>
                    </div>
                    <div class="item-meta">
                      <span class="item-time">{{ formatTime(record.created_at) }}</span>
                      <el-icon class="item-arrow"><ArrowRight /></el-icon>
                    </div>
                  </div>
                  <div v-if="liuyaoHistory.length > 5" class="view-more" @click="$router.push('/liuyao')">
                    查看全部 {{ liuyaoHistory.length }} 条记录 <el-icon><ArrowRight /></el-icon>
                  </div>
                </template>
                <el-empty v-else description="暂无六爻记录">
                  <el-button type="primary" size="small" @click="$router.push('/liuyao')">去占卜</el-button>
                </el-empty>
              </AsyncState>
            </div>

            <!-- 八字合婚 -->
            <div v-show="activeHistoryTab === 'hehun'" class="history-list">
              <AsyncState :status="hehunStatus" loadingText="正在加载合婚记录..." @retry="loadHehunHistory">
                <template v-if="hehunHistory.length > 0">
                  <div 
                    v-for="record in hehunHistory.slice(0, 5)" 
                    :key="record.id" 
                    class="history-item"
                    @click="viewHehunDetail(record)"
                  >
                    <div class="item-icon hehun-icon">
                      <span class="icon-emoji">💕</span>
                    </div>
                    <div class="item-main">
                      <span class="item-title">{{ record.male_name || '男方' }} × {{ record.female_name || '女方' }}</span>
                      <span v-if="record.total_score" class="item-subtitle score-tag">{{ record.total_score }}分</span>
                    </div>
                    <div class="item-meta">
                      <span class="item-time">{{ formatTime(record.created_at) }}</span>
                      <el-icon class="item-arrow"><ArrowRight /></el-icon>
                    </div>
                  </div>
                  <div v-if="hehunHistory.length > 5" class="view-more" @click="$router.push('/hehun')">
                    查看全部 {{ hehunHistory.length }} 条记录 <el-icon><ArrowRight /></el-icon>
                  </div>
                </template>
                <el-empty v-else description="暂无合婚记录">
                  <el-button type="primary" size="small" @click="$router.push('/hehun')">去合婚</el-button>
                </el-empty>
              </AsyncState>
            </div>
          </div>
        </div>

        <!-- 积分明细与邀请 -->
        <div class="bottom-grid">
          <!-- 积分明细 -->
          <div class="points-card card card-hover">
            <div class="section-header">
              <h3><el-icon><Coin /></el-icon> 积分明细</h3>
              <router-link to="/recharge" class="header-link">
                去充值 <el-icon><ArrowRight /></el-icon>
              </router-link>
            </div>
            <div class="points-list" v-if="pointsHistory.length > 0">
              <div v-for="record in pointsHistory.slice(0, 5)" :key="record.id" class="points-item">
                <div class="points-info">
                  <span class="points-action">{{ record.business_label || record.action || record.reason || '积分变动' }}</span>
                  <span class="points-time">{{ formatTime(record.created_at || record.createdAt) }}</span>
                </div>
                <span class="points-amount" :class="{ positive: record.points > 0, negative: record.points < 0 }">
                  {{ record.points > 0 ? '+' : '' }}{{ record.points }}
                </span>
              </div>
            </div>
            <el-empty v-else description="暂无积分记录" />
          </div>

          <!-- 邀请好友 -->
          <div class="invite-card card card-hover">
            <div class="section-header">
              <h3><el-icon><Present /></el-icon> 邀请好友</h3>
            </div>
            <div class="invite-content">
              <p class="invite-desc">每邀请一位好友消费，双方各得 <strong>{{ invitePoints || 20 }} 积分</strong></p>
              <div class="invite-stats-row">
                <div class="invite-stat">
                  <span class="stat-num">{{ inviteCount }}</span>
                  <span class="stat-label">已邀请</span>
                </div>
                <div class="invite-stat">
                  <span class="stat-num">{{ inviteSuccessCount }}</span>
                  <span class="stat-label">已消费</span>
                </div>
                <div class="invite-stat">
                  <span class="stat-num">{{ invitePoints }}</span>
                  <span class="stat-label">获积分</span>
                </div>
              </div>
              <div class="invite-actions">
                <el-button type="primary" @click="copyInviteCode" class="invite-btn">
                  <el-icon><DocumentCopy /></el-icon> 复制邀请码
                </el-button>
                <el-button @click="copyInviteLink">
                  <el-icon><Link /></el-icon> 分享链接
                </el-button>
              </div>
            </div>
          </div>
        </div>

        <!-- 其他功能 -->
        <div class="more-section card card-hover">
          <div class="section-header">
            <h3><el-icon><Setting /></el-icon> 其他功能</h3>
          </div>
          <div class="more-grid">
            <div class="more-item" @click="restartTourGuide">
              <el-icon><Guide /></el-icon>
              <span>重新查看引导</span>
              <el-icon class="more-arrow"><ArrowRight /></el-icon>
            </div>
            <div class="more-item" @click="showFeedback = true">
              <el-icon><ChatDotRound /></el-icon>
              <span>反馈建议</span>
              <el-icon class="more-arrow"><ArrowRight /></el-icon>
            </div>
            <div class="more-item" @click="handleLogout">
              <el-icon><SwitchButton /></el-icon>
              <span>退出登录</span>
              <el-icon class="more-arrow"><ArrowRight /></el-icon>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 反馈弹窗 -->
    <el-dialog
      v-model="showFeedback"
      title="反馈建议"
      width="500px"
      :close-on-click-modal="false"
    >
      <div class="feedback-form">
        <el-input
          v-model="feedbackContent"
          type="textarea"
          :rows="4"
          placeholder="请输入您的意见或建议..."
        />
        <el-input
          v-model="feedbackContact"
          placeholder="手机号或邮箱（选填）"
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
  Present, DocumentCopy, Link, Setting, Guide, 
  ChatDotRound, SwitchButton, Plus, Calendar,
  Star, Link as LinkIcon, Loading
} from '@element-plus/icons-vue'
import PageHeroHeader from '../../components/PageHeroHeader.vue'
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
  inviteCount, invitePoints, inviteLink, inviteSuccessCount,
  checkinStatus, checkinLoading,

  // 计算属性
  pointsLevelName,

  // 方法
  formatTime, formatDate,
  restartTourGuide,
  loadBaziHistory, loadTarotHistory, loadLiuyaoHistory, loadHehunHistory,
  submitFeedbackForm,
  viewDetail, viewTarotDetail, viewLiuyaoDetail, viewHehunDetail,
  copyInviteCode, copyInviteLink, handleLogout, doCheckin,
} = useProfile()

// 快捷统计
const quickStats = computed(() => [
  { key: 'bazi', value: baziCount.value, label: '排盘' },
  { key: 'tarot', value: tarotCount.value, label: '塔罗' },
  { key: 'liuyao', value: liuyaoCount.value, label: '六爻' },
  { key: 'hehun', value: hehunCount.value, label: '合婚' },
])

// 功能卡片
const featureCards = computed(() => [
  { 
    key: 'checkin', 
    title: checkinStatus.value.today_checkin ? '今日已签到' : '每日签到', 
    desc: checkinStatus.value.today_checkin 
      ? `连续签到 ${checkinStatus.value.consecutive_days} 天，明天继续！` 
      : '签到领积分，连续签到奖励更多',
    icon: Calendar,
    path: null,
    action: 'checkin',
    checked: checkinStatus.value.today_checkin
  },
  { 
    key: 'vip', 
    title: 'VIP会员', 
    desc: isVip.value ? `到期时间: ${vipExpireTime.value}` : '开通VIP享受更多特权',
    icon: Trophy,
    path: '/vip'
  },
  { 
    key: 'points', 
    title: '积分攻略', 
    desc: '了解如何获取更多积分',
    icon: Star,
    path: null,
    action: 'guide'
  },
  { 
    key: 'invite', 
    title: '邀请好友', 
    desc: `已邀请 ${inviteCount.value} 人，获得 ${invitePoints.value} 积分`,
    icon: LinkIcon,
    path: null,
    action: 'invite'
  },
])

// 控制反馈弹窗
const showFeedback = ref(false)

// 处理卡片点击
const handleCardClick = async (card) => {
  if (card.path) {
    // 使用原生跳转
    window.location.href = card.path
  } else if (card.action === 'checkin') {
    // 执行签到
    await doCheckin()
  } else if (card.action === 'invite') {
    copyInviteCode()
  } else if (card.action === 'guide') {
    // 显示积分攻略
    ElMessage.info('积分攻略：每日签到、邀请好友、充值均可获得积分')
  }
}
</script>

<style scoped>
@import './style.css';
</style>
