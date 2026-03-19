<template>
  <div class="seo-stats-page">
    <div class="page-header">
      <h1 class="page-title">SEO数据统计</h1>
      <p class="page-desc">搜索引擎收录情况、关键词排名、流量分析</p>
    </div>

    <!-- 概览卡片 -->
    <div class="stats-overview">
      <el-card class="stat-card">
        <div class="stat-icon baidu">
          <img src="/icons/baidu.svg" alt="百度" />
        </div>
        <div class="stat-content">
          <div class="stat-number">{{ stats.baidu.indexed }}</div>
          <div class="stat-label">百度收录页面</div>
          <div class="stat-trend" :class="stats.baidu.trend > 0 ? 'up' : 'down'">
            <el-icon v-if="stats.baidu.trend > 0"><ArrowUp /></el-icon>
            <el-icon v-else><ArrowDown /></el-icon>
            {{ Math.abs(stats.baidu.trend) }}% 较上周
          </div>
        </div>
      </el-card>

      <el-card class="stat-card">
        <div class="stat-icon bing">
          <img src="/icons/bing.svg" alt="必应" />
        </div>
        <div class="stat-content">
          <div class="stat-number">{{ stats.bing.indexed }}</div>
          <div class="stat-label">必应收录页面</div>
          <div class="stat-trend" :class="stats.bing.trend > 0 ? 'up' : 'down'">
            <el-icon v-if="stats.bing.trend > 0"><ArrowUp /></el-icon>
            <el-icon v-else><ArrowDown /></el-icon>
            {{ Math.abs(stats.bing.trend) }}% 较上周
          </div>
        </div>
      </el-card>

      <el-card class="stat-card">
        <div class="stat-icon keywords">
          <el-icon><TrendCharts /></el-icon>
        </div>
        <div class="stat-content">
          <div class="stat-number">{{ stats.keywords.total }}</div>
          <div class="stat-label">关键词排名</div>
          <div class="stat-trend up">
            <el-icon><ArrowUp /></el-icon>
            前10页: {{ stats.keywords.top10 }}个
          </div>
        </div>
      </el-card>

      <el-card class="stat-card">
        <div class="stat-icon traffic">
          <el-icon><View /></el-icon>
        </div>
        <div class="stat-content">
          <div class="stat-number">{{ stats.traffic.organic }}</div>
          <div class="stat-label">自然搜索流量</div>
          <div class="stat-trend" :class="stats.traffic.trend > 0 ? 'up' : 'down'">
            <el-icon v-if="stats.traffic.trend > 0"><ArrowUp /></el-icon>
            <el-icon v-else><ArrowDown /></el-icon>
            {{ Math.abs(stats.traffic.trend) }}% 较上月
          </div>
        </div>
      </el-card>
    </div>

    <!-- 关键词排名 -->
    <el-row :gutter="24">
      <el-col :xs="24" :lg="14">
        <el-card class="keyword-card">
          <template #header>
            <div class="card-header">
              <span>核心关键词排名</span>
              <el-radio-group v-model="keywordFilter" size="small">
                <el-radio-button label="all">全部</el-radio-button>
                <el-radio-button label="top10">前10名</el-radio-button>
                <el-radio-button label="top50">前50名</el-radio-button>
              </el-radio-group>
            </div>
          </template>

          <el-table :data="filteredKeywords" stripe>
            <el-table-column type="index" width="50" />
            <el-table-column prop="keyword" label="关键词" min-width="150">
              <template #default="{ row }">
                <div class="keyword-cell">
                  <span class="keyword-text">{{ row.keyword }}</span>
                  <el-tag size="small" :type="getKeywordType(row.category)">
                    {{ row.category }}
                  </el-tag>
                </div>
              </template>
            </el-table-column>
            <el-table-column prop="baiduRank" label="百度排名" width="100" align="center">
              <template #default="{ row }">
                <rank-badge :rank="row.baiduRank" />
              </template>
            </el-table-column>
            <el-table-column prop="bingRank" label="必应排名" width="100" align="center">
              <template #default="{ row }">
                <rank-badge :rank="row.bingRank" />
              </template>
            </el-table-column>
            <el-table-column prop="searchVolume" label="月搜索量" width="100" align="right">
              <template #default="{ row }">
                {{ formatNumber(row.searchVolume) }}
              </template>
            </el-table-column>
            <el-table-column prop="trend" label="趋势" width="80" align="center">
              <template #default="{ row }">
                <el-icon :class="row.trend > 0 ? 'trend-up' : 'trend-down'">
                  <ArrowUp v-if="row.trend > 0" />
                  <ArrowDown v-else />
                </el-icon>
              </template>
            </el-table-column>
          </el-table>
        </el-card>
      </el-col>

      <el-col :xs="24" :lg="10">
        <el-card class="chart-card">
          <template #header>
            <div class="card-header">
              <span>搜索引擎流量占比</span>
            </div>
          </template>
          <div ref="pieChart" class="chart-container"></div>
        </el-card>
      </el-col>
    </el-row>

    <!-- 收录趋势 -->
    <el-card class="trend-card">
      <template #header>
        <div class="card-header">
          <span>收录趋势（近30天）</span>
          <el-radio-group v-model="trendType" size="small">
            <el-radio-button label="indexed">收录量</el-radio-button>
            <el-radio-button label="traffic">流量</el-radio-button>
          </el-radio-group>
        </div>
      </template>
      <div ref="trendChart" class="chart-container-large"></div>
    </el-card>

    <!-- 页面收录详情 -->
    <el-card class="pages-card">
      <template #header>
        <div class="card-header">
          <span>页面收录详情</span>
          <el-input
            v-model="pageSearch"
            placeholder="搜索页面..."
            prefix-icon="Search"
            clearable
            style="width: 200px"
          />
        </div>
      </template>

      <el-table :data="filteredPages" stripe>
        <el-table-column prop="url" label="页面URL" min-width="200">
          <template #default="{ row }">
            <a :href="row.url" target="_blank" class="page-link">
              {{ row.url }}
              <el-icon><Link /></el-icon>
            </a>
          </template>
        </el-table-column>
        <el-table-column prop="title" label="页面标题" min-width="200" />
        <el-table-column prop="baiduStatus" label="百度收录" width="100" align="center">
          <template #default="{ row }">
            <el-tag :type="row.baiduStatus === '已收录' ? 'success' : 'warning'" size="small">
              {{ row.baiduStatus }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="bingStatus" label="必应收录" width="100" align="center">
          <template #default="{ row }">
            <el-tag :type="row.bingStatus === '已收录' ? 'success' : 'warning'" size="small">
              {{ row.bingStatus }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="lastCrawl" label="最后抓取" width="150" align="center" />
        <el-table-column prop="traffic" label="搜索流量" width="100" align="right">
          <template #default="{ row }">
            {{ formatNumber(row.traffic) }}
          </template>
        </el-table-column>
        <el-table-column label="操作" width="120" align="center">
          <template #default="{ row }">
            <el-button type="primary" link @click="refreshPage(row)">
              刷新
            </el-button>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination-wrapper">
        <el-pagination
          v-model:current-page="pageCurrent"
          v-model:page-size="pageSize"
          :total="pageTotal"
          :page-sizes="[10, 20, 50]"
          layout="total, sizes, prev, pager, next"
        />
      </div>
    </el-card>

    <!-- SEO建议 -->
    <el-card class="suggestions-card">
      <template #header>
        <div class="card-header">
          <span>SEO优化建议</span>
          <el-tag :type="suggestions.filter(s => s.priority === 'high').length > 0 ? 'danger' : 'success'">
            {{ suggestions.filter(s => s.priority === 'high').length }} 项紧急
          </el-tag>
        </div>
      </template>

      <div class="suggestion-list">
        <div
          v-for="(item, index) in suggestions"
          :key="index"
          class="suggestion-item"
          :class="item.priority"
        >
          <div class="suggestion-icon">
            <el-icon v-if="item.priority === 'high'"><Warning /></el-icon>
            <el-icon v-else-if="item.priority === 'medium'"><InfoFilled /></el-icon>
            <el-icon v-else><CircleCheck /></el-icon>
          </div>
          <div class="suggestion-content">
            <div class="suggestion-title">{{ item.title }}</div>
            <div class="suggestion-desc">{{ item.description }}</div>
          </div>
          <el-button
            :type="item.priority === 'high' ? 'primary' : 'default'"
            size="small"
            @click="handleSuggestion(item)"
          >
            {{ item.action }}
          </el-button>
        </div>
      </div>
    </el-card>
  </div>
</template>

<script setup>
import { ref, computed, h, onMounted, onUnmounted, watch } from 'vue'
import { ElMessage } from 'element-plus'
// echarts 按需引入，减少打包体积
import { use, init as echartsInit } from 'echarts/core'
import { PieChart, LineChart } from 'echarts/charts'
import { TooltipComponent, LegendComponent, GridComponent } from 'echarts/components'
import { CanvasRenderer } from 'echarts/renderers'
import { graphic } from 'echarts/core'
const { LinearGradient } = graphic
import {
  ArrowUp, ArrowDown, TrendCharts, View, Search, Link,
  Warning, InfoFilled, CircleCheck
} from '@element-plus/icons-vue'
import { getSeoStats } from '@/api/admin'

// 注册 echarts 所需组件
use([PieChart, LineChart, TooltipComponent, LegendComponent, GridComponent, CanvasRenderer])

// 图表实例
let pieChartInstance = null
let trendChartInstance = null

// 图表DOM引用
const pieChart = ref(null)
const trendChart = ref(null)

// 加载状态
const loading = ref(false)

// 统计数据（从API获取）
const stats = ref({
  baidu: { indexed: 0, trend: 0 },
  bing: { indexed: 0, trend: 0 },
  keywords: { total: 0, top10: 0 },
  traffic: { organic: 0, trend: 0 }
})

// 关键词数据（从API获取）
const keywordFilter = ref('all')
const keywords = ref([])

// 页面数据（从API获取）
const pageSearch = ref('')
const pageCurrent = ref(1)
const pageSize = ref(10)
const pageTotal = ref(0)
const pages = ref([])

// 建议数据（从API获取，降级为空数组）
const suggestions = ref([])

// 趋势图原始数据（从API获取）
const trendRawData = ref({ trend: [], dates: [] })

// 趋势图类型
const trendType = ref('indexed')

// ── 数据加载 ────────────────────────────────────────────────────
async function loadStats() {
  loading.value = true
  try {
    const params = {
      page: pageCurrent.value,
      pageSize: pageSize.value,
      keyword: pageSearch.value || undefined
    }
    const res = await getSeoStats(params)
    const data = res?.data ?? res
    if (!data) return

    // 统计卡片
    if (data.stats) stats.value = data.stats

    // 关键词列表
    if (Array.isArray(data.keywords)) keywords.value = data.keywords

    // 页面收录列表
    if (data.pages) {
      pages.value = data.pages.list ?? []
      pageTotal.value = data.pages.total ?? 0
    }

    // 趋势图数据
    if (data.trend) {
      trendRawData.value = data.trend
      updateTrendChart()
    }

    // 优化建议
    if (Array.isArray(data.suggestions)) suggestions.value = data.suggestions

    // 更新饼图（用百度/必应收录量）
    updatePieChart()
  } catch {
    ElMessage.error('获取SEO统计数据失败')
  } finally {
    loading.value = false
  }
}

// ── 计算属性 ─────────────────────────────────────────────────────
const filteredKeywords = computed(() => {
  let result = keywords.value
  if (keywordFilter.value === 'top10') {
    result = result.filter(k => (k.baidu_rank || k.baiduRank) <= 10 || (k.bing_rank || k.bingRank) <= 10)
  } else if (keywordFilter.value === 'top50') {
    result = result.filter(k => (k.baidu_rank || k.baiduRank) <= 50 || (k.bing_rank || k.bingRank) <= 50)
  }
  return result
})

const filteredPages = computed(() => {
  if (!pageSearch.value) return pages.value
  return pages.value.filter(p =>
    p.url?.includes(pageSearch.value) ||
    p.title?.includes(pageSearch.value)
  )
})

// ── 工具方法 ─────────────────────────────────────────────────────
const getKeywordType = (category) => {
  const map = { '核心词': 'danger', '长尾词': 'success', '相关词': 'info', '特色词': 'warning' }
  return map[category] || 'info'
}

const formatNumber = (num) => {
  const n = Number(num) || 0
  if (n >= 10000) return (n / 10000).toFixed(1) + '万'
  return n.toLocaleString()
}

const refreshPage = (row) => {
  ElMessage.success(`已刷新页面 ${row.url} 的收录状态`)
}

const handleSuggestion = (item) => {
  ElMessage.info(`正在处理: ${item.title}`)
}

// 排名徽章组件
const RankBadge = {
  props: ['rank'],
  setup(props) {
    const type = computed(() => {
      const r = Number(props.rank)
      if (r <= 10) return 'success'
      if (r <= 30) return 'warning'
      return 'info'
    })
    return () => h('el-tag', { type: type.value, size: 'small' }, props.rank)
  }
}

// ── 图表方法 ─────────────────────────────────────────────────────
const updatePieChart = () => {
  if (!pieChartInstance) return
  const baiduVal = stats.value.baidu?.indexed ?? 0
  const bingVal = stats.value.bing?.indexed ?? 0
  pieChartInstance.setOption({
    tooltip: { trigger: 'item', formatter: '{b}: {c} ({d}%)' },
    legend: { bottom: '0', left: 'center', textStyle: { color: '#8b8b8b' }, itemWidth: 10, itemHeight: 10 },
    series: [{
      name: '收录分布',
      type: 'pie',
      radius: ['40%', '70%'],
      avoidLabelOverlap: false,
      itemStyle: { borderRadius: 8, borderColor: 'transparent', borderWidth: 2 },
      label: { show: false },
      emphasis: { label: { show: true, fontSize: '14', fontWeight: 'bold' } },
      data: [
        { value: baiduVal, name: '百度', itemStyle: { color: '#2932e1' } },
        { value: bingVal, name: '必应', itemStyle: { color: '#008373' } }
      ]
    }]
  })
}

const initPieChart = () => {
  if (!pieChart.value) return
  pieChartInstance = echartsInit(pieChart.value)
  updatePieChart()
}

const initTrendChart = () => {
  if (!trendChart.value) return
  trendChartInstance = echartsInit(trendChart.value)
  updateTrendChart()
}

const updateTrendChart = () => {
  if (!trendChartInstance) return
  const isTraffic = trendType.value === 'traffic'
  const raw = trendRawData.value
  const dates = raw?.dates?.length ? raw.dates : Array.from({ length: 30 }, (_, i) => {
    const d = new Date(); d.setDate(d.getDate() - 29 + i)
    return `${d.getMonth()+1}-${d.getDate()}`
  })
  const baiduData = isTraffic ? (raw?.baidu_traffic ?? []) : (raw?.baidu_indexed ?? [])
  const bingData = isTraffic ? (raw?.bing_traffic ?? []) : (raw?.bing_indexed ?? [])

  const option = {
    tooltip: { trigger: 'axis' },
    grid: { left: '3%', right: '4%', bottom: '3%', containLabel: true },
    xAxis: {
      type: 'category',
      boundaryGap: false,
      data: dates,
      axisLabel: { color: '#8b8b8b' }
    },
    yAxis: { type: 'value', axisLabel: { color: '#8b8b8b' } },
    series: [
      {
        name: isTraffic ? '百度流量' : '百度收录',
        type: 'line',
        smooth: true,
        data: baiduData,
        itemStyle: { color: '#2932e1' },
        areaStyle: { color: new LinearGradient(0, 0, 0, 1, [{ offset: 0, color: 'rgba(41, 50, 225, 0.2)' }, { offset: 1, color: 'rgba(41, 50, 225, 0)' }]) }
      },
      {
        name: isTraffic ? '必应流量' : '必应收录',
        type: 'line',
        smooth: true,
        data: bingData,
        itemStyle: { color: '#008373' },
        areaStyle: { color: new LinearGradient(0, 0, 0, 1, [{ offset: 0, color: 'rgba(0, 131, 115, 0.2)' }, { offset: 1, color: 'rgba(0, 131, 115, 0)' }]) }
      }
    ]
  }
  trendChartInstance.setOption(option)
}

watch(trendType, updateTrendChart)
watch(pageCurrent, loadStats)
watch(pageSearch, () => { pageCurrent.value = 1; loadStats() })

const handleResize = () => {
  pieChartInstance?.resize()
  trendChartInstance?.resize()
}

onMounted(async () => {
  initPieChart()
  initTrendChart()
  await loadStats()
  window.addEventListener('resize', handleResize)
})

onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
  pieChartInstance?.dispose()
  trendChartInstance?.dispose()
})
</script>

