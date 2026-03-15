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
import { getStatistics } from '@/api/dashboard'

const statistics = ref([
  { title: '总用户数', value: 12580, trend: 12.5, color: '#409eff', icon: 'UserFilled' },
  { title: '今日新增', value: 128, trend: 8.2, color: '#67c23a', icon: 'User' },
  { title: '八字排盘', value: 8562, trend: -2.1, color: '#e6a23c', icon: 'Calendar' },
  { title: '塔罗占卜', value: 4321, trend: 15.3, color: '#f56c6c', icon: 'MagicStick' }
])

const realtimeData = ref([
  { time: '10:23:45', action: '八字排盘', user: '用户138****8888' },
  { time: '10:22:12', action: '塔罗抽牌', user: '用户139****6666' },
  { time: '10:20:08', action: '用户注册', user: '新用户' },
  { time: '10:18:33', action: '每日运势', user: '用户137****9999' },
  { time: '10:15:21', action: '积分兑换', user: '用户136****5555' }
])

const pendingFeedback = ref([
  { content: '八字分析结果不够详细，希望能增加更多解读', type: '建议', time: '2026-03-15 09:30' },
  { content: '塔罗牌加载太慢，体验不好', type: '问题', time: '2026-03-15 08:45' },
  { content: '积分规则不清楚', type: '咨询', time: '2026-03-14 16:20' }
])

const userChart = ref(null)
const featureChart = ref(null)
let userChartInstance = null
let featureChartInstance = null

onMounted(() => {
  initCharts()
  loadStatistics()
})

onUnmounted(() => {
  userChartInstance?.dispose()
  featureChartInstance?.dispose()
})

function initCharts() {
  // 用户增长趋势图
  userChartInstance = echarts.init(userChart.value)
  userChartInstance.setOption({
    tooltip: { trigger: 'axis' },
    grid: { left: '3%', right: '4%', bottom: '3%', containLabel: true },
    xAxis: {
      type: 'category',
      boundaryGap: false,
      data: ['周一', '周二', '周三', '周四', '周五', '周六', '周日']
    },
    yAxis: { type: 'value' },
    series: [
      {
        name: '新增用户',
        type: 'line',
        smooth: true,
        data: [120, 132, 101, 134, 90, 230, 210],
        areaStyle: { opacity: 0.3 }
      },
      {
        name: '活跃用户',
        type: 'line',
        smooth: true,
        data: [220, 182, 191, 234, 290, 330, 310]
      }
    ]
  })

  // 功能使用分布图
  featureChartInstance = echarts.init(featureChart.value)
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
        data: [
          { value: 1048, name: '八字排盘' },
          { value: 735, name: '塔罗占卜' },
          { value: 580, name: '每日运势' },
          { value: 484, name: '积分兑换' },
          { value: 300, name: '其他' }
        ]
      }
    ]
  })
}

async function loadStatistics() {
  try {
    const { data } = await getStatistics()
    // 更新统计数据
  } catch (error) {
    console.error(error)
  }
}

function handleFeedback(row) {
  console.log('处理反馈', row)
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
