<template>
  <div class="app-container">
    <!-- 统计卡片 -->
    <el-row :gutter="20">
      <el-col :xs="24" :sm="12" :lg="6" v-for="item in statistics" :key="item.title">
        <el-card class="stat-card" shadow="hover">
          <div class="stat-content">
            <div class="stat-icon" :style="{ background: item.color }">
              <el-icon :size="24" color="#fff">
                <component :is="item.icon" />
              </el-icon>
            </div>
            <div class="stat-info">
              <div class="stat-title">{{ item.title }}</div>
              <div class="stat-value">{{ item.value }}</div>
              <div class="stat-change" :class="item.trend > 0 ? 'up' : 'down'">
                {{ item.trend > 0 ? '+' : '' }}{{ item.trend }}% 较昨日
              </div>
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <!-- 图表区域 -->
    <el-row :gutter="20" style="margin-top: 20px;">
      <el-col :xs="24" :lg="16">
        <el-card>
          <template #header>
            <span>用户增长趋势</span>
          </template>
          <div ref="userChart" style="height: 350px;"></div>
        </el-card>
      </el-col>
      <el-col :xs="24" :lg="8">
        <el-card>
          <template #header>
            <span>功能使用分布</span>
          </template>
          <div ref="featureChart" style="height: 350px;"></div>
        </el-card>
      </el-col>
    </el-row>

    <!-- 实时数据和最新记录 -->
    <el-row :gutter="20" style="margin-top: 20px;">
      <el-col :xs="24" :lg="12">
        <el-card>
          <template #header>
            <span>实时数据</span>
          </template>
          <div class="realtime-list">
            <div v-for="(item, index) in realtimeData" :key="index" class="realtime-item">
              <span class="time">{{ item.time }}</span>
              <span class="action">{{ item.action }}</span>
              <span class="user">{{ item.user }}</span>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :xs="24" :lg="12">
        <el-card>
          <template #header>
            <span>待处理反馈</span>
            <el-button text type="primary" @click="$router.push('/feedback/list')">查看更多</el-button>
          </template>
          <el-table :data="pendingFeedback" stripe>
            <el-table-column prop="content" label="反馈内容" show-overflow-tooltip />
            <el-table-column prop="type" label="类型" width="100">
              <template #default="{ row }">
                <el-tag size="small">{{ row.type }}</el-tag>
              </template>
            </el-table-column>
            <el-table-column prop="time" label="时间" width="150" />
            <el-table-column label="操作" width="100">
              <template #default="{ row }">
                <el-button link type="primary" @click="handleFeedback(row)">处理</el-button>
              </template>
            </el-table-column>
          </el-table>
        </el-card>
      </el-col>
    </el-row>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import * as echarts from 'echarts'
import { getStatistics, getTrendData, getRealtimeData, getChartData, getPendingFeedback } from '@/api/dashboard'
import { ElMessage } from 'element-plus'

const statistics = ref([
  { title: '总用户数', value: 0, trend: 0, color: '#409eff', icon: 'UserFilled' },
  { title: '今日新增', value: 0, trend: 0, color: '#67c23a', icon: 'User' },
  { title: '八字排盘', value: 0, trend: 0, color: '#e6a23c', icon: 'Calendar' },
  { title: '塔罗占卜', value: 0, trend: 0, color: '#f56c6c', icon: 'MagicStick' }
])

const realtimeData = ref([])
const pendingFeedback = ref([])

const userChart = ref(null)
const featureChart = ref(null)
let userChartInstance = null
let featureChartInstance = null

onMounted(() => {
  initCharts()
  loadAllData()
})

onUnmounted(() => {
  userChartInstance?.dispose()
  featureChartInstance?.dispose()
})

async function loadAllData() {
  await Promise.all([
    loadStatistics(),
    loadRealtimeData(),
    loadPendingFeedback(),
    loadTrendData(),
    loadChartData()
  ])
}

async function loadStatistics() {
  try {
    const res = await getStatistics()
    if (res.code === 200) {
      const stats = res.data.statistics
      statistics.value = [
        { title: '总用户数', value: stats.total_users || 0, trend: 0, color: '#409eff', icon: 'UserFilled' },
        { title: '今日新增', value: stats.today_users || 0, trend: 0, color: '#67c23a', icon: 'User' },
        { title: '八字排盘', value: stats.total_bazi || 0, trend: 0, color: '#e6a23c', icon: 'Calendar' },
        { title: '塔罗占卜', value: stats.total_tarot || 0, trend: 0, color: '#f56c6c', icon: 'MagicStick' }
      ]
    }
  } catch (error) {
    console.error('加载统计数据失败:', error)
  }
}

