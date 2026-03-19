<template>
  <div class="dashboard-admin">
    <div class="dash-header">
      <h2>📊 运营仪表板</h2>
      <div class="header-actions">
        <el-text type="info" size="small">最后更新：{{ lastRefresh }}</el-text>
        <el-button size="small" :icon="Refresh" :loading="loading" @click="loadAll">刷新</el-button>
      </div>
    </div>

    <!-- 核心统计卡片 -->
    <el-row :gutter="16" class="stats-row" v-loading="loading">
      <el-col :xs="12" :sm="6" v-for="stat in statsCards" :key="stat.key">
        <div class="stat-card" :class="`stat-card--${stat.color}`">
          <div class="stat-icon">{{ stat.icon }}</div>
          <div class="stat-info">
            <div class="stat-value">{{ formatNumber(stat.value) }}</div>
            <div class="stat-label">{{ stat.label }}</div>
            <div class="stat-sub" v-if="stat.sub">
              <span :class="stat.trend > 0 ? 'up' : 'down'">
                {{ stat.trend > 0 ? '↑' : '↓' }} {{ Math.abs(stat.trend) }}%
              </span>
              较昨日
            </div>
          </div>
        </div>
      </el-col>
    </el-row>

    <!-- 趋势图 + 实时数据 -->
    <el-row :gutter="16" class="chart-row">
      <el-col :xs="24" :sm="16">
        <el-card class="chart-card">
          <template #header>
            <div class="card-head">
              <span>📈 7日用户与收入趋势</span>
              <el-radio-group v-model="trendType" size="small" @change="loadTrend">
                <el-radio-button value="user">用户</el-radio-button>
                <el-radio-button value="income">收入</el-radio-button>
                <el-radio-button value="usage">使用量</el-radio-button>
              </el-radio-group>
            </div>
          </template>
          <div class="chart-placeholder" v-if="trendData.length === 0">
            <el-empty description="暂无趋势数据" :image-size="80" />
          </div>
          <div v-else class="simple-chart">
            <div class="chart-bars">
              <div
                v-for="(item, i) in trendData"
                :key="i"
                class="bar-item"
                :title="`${item.date}: ${item.value}`"
              >
                <div
                  class="bar"
                  :style="{ height: `${(item.value / maxTrend) * 100}%` }"
                ></div>
                <div class="bar-label">{{ item.date?.slice(-5) }}</div>
              </div>
            </div>
          </div>
        </el-card>
      </el-col>

      <el-col :xs="24" :sm="8">
        <el-card class="realtime-card">
          <template #header>
            <div class="card-head">
              <span>⚡ 实时数据</span>
              <el-tag type="success" size="small">在线</el-tag>
            </div>
          </template>
          <div v-loading="realtimeLoading">
            <div class="realtime-item" v-for="item in realtimeItems" :key="item.key">
              <span class="rt-label">{{ item.label }}</span>
              <span class="rt-value" :class="`rt-${item.color}`">{{ item.value }}</span>
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <!-- 待处理事项 + 功能使用分布 -->
    <el-row :gutter="16" class="bottom-row">
      <el-col :xs="24" :sm="12">
        <el-card>
          <template #header><span>⚠️ 待处理事项</span></template>
          <div v-loading="pendingLoading">
            <el-empty v-if="pendingItems.length === 0" description="暂无待处理" :image-size="60" />
            <div v-else>
              <div class="pending-item" v-for="item in pendingItems" :key="item.type">
                <div class="pending-info">
                  <el-tag :type="item.urgency === 'high' ? 'danger' : 'warning'" size="small">
                    {{ item.urgency === 'high' ? '紧急' : '普通' }}
                  </el-tag>
                  <span class="pending-title">{{ item.title }}</span>
                </div>
                <span class="pending-count">{{ item.count }} 条</span>
              </div>
            </div>
          </div>
        </el-card>
      </el-col>

      <el-col :xs="24" :sm="12">
        <el-card>
          <template #header><span>🔮 功能使用分布（今日）</span></template>
          <div v-loading="loading">
            <div class="usage-item" v-for="item in usageDistribution" :key="item.feature">
              <span class="usage-name">{{ item.name }}</span>
              <el-progress
                :percentage="item.pct"
                :color="item.color"
                :stroke-width="10"
                style="flex:1; margin:0 12px"
              />
              <span class="usage-count">{{ item.count }}</span>
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { ElMessage } from 'element-plus'
import { Refresh } from '@element-plus/icons-vue'
import { getDashboardStats, getDashboardTrend, getDashboardRealtime, getPendingFeedback } from '@/api/admin'

const loading = ref(false)
const realtimeLoading = ref(false)
const pendingLoading = ref(false)
const lastRefresh = ref('')
const trendType = ref('user')

