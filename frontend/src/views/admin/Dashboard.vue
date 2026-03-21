<template>
  <div class="dashboard">
    <div class="dashboard-header">
      <h2>仪表板</h2>
      <p>数据总览</p>
    </div>

    <div class="stats-grid">
      <el-card class="stat-card">
        <div class="stat-item">
          <div class="stat-icon" style="background: #409eff;">
            <el-icon size="24"><User /></el-icon>
          </div>
          <div class="stat-info">
            <div class="stat-label">总用户数</div>
            <div class="stat-value">{{ stats.totalUsers }}</div>
          </div>
        </div>
      </el-card>

      <el-card class="stat-card">
        <div class="stat-item">
          <div class="stat-icon" style="background: #67c23a;">
            <el-icon size="24"><DataLine /></el-icon>
          </div>
          <div class="stat-info">
            <div class="stat-label">今日访问</div>
            <div class="stat-value">{{ stats.todayVisits }}</div>
          </div>
        </div>
      </el-card>

      <el-card class="stat-card">
        <div class="stat-item">
          <div class="stat-icon" style="background: #e6a23c;">
            <el-icon size="24"><Money /></el-icon>
          </div>
          <div class="stat-info">
            <div class="stat-label">总收入</div>
            <div class="stat-value">¥{{ stats.totalRevenue }}</div>
          </div>
        </div>
      </el-card>

      <el-card class="stat-card">
        <div class="stat-item">
          <div class="stat-icon" style="background: #f56c6c;">
            <el-icon size="24"><Warning /></el-icon>
          </div>
          <div class="stat-info">
            <div class="stat-label">待处理</div>
            <div class="stat-value">{{ stats.pendingTasks }}</div>
          </div>
        </div>
      </el-card>
    </div>

    <div class="charts-row">
      <el-card class="chart-card">
        <template #header>
          <div class="card-header">
            <span>用户增长趋势</span>
          </div>
        </template>
        <div ref="userChartRef" style="height: 300px;"></div>
      </el-card>

      <el-card class="chart-card">
        <template #header>
          <div class="card-header">
            <span>收入统计</span>
          </div>
        </template>
        <div ref="revenueChartRef" style="height: 300px;"></div>
      </el-card>
    </div>

    <el-card class="recent-activities">
      <template #header>
        <div class="card-header">
          <span>最近活动</span>
        </div>
      </template>
      <el-table :data="recentActivities" style="width: 100%">
        <el-table-column prop="time" label="时间" width="180" />
        <el-table-column prop="type" label="类型" width="120" />
        <el-table-column prop="content" label="内容" />
      </el-table>
    </el-card>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { User, DataLine, Money, Warning } from '@element-plus/icons-vue'
import * as echarts from 'echarts'

const userChartRef = ref(null)
const revenueChartRef = ref(null)

const stats = ref({
  totalUsers: 1234,
  todayVisits: 56,
  totalRevenue: '12,345',
  pendingTasks: 3
})

const recentActivities = ref([
  { time: '2026-03-20 15:30', type: '订单', content: '新订单 #12345 - ¥99' },
  { time: '2026-03-20 14:20', type: '用户', content: '用户 张三 注册' },
  { time: '2026-03-20 12:10', type: '反馈', content: '收到用户反馈：界面很好用' }
])

const initCharts = () => {
  const userChart = echarts.init(userChartRef.value)
  userChart.setOption({
    tooltip: { trigger: 'axis' },
    xAxis: {
      type: 'category',
      data: ['周一', '周二', '周三', '周四', '周五', '周六', '周日']
    },
    yAxis: { type: 'value' },
    series: [{
      data: [12, 25, 18, 30, 22, 45, 38],
      type: 'line',
      smooth: true
    }]
  })

  const revenueChart = echarts.init(revenueChartRef.value)
  revenueChart.setOption({
    tooltip: { trigger: 'axis' },
    xAxis: {
      type: 'category',
      data: ['一月', '二月', '三月']
    },
    yAxis: { type: 'value' },
    series: [{
      data: [3200, 5400, 6800],
      type: 'bar'
    }]
  })
}

onMounted(() => {
  initCharts()
  window.addEventListener('resize', () => {
    userChartRef.value && echarts.getInstanceByDom(userChartRef.value)?.resize()
    revenueChartRef.value && echarts.getInstanceByDom(revenueChartRef.value)?.resize()
  })
})
</script>

<style scoped>
.dashboard-header {
  margin-bottom: 24px;
}
.dashboard-header h2 {
  margin: 0 0 8px;
  font-size: 20px;
  color: #333;
}
.dashboard-header p {
  margin: 0;
  font-size: 14px;
  color: #999;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
  margin-bottom: 24px;
}

.stat-card :deep(.el-card__body) {
  padding: 20px;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 16px;
}

.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
}

.stat-info {
  flex: 1;
}

.stat-label {
  font-size: 14px;
  color: #666;
  margin-bottom: 4px;
}

.stat-value {
  font-size: 24px;
  font-weight: bold;
  color: #333;
}

.charts-row {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
  margin-bottom: 24px;
}

.chart-card {
  height: 380px;
}

.recent-activities {
  margin-bottom: 24px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
</style>
