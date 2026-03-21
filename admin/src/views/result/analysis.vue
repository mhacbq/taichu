<template>
  <div class="app-container">
    <el-card shadow="never" class="analysis-header">
      <div class="header-content">
        <div>
          <div class="page-title">测算数据统计</div>
          <div class="page-subtitle">测算量趋势、热门类型排行等多维度洞察</div>
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
      <el-col :xs="24" :sm="12" :lg="6" v-for="stat in calculationStats" :key="stat.label">
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
            <div class="card-title">测算量趋势</div>
          </template>
          <div ref="trendChartRef" style="height: 300px;"></div>
        </el-card>
      </el-col>
      <el-col :xs="24" :lg="12">
        <el-card shadow="never">
          <template #header>
            <div class="card-title">热门类型排行</div>
          </template>
          <div ref="typeRankChartRef" style="height: 300px;"></div>
        </el-card>
      </el-col>
    </el-row>

    <el-row :gutter="20" class="charts-row">
      <el-col :xs="24" :lg="12">
        <el-card shadow="never">
          <template #header>
            <div class="card-title">测算类型分布</div>
          </template>
          <div ref="typeDistChartRef" style="height: 300px;"></div>
        </el-card>
      </el-col>
      <el-col :xs="24" :lg="12">
        <el-card shadow="never">
          <template #header>
            <div class="card-title">用户测算排行</div>
          </template>
          <div ref="userRankChartRef" style="height: 300px;"></div>
        </el-card>
      </el-col>
    </el-row>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import * as echarts from 'echarts'
import { getResultAnalysis } from '@/api/analysis'

const loading = ref(false)
const dateRange = ref([])
const trendChartRef = ref(null)
const typeRankChartRef = ref(null)
const typeDistChartRef = ref(null)
const userRankChartRef = ref(null)

const calculationStats = ref([
  { label: '总测算量', value: '0', trend: 0 },
  { label: '今日测算', value: '0', trend: 0 },
  { label: '测算用户数', value: '0', trend: 0 },
  { label: '人均测算', value: '0', trend: 0 }
])

let trendChart = null
let typeRankChart = null
let typeDistChart = null
let userRankChart = null

onMounted(() => {
  initCharts()
  loadData()
})

onUnmounted(() => {
  if (trendChart) trendChart.dispose()
  if (typeRankChart) typeRankChart.dispose()
  if (typeDistChart) typeDistChart.dispose()
  if (userRankChart) userRankChart.dispose()
})

function initCharts() {
  trendChart = echarts.init(trendChartRef.value)
  typeRankChart = echarts.init(typeRankChartRef.value)
  typeDistChart = echarts.init(typeDistChartRef.value)
  userRankChart = echarts.init(userRankChartRef.value)
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

    const { data } = await getResultAnalysis(params)
    
    const totalCalculations = data.trend?.reduce((sum, item) => sum + (parseInt(item.count) || 0), 0) || 0
    
    calculationStats.value = [
      { label: '总测算量', value: totalCalculations, trend: 0 },
      { label: '今日测算', value: data.trend?.[data.trend.length - 1]?.count || 0, trend: 0 },
      { label: '测算用户数', value: totalCalculations, trend: 0 },
      { label: '人均测算', value: totalCalculations > 0 ? 1 : 0, trend: 0 }
    ]
    
    renderTrendChart(data.trend)
    renderTypeRankChart(data.type_ranking)
    renderTypeDistChart(data.type_distribution)
  } catch (error) {
    console.error('加载测算数据失败:', error)
  } finally {
    loading.value = false
  }
}

function renderTrendChart(data) {
  const option = {
    tooltip: {
      trigger: 'axis'
    },
    xAxis: {
      type: 'category',
      data: data?.dates || []
    },
    yAxis: {
      type: 'value'
    },
    series: [
      {
        name: '测算量',
        type: 'line',
        data: data?.values || [],
        smooth: true,
        areaStyle: {
          color: {
            type: 'linear',
            x: 0, y: 0, x2: 0, y2: 1,
            colorStops: [
              { offset: 0, color: 'rgba(64, 158, 255, 0.5)' },
              { offset: 1, color: 'rgba(64, 158, 255, 0.05)' }
            ]
          }
        }
      }
    ]
  }
  trendChart.setOption(option)
}

function renderTypeRankChart(data) {
  const option = {
    tooltip: {
      trigger: 'axis',
      axisPointer: {
        type: 'shadow'
      }
    },
    grid: {
      left: '3%',
      right: '4%',
      bottom: '3%',
      containLabel: true
    },
    xAxis: {
      type: 'value'
    },
    yAxis: {
      type: 'category',
      data: data?.types || []
    },
    series: [
      {
        name: '测算次数',
        type: 'bar',
        data: data?.counts || [],
        itemStyle: {
          color: '#409eff'
        }
      }
    ]
  }
  typeRankChart.setOption(option)
}

function renderTypeDistChart(data) {
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
  typeDistChart.setOption(option)
}

function renderUserRankChart(data) {
  const option = {
    tooltip: {
      trigger: 'axis',
      axisPointer: {
        type: 'shadow'
      }
    },
    grid: {
      left: '3%',
      right: '4%',
      bottom: '3%',
      containLabel: true
    },
    xAxis: {
      type: 'value'
    },
    yAxis: {
      type: 'category',
      data: data?.users || []
    },
    series: [
      {
        name: '测算次数',
        type: 'bar',
        data: data?.counts || [],
        itemStyle: {
          color: '#67c23a'
        }
      }
    ]
  }
  userRankChart.setOption(option)
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
