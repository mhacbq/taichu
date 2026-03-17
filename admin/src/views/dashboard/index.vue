<template>
  <div class="app-container">
    <el-card shadow="never" class="dashboard-header-card">
      <div class="dashboard-header">
        <div>
          <div class="page-title">运营看板</div>
          <div class="page-subtitle">集中查看核心经营指标、实时动态，并快速跳转到高频运营模块。</div>
          <div class="page-meta">
            最近更新时间：{{ lastUpdatedText }}
            <span class="meta-divider">|</span>
            近 15 分钟活跃用户：{{ onlineUsersText }}
          </div>
        </div>
        <div class="header-actions">
          <el-button :loading="refreshing" @click="handleRefresh">
            <el-icon><Refresh /></el-icon>
            刷新看板
          </el-button>
          <el-button type="primary" :loading="exporting" :disabled="readonlyMode" @click="handleExport">
            <el-icon><Download /></el-icon>
            导出实时快照
          </el-button>
        </div>
      </div>
    </el-card>

    <el-card v-if="dashboardError" shadow="never" class="dashboard-error-card">
      <el-result icon="warning" :title="dashboardError.title" :sub-title="dashboardError.description">
        <template #extra>
          <el-button type="primary" :loading="refreshing" @click="handleRefresh">重新加载</el-button>
        </template>
      </el-result>
    </el-card>

    <template v-else>
      <el-card shadow="never" class="quick-actions-card">
        <template #header>
          <div class="card-header">
            <div>
              <div class="section-title">运营快捷操作</div>
              <div class="section-subtitle">黄历、知识库、订单、公告、SEO、系统配置等高频入口一步直达。</div>
            </div>
            <el-tag type="warning" effect="plain">待处理反馈 {{ pendingFeedbackCount }}</el-tag>
          </div>
        </template>
        <div class="quick-actions-grid">
          <button
            v-for="item in quickActions"
            :key="item.title"
            type="button"
            class="quick-action-item"
            @click="goTo(item.path)"
          >
            <div class="quick-action-title">{{ item.title }}</div>
            <div class="quick-action-desc">{{ item.description }}</div>
          </button>
        </div>
      </el-card>

      <el-row :gutter="20" class="section-row">
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
                <div class="stat-change" :class="item.trendType">
                  {{ formatTrendText(item) }}
                </div>
              </div>
            </div>
          </el-card>
        </el-col>
      </el-row>

      <el-row :gutter="20" class="section-row">
        <el-col :xs="24" :lg="16">
          <el-card>
            <template #header>
              <span>用户增长趋势（近 7 天）</span>
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

      <el-row :gutter="20" class="section-row">
        <el-col :xs="24" :lg="12">
          <el-card>
            <template #header>
              <div class="card-header simple-header">
                <div>
                  <div class="section-title">实时数据</div>
                  <div class="section-subtitle">按时间倒序展示最近关键运营事件。</div>
                </div>
                <el-tag type="info" effect="plain">最近 {{ realtimeData.length }} 条</el-tag>
              </div>
            </template>
            <div v-if="realtimeData.length" class="realtime-list">
              <div v-for="(item, index) in realtimeData" :key="index" class="realtime-item">
                <span class="time">{{ item.time }}</span>
                <span class="action">{{ item.action }}</span>
                <span class="user">{{ item.user }}</span>
              </div>
            </div>
            <el-empty v-else description="暂无实时动态" :image-size="96" />
          </el-card>
        </el-col>
        <el-col :xs="24" :lg="12">
          <el-card>
            <template #header>
              <div class="card-header simple-header">
                <div>
                  <div class="section-title">待处理反馈</div>
                  <div class="section-subtitle">优先处理用户最新提交的问题与建议。</div>
                </div>
                <el-button text type="primary" @click="goTo('/feedback/list')">查看更多</el-button>
              </div>
            </template>
            <el-table v-if="pendingFeedback.length" :data="pendingFeedback" stripe>
              <el-table-column prop="displayContent" label="反馈内容" show-overflow-tooltip />
              <el-table-column prop="typeLabel" label="类型" width="110">
                <template #default="{ row }">
                  <el-tag size="small">{{ row.typeLabel }}</el-tag>
                </template>
              </el-table-column>
              <el-table-column prop="time" label="时间" width="170" />
              <el-table-column label="操作" width="100">
                <template #default="{ row }">
                  <el-button link type="primary" @click="handleFeedback(row)">处理</el-button>
                </template>
              </el-table-column>
            </el-table>
            <el-empty v-else description="当前没有待处理反馈" :image-size="96" />
          </el-card>
        </el-col>
      </el-row>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import * as echarts from 'echarts'
