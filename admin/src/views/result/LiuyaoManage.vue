<template>
  <div class="app-container">
    <el-card class="search-form" shadow="never">
      <el-form :model="queryForm" inline>
        <el-form-item label="用户ID">
          <el-input v-model="queryForm.user_id" placeholder="请输入用户ID" clearable />
        </el-form-item>
        <el-form-item label="关键词">
          <el-input v-model="queryForm.keyword" placeholder="搜索问题或结果" clearable />
        </el-form-item>
        <el-form-item label="测算时间">
          <el-date-picker
            v-model="queryForm.dateRange"
            type="daterange"
            range-separator="至"
            start-placeholder="开始日期"
            end-placeholder="结束日期"
            value-format="YYYY-MM-DD"
          />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">搜索</el-button>
          <el-button @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <div class="table-operations mb-4">
      <el-space>
        <el-button
          type="danger"
          :disabled="!selectedItems.length"
          @click="handleBatchDelete"
        >
          <el-icon><Delete /></el-icon>批量删除
        </el-button>
        <el-button @click="handleRefreshStats">
          <el-icon><Refresh /></el-icon>刷新统计
        </el-button>
      </el-space>
    </div>

    <!-- 统计卡片 -->
    <el-row :gutter="20" class="stats-row mb-4">
      <el-col :span="6">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value gold-text">{{ stats.total || 0 }}</div>
            <div class="stat-label">总测算次数</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value gold-text">{{ stats.today || 0 }}</div>
            <div class="stat-label">今日测算</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value gold-text">{{ stats.this_month || 0 }}</div>
            <div class="stat-label">本月测算</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value gold-text">{{ stats.this_year || 0 }}</div>
            <div class="stat-label">本年测算</div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <!-- 图表统计区域 -->
    <el-row :gutter="20" class="mb-4">
      <el-col :span="16">
        <el-card shadow="never">
          <template #header>
            <div class="card-header">
              <span class="card-title">测算趋势</span>
              <el-radio-group v-model="chartPeriod" size="small" @change="loadTrendData">
                <el-radio-button value="7d">近7天</el-radio-button>
                <el-radio-button value="30d">近30天</el-radio-button>
                <el-radio-button value="90d">近3个月</el-radio-button>
                <el-radio-button value="1y">近1年</el-radio-button>
              </el-radio-group>
            </div>
          </template>
          <div v-loading="chartsLoading.dateTrend" style="height: 300px">
            <v-chart v-if="dateTrendOption" :option="dateTrendOption" autoresize />
          </div>
        </el-card>
      </el-col>
      <el-col :span="8">
        <el-card shadow="never">
          <template #header>
            <span class="card-title">起卦方式分布</span>
          </template>
          <div v-loading="chartsLoading.methodDistribution" style="height: 300px">
            <v-chart v-if="methodDistributionOption" :option="methodDistributionOption" autoresize />
          </div>
        </el-card>
      </el-col>
    </el-row>

    <el-row :gutter="20" class="mb-4">
      <el-col :span="12">
        <el-card shadow="never">
          <template #header>
            <span class="card-title">AI分析使用情况</span>
          </template>
          <div v-loading="chartsLoading.aiUsage" style="height: 300px">
            <v-chart v-if="aiUsageOption" :option="aiUsageOption" autoresize />
          </div>
        </el-card>
      </el-col>
      <el-col :span="12">
        <el-card shadow="never">
          <template #header>
            <span class="card-title">时段分布</span>
          </template>
          <div v-loading="chartsLoading.timeDistribution" style="height: 300px">
            <v-chart v-if="timeDistributionOption" :option="timeDistributionOption" autoresize />
          </div>
        </el-card>
      </el-col>
    </el-row>

    <el-card shadow="never">
      <div v-if="pageError" class="page-state">
        <el-result icon="warning" :title="pageError.title" :sub-title="pageError.description">
          <template #extra>
            <el-button type="primary" :loading="loading" @click="loadList">重新加载</el-button>
          </template>
        </el-result>
      </div>

      <template v-else>
        <el-table
          v-loading="loading"
          :data="dataList"
          stripe
          empty-text="暂无测算数据"
          @selection-change="handleSelectionChange"
        >
          <el-table-column type="selection" width="55" />
          <el-table-column type="index" label="#" width="50" />
          <el-table-column prop="id" label="ID" width="80" />
          <el-table-column label="用户信息" min-width="100">
            <template #default="{ row }">
              <div class="user-info">
                <div class="user-name">{{ row.user_id }}</div>
              </div>
            </template>
          </el-table-column>
          <el-table-column prop="question" label="占问事项" min-width="200" show-overflow-tooltip />
          <el-table-column prop="hexagram" label="卦象" width="100" />
          <el-table-column prop="created_at" label="测算时间" width="160" />
          <el-table-column label="操作" width="150" fixed="right">
            <template #default="{ row }">
              <el-button link type="primary" @click="handleView(row)">查看详情</el-button>
              <el-button link type="danger" @click="handleDelete(row)">删除</el-button>
            </template>
          </el-table-column>
        </el-table>

        <div class="pagination-container">
          <el-pagination
            v-model:current-page="queryForm.page"
            v-model:page-size="queryForm.pageSize"
            :total="total"
            :page-sizes="[10, 20, 50, 100]"
            layout="total, sizes, prev, pager, next, jumper"
            @size-change="handleSizeChange"
            @current-change="handleCurrentChange"
          />
        </div>
      </template>
    </el-card>

    <el-dialog v-model="detailDialog.visible" title="六爻测算详情" width="800px" destroy-on-close>
      <el-descriptions v-if="detailDialog.data" :column="1" border>
        <el-descriptions-item label="ID">{{ detailDialog.data.id }}</el-descriptions-item>
        <el-descriptions-item label="用户ID">{{ detailDialog.data.user_id }}</el-descriptions-item>
        <el-descriptions-item label="占问事项">{{ detailDialog.data.question }}</el-descriptions-item>
        <el-descriptions-item label="卦象">{{ detailDialog.data.hexagram }}</el-descriptions-item>
        <el-descriptions-item label="卦名">{{ detailDialog.data.hexagram_name }}</el-descriptions-item>
        <el-descriptions-item label="爻辞">
          <div class="result-content">{{ detailDialog.data.yao_text || '暂无' }}</div>
        </el-descriptions-item>
        <el-descriptions-item label="分析结果">
          <div class="result-content">{{ detailDialog.data.result || '暂无' }}</div>
        </el-descriptions-item>
        <el-descriptions-item label="测算时间">{{ detailDialog.data.created_at }}</el-descriptions-item>
      </el-descriptions>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Delete, Refresh } from '@element-plus/icons-vue'
