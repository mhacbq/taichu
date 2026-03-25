<script setup>
import { ref, computed, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { getSeoStats } from '@/api/admin'

// 统计数据
const loading = ref(false)
const stats = ref({
  total: 0,
  active: 0,
  inactive: 0,
  coverage: 0,
  unconfigured: [],
  unconfigured_count: 0
})

// 覆盖率颜色
const coverageColor = computed(() => {
  const c = stats.value.coverage
  if (c >= 80) return '#67c23a'
  if (c >= 50) return '#e6a23c'
  return '#f56c6c'
})

// 覆盖率状态文字
const coverageStatus = computed(() => {
  const c = stats.value.coverage
  if (c >= 80) return '良好'
  if (c >= 50) return '一般'
  return '待完善'
})

// 加载统计数据
const loadStats = async () => {
  loading.value = true
  try {
    const res = await getSeoStats()
    if (res.code === 0) {
      stats.value = res.data
    } else {
      ElMessage.error(res.msg || '获取SEO统计失败')
    }
  } catch (e) {
    ElMessage.error('获取SEO统计失败，请稍后重试')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadStats()
})
</script>

<template>
  <div class="seo-stats-page" v-loading="loading">
    <div class="page-header">
      <h1 class="page-title">SEO 配置覆盖率</h1>
      <p class="page-desc">检查各页面 SEO 配置完整性，确保搜索引擎可正确收录</p>
      <el-button type="primary" :icon="'Refresh'" @click="loadStats" :loading="loading" size="small">
        刷新数据
      </el-button>
    </div>

    <!-- 概览卡片 -->
    <el-row :gutter="20" class="stats-overview">
      <el-col :xs="12" :sm="6">
        <el-card class="stat-card">
          <div class="stat-number" style="color: #409eff">{{ stats.total }}</div>
          <div class="stat-label">已配置页面总数</div>
        </el-card>
      </el-col>
      <el-col :xs="12" :sm="6">
        <el-card class="stat-card">
          <div class="stat-number" style="color: #67c23a">{{ stats.active }}</div>
          <div class="stat-label">已启用配置</div>
        </el-card>
      </el-col>
      <el-col :xs="12" :sm="6">
        <el-card class="stat-card">
          <div class="stat-number" style="color: #e6a23c">{{ stats.inactive }}</div>
          <div class="stat-label">已禁用配置</div>
        </el-card>
      </el-col>
      <el-col :xs="12" :sm="6">
        <el-card class="stat-card">
          <div class="stat-number" :style="{ color: coverageColor }">{{ stats.coverage }}%</div>
          <div class="stat-label">页面覆盖率 · {{ coverageStatus }}</div>
        </el-card>
      </el-col>
    </el-row>

    <!-- 覆盖率进度条 -->
    <el-card class="coverage-card">
      <template #header>
        <span class="card-title">📊 SEO 覆盖率详情</span>
      </template>
      <div class="coverage-bar-wrap">
        <div class="coverage-label">
          <span>整体覆盖率</span>
          <span :style="{ color: coverageColor, fontWeight: 600 }">{{ stats.coverage }}%</span>
        </div>
        <el-progress
          :percentage="stats.coverage"
          :color="coverageColor"
          :stroke-width="16"
          :show-text="false"
        />
        <div class="coverage-hint">
          共 {{ stats.total + stats.unconfigured_count }} 个页面，已配置 {{ stats.total }} 个，未配置 {{ stats.unconfigured_count }} 个
        </div>
      </div>
    </el-card>

    <!-- 未配置页面列表 -->
    <el-card v-if="stats.unconfigured_count > 0" class="unconfigured-card">
      <template #header>
        <div class="card-header-row">
          <span class="card-title">⚠️ 未配置 SEO 的页面（{{ stats.unconfigured_count }} 个）</span>
          <el-tag type="warning" size="small">需要补充</el-tag>
        </div>
      </template>
      <div class="unconfigured-list">
        <el-tag
          v-for="route in stats.unconfigured"
          :key="route"
          type="danger"
          class="route-tag"
          effect="plain"
        >
          {{ route }}
        </el-tag>
      </div>
      <el-alert
        title="建议前往「SEO配置」页面，为以上页面补充 title、description、keywords 等 SEO 信息，有助于提升搜索引擎收录效果。"
        type="warning"
        :closable="false"
        show-icon
        style="margin-top: 16px"
      />
    </el-card>

    <!-- 全部覆盖时的提示 -->
    <el-card v-else class="all-covered-card">
      <el-result
        icon="success"
        title="所有页面均已配置 SEO"
        sub-title="太棒了！所有页面都已完成 SEO 配置，搜索引擎可以正确收录您的网站。"
      >
        <template #extra>
          <el-button type="primary" @click="$router.push('/seo/index')">查看 SEO 配置</el-button>
        </template>
      </el-result>
    </el-card>

    <!-- 说明 -->
    <el-card class="tips-card">
      <template #header>
        <span class="card-title">💡 SEO 优化建议</span>
      </template>
      <ul class="tips-list">
        <li>每个页面的 <strong>title</strong> 应包含核心关键词，长度建议 20-60 字符</li>
        <li><strong>description</strong> 应简洁描述页面内容，长度建议 80-160 字符</li>
        <li><strong>keywords</strong> 建议 3-8 个关键词，用英文逗号分隔</li>
        <li>确保所有页面的 SEO 配置处于「启用」状态</li>
        <li>定期检查 robots.txt 配置，确保搜索引擎可以正常抓取</li>
        <li>提交站点地图（Sitemap）可加速搜索引擎收录</li>
      </ul>
    </el-card>
  </div>
</template>

<style scoped>
.seo-stats-page {
  padding: 20px;
}

.page-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 24px;
  flex-wrap: wrap;
}

.page-title {
  font-size: 20px;
  font-weight: 600;
  color: #303133;
  margin: 0;
}

.page-desc {
  font-size: 14px;
  color: #909399;
  margin: 0;
  flex: 1;
}

.stats-overview {
  margin-bottom: 20px;
}

.stat-card {
  text-align: center;
  padding: 8px 0;
}

.stat-number {
  font-size: 32px;
  font-weight: 700;
  line-height: 1.2;
  margin-bottom: 6px;
}

.stat-label {
  font-size: 13px;
  color: #909399;
}

.coverage-card,
.unconfigured-card,
.all-covered-card,
.tips-card {
  margin-bottom: 20px;
}

.card-title {
  font-size: 15px;
  font-weight: 600;
  color: #303133;
}

.card-header-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.coverage-bar-wrap {
  padding: 8px 0;
}

.coverage-label {
  display: flex;
  justify-content: space-between;
  margin-bottom: 10px;
  font-size: 14px;
  color: #606266;
}

.coverage-hint {
  margin-top: 10px;
  font-size: 13px;
  color: #909399;
}

.unconfigured-list {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.route-tag {
  font-size: 13px;
}

.tips-list {
  padding-left: 20px;
  margin: 0;
  line-height: 2;
  color: #606266;
  font-size: 14px;
}

.tips-list li {
  margin-bottom: 4px;
}
</style>
