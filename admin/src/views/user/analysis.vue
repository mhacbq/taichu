<template>
  <div class="app-container">
    <el-card shadow="never" class="analysis-header">
      <div class="header-content">
        <div>
          <div class="page-title">用户增长分析</div>
          <div class="page-subtitle">增长趋势、留存分析、来源分析等多维度洞察</div>
        </div>
        <div class="header-actions">
          <el-date-picker
            v-model="dateRange"
            type="daterange"
            range-separator="至"
            start-placeholder="开始日期"
            end-placeholder="结束日期"
            value-format="YYYY-MM-DD"
            @change="handleDateChange"
          />
          <el-button type="primary" :loading="loading" @click="loadData">刷新数据</el-button>
        </div>
      </div>
    </el-card>

    <el-row :gutter="20" class="stats-row">
      <el-col :xs="24" :sm="12" :lg="6" v-for="stat in userStats" :key="stat.label">
        <el-card class="stat-card" shadow="hover">
          <div class="stat-label">{{ stat.label }}</div>
          <div class="stat-value">{{ stat.value }}</div>
          <div class="stat-trend" :class="stat.trend > 0 ? 'up' : 'down'">
            {{ stat.trend > 0 ? '↑' : '↓' }} {{ Math.abs(stat.trend) }}% 较上期
          </div>
        </el-card>
      </el-col>
    </el-row>

    <el-row :gutter="20" class="charts-row">
      <el-col :xs="24" :lg="12">
        <el-card shadow="never">
          <template #header>
            <div class="card-title">用户增长趋势</div>
          </template>
          <div ref="growthChartRef" style="height: 300px;"></div>
        </el-card>
      </el-col>
      <el-col :xs="24" :lg="12">
        <el-card shadow="never">
          <template #header>
            <div class="card-title">用户来源分析</div>
          </template>
          <div ref="sourceChartRef" style="height: 300px;"></div>
        </el-card>
      </el-col>
    </el-row>

    <el-row :gutter="20" class="charts-row">
      <el-col :xs="24">
        <el-card shadow="never">
          <template #header>
            <div class="card-title">留存分析</div>
          </template>
          <div ref="retentionChartRef" style="height: 400px;"></div>
        </el-card>
      </el-col>
    </el-row>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import * as echarts from 'echarts'
import { getUserAnalysis } from '@/api/analysis'

const loading = ref(false)
const dateRange = ref([])
const growthChartRef = ref(null)
const sourceChartRef = ref(null)
const retentionChartRef = ref(null)

const userStats = ref([
  { label: '总用户数', value: '0', trend: 0 },
  { label: '新增用户', value: '0', trend: 0 },
  { label: '活跃用户', value: '0', trend: 0 },
  { label: '7日留存率', value: '0%', trend: 0 }
])

let growthChart = null
let sourceChart = null
let retentionChart = null

onMounted(() => {
  initCharts()
  loadData()
})

onUnmounted(() => {
  if (growthChart) growthChart.dispose()
  if (sourceChart) sourceChart.dispose()
  if (retentionChart) retentionChart.dispose()
})

function initCharts() {
  growthChart = echarts.init(growthChartRef.value)
  sourceChart = echarts.init(sourceChartRef.value)
  retentionChart = echarts.init(retentionChartRef.value)
}

function handleDateChange() {
  loadData()
}

async function loadData() {
  loading.value = true
  try {
    const params = {
      start_date: dateRange.value?.[0],
      end_date: dateRange.value?.[1]
    }

    const { data } = await getUserAnalysis(params)
    
    // 计算统计数据
    const newUsers = data.growth?.reduce((sum, item) => sum + (parseInt(item.count) || 0), 0) || 0
    
    userStats.value = [
      { label: '总用户数', value: newUsers, trend: 0 },
      { label: '新增用户', value: newUsers, trend: 0 },
      { label: '活跃用户', value: newUsers, trend: 0 },
      { label: '7日留存率', value: `${data.retention_rate}%`, trend: 0 }
    ]
    
    renderGrowthChart(data.growth)
    renderSourceChart(data.source)
    renderRetentionChart(data.retention_rate)
  } catch (error) {
    console.error('加载用户数据失败:', error)
  } finally {
    loading.value = false
  }
}

