<script setup>
import { ref, onMounted, computed } from 'vue'
import { ElMessage } from 'element-plus'
import { Money, Coin, TrendCharts, Clock } from '@element-plus/icons-vue'
import {
  getPaymentStats,
  getPaymentTrend
} from '../../api/admin'

const loading = ref(false)
const stats = ref({
  total_amount: 0,
  total_points: 0,
  order_count: 0,
  avg_amount: 0,
  pending_count: 0
})
const trendData = ref([])

const loadAnalysisData = async () => {
  loading.value = true
  try {
    const [statsRes, trendRes] = await Promise.all([
      getPaymentStats(),
      getPaymentTrend({ days: 7 })
    ])

    if (statsRes.code === 200) {
      stats.value = statsRes.data
    }

    if (trendRes.code === 200) {
      trendData.value = trendRes.data || []
    }
  } catch (error) {
    console.error('加载充值数据失败:', error)
    ElMessage.error('加载数据失败')
  } finally {
    loading.value = false
  }
}

const maxAmount = computed(() => {
  if (trendData.value.length === 0) return 1
  return Math.max(...trendData.value.map(d => d.amount)) || 1
})

onMounted(() => {
  loadAnalysisData()
})
</script>

<template>
  <div class="admin-payment-analysis" v-loading="loading">
    <div class="page-header">
      <h2>充值分析</h2>
      <el-button type="primary" @click="loadAnalysisData" :loading="loading">
        刷新数据
      </el-button>
    </div>

    <!-- 统计卡片 -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon amount-icon">
          <el-icon><Money /></el-icon>
        </div>
        <div class="stat-content">
          <div class="stat-label">总充值金额</div>
          <div class="stat-value">¥{{ Number(stats.total_amount).toFixed(2) }}</div>
          <div class="stat-sub">订单数: {{ stats.order_count }}</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon points-icon">
          <el-icon><Coin /></el-icon>
        </div>
        <div class="stat-content">
          <div class="stat-label">总充值积分</div>
          <div class="stat-value">{{ stats.total_points }}</div>
          <div class="stat-sub">已发放积分</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon avg-icon">
          <el-icon><TrendCharts /></el-icon>
        </div>
        <div class="stat-content">
          <div class="stat-label">客单价</div>
          <div class="stat-value">¥{{ Number(stats.avg_amount).toFixed(2) }}</div>
          <div class="stat-sub">平均每单</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon pending-icon">
          <el-icon><Clock /></el-icon>
        </div>
        <div class="stat-content">
          <div class="stat-label">待支付订单</div>
          <div class="stat-value">{{ stats.pending_count }}</div>
          <div class="stat-sub">未完成订单</div>
        </div>
      </div>
    </div>

    <!-- 趋势图表 -->
    <div class="chart-section">
      <h3>充值趋势（最近7天）</h3>
      <div v-if="trendData.length > 0" class="chart-container">
        <div class="chart-bar" v-for="item in trendData" :key="item.date">
          <div class="bar" :style="{ height: (item.amount / maxAmount * 100) + '%' }"></div>
          <div class="label">{{ item.date.slice(5) }}</div>
          <div class="value">¥{{ Number(item.amount).toFixed(0) }}</div>
        </div>
      </div>
      <div v-else class="chart-empty">暂无数据</div>
    </div>

    <!-- 数据表格 -->
    <div class="table-section">
      <h3>每日详情</h3>
      <el-table :data="trendData" stripe>
        <el-table-column prop="date" label="日期" width="120" />
        <el-table-column prop="amount" label="充值金额（元）" width="150">
          <template #default="{ row }">
            ¥{{ Number(row.amount).toFixed(2) }}
          </template>
        </el-table-column>
        <el-table-column prop="count" label="订单数" width="100" />
        <el-table-column label="客单价" width="120">
          <template #default="{ row }">
            ¥{{ row.count > 0 ? (Number(row.amount) / row.count).toFixed(2) : '0.00' }}
          </template>
        </el-table-column>
      </el-table>
    </div>
  </div>
</template>

<style scoped>
.admin-payment-analysis {
  padding: 24px;
}

.page-header {
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

.amount-icon { background: #e8f5e9; color: #4caf50; }
.orders-icon { background: #e3f2fd; color: #2196f3; }
.points-icon { background: #fff8e1; color: #ffc107; }
.pending-icon { background: #ffebee; color: #f44336; }
.avg-icon { background: #fff3e0; color: #ff9800; }

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
  background: linear-gradient(135deg, #4caf50 0%, #8bc34a 100%);
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

.table-section {
  background: white;
  padding: 24px;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.table-section h3 {
  margin: 0 0 20px 0;
  color: #333;
}
</style>