import request from '@/api/request'
import { use } from 'echarts/core'
import { CanvasRenderer } from 'echarts/renderers'
import { LineChart, PieChart, BarChart } from 'echarts/charts'
import { TitleComponent, TooltipComponent, LegendComponent, GridComponent } from 'echarts/components'
import VChart from 'vue-echarts'

use([CanvasRenderer, LineChart, PieChart, BarChart, TitleComponent, TooltipComponent, LegendComponent, GridComponent])

const loading = ref(false)
const dataList = ref([])
const total = ref(0)
const selectedItems = ref([])
const pageError = ref(null)

const queryForm = reactive({
  page: 1,
  pageSize: 20,
  user_id: '',
  keyword: '',
  dateRange: []
})

const stats = reactive({
  total: 0,
  today: 0,
  this_month: 0,
  this_year: 0
})

const detailDialog = reactive({
  visible: false,
  data: null
})

const API_BASE = '/liuyao-manage'

const loadList = async () => {
  loading.value = true
  pageError.value = null

  try {
    const params = {
      ...queryForm,
      start_date: queryForm.dateRange?.[0] || '',
      end_date: queryForm.dateRange?.[1] || ''
    }
    delete params.dateRange

    const res = await request.get(API_BASE, { params })
    dataList.value = res.data.list
    total.value = res.data.total
  } catch (error) {
    pageError.value = {
      title: '加载失败',
      description: error.message || '获取数据失败'
    }
  } finally {
    loading.value = false
  }
}

const loadStats = async () => {
  try {
    const res = await request.get(`${API_BASE}/stats`)
    Object.assign(stats, res.data)
  } catch (error) {
    console.error('加载统计数据失败:', error)
  }
}

const handleSearch = () => {
  queryForm.page = 1
  loadList()
}

const handleReset = () => {
  Object.assign(queryForm, {
    page: 1,
    pageSize: 20,
    user_id: '',
    keyword: '',
    dateRange: []
  })
  loadList()
}

const handleSelectionChange = (selection) => {
  selectedItems.value = selection
}

const handleView = (row) => {
  detailDialog.data = row
  detailDialog.visible = true
}

const handleDelete = async (row) => {
  try {
    await ElMessageBox.confirm('确认删除此测算结果吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    })

    await request.delete(`${API_BASE}/${row.id}`)
    ElMessage.success('删除成功')
    loadList()
    loadStats()
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('删除失败')
    }
  }
}