import { Download, Refresh } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'
import {
  exportRealtimeDashboard,
  getChartData,
  getPendingFeedback,
  getRealtimeData,
  getStatistics,
  getTrendData,
  refreshDashboardStats
} from '@/api/dashboard'
import { useUserStore } from '@/stores/user'
import { createReadonlyErrorState } from '@/utils/page-error'
import { hasRoutePermission, normalizeAdminRoles } from '@/utils/admin-permission'

const router = useRouter()
const userStore = useUserStore()

const refreshing = ref(false)
const exporting = ref(false)
const dashboardError = ref(null)
const statistics = ref(createInitialStatistics())
const realtimeData = ref([])
const pendingFeedback = ref([])
const pendingFeedbackCount = ref(0)
const realtimeMeta = ref(createInitialRealtimeMeta())

const userChart = ref(null)
const featureChart = ref(null)
let userChartInstance = null
let featureChartInstance = null

const quickActionDefinitions = [
  {
    title: '黄历管理',
    description: '维护每日黄历、宜忌与生成结果。',
    path: '/content/almanac',
    roles: ['admin', 'operator']
  },
  {
    title: '知识库文章',
    description: '发布命理文章并维护文章分类树。',
    path: '/site/knowledge',
    roles: ['admin', 'operator']
  },
  {
    title: '充值订单',
    description: '查看充值订单、补单与退款处理。',
    path: '/payment/orders',
    roles: ['admin', 'operator']
  },
  {
    title: 'VIP订单',
    description: '集中处理会员订单与退款申请。',
    path: '/payment/vip-orders',
    roles: ['admin', 'operator']
  },
  {
    title: '系统公告',
    description: '发布公告、维护草稿与上线状态。',
    path: '/system/notice',
    roles: ['admin', 'operator']
  },
  {
    title: '反馈列表',
    description: '查看最新反馈并快速进入处理页。',
    path: '/feedback/list',
    roles: ['admin', 'operator']
  },
  {
    title: 'SEO管理',
    description: '更新页面 SEO、Robots 与收录提交。',
    path: '/site/seo',
    roles: ['admin']
  },
  {
    title: '系统设置',
    description: '调整站点配置并即时同步到前台。',
    path: '/system/settings',
    roles: ['admin']
  }
]

const currentRoles = computed(() => normalizeAdminRoles(
  userStore.roles || userStore.userInfo?.roles || userStore.userInfo?.role || []
))
const readonlyMode = computed(() => Boolean(dashboardError.value))
const quickActions = computed(() => quickActionDefinitions.filter(item => hasRoutePermission(currentRoles.value, item.roles)))
const lastUpdatedText = computed(() => (readonlyMode.value ? '加载失败' : (realtimeMeta.value.timestamp || '尚未加载')))
const onlineUsersText = computed(() => (readonlyMode.value ? '--' : realtimeMeta.value.onlineUsers))

onMounted(() => {
  initCharts()
  window.addEventListener('resize', handleResize)
  reloadDashboard()
})

onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
  userChartInstance?.dispose()
  featureChartInstance?.dispose()
})

function createInitialStatistics() {
  return [
    { title: '总用户数', value: 0, trend: 0, trendType: 'flat', color: '#409eff', icon: 'UserFilled' },
    { title: '今日新增', value: 0, trend: 0, trendType: 'flat', color: '#67c23a', icon: 'User' },
    { title: '八字排盘', value: 0, trend: 0, trendType: 'flat', color: '#e6a23c', icon: 'Calendar' },
    { title: '塔罗占卜', value: 0, trend: 0, trendType: 'flat', color: '#f56c6c', icon: 'MagicStick' }
  ]
}

function createInitialRealtimeMeta() {
  return {
    timestamp: '',
    onlineUsers: 0,
    pendingFeedback: 0
  }
}

function resetDashboardData() {
  statistics.value = createInitialStatistics()
  realtimeData.value = []
  pendingFeedback.value = []
  pendingFeedbackCount.value = 0
  realtimeMeta.value = createInitialRealtimeMeta()
  updateUserChart({ dates: [], newUsers: [], baziTrend: [], tarotTrend: [] })
  updateFeatureChart([])
}