<style scoped>
.seo-stats-page {
  padding: 24px;
}

.page-header {
  margin-bottom: 24px;
}

.page-title {
  font-size: 28px;
  font-weight: 600;
  margin: 0 0 8px;
}

.page-desc {
  color: var(--text-secondary);
  margin: 0;
}

.stats-overview {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 24px;
}

.stat-card {
  display: flex;
  align-items: center;
  padding: 20px;
}

.stat-icon {
  width: 56px;
  height: 56px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 16px;
  font-size: 28px;
}

.stat-icon.baidu {
  background: #2932e1;
}

.stat-icon.bing {
  background: #008373;
}

.stat-icon.keywords {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: var(--text-primary);
}

.stat-icon.traffic {
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  color: var(--text-primary);
}

.stat-icon img {
  width: 32px;
  height: 32px;
}

.stat-content {
  flex: 1;
}

.stat-number {
  font-size: 28px;
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: 4px;
}

.stat-label {
  font-size: 13px;
  color: var(--text-secondary);
  margin-bottom: 4px;
}

.stat-trend {
  font-size: 12px;
  display: flex;
  align-items: center;
  gap: 4px;
}

.stat-trend.up {
  color: #10b981;
}

.stat-trend.down {
  color: #ef4444;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.keyword-card, .chart-card, .trend-card, .pages-card, .suggestions-card {
  margin-bottom: 24px;
}