async function loadRealtimeData() {
  try {
    const res = await getRealtimeData()
    if (res.code === 200) {
      realtimeData.value = res.data.realtime_list || []
    }
  } catch (error) {
    console.error('加载实时数据失败:', error)
  }
}

async function loadPendingFeedback() {
  try {
    const res = await getPendingFeedback()
    if (res.code === 200) {
      pendingFeedback.value = res.data.list || []
    }
  } catch (error) {
    console.error('加载待处理反馈失败:', error)
  }
}

async function loadTrendData() {
  try {
    const res = await getTrendData({ days: 7 })
    if (res.code === 200) {
      const data = res.data
      const formattedData = {
        dates: data.user_trend.map(item => item.date),
        newUsers: data.user_trend.map(item => item.count),
        baziTrend: data.bazi_trend.map(item => item.count),
        tarotTrend: data.tarot_trend.map(item => item.count)
      }
      updateUserChart(formattedData)
    }
  } catch (error) {
    console.error('加载趋势数据失败:', error)
  }
}

async function loadChartData() {
  try {
    const res = await getChartData('feature_usage')
    if (res.code === 200) {
      updateFeatureChart(res.data)
    }
  } catch (error) {
    console.error('加载图表数据失败:', error)
  }
}

function initCharts() {
  // 初始化图表实例
  if (userChart.value) {
    userChartInstance = echarts.init(userChart.value)
  }
  if (featureChart.value) {
    featureChartInstance = echarts.init(featureChart.value)
  }
}

function updateUserChart(data) {
  if (!userChartInstance) return
  
  userChartInstance.setOption({
    tooltip: { trigger: 'axis' },
    legend: { data: ['新增用户', '八字排盘', '塔罗占卜'] },
    grid: { left: '3%', right: '4%', bottom: '3%', containLabel: true },
    xAxis: {
      type: 'category',
      boundaryGap: false,
      data: data.dates || []
    },
    yAxis: { type: 'value' },
    series: [
      {
        name: '新增用户',
        type: 'line',
        smooth: true,
        data: data.newUsers || [],
        areaStyle: { opacity: 0.1 }
      },
      {
        name: '八字排盘',
        type: 'line',
        smooth: true,
        data: data.baziTrend || []
      },
      {
        name: '塔罗占卜',
        type: 'line',
        smooth: true,
        data: data.tarotTrend || []
      }
    ]
  })
}

function updateFeatureChart(data) {
  if (!featureChartInstance) return
  
  featureChartInstance.setOption({
    tooltip: { trigger: 'item' },
    legend: { orient: 'vertical', left: 'left' },
    series: [
      {
        name: '功能使用',
        type: 'pie',
        radius: ['40%', '70%'],
        avoidLabelOverlap: false,
        itemStyle: { borderRadius: 10, borderColor: '#fff', borderWidth: 2 },
        label: { show: false, position: 'center' },
        emphasis: {
          label: { show: true, fontSize: 20, fontWeight: 'bold' }
        },
        data: data || []
      }
    ]
  })
}

function handleFeedback(row) {
  router.push({
    path: '/feedback/list',
    query: { id: row.id }
  })
}
</script>

<style lang="scss" scoped>
.stat-card {
  margin-bottom: 20px;
  
  .stat-content {
    display: flex;
    align-items: center;
  }
  
  .stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
  }
  
  .stat-info {
    flex: 1;
  }
  
  .stat-title {
    font-size: 14px;
    color: #909399;
    margin-bottom: 8px;
  }
  
  .stat-value {
    font-size: 24px;
    font-weight: 600;
    color: #303133;
    margin-bottom: 5px;
  }
  
  .stat-change {
    font-size: 12px;
    
    &.up {
      color: #67c23a;
    }
    
    &.down {
      color: #f56c6c;
    }
  }
}

.realtime-list {
  .realtime-item {
    display: flex;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #ebeef5;
    
    &:last-child {
      border-bottom: none;
    }
    
    .time {
      width: 80px;
      color: #909399;
      font-size: 13px;
    }
    
    .action {
      flex: 1;
      margin: 0 10px;
    }
    
    .user {
      color: #606266;
      font-size: 13px;
    }
  }
}

:deep(.el-card__header) {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
</style>