function handleResize() {
  userChartInstance?.resize()
  featureChartInstance?.resize()
}

async function reloadDashboard() {
  try {
    await Promise.all([
      loadStatistics(),
      loadRealtimeData(),
      loadPendingFeedback(),
      loadTrendData(),
      loadChartData()
    ])
    dashboardError.value = null
  } catch (error) {
    resetDashboardData()
    dashboardError.value = createReadonlyErrorState(error, '运营看板', 'stats_view')
    throw error
  }
}

async function loadStatistics() {
  const res = await getStatistics({ showErrorMessage: false })
  const data = res.data || {}
  const stats = data.statistics || {}
  const overview = data.overview || {}
  const newUserChange = normalizeChange(overview.new_users?.change)

  statistics.value = [
    { title: '总用户数', value: Number(stats.total_users || 0), trend: 0, trendType: 'flat', color: '#409eff', icon: 'UserFilled' },
    { title: '今日新增', value: Number(stats.today_users || 0), trend: newUserChange.value, trendType: newUserChange.type, color: '#67c23a', icon: 'User' },
    { title: '八字排盘', value: Number(stats.total_bazi || 0), trend: 0, trendType: 'flat', color: '#e6a23c', icon: 'Calendar' },
    { title: '塔罗占卜', value: Number(stats.total_tarot || 0), trend: 0, trendType: 'flat', color: '#f56c6c', icon: 'MagicStick' }
  ]
}

async function loadRealtimeData() {
  const res = await getRealtimeData(undefined, { showErrorMessage: false })
  const data = res.data || {}

  realtimeData.value = Array.isArray(data.realtime_list) ? data.realtime_list : []
  realtimeMeta.value = {
    timestamp: data.timestamp || new Date().toLocaleString('zh-CN', { hour12: false }),
    onlineUsers: Number(data.online_users || 0),
    pendingFeedback: Number(data.pending_feedback || 0)
  }
}

async function loadPendingFeedback() {
  const res = await getPendingFeedback({ showErrorMessage: false })
  const data = res.data || {}

  pendingFeedbackCount.value = Number(data.count || 0)
  pendingFeedback.value = Array.isArray(data.list)
    ? data.list.map(normalizeFeedbackRow)
    : []
}

async function loadTrendData() {
  const res = await getTrendData({ days: 7 }, { showErrorMessage: false })
  const data = res.data || {}
  const userTrend = Array.isArray(data.user_trend) ? data.user_trend : []
  const baziTrend = Array.isArray(data.bazi_trend) ? data.bazi_trend : []
  const tarotTrend = Array.isArray(data.tarot_trend) ? data.tarot_trend : []

  updateTrendStatistics(baziTrend, tarotTrend)
  updateUserChart({
    dates: userTrend.map(item => item.date),
    newUsers: userTrend.map(item => item.count),
    baziTrend: baziTrend.map(item => item.count),
    tarotTrend: tarotTrend.map(item => item.count)
  })
}

async function loadChartData() {
  const res = await getChartData('feature_usage', undefined, { showErrorMessage: false })
  updateFeatureChart(res.data || [])
}

function normalizeFeedbackRow(row = {}) {
  const title = String(row.title || '').trim()
  const content = String(row.content || '').trim()
  return {
    ...row,
    typeLabel: formatFeedbackType(row.type),
    time: row.created_at || row.time || '-',
    displayContent: title && content ? `${title}：${content}` : title || content || '未填写内容'
  }
}

function formatFeedbackType(type) {
  const typeMap = {
    suggestion: '建议',
    bug: 'Bug',
    complaint: '投诉',
    praise: '表扬',
    other: '其他'
  }
  return typeMap[type] || type || '反馈'
}

function normalizeChange(changePayload) {
  const rawValue = Number(changePayload?.value || 0)
  const typeMap = {
    increase: 'up',
    decrease: 'down',
    flat: 'flat'
  }

  return {
    value: Number(rawValue.toFixed(2)),
    type: typeMap[changePayload?.type] || 'flat'
  }
}