const stats = ref({})
const trendData = ref([])
const realtimeData = ref({})
const pendingData = ref({})

const statsCards = computed(() => [
  { key: 'total_users', label: '总用户数', value: stats.value.total_users || 0, icon: '👥', color: 'blue', trend: stats.value.user_trend || 0, sub: true },
  { key: 'today_new_users', label: '今日新增', value: stats.value.today_new_users || 0, icon: '🆕', color: 'green', trend: stats.value.new_user_trend || 0, sub: true },
  { key: 'today_revenue', label: '今日收入', value: `¥${(stats.value.today_revenue || 0).toFixed(2)}`, icon: '💰', color: 'gold', trend: stats.value.revenue_trend || 0, sub: true },
  { key: 'today_usage', label: '今日使用次数', value: stats.value.today_usage || 0, icon: '🔮', color: 'purple', trend: stats.value.usage_trend || 0, sub: true },
])

const maxTrend = computed(() => Math.max(...trendData.value.map(d => d.value || 0), 1))

const realtimeItems = computed(() => [
  { key: 'online', label: '当前在线', value: realtimeData.value.online_users || 0, color: 'green' },
  { key: 'today_req', label: '今日请求数', value: formatNumber(realtimeData.value.today_requests || 0), color: 'blue' },
  { key: 'ai_calls', label: 'AI调用次数', value: formatNumber(realtimeData.value.ai_calls || 0), color: 'purple' },
  { key: 'error_rate', label: '错误率', value: `${(realtimeData.value.error_rate || 0).toFixed(2)}%`, color: (realtimeData.value.error_rate || 0) > 1 ? 'red' : 'green' },
  { key: 'avg_resp', label: '平均响应时间', value: `${realtimeData.value.avg_response_ms || 0}ms`, color: (realtimeData.value.avg_response_ms || 0) > 500 ? 'orange' : 'green' },
  { key: 'pending_orders', label: '待支付订单', value: realtimeData.value.pending_orders || 0, color: 'orange' },
])

const pendingItems = computed(() => {
  const items = []
  const p = pendingData.value
  if (p.unread_feedback > 0) items.push({ type: 'feedback', title: '未处理用户反馈', count: p.unread_feedback, urgency: 'high' })
  if (p.pending_orders > 0) items.push({ type: 'orders', title: '待确认订单', count: p.pending_orders, urgency: 'normal' })
  if (p.risk_events > 0) items.push({ type: 'risk', title: '风险事件待审核', count: p.risk_events, urgency: 'high' })
  if (p.low_points_users > 0) items.push({ type: 'points', title: '积分即将耗尽用户', count: p.low_points_users, urgency: 'normal' })
  return items
})

const usageDistribution = computed(() => {
  const dist = stats.value.usage_distribution || {}
  const colors = { bazi: '#e6a23c', tarot: '#9b59b6', liuyao: '#3498db', hehun: '#e74c3c', daily: '#27ae60', qiming: '#f39c12' }
  const names = { bazi: '八字排盘', tarot: '塔罗占卜', liuyao: '六爻占卜', hehun: '合婚分析', daily: '每日运势', qiming: '取名建议' }
  const total = Object.values(dist).reduce((a, b) => a + b, 0) || 1
  return Object.entries(dist).map(([k, v]) => ({
    feature: k,
    name: names[k] || k,
    count: v,
    pct: Math.round((v / total) * 100),
    color: colors[k] || '#409eff',
  })).sort((a, b) => b.count - a.count)
})

const formatNumber = (n) => {
  if (typeof n !== 'number') return n
  return n >= 10000 ? (n / 10000).toFixed(1) + 'w' : n.toLocaleString()
}

const loadStats = async () => {
  loading.value = true
  try {
    const res = await getDashboardStats()
    if (res.code === 200) {
      const d = res.data || {}
      // 后台返回嵌套结构，做扁平化适配
      const s = d.statistics || {}
      const ov = d.overview || {}
      const us = d.user_stats || {}
      const os = d.order_stats || {}
      const ds = d.divination_stats || {}
      stats.value = {
        total_users: s.total_users || us.total || 0,
        today_new_users: s.today_users || us.today_new || 0,
        today_revenue: ov.today_revenue?.amount || os.today_revenue || 0,
        today_usage: (ds.today?.bazi || 0) + (ds.today?.tarot || 0) + (ds.today?.liuyao || 0),
        user_trend: us.trend_pct || 0,
        new_user_trend: us.today_trend_pct || 0,
        revenue_trend: os.revenue_trend || 0,
        usage_trend: ds.trend_pct || 0,
        usage_distribution: {
          bazi: ds.today?.bazi || 0,
          tarot: ds.today?.tarot || 0,
          liuyao: ds.today?.liuyao || 0,
          hehun: ds.today?.hehun || 0,
          daily: ds.today?.daily || 0,
        },
      }
    }
  } catch { /* 静默处理 */ } finally {
    loading.value = false
  }
}

