<template>
  <div class="profile-page">
    <div class="container">
      <div class="page-header">
        <BackButton />
        <h1 class="section-title">个人中心</h1>
      </div>
      
      <!-- 签到卡片 -->
      <CheckinCard />

      <div class="profile-grid">
        <!-- 用户信息卡片 -->
        <div class="user-info card">
          <div class="avatar-section">
            <div class="avatar">
              <img v-if="userInfo.avatar" :src="userInfo.avatar" alt="头像">
              <span v-else>{{ userInfo.nickname?.[0] || '用' }}</span>
            </div>
            <h3>{{ userInfo.nickname || '用户' }}</h3>
            <p class="user-id">ID: {{ userInfo.id || '--' }}</p>
          </div>
          <div class="user-stats">
            <div class="stat">
              <span class="stat-value">{{ pointsBalance }}</span>
              <span class="stat-label">积分</span>
            </div>
            <div class="stat">
              <span class="stat-value">{{ baziCount }}</span>
              <span class="stat-label">排盘次数</span>
            </div>
            <div class="stat">
              <span class="stat-value">{{ tarotCount }}</span>
              <span class="stat-label">占卜次数</span>
            </div>
          </div>
        </div>

        <!-- 积分明细 -->
        <div class="points-section card">
          <h3>积分明细</h3>
          <div class="points-list" v-if="pointsHistory.length > 0">
            <div v-for="record in pointsHistory" :key="record.id" class="points-item">
              <div class="points-info">
                <span class="points-action">{{ record.action }}</span>
                <span class="points-time" :title="formatDate(record.createdAt)">{{ formatTime(record.createdAt) }}</span>
              </div>
              <span class="points-change" :class="{ positive: record.points > 0, negative: record.points < 0 }">
                {{ record.points > 0 ? '+' : '' }}{{ record.points }}
              </span>
            </div>
          </div>
          <el-empty v-else description="暂无积分记录" />
        </div>

        <!-- 排盘历史 -->
        <div class="history-section card">
          <h3>排盘历史</h3>
          <div class="history-list" v-if="baziHistory.length > 0">
            <div v-for="record in baziHistory" :key="record.id" class="history-item">
              <div class="history-info">
                <span class="history-date" :title="formatDateTime(record.createdAt)">{{ formatTime(record.createdAt) }}</span>
                <span class="history-birth">{{ formatDate(record.birthDate) }} · {{ record.gender === 'male' ? '男' : '女' }}</span>
              </div>
              <div class="history-bazi">
                <span class="bazi-pillar">{{ record.yearGan }}{{ record.yearZhi }}</span>
                <span class="bazi-pillar">{{ record.monthGan }}{{ record.monthZhi }}</span>
                <span class="bazi-pillar highlight">{{ record.dayGan }}{{ record.dayZhi }}</span>
                <span class="bazi-pillar">{{ record.hourGan }}{{ record.hourZhi }}</span>
              </div>
              <el-button type="primary" size="small" @click="viewDetail(record)">查看</el-button>
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
        </div>

        <!-- 塔罗历史 -->
        <div class="history-section card">
          <h3>塔罗占卜历史</h3>
          <div class="history-list" v-if="tarotHistory.length > 0">
            <div v-for="(record, index) in tarotHistory" :key="index" class="history-item tarot-item">
              <div class="history-info">
                <span class="history-date">{{ formatTime(record.date) }}</span>
                <span class="history-question" :title="record.question">{{ record.question }}</span>
              </div>
              <div class="tarot-cards">
                <span v-for="(card, cidx) in record.cards.slice(0, 3)" :key="cidx" class="tarot-mini">
                  {{ card.emoji }}<small v-if="card.reversed">逆</small>
                </span>
                <span v-if="record.cards.length > 3" class="more-cards">+{{ record.cards.length - 3 }}</span>
              </div>
              <el-button type="primary" size="small" @click="viewTarotDetail(record)">查看</el-button>
            </div>
          </div>
          <el-empty v-else description="暂无占卜记录" />
        </div>

        <!-- 积分获取攻略 -->
        <div class="points-guide-section card">
          <h3>💎 积分获取攻略</h3>
          <div class="points-methods">
            <div class="method-item" v-for="method in pointsMethods" :key="method.id">
              <div class="method-icon">{{ method.icon }}</div>
              <div class="method-info">
                <h4>{{ method.name }}</h4>
                <p>{{ method.desc }}</p>
              </div>
              <div class="method-reward">+{{ method.points }}</div>
              <el-button 
                v-if="method.action" 
                type="primary" 
                size="small"
                @click="handleMethodAction(method)"
              >
                {{ method.actionText }}
              </el-button>
              <span v-else class="completed-badge">✓</span>
            </div>
          </div>
        </div>

        <!-- 邀请好友 -->
        <div class="invite-section card">
          <h3>🎁 邀请好友赚积分</h3>
          <div class="invite-content">
            <p class="invite-desc">每邀请一位好友注册，您和好友各获得 <strong>20积分</strong></p>
            <div class="invite-code">
              <span class="code-label">您的邀请码：</span>
              <span class="code-value">{{ inviteCode }}</span>
              <el-button type="primary" size="small" @click="copyInviteCode">
                复制
              </el-button>
            </div>
            <div class="invite-stats">
              <div class="stat">
                <span class="stat-value">{{ inviteCount }}</span>
                <span class="stat-label">已邀请</span>
              </div>
              <div class="stat">
                <span class="stat-value">{{ invitePoints }}</span>
                <span class="stat-label">获得积分</span>
              </div>
            </div>
          </div>
        </div>

        <!-- 反馈建议 -->
        <div class="feedback-section card">
          <h3>反馈建议</h3>
          <div class="feedback-form">
            <el-input
              v-model="feedbackContent"
              type="textarea"
              :rows="4"
              placeholder="请输入您的意见或建议，帮助我们改进服务..."
              class="feedback-input"
            />
            <el-input
              v-model="feedbackContact"
              placeholder="请输入您的手机号或邮箱（方便我们回复您）"
              class="contact-input"
            />
            <el-button type="primary" @click="submitFeedbackForm" :loading="feedbackLoading">
              提交反馈
            </el-button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { getUserInfo, getPointsBalance, getPointsHistory, getBaziHistory, submitFeedback } from '../api'
