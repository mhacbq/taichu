<template>
  <div class="app-container">
    <el-card shadow="never" class="analysis-header">
      <div class="header-content">
        <div>
          <div class="page-title">充值数据分析</div>
          <div class="page-subtitle">渠道对比、复购率、充值热力图等多维度分析</div>
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
      <el-col :xs="24" :sm="12" :lg="6" v-for="stat in rechargeStats" :key="stat.label">
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
            <div class="card-title">渠道对比分析</div>
          </template>
          <div ref="channelChartRef" style="height: 300px;"></div>
        </el-card>
      </el-col>
      <el-col :xs="24" :lg="12">
        <el-card shadow="never">
          <template #header>
            <div class="card-title">复购率趋势</div>
          </template>
          <div ref="repurchaseChartRef" style="height: 300px;"></div>
        </el-card>
      </el-col>
    </el-row>

    <el-row :gutter="20" class="charts-row">
      <el-col :xs="24">
        <el-card shadow="never">
          <template #header>
            <div class="card-title">充值热力图（按时间段分布）</div>
          </template>
          <div ref="heatmapChartRef" style="height: 400px;"></div>
        </el-card>
      </el-col>
    </el-row>

    <el-card shadow="never" class="table-card">
      <template #header>
        <div class="card-title">充值详情记录</div>
      </template>
      <el-table v-loading="loading" :data="rechargeList" stripe>
        <el-table-column prop="id" label="订单号" width="120" />
        <el-table-column prop="user_id" label="用户ID" width="100" />
        <el-table-column prop="amount" label="充值金额" width="120" />
        <el-table-column prop="channel" label="支付渠道" width="100">
          <template #default="{ row }">
            <el-tag :type="getChannelType(row.channel)">{{ getChannelText(row.channel) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="status" label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)">{{ getStatusText(row.status) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="充值时间" width="160" />
      </el-table>
      <div class="pagination-container">
        <el-pagination
          v-model:current-page="pagination.page"
          v-model:page-size="pagination.pageSize"
          :total="pagination.total"
          layout="total, sizes, prev, pager, next"
          @size-change="loadData"
          @current-change="loadData"
        />
      </div>
    </el-card>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, onUnmounted } from 'vue'
import * as echarts from 'echarts'
import { getPaymentAnalysis } from '@/api/analysis'

const loading = ref(false)
const dateRange = ref([])
const channelChartRef = ref(null)
const repurchaseChartRef = ref(null)
const heatmapChartRef = ref(null)

const rechargeStats = ref([
  { label: '总充值金额', value: '¥0', trend: 0 },
  { label: '充值订单数', value: '0', trend: 0 },
  { label: '人均充值', value: '¥0', trend: 0 },
  { label: '复购率', value: '0%', trend: 0 }
])

const rechargeList = ref([])
const pagination = reactive({
  page: 1,
  pageSize: 20,
  total: 0
})

let channelChart = null
let repurchaseChart = null
let heatmapChart = null

onMounted(() => {
  initCharts()
  loadData()
})

onUnmounted(() => {
  if (channelChart) channelChart.dispose()
  if (repurchaseChart) repurchaseChart.dispose()
  if (heatmapChart) heatmapChart.dispose()
})

function initCharts() {
  channelChart = echarts.init(channelChartRef.value)
  repurchaseChart = echarts.init(repurchaseChartRef.value)
  heatmapChart = echarts.init(heatmapChartRef.value)
}

function handleDateChange() {
  pagination.page = 1
  loadData()
}

async function loadData() {
  loading.value = true
  try {
    const params = {
      start_date: dateRange.value?.[0],
      end_date: dateRange.value?.[1],
      page: pagination.page,
      pageSize: pagination.pageSize
    }

    const { data } = await getPaymentAnalysis(params)
    
    // 计算统计数据
    const totalAmount = data.channel?.reduce((sum, item) => sum + (parseFloat(item.total_amount) || 0), 0) || 0
    const totalCount = data.channel?.reduce((sum, item) => sum + (parseInt(item.count) || 0), 0) || 0
    const avgAmount = totalCount > 0 ? (totalAmount / totalCount).toFixed(2) : 0
    
    rechargeStats.value = [
      { label: '总充值金额', value: `¥${totalAmount.toFixed(2)}`, trend: 0 },
      { label: '充值订单数', value: totalCount, trend: 0 },
      { label: '人均充值', value: `¥${avgAmount}`, trend: 0 },
      { label: '复购率', value: `${data.repeat_rate}%`, trend: 0 }
    ]
    
    renderChannelChart(data.channel)
    renderRepurchaseChart(data.trend)
    renderHeatmapChart(data.heatmap)
  } catch (error) {
    console.error('加载充值数据失败:', error)
  } finally {
    loading.value = false
  }
}

function renderChannelChart(data) {
  const option = {
    tooltip: {
      trigger: 'item',
      formatter: '{b}: {c}元 ({d}%)'
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
  channelChart.setOption(option)
}

function renderRepurchaseChart(data) {
  const option = {
    tooltip: {
      trigger: 'axis'
    },
    xAxis: {
      type: 'category',
      data: data?.dates || []
    },
    yAxis: {
      type: 'value',
      axisLabel: {
        formatter: '{value}%'
      }
    },
    series: [
      {
        name: '复购率',
        type: 'line',
        data: data?.rates || [],
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
  repurchaseChart.setOption(option)
}

function renderHeatmapChart(data) {
  const option = {
    tooltip: {
      position: 'top'
    },
    grid: {
      height: '70%',
      top: '10%'
    },
    xAxis: {
      type: 'category',
      data: data?.hours || Array.from({ length: 24 }, (_, i) => `${i}:00`),
      splitArea: {
        show: true
      }
    },
    yAxis: {
      type: 'category',
      data: data?.dates || [],
      splitArea: {
        show: true
      }
    },
    visualMap: {
      min: 0,
      max: data?.max || 10,
      calculable: true,
      orient: 'horizontal',
      left: 'center',
      bottom: '0%',
      inRange: {
        color: ['#50a3ba', '#eac736', '#d94e5d']
      }
    },
    series: [
      {
        name: '充值次数',
        type: 'heatmap',
        data: data?.values || [],
        label: {
          show: true
        },
        emphasis: {
          itemStyle: {
            shadowBlur: 10,
            shadowColor: 'rgba(0, 0, 0, 0.5)'
          }
        }
      }
    ]
  }
  heatmapChart.setOption(option)
}

function getChannelType(channel) {
  const map = { wechat: 'success', alipay: 'primary' }
  return map[channel] || 'info'
}

function getChannelText(channel) {
  const map = { wechat: '微信支付', alipay: '支付宝' }
  return map[channel] || channel
}

function getStatusType(status) {
  const map = { success: 'success', pending: 'warning', failed: 'danger' }
  return map[status] || 'info'
}

function getStatusText(status) {
  const map = { success: '成功', pending: '处理中', failed: '失败' }
  return map[status] || status
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

.table-card {
  margin-bottom: 20px;
}

.pagination-container {
  display: flex;
  justify-content: flex-end;
  margin-top: 20px;
}
</style>
