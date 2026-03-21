<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import {
  getDashboardStats,
  getDashboardTrend,
  getDashboardChart,
  getPendingFeedback
} from '../../api/admin'

const router = useRouter()
const loading = ref(false)
const stats = ref({
  total_users: 0,
  today_users: 0,
  total_orders: 0,
  today_orders: 0,
  total_points: 0
})
const trendData = ref([])
const pendingFeedbackCount = ref(0)

const goTo = (path) => {
  router.push(path)
}

const loadDashboardData = async () => {
  loading.value = true
  try {
    const [statsRes, trendRes, feedbackRes] = await Promise.all([
      getDashboardStats(),
      getDashboardTrend(),
      getPendingFeedback()
    ])

    if (statsRes.code === 200) {
      stats.value = statsRes.data
    }

    if (trendRes.code === 200) {
      trendData.value = trendRes.data.user_trend || []
    }

    if (feedbackRes.code === 200) {
      pendingFeedbackCount.value = feedbackRes.data.count || 0
    }
  } catch (error) {
    console.error('加载仪表盘数据失败:', error)
    ElMessage.error('加载数据失败')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadDashboardData()
})
</script>

<template>
  <div class="admin-dashboard" v-loading="loading">
    <div class="dashboard-header">
      <h2>仪表盘</h2>
      <el-button type="primary" @click="loadDashboardData" :loading="loading">
        刷新数据
      </el-button>
    </div>

    <!-- 统计卡片 -->
    <div class="stats-grid">
      <div class="stat-card" @click="goTo('/maodou/list')">
        <div class="stat-icon user-icon">
          <el-icon><User /></el-icon>
        </div>
        <div class="stat-content">
          <div class="stat-label">总用户数</div>
          <div class="stat-value">{{ stats.total_users }}</div>
          <div class="stat-sub">今日新增: {{ stats.today_users }}</div>
        </div>
      </div>

      <div class="stat-card" @click="goTo('/maodou/payment/orders')">
        <div class="stat-icon order-icon">
          <el-icon><ShoppingCart /></el-icon>
        </div>
        <div class="stat-content">
          <div class="stat-label">总订单数</div>
          <div class="stat-value">{{ stats.total_orders }}</div>
          <div class="stat-sub">今日订单: {{ stats.today_orders }}</div>
        </div>
      </div>

      <div class="stat-card" @click="goTo('/maodou/points/records')">
        <div class="stat-icon points-icon">
          <el-icon><Coin /></el-icon>
        </div>
        <div class="stat-content">
          <div class="stat-label">积分总量</div>
          <div class="stat-value">{{ stats.total_points }}</div>
          <div class="stat-sub">积分消耗</div>
        </div>
      </div>

      <div class="stat-card" @click="goTo('/maodou/feedback/list')">
        <div class="stat-icon feedback-icon">
          <el-icon><ChatDotRound /></el-icon>
        </div>
        <div class="stat-content">
          <div class="stat-label">待处理反馈</div>
          <div class="stat-value">{{ pendingFeedbackCount }}</div>
          <div class="stat-sub">需回复</div>
        </div>
      </div>
    </div>

    <!-- 趋势图表 -->
    <div class="chart-section">
      <h3>用户增长趋势（最近7天）</h3>
      <div v-if="trendData.length > 0" class="chart-container">
        <div class="chart-bar" v-for="item in trendData" :key="item.date">
          <div class="bar" :style="{ height: (item.count / Math.max(...trendData.map(d => d.count)) * 100) + '%' }"></div>
          <div class="label">{{ item.date.slice(5) }}</div>
          <div class="value">{{ item.count }}</div>
        </div>
      </div>
      <div v-else class="chart-empty">暂无数据</div>
    </div>

    <!-- 快捷入口 -->
    <div class="quick-actions">
      <h3>快捷入口</h3>
      <div class="action-grid">
        <el-button @click="goTo('/maodou/list')">用户管理</el-button>
        <el-button @click="goTo('/maodou/payment/orders')">订单管理</el-button>
        <el-button @click="goTo('/maodou/points/records')">积分记录</el-button>
        <el-button @click="goTo('/maodou/feedback/list')">反馈管理</el-button>
        <el-button @click="goTo('/maodou/bazi-manage')">八字管理</el-button>
        <el-button @click="goTo('/maodou/tarot-manage')">塔罗管理</el-button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.admin-dashboard {
  padding: 24px;
}

.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.stat-card {
  display: flex;
  align-items: center;
  padding: 20px;
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  cursor: pointer;
  transition: transform 0.2s;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.stat-icon {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 16px;
}

.user-icon { background: #e3f2fd; color: #1976d2; }
.order-icon { background: #e8f5e9; color: #388e3c; }
.points-icon { background: #fff3e0; color: #f57c00; }
.feedback-icon { background: #f3e5f5; color: #7b1fa2; }

.stat-icon .el-icon {
  font-size: 28px;
}

.stat-content {
  flex: 1;
}

.stat-label {
  font-size: 14px;
  color: #666;
  margin-bottom: 8px;
}

.stat-value {
  font-size: 28px;
  font-weight: bold;
  color: #333;
  margin-bottom: 4px;
}

.stat-sub {
  font-size: 12px;
  color: #999;
}

.chart-section {
  background: white;
  padding: 24px;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  margin-bottom: 30px;
}

.chart-section h3 {
  margin: 0 0 20px 0;
  color: #333;
}

.chart-container {
  display: flex;
  align-items: flex-end;
  justify-content: space-around;
  height: 200px;
  padding: 0 20px;
}

.chart-bar {
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 60px;
}

.bar {
  width: 40px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 4px 4px 0 0;
  transition: height 0.3s;
}

.label {
  margin-top: 8px;
  font-size: 12px;
  color: #666;
}

.value {
  margin-top: 4px;
  font-size: 14px;
  font-weight: bold;
  color: #333;
}

.chart-empty {
  text-align: center;
  color: #999;
  padding: 40px 0;
}

.quick-actions {
  background: white;
  padding: 24px;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.quick-actions h3 {
  margin: 0 0 20px 0;
  color: #333;
}

.action-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  gap: 12px;
}
</style>