import { formatTime, formatDate, formatDateTime } from '../utils/format'
import CheckinCard from '../components/CheckinCard.vue'
import BackButton from '../components/BackButton.vue'

const userInfo = ref({})
const pointsBalance = ref(0)
const baziCount = ref(0)
const tarotCount = ref(0)
const pointsHistory = ref([])
const baziHistory = ref([])
const tarotHistory = ref([])
const feedbackContent = ref('')
const feedbackContact = ref('')
const feedbackLoading = ref(false)

// 分页相关
const baziCurrentPage = ref(1)
const baziPageSize = ref(5)
const baziTotal = ref(0)

// 积分获取方式
const pointsMethods = ref([
  { id: 1, icon: '📅', name: '每日签到', desc: '每天签到领积分', points: 5, action: 'checkin', actionText: '去签到' },
  { id: 2, icon: '🎁', name: '新手礼包', desc: '新用户注册奖励', points: 100, action: null, actionText: '' },
  { id: 3, icon: '👥', name: '邀请好友', desc: '每邀请一位好友', points: 20, action: 'invite', actionText: '去邀请' },
  { id: 4, icon: '📤', name: '分享结果', desc: '分享排盘或占卜结果', points: 5, action: null, actionText: '' },
  { id: 5, icon: '💬', name: '提交反馈', desc: '提交有价值的建议', points: 10, action: 'feedback', actionText: '去反馈' }
])

// 邀请相关
const inviteCode = ref('')
const inviteCount = ref(0)
const invitePoints = ref(0)

const loadUserData = async () => {
  try {
    const [userRes, pointsRes, historyRes] = await Promise.all([
      getUserInfo(),
      getPointsBalance(),
      getPointsHistory(),
    ])
    
    if (userRes.code === 0) {
      userInfo.value = userRes.data
    }
    
    if (pointsRes.code === 0) {
      pointsBalance.value = pointsRes.data.balance
      baziCount.value = pointsRes.data.baziCount || 0
      tarotCount.value = pointsRes.data.tarotCount || 0
    }
    
    if (historyRes.code === 0) {
      pointsHistory.value = historyRes.data || []
    }
    
    // 加载排盘历史
    await loadBaziHistory()
    
    // 加载本地保存的塔罗历史
    loadTarotHistory()
  } catch (error) {
    console.error('加载用户数据失败:', error)
  }
}