function getSeriesChange(series = []) {
  const today = Number(series.at(-1)?.count || 0)
  const yesterday = Number(series.at(-2)?.count || 0)

  if (yesterday === 0) {
    if (today === 0) {
      return { value: 0, type: 'flat' }
    }
    return { value: 100, type: 'up' }
  }

  const change = ((today - yesterday) / yesterday) * 100
  if (change === 0) {
    return { value: 0, type: 'flat' }
  }

  return {
    value: Number(Math.abs(change).toFixed(2)),
    type: change > 0 ? 'up' : 'down'
  }
}

function updateTrendStatistics(baziTrend, tarotTrend) {
  const baziChange = getSeriesChange(baziTrend)
  const tarotChange = getSeriesChange(tarotTrend)

  statistics.value = statistics.value.map(item => {
    if (item.title === '八字排盘') {
      return { ...item, trend: baziChange.value, trendType: baziChange.type }
    }
    if (item.title === '塔罗占卜') {
      return { ...item, trend: tarotChange.value, trendType: tarotChange.type }
    }
    return item
  })
}

function initCharts() {
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

function formatTrendText(item) {
  if (item.trendType === 'flat') {
    return '与昨日持平'
  }
  return `${item.trendType === 'up' ? '+' : '-'}${item.trend}% 较昨日`
}

function goTo(path) {
  router.push(path)
}

function handleFeedback(row) {
  router.push({
    path: '/feedback/list',
    query: { id: row.id }
  })
}

async function handleRefresh() {
  refreshing.value = true
  try {
    await refreshDashboardStats({}, { showErrorMessage: false })
    await reloadDashboard()
    ElMessage.success('看板数据已刷新')
  } catch (error) {
    ElMessage.error(error.message || '刷新看板失败')
  } finally {
    refreshing.value = false
  }
}

async function handleExport() {
  if (readonlyMode.value) {
    ElMessage.warning('运营看板尚未成功加载，当前为只读保护状态，暂不允许导出默认快照')
    return
  }

  exporting.value = true
  try {
    const response = await exportRealtimeDashboard({ limit: 50 })
    downloadBlob(response.data, parseFileName(response.headers?.['content-disposition']))
    ElMessage.success('实时快照导出成功')
  } catch (error) {
    ElMessage.error(error.message || '导出实时快照失败')
  } finally {
    exporting.value = false
  }
}

function parseFileName(contentDisposition = '') {
  const matched = contentDisposition.match(/filename\*=UTF-8''([^;]+)|filename="?([^";]+)"?/i)
  const rawFileName = matched?.[1] || matched?.[2]
  if (!rawFileName) {
    return `dashboard_realtime_${Date.now()}.csv`
  }
  return decodeURIComponent(rawFileName)
}

function downloadBlob(blob, fileName) {
  const url = window.URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = fileName
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  window.URL.revokeObjectURL(url)
}
</script>

<style lang="scss" scoped>
.app-container {
  padding: 20px;
}

.dashboard-header-card,
.quick-actions-card,
.dashboard-error-card {
  margin-bottom: 20px;
}

.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 20px;
}

.page-title {
  font-size: 22px;
  font-weight: 600;
  color: #303133;
}

.page-subtitle {
  margin-top: 8px;
  color: #606266;
  font-size: 14px;
}

.page-meta {
  margin-top: 10px;
  color: #909399;
  font-size: 13px;
}

.meta-divider {
  margin: 0 8px;
}

.header-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}

.section-row {
  margin-top: 20px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
}

.simple-header {
  align-items: flex-start;
}

.section-title {
  color: #303133;
  font-size: 16px;
  font-weight: 600;
}

.section-subtitle {
  margin-top: 6px;
  color: #909399;
  font-size: 13px;
}

.quick-actions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 14px;
}

.quick-action-item {
  border: 1px solid var(--el-border-color-light);
  border-radius: 12px;
  background: #fff;
  padding: 16px;
  text-align: left;
  cursor: pointer;
  transition: all 0.2s ease;
}

.quick-action-item:hover {
  border-color: var(--el-color-primary);
  box-shadow: 0 8px 20px rgba(64, 158, 255, 0.12);
  transform: translateY(-2px);
}

.quick-action-title {
  color: #303133;
  font-size: 15px;
  font-weight: 600;
}

.quick-action-desc {
  margin-top: 8px;
  color: #909399;
  font-size: 13px;
  line-height: 1.6;
}

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

    &.flat {
      color: #909399;
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

@media (max-width: 768px) {
  .dashboard-header,
  .card-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .header-actions {
    width: 100%;
  }
}
</style>
