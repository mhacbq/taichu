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

onMounted(() => {
  loadUserData()
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