const loadTrend = async () => {
  try {
    const res = await getDashboardTrend({ type: trendType.value, days: 7 })
    if (res.code === 200) {
      const d = res.data || {}
      // 后台返回 user_trend/bazi_trend 等数组
      const keyMap = { user: 'user_trend', income: 'order_trend', usage: 'bazi_trend' }
      const raw = d[keyMap[trendType.value]] || d.list || []
      trendData.value = raw.map(item => ({
        date: item.date || item.stat_date,
        value: item.value || item.new_users || item.count || item.amount || 0,
      }))
    }
  } catch {}
}

const loadRealtime = async () => {
  realtimeLoading.value = true
  try {
    const res = await getDashboardRealtime()
    if (res.code === 200) realtimeData.value = res.data || {}
  } catch {} finally {
    realtimeLoading.value = false
  }
}

const loadPending = async () => {
  pendingLoading.value = true
  try {
    const res = await getPendingFeedback()
    if (res.code === 200) pendingData.value = res.data || {}
  } catch {} finally {
    pendingLoading.value = false
  }
}

const loadAll = async () => {
  await Promise.all([loadStats(), loadTrend(), loadRealtime(), loadPending()])
  lastRefresh.value = new Date().toLocaleTimeString()
}

// 定时刷新实时数据（60秒）
let timer = null
onMounted(() => {
  loadAll()
  timer = setInterval(loadRealtime, 60000)
})
onUnmounted(() => clearInterval(timer))
</script>

<style scoped>
.dashboard-admin { padding: 20px; }
.dash-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }
.dash-header h2 { margin:0; font-size:20px; }
.header-actions { display:flex; align-items:center; gap:10px; }

.stats-row { margin-bottom:16px; }
.stat-card {
  background: #fff; border-radius: 12px; padding: 20px; margin-bottom:16px;
  display:flex; align-items:center; gap:16px;
  box-shadow: 0 2px 8px rgba(0,0,0,.06);
  border-left: 4px solid #409eff;
}
.stat-card--blue { border-left-color:#409eff; }
.stat-card--green { border-left-color:#67c23a; }
.stat-card--gold { border-left-color:#e6a23c; }
.stat-card--purple { border-left-color:#9b59b6; }
.stat-icon { font-size:32px; }
.stat-value { font-size:22px; font-weight:700; color:#303133; }
.stat-label { font-size:13px; color:#909399; margin-top:2px; }
.stat-sub { font-size:12px; color:#909399; margin-top:4px; }
.up { color:#67c23a; } .down { color:#f56c6c; }

.chart-row, .bottom-row { margin-bottom:16px; }
.card-head { display:flex; justify-content:space-between; align-items:center; }
.chart-card .chart-placeholder { height:200px; display:flex; align-items:center; justify-content:center; }
.simple-chart { height:200px; display:flex; align-items:flex-end; }
.chart-bars { display:flex; align-items:flex-end; gap:8px; width:100%; height:100%; padding:0 8px; }
.bar-item { flex:1; display:flex; flex-direction:column; align-items:center; height:100%; }
.bar { width:100%; background:linear-gradient(180deg,#409eff,#79bbff); border-radius:4px 4px 0 0; min-height:4px; transition:height .3s; }
.bar-label { font-size:11px; color:#909399; margin-top:4px; }

.realtime-item { display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid #f0f0f0; }
.realtime-item:last-child { border-bottom:none; }
.rt-label { color:#606266; font-size:14px; }
.rt-value { font-weight:600; }
.rt-green { color:#67c23a; } .rt-blue { color:#409eff; } .rt-purple { color:#9b59b6; }
.rt-red { color:#f56c6c; } .rt-orange { color:#e6a23c; }

.pending-item { display:flex; justify-content:space-between; align-items:center; padding:10px 0; border-bottom:1px solid #f0f0f0; }
.pending-item:last-child { border-bottom:none; }
.pending-info { display:flex; align-items:center; gap:8px; }
.pending-title { font-size:14px; color:#303133; }
.pending-count { font-weight:700; color:#e6a23c; }

.usage-item { display:flex; align-items:center; margin-bottom:12px; }
.usage-name { width:80px; font-size:13px; color:#606266; flex-shrink:0; }
.usage-count { width:50px; text-align:right; font-size:13px; font-weight:600; color:#303133; }
</style>