function renderGrowthChart(data) {
  const dates = data?.map(item => item.date) || []
  const counts = data?.map(item => item.count) || []
  
  const option = {
    tooltip: {
      trigger: 'axis'
    },
    xAxis: {
      type: 'category',
      data: dates
    },
    yAxis: {
      type: 'value'
    },
    series: [
      {
        name: '新增用户',
        type: 'bar',
        data: data?.newUsers || [],
        itemStyle: {
          color: '#409eff'
        }
      },
      {
        name: '活跃用户',
        type: 'line',
        data: data?.activeUsers || [],
        smooth: true,
        itemStyle: {
          color: '#67c23a'
        }
      },
      {
        name: '累计用户',
        type: 'line',
        data: data?.totalUsers || [],
        smooth: true,
        itemStyle: {
          color: '#e6a23c'
        }
      }
    ]
  }
  growthChart.setOption(option)
}

function renderSourceChart(data) {
  const option = {
    tooltip: {
      trigger: 'item',
      formatter: '{b}: {c} ({d}%)'
    },
    legend: {
      orient: 'vertical',
      right: 10,
      top: 'center'
    },
    series: [
      {
        type: 'pie',
        radius: ['40%', '70%'],
        avoidLabelOverlap: false,
        itemStyle: {
          borderRadius: 10,
          borderColor: '#fff',
          borderWidth: 2
        },
        label: {
          show: false,
          position: 'center'
        },
        emphasis: {
          label: {
            show: true,
            fontSize: 20,
            fontWeight: 'bold'
          }
        },
        data: data || []
      }
    ]
  }
  sourceChart.setOption(option)
}

function renderRetentionChart(data) {
  const option = {
    tooltip: {
      trigger: 'axis',
      axisPointer: {
        type: 'shadow'
      }
    },
    legend: {
      data: ['次日留存', '7日留存', '30日留存']
    },
    xAxis: {
      type: 'category',
      data: data?.dates || []
    },
    yAxis: {
      type: 'value',
      axisLabel: {
        formatter: '{value}%'
      },
      max: 100
    },
    series: [
      {
        name: '次日留存',
        type: 'bar',
        data: data?.day1 || [],
        itemStyle: {
          color: '#409eff'
        }
      },
      {
        name: '7日留存',
        type: 'bar',
        data: data?.day7 || [],
        itemStyle: {
          color: '#67c23a'
        }
      },
      {
        name: '30日留存',
        type: 'bar',
        data: data?.day30 || [],
        itemStyle: {
          color: '#e6a23c'
        }
      }
    ]
  }
  retentionChart.setOption(option)
}
</script>

<style lang="scss" scoped>
.analysis-header {
  margin-bottom: 20px;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  flex-wrap: wrap;
  gap: 15px;
}

.page-title {
  font-size: 20px;
  font-weight: 600;
  color: #303133;
  margin-bottom: 8px;
}

.page-subtitle {
  font-size: 14px;
  color: #909399;
}

.header-actions {
  display: flex;
  gap: 10px;
}

.stats-row {
  margin-bottom: 20px;
}

.stat-card {
  text-align: center;
  padding: 20px;

  .stat-label {
    font-size: 14px;
    color: #909399;
    margin-bottom: 10px;
  }

  .stat-value {
    font-size: 28px;
    font-weight: 600;
    color: #303133;
    margin-bottom: 8px;
  }

  .stat-trend {
    font-size: 12px;
    
    &.up {
      color: #67c23a;
    }
    
    &.down {
      color: #f56c6c;
    }
  }
}

.charts-row {
  margin-bottom: 20px;
}

.card-title {
  font-size: 16px;
  font-weight: 500;
  color: #303133;
}
</style>