// 加载排盘历史（支持分页）
const loadBaziHistory = async () => {
  try {
    const baziRes = await getBaziHistory(baziPageSize.value)
    if (baziRes.code === 0) {
      const allData = baziRes.data || []
      baziTotal.value = allData.length
      
      // 前端分页
      const start = (baziCurrentPage.value - 1) * baziPageSize.value
      const end = start + baziPageSize.value
      baziHistory.value = allData.slice(start, end)
    }
  } catch (error) {
    console.error('加载排盘历史失败:', error)
  }
}

// 加载本地塔罗历史
const loadTarotHistory = () => {
  const saved = JSON.parse(localStorage.getItem('tarot_saved') || '[]')
  tarotHistory.value = saved.slice(0, 10) // 显示最近10条
}

const submitFeedbackForm = async () => {
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
    
    if (response.code === 0) {
      ElMessage.success('反馈提交成功，感谢您的建议！')
      feedbackContent.value = ''
      feedbackContact.value = ''
    } else {
      ElMessage.error(response.message || '提交失败')
    }
  } catch (error) {
    ElMessage.error('网络错误，请稍后重试')
    console.error(error)
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
  const cardNames = record.cards.map(c => c.name + (c.reversed ? '(逆位)' : '(正位)')).join('、')
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
  navigator.clipboard.writeText(inviteCode.value).then(() => {
    ElMessage.success('邀请码已复制到剪贴板')
  })
}

// 生成邀请码
const generateInviteCode = () => {
  const userInfo = JSON.parse(localStorage.getItem('userInfo') || '{}')
  if (userInfo.id) {
    inviteCode.value = 'TC' + userInfo.id.toString().slice(-6).toUpperCase()
  }
}

onMounted(() => {
  loadUserData()
  generateInviteCode()
})
</script>

<style scoped>
.profile-page {
  padding: 60px 0;
}

.page-header {
  display: flex;
  align-items: center;
  gap: 20px;
  margin-bottom: 30px;
}

.page-header .section-title {
  margin: 0;
}

.profile-grid {
  max-width: 1000px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: 300px 1fr;
  gap: 30px;
}

.user-info {
  text-align: center;
}

.avatar-section {
  margin-bottom: 30px;
}