const handleBatchDelete = async () => {
  try {
    await ElMessageBox.confirm(`确认删除选中的 ${selectedItems.value.length} 条测算结果吗？`, '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    })

    const ids = selectedItems.value.map(item => item.id)
    await request.post(`${API_BASE}/batch-delete`, { ids })
    ElMessage.success('批量删除成功')
    loadList()
    loadStats()
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('批量删除失败')
    }
  }
}

const handleRefreshStats = () => {
  loadStats()
  loadTrendData()
}

const handleSizeChange = () => {
  loadList()
}

const handleCurrentChange = () => {
  loadList()
}

// 图表相关状态
const chartPeriod = ref('30d')
const chartsLoading = reactive({
  dateTrend: false,
  methodDistribution: false,
  aiUsage: false,
  timeDistribution: false
})

const dateTrendOption = ref(null)
const methodDistributionOption = ref(null)
const aiUsageOption = ref(null)
const timeDistributionOption = ref(null)

const loadTrendData = async () => {
  chartsLoading.dateTrend = true
  chartsLoading.methodDistribution = true
  chartsLoading.aiUsage = true
  chartsLoading.timeDistribution = true

  try {
    const res = await request.get(`${API_BASE}/trend`, { params: { period: chartPeriod.value } })
    const data = res.data
    if (data) {

      // 日期趋势图
      dateTrendOption.value = {
        tooltip: { trigger: 'axis' },
        xAxis: {
          type: 'category',
          data: data.date_trend.map(item => item.date)
        },
        yAxis: { type: 'value' },
        series: [{
          name: '测算次数',
          type: 'line',
          data: data.date_trend.map(item => item.count),
          smooth: true,
          itemStyle: { color: '#D4AF37' },
          areaStyle: { opacity: 0.1 }
        }]
      }

      // 起卦方式分布图
      methodDistributionOption.value = {
        tooltip: { trigger: 'item' },
        legend: { orient: 'vertical', right: 10, top: 'center' },
        series: [{
          type: 'pie',
          radius: ['40%', '70%'],
          data: data.method_distribution,
          emphasis: { itemStyle: { shadowBlur: 10, shadowOffsetX: 0, shadowColor: 'rgba(0, 0, 0, 0.5)' } }
        }]
      }

      // AI使用情况
      aiUsageOption.value = {
        tooltip: { trigger: 'item' },
        legend: { bottom: 10 },
        series: [{
          type: 'pie',
          radius: '60%',
          data: data.ai_usage,
          itemStyle: {
            color: (params) => params.name === '基础分析' ? '#909399' : '#D4AF37'
          }
        }]
      }

      // 时段分布
      const timeSlots = ['凌晨(0-6点)', '上午(6-12点)', '下午(12-18点)', '晚上(18-24点)']
      const timeData = timeSlots.map(slot => {
        const found = data.time_distribution.find(item => item.time_slot === slot)
        return found ? found.count : 0
      })
      timeDistributionOption.value = {
        tooltip: { trigger: 'axis', axisPointer: { type: 'shadow' } },
        xAxis: { type: 'category', data: timeSlots },
        yAxis: { type: 'value' },
        series: [{
          type: 'bar',
          data: timeData,
          itemStyle: { color: '#D4AF37' }
        }]
      }
    }
  } catch (error) {
    console.error('加载图表数据失败:', error)
  } finally {
    chartsLoading.dateTrend = false
    chartsLoading.methodDistribution = false
    chartsLoading.aiUsage = false
    chartsLoading.timeDistribution = false
  }
}

onMounted(() => {
  loadList()
  loadStats()
  loadTrendData()
})
</script>

<style scoped>
.app-container {
  padding: 20px;
  background: #fff;
}

.gold-text {
  color: #D4AF37;
  font-weight: 600;
}

.search-form {
  margin-bottom: 20px;
}

.table-operations {
  margin-bottom: 20px;
}

.stats-row {
  margin-bottom: 20px;
}

.stat-item {
  text-align: center;
}

.stat-value {
  font-size: 32px;
  font-weight: bold;
  margin-bottom: 8px;
}

.stat-label {
  color: #666;
  font-size: 14px;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 10px;
}

.user-name {
  font-weight: 500;
}

.pagination-container {
  margin-top: 20px;
  text-align: right;
}

.result-content {
  max-height: 300px;
  overflow-y: auto;
  white-space: pre-wrap;
  line-height: 1.6;
}

.mb-4 {
  margin-bottom: 16px;
}

.page-state {
  padding: 40px 0;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-title {
  font-weight: 600;
  font-size: 16px;
  color: #333;
}
</style>