.keyword-cell {
  display: flex;
  align-items: center;
  gap: 8px;
}

.keyword-text {
  font-weight: 500;
}

.trend-up {
  color: #10b981;
}

.trend-down {
  color: #ef4444;
}

.chart-container {
  height: 300px;
}

.chart-container-large {
  height: 350px;
}

.page-link {
  color: var(--primary-color);
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 4px;
}

.page-link:hover {
  text-decoration: underline;
}

.pagination-wrapper {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
}

.suggestion-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.suggestion-item {
  display: flex;
  align-items: center;
  padding: 16px;
  border-radius: 8px;
  background: #f8fafc;
  gap: 12px;
}

.suggestion-item.high {
  background: #fef2f2;
  border: 1px solid #fecaca;
}

.suggestion-item.medium {
  background: #fffbeb;
  border: 1px solid #fde68a;
}

.suggestion-item.low {
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
}

.suggestion-icon {
  font-size: 24px;
}

.suggestion-item.high .suggestion-icon {
  color: #ef4444;
}

.suggestion-item.medium .suggestion-icon {
  color: #f59e0b;
}

.suggestion-item.low .suggestion-icon {
  color: #10b981;
}

.suggestion-content {
  flex: 1;
}

.suggestion-title {
  font-weight: 500;
  margin-bottom: 4px;
}

.suggestion-desc {
  font-size: 13px;
  color: var(--text-secondary);
}

@media (max-width: 1200px) {
  .stats-overview {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .stats-overview {
    grid-template-columns: 1fr;
  }
  
  .card-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }
}
</style>