.avatar {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  background: linear-gradient(135deg, #e94560 0%, #ff6b6b 100%);
  margin: 0 auto 15px;
  display: flex;
  justify-content: center;
  align-items: center;
  font-size: 36px;
  color: #fff;
  overflow: hidden;
}

.avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.avatar-section h3 {
  color: #fff;
  margin-bottom: 5px;
}

.user-id {
  color: rgba(255, 255, 255, 0.5);
  font-size: 14px;
}

.user-stats {
  display: flex;
  justify-content: space-around;
  padding-top: 20px;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.stat {
  text-align: center;
}

.stat-value {
  display: block;
  font-size: 24px;
  font-weight: bold;
  color: #e94560;
}

.stat-label {
  font-size: 12px;
  color: rgba(255, 255, 255, 0.6);
}

.points-section,
.history-section,
.feedback-section {
  grid-column: 2;
}

.points-section h3,
.history-section h3,
.feedback-section h3 {
  color: #fff;
  margin-bottom: 20px;
}

.points-list {
  max-height: 300px;
  overflow-y: auto;
}

.points-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.points-item:last-child {
  border-bottom: none;
}

.points-info {
  display: flex;
  flex-direction: column;
}

.points-action {
  color: rgba(255, 255, 255, 0.9);
}

.points-time {
  font-size: 12px;
  color: rgba(255, 255, 255, 0.5);
  margin-top: 5px;
}

.points-change {
  font-weight: bold;
  font-size: 16px;
}

.points-change.positive {
  color: #67C23A;
}

.points-change.negative {
  color: #F56C6C;
}

.history-list {
  max-height: 300px;
  overflow-y: auto;
}

.history-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  flex-wrap: wrap;
  gap: 10px;
}

.history-item:last-child {
  border-bottom: none;
}

.history-info {
  display: flex;
  flex-direction: column;
}

.history-date {
  color: rgba(255, 255, 255, 0.9);
}

.history-gender {
  font-size: 12px;
  color: rgba(255, 255, 255, 0.5);
  margin-top: 5px;
}

.history-bazi {
  display: flex;
  gap: 10px;
  font-family: monospace;
}

.bazi-pillar {
  color: rgba(255, 255, 255, 0.8);
}

.bazi-pillar.highlight {
  color: #e94560;
  font-weight: bold;
}

.feedback-section .el-button {
  margin-top: 15px;
}

.feedback-form {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.feedback-input,
.contact-input {
  width: 100%;
}

.contact-input {
  margin-top: 5px;
}

.history-birth {
  font-size: 12px;
  color: rgba(255, 255, 255, 0.5);
  margin-top: 5px;
}

/* 塔罗历史样式 */
.tarot-item {
  align-items: center;
}

.history-question {
  font-size: 12px;
  color: rgba(255, 255, 255, 0.5);
  margin-top: 5px;
  max-width: 200px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.tarot-cards {
  display: flex;
  gap: 5px;
  align-items: center;
}

.tarot-mini {
  font-size: 20px;
  position: relative;
}

.tarot-mini small {
  font-size: 8px;
  color: #e94560;
  position: absolute;
  bottom: -5px;
  right: -5px;
}

.more-cards {
  font-size: 12px;
  color: rgba(255, 255, 255, 0.5);
  margin-left: 5px;
}

/* 分页样式 */
.pagination-wrapper {
  margin-top: 20px;
  display: flex;
  justify-content: center;
}

:deep(.el-pagination) {
  --el-pagination-bg-color: transparent;
  --el-pagination-hover-color: #e94560;
  --el-pagination-button-color: rgba(255, 255, 255, 0.8);
}

:deep(.el-pagination .btn-prev),
:deep(.el-pagination .btn-next),
:deep(.el-pagination .number) {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 6px;
}

:deep(.el-pagination .number.active) {
  background: #e94560;
  color: #fff;
}

/* 积分获取攻略 */
.points-guide-section h3 {
  color: #fff;
  margin-bottom: 20px;
}

.points-methods {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.method-item {
  display: flex;
  align-items: center;
  gap: 15px;
  padding: 15px;
  background: rgba(255, 255, 255, 0.03);
  border-radius: 10px;
  transition: all 0.3s ease;
}

.method-item:hover {
  background: rgba(255, 255, 255, 0.05);
}

.method-icon {
  font-size: 28px;
  width: 50px;
  height: 50px;
  background: rgba(233, 69, 96, 0.1);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.method-info {
  flex: 1;
}

.method-info h4 {
  color: #fff;
  font-size: 15px;
  margin-bottom: 4px;
}

.method-info p {
  color: rgba(255, 255, 255, 0.5);
  font-size: 13px;
}

.method-reward {
  color: #ffd700;
  font-weight: bold;
  font-size: 16px;
}

.completed-badge {
  color: #67C23A;
  font-size: 18px;
}

/* 邀请好友 */
.invite-section h3 {
  color: #fff;
  margin-bottom: 20px;
}

.invite-content {
  text-align: center;
}

.invite-desc {
  color: rgba(255, 255, 255, 0.8);
  margin-bottom: 25px;
}

.invite-code {
  background: rgba(255, 255, 255, 0.05);
  border: 2px dashed rgba(233, 69, 96, 0.5);
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 25px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 15px;
  flex-wrap: wrap;
}

.code-label {
  color: rgba(255, 255, 255, 0.6);
  font-size: 14px;
}

.code-value {
  color: #e94560;
  font-size: 24px;
  font-weight: bold;
  letter-spacing: 2px;
}

.invite-stats {
  display: flex;
  justify-content: center;
  gap: 50px;
}

.invite-stats .stat {
  text-align: center;
}

.invite-stats .stat-value {
  display: block;
  font-size: 28px;
  font-weight: bold;
  color: #e94560;
}

.invite-stats .stat-label {
  font-size: 13px;
  color: rgba(255, 255, 255, 0.6);
}

@media (max-width: 768px) {
  .profile-grid {
    grid-template-columns: 1fr;
  }
  
  .points-section,
  .history-section,
  .feedback-section {
    grid-column: 1;
  }
  
  .history-question {
    max-width: 120px;
  }
}
</style>
