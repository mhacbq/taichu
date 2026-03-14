<template>
  <div class="profile-page">
    <div class="container">
      <h1 class="section-title">个人中心</h1>
      
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
                <span class="points-time">{{ record.createdAt }}</span>
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
                <span class="history-date">{{ record.birthDate }}</span>
                <span class="history-gender">{{ record.gender === 'male' ? '男' : '女' }}</span>
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
        </div>

        <!-- 反馈建议 -->
        <div class="feedback-section card">
          <h3>反馈建议</h3>
          <el-input
            v-model="feedbackContent"
            type="textarea"
            :rows="4"
            placeholder="请输入您的意见或建议，帮助我们改进服务..."
          />
          <el-button type="primary" @click="submitFeedbackForm" :loading="feedbackLoading">
            提交反馈
          </el-button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { getUserInfo, getPointsBalance, getPointsHistory, getBaziHistory, submitFeedback } from '../api'

const userInfo = ref({})
const pointsBalance = ref(0)
const baziCount = ref(0)
const tarotCount = ref(0)
const pointsHistory = ref([])
const baziHistory = ref([])
const feedbackContent = ref('')
const feedbackLoading = ref(false)

const loadUserData = async () => {
  try {
    const [userRes, pointsRes, historyRes] = await Promise.all([
      getUserInfo(),
      getPointsBalance(),
      getPointsHistory(),
      getBaziHistory(),
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
    
    // Load bazi history
    const baziRes = await getBaziHistory()
    if (baziRes.code === 0) {
      baziHistory.value = baziRes.data || []
    }
  } catch (error) {
    console.error('加载用户数据失败:', error)
  }
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
    })
    
    if (response.code === 0) {
      ElMessage.success('反馈提交成功，感谢您的建议！')
      feedbackContent.value = ''
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
  // TODO: 查看排盘详情
  ElMessage.info('功能开发中...')
}

onMounted(() => {
  loadUserData()
})
</script>

<style scoped>
.profile-page {
  padding: 60px 0;
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

@media (max-width: 768px) {
  .profile-grid {
    grid-template-columns: 1fr;
  }
  
  .points-section,
  .history-section,
  .feedback-section {
    grid-column: 1;
  }
}
</style>
