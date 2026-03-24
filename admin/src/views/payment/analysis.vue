<script setup lang="ts">
import { ref, onMounted } from 'vue'
import * as echarts from 'echarts'
import { ElMessage } from 'element-plus'
import request from '@/api/request'

const loading = ref(false)
const chartRef = ref(null)
let chartInstance = null

const stats = ref({
  total_amount: 0,
  order_count: 0,
  vip_count: 0,
  recharge_count: 0
})

onMounted(() => {
  fetchAnalysisData()
})

async function fetchAnalysisData() {
  loading.value = true
  try {
    const res = await request.get('/analysis/payment')
    stats.value = res.data.stats
    initChart(res.data.chart_data)
  } catch (error) {
    ElMessage.error('获取充值数据失败')
  } finally {
    loading.value = false
  }
}

function initChart(chartData) {
  if (!chartRef.value) return
  
  if (!chartInstance) {
    chartInstance = echarts.init(chartRef.value)
  }
  
  const option = {
    title: {
      text: '充值趋势'
    },
    tooltip: {
      trigger: 'axis'
    },
    legend: {
      data: ['充值金额', '订单数量']
    },
    xAxis: {
      type: 'category',
      data: chartData?.dates || ['周一', '周二', '周三', '周四', '周五', '周六', '周日']
    },
    yAxis: [
      {
        type: 'value',
        name: '金额(元)',
        position: 'left'
      },
      {
        type: 'value',
        name: '订单数',
        position: 'right'
      }
    ],
    series: [
      {
        name: '充值金额',
        type: 'bar',
        data: chartData?.amounts || [1200, 1800, 1500, 2000, 2200, 2800, 2500],
        itemStyle: {
          color: '#409EFF'
        }
      },
      {
        name: '订单数量',
        type: 'line',
        yAxisIndex: 1,
        data: chartData?.counts || [12, 18, 15, 20, 22, 28, 25],
        itemStyle: {
          color: '#67C23A'
        }
      }
    ]
  }
  
  chartInstance.setOption(option)
}
</script>

<template>
  <div class="app-container">
    <el-card v-loading="loading">
      <template #header>
        <div class="card-header">
          <span>充值数据分析</span>
          <el-button type="primary" @click="fetchAnalysisData">刷新</el-button>
        </div>
      </template>

      <el-row :gutter="20" class="stats-row">
        <el-col :span="6">
          <el-statistic title="总充值金额" :value="stats.total_amount" :precision="2" prefix="¥" />
        </el-col>
        <el-col :span="6">
          <el-statistic title="总订单数" :value="stats.order_count" />
        </el-col>
        <el-col :span="6">
          <el-statistic title="VIP订单" :value="stats.vip_count" />
        </el-col>
        <el-col :span="6">
          <el-statistic title="充值订单" :value="stats.recharge_count" />
        </el-col>
      </el-row>

      <el-divider />

      <div ref="chartRef" style="height: 400px"></div>
    </el-card>
  </div>
</template>

<style scoped>
.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.stats-row {
  margin-bottom: 20px;
}
</style>
