<template>
  <div class="app-container">
    <el-card v-if="pageError" shadow="never" class="page-state-card">
      <el-result icon="warning" :title="pageError.title" :sub-title="pageError.description">
        <template #extra>
          <el-button type="primary" :loading="loading" @click="loadData">重新加载</el-button>
        </template>
      </el-result>
    </el-card>

    <template v-else>
      <el-row :gutter="16" class="stats-row">
        <el-col :xs="12" :sm="12" :md="6">
          <el-card shadow="hover" class="stats-card">
            <div class="stats-label">SEO 配置总数</div>
            <div class="stats-value">{{ total }}</div>
          </el-card>
        </el-col>
        <el-col :xs="12" :sm="12" :md="6">
          <el-card shadow="hover" class="stats-card">
            <div class="stats-label">平均标题长度</div>
            <div class="stats-value">{{ averageTitleLength }}</div>
          </el-card>
        </el-col>
        <el-col :xs="12" :sm="12" :md="6">
          <el-card shadow="hover" class="stats-card">
            <div class="stats-label">平均描述长度</div>
            <div class="stats-value">{{ averageDescLength }}</div>
          </el-card>
        </el-col>
        <el-col :xs="12" :sm="12" :md="6">
          <el-card shadow="hover" class="stats-card">
            <div :class="['stats-value', seoHealthColor]">{{ seoHealthScore }}</div>
            <div class="stats-label">当前页健康度</div>
          </el-card>
        </el-col>
      </el-row>

      <el-tabs v-model="activeTab">
        <el-tab-pane label="页面 SEO 配置" name="configs">
          <el-card shadow="never">
            <template #header>
              <div class="card-header">
                <div>
                  <div class="section-title">配置列表</div>
                  <div class="section-subtitle">支持按路由、标题、启用状态筛选，并直接维护元信息。</div>
                </div>
                <el-button type="primary" :disabled="readonlyMode" @click="showAddDialog">
                  <el-icon><Plus /></el-icon>新增配置
                </el-button>
              </div>
            </template>

            <el-form :model="filters" inline class="filter-form">
              <el-form-item label="关键词">
                <el-input v-model="filters.keyword" placeholder="搜索路由、标题或描述" clearable />
              </el-form-item>
              <el-form-item label="状态">
                <el-select v-model="filters.is_active" placeholder="全部状态" clearable style="width: 150px">
                  <el-option label="启用" :value="1" />
                  <el-option label="停用" :value="0" />
                </el-select>
              </el-form-item>
              <el-form-item>
                <el-button type="primary" @click="handleSearch">
                  <el-icon><Search /></el-icon>搜索
                </el-button>
                <el-button @click="handleReset">重置</el-button>
              </el-form-item>
            </el-form>

            <el-table :data="pageConfigs" stripe border v-loading="loading">
              <el-table-column prop="route" label="页面路由" min-width="150">
                <template #default="{ row }">
                  <code>{{ row.route }}</code>
                </template>
              </el-table-column>
              <el-table-column prop="title" label="标题" min-width="220" show-overflow-tooltip />
              <el-table-column prop="description" label="描述" min-width="260" show-overflow-tooltip />
              <el-table-column label="关键词" min-width="220">
                <template #default="{ row }">
                  <div class="keyword-list">
                    <el-tag v-for="item in formatKeywords(row.keywords)" :key="item" size="small">{{ item }}</el-tag>
                  </div>
                </template>
              </el-table-column>
              <el-table-column prop="changefreq" label="更新频率" width="110" />
              <el-table-column prop="priority" label="优先级" width="90" />
              <el-table-column label="状态" width="90">
                <template #default="{ row }">
                  <el-tag :type="row.is_active ? 'success' : 'info'">{{ row.is_active ? '启用' : '停用' }}</el-tag>
                </template>
              </el-table-column>
              <el-table-column prop="updated_at" label="更新时间" width="168" />
              <el-table-column label="操作" width="150" fixed="right">
                <template #default="{ row }">
                  <el-button link type="primary" :disabled="readonlyMode" @click="editConfig(row)">编辑</el-button>
                  <el-button link type="danger" :disabled="readonlyMode" @click="handleDelete(row)">删除</el-button>
                </template>
              </el-table-column>
            </el-table>

            <div class="pagination-container">
              <el-pagination
                v-model:current-page="filters.page"
                v-model:page-size="filters.pageSize"
                :total="total"
                :page-sizes="[10, 20, 50, 100]"
                layout="total, sizes, prev, pager, next"
                @size-change="handlePageSizeChange"
                @current-change="handlePageChange"
              />
            </div>
          </el-card>
        </el-tab-pane>

        <el-tab-pane label="站点地图 / Robots / 收录" name="tools">
          <el-row :gutter="16">
            <el-col :xs="24" :lg="12">
              <el-card shadow="never" class="tool-card">
                <template #header>
                  <div class="card-header simple-header">
                    <span>站点地图状态</span>
                  </div>
                </template>
                <el-descriptions :column="1" border>
                  <el-descriptions-item label="最近更新时间">{{ sitemap.lastModified || '-' }}</el-descriptions-item>
                  <el-descriptions-item label="已收录 URL 数">{{ sitemap.urlCount || 0 }}</el-descriptions-item>
                  <el-descriptions-item label="预估文件大小">{{ sitemap.fileSize || '-' }}</el-descriptions-item>
                  <el-descriptions-item label="百度收录提交状态">
                    <el-tag :type="sitemap.baiduIndexed ? 'success' : 'info'">
                      {{ sitemap.baiduIndexed ? '已提交过 Sitemap' : '暂未提交' }}
                    </el-tag>
                  </el-descriptions-item>
                </el-descriptions>
              </el-card>
            </el-col>
            <el-col :xs="24" :lg="12">
              <el-card shadow="never" class="tool-card">
                <template #header>
                  <div class="card-header simple-header">
                    <span>收录提交</span>
                  </div>
                </template>
                <el-form :model="submitForm" label-width="90px">
                  <el-form-item label="搜索引擎">
                    <el-select v-model="submitForm.engine" style="width: 100%">
                      <el-option v-for="item in engineOptions" :key="item" :label="engineLabelMap[item]" :value="item" />
                    </el-select>
                  </el-form-item>
                  <el-form-item label="提交类型">
                    <el-radio-group v-model="submitForm.type">
                      <el-radio label="sitemap">Sitemap</el-radio>
                      <el-radio label="url">单页 URL</el-radio>
                    </el-radio-group>
                  </el-form-item>
                  <el-form-item label="目标地址">
                    <el-input v-model="submitForm.url" placeholder="留空时默认提交站点根地址或 sitemap.xml" />
                  </el-form-item>
                  <el-form-item>
                    <el-button type="primary" :loading="submitting" :disabled="readonlyMode" @click="handleSubmitSeo">提交收录</el-button>
                  </el-form-item>
                </el-form>

                <div class="submit-status">
                  <div class="submit-status-title">最近提交状态</div>
                  <div class="status-tag-group">
                    <div v-for="engine in engineOptions" :key="engine" class="status-tag-item">
                      <span>{{ engineLabelMap[engine] }}</span>
                      <el-tag :type="submitStatus[engine]?.type || 'info'">
                        {{ submitStatus[engine]?.text || '未提交' }}
                      </el-tag>
                    </div>
                  </div>
                </div>
              </el-card>
            </el-col>
          </el-row>

          <el-card shadow="never" class="tool-card robots-card">
            <template #header>
              <div class="card-header">
                <div>
                  <div class="section-title">Robots.txt</div>
                  <div class="section-subtitle">直接编辑 robots 原文，保存后会同步写入后台配置。</div>
                </div>
                <el-button type="primary" :loading="savingRobots" :disabled="readonlyMode" @click="saveRobots">保存 Robots</el-button>
              </div>
            </template>
            <el-input
              v-model="robotsContent"
              type="textarea"
              :rows="12"
              :disabled="readonlyMode"
              placeholder="请输入 robots.txt 内容"
            />
          </el-card>
        </el-tab-pane>
      </el-tabs>

      <el-dialog
        v-model="dialogVisible"
        :title="isEditing ? '编辑 SEO 配置' : '新增 SEO 配置'"
        width="760px"
      >
        <el-form :model="form" label-width="110px" :disabled="readonlyMode">
          <el-form-item label="页面路由" required>
            <el-input v-model="form.route" placeholder="例如 /bazi" :disabled="isEditing || readonlyMode" />
          </el-form-item>
          <el-form-item label="页面标题" required>
            <el-input v-model="form.title" maxlength="100" show-word-limit placeholder="建议 20-60 字" />
          </el-form-item>
          <el-form-item label="页面描述" required>
            <el-input
              v-model="form.description"
              type="textarea"
              :rows="3"
              maxlength="255"
              show-word-limit
              placeholder="建议 50-200 字"
            />
          </el-form-item>
          <el-form-item label="关键词" required>
            <el-input v-model="form.keywords_input" placeholder="多个关键词请用英文逗号分隔" />
          </el-form-item>
          <el-form-item label="Robots">
            <el-radio-group v-model="form.robots">
              <el-radio label="index,follow">允许收录</el-radio>
              <el-radio label="noindex,follow">禁止收录但允许跟踪</el-radio>
              <el-radio label="noindex,nofollow">禁止收录且禁止跟踪</el-radio>
            </el-radio-group>
          </el-form-item>
          <el-row :gutter="16">
            <el-col :span="12">
              <el-form-item label="优先级">
                <el-input-number v-model="form.priority" :min="0" :max="1" :step="0.1" :precision="1" />
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="更新频率">
                <el-select v-model="form.changefreq" style="width: 100%">
                  <el-option v-for="item in changefreqOptions" :key="item" :label="item" :value="item" />
                </el-select>
              </el-form-item>
            </el-col>
          </el-row>
          <el-row :gutter="16">
            <el-col :span="12">
              <el-form-item label="OG 类型">
                <el-select v-model="form.og_type" style="width: 100%">
                  <el-option label="website" value="website" />
                  <el-option label="article" value="article" />
                </el-select>
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="启用状态">
                <el-switch v-model="form.is_active" :active-value="1" :inactive-value="0" />
              </el-form-item>
            </el-col>
          </el-row>
          <el-form-item label="规范链接">
            <el-input v-model="form.canonical" placeholder="例如 https://taichu.chat/bazi" />
          </el-form-item>
          <el-form-item label="分享图片">
            <el-input v-model="form.image" placeholder="请输入分享图片 URL（可选）" />
          </el-form-item>
        </el-form>
        <template #footer>
          <el-button @click="dialogVisible = false">取消</el-button>
          <el-button type="primary" :loading="saving" :disabled="readonlyMode" @click="handleSaveConfig">保存</el-button>
        </template>
      </el-dialog>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Plus, Search } from '@element-plus/icons-vue'
import {
  getSeoConfigs,
  saveSeoConfig,
  deleteSeoConfig,
  getRobotsConfig,
  saveRobotsConfig,
  generateSitemap
} from '@/api/siteContent'
import { createReadonlyErrorState } from '@/utils/page-error'

const activeTab = ref('configs')
const loading = ref(false)
const saving = ref(false)
const savingRobots = ref(false)
const submitting = ref(false)
const dialogVisible = ref(false)
const isEditing = ref(false)
const pageError = ref(null)
const pageConfigs = ref([])
const total = ref(0)
const robotsContent = ref('')
const sitemap = ref(createDefaultSitemap())
const submitStatus = ref({})

const filters = reactive({
  keyword: '',
  is_active: '',
  page: 1,
  pageSize: 10
})

const form = ref(createDefaultForm())
const submitForm = reactive({
  engine: 'baidu',
  type: 'sitemap',
  url: ''
})

const readonlyMode = computed(() => Boolean(pageError.value))
const engineOptions = ['baidu', 'bing', '360', 'sogou']
const engineLabelMap = {
  baidu: '百度',
  bing: 'Bing',
  360: '360 搜索',
  sogou: '搜狗'
}
const changefreqOptions = ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never']

function createDefaultSitemap() {
  return {
    lastModified: '',
    urlCount: 0,
    fileSize: '-',
    baiduIndexed: false
  }
}

function createDefaultForm() {
  return {
    id: 0,
    route: '',
    title: '',
    description: '',
    keywords_input: '',
    robots: 'index,follow',
    priority: 0.5,
    changefreq: 'weekly',
    og_type: 'website',
    canonical: '',
    image: '',
    is_active: 1
  }
}

onMounted(() => {
  loadData()
})

const averageTitleLength = computed(() => {
  if (!pageConfigs.value.length) return 0
  const totalLength = pageConfigs.value.reduce((sum, item) => sum + (item.title?.length || 0), 0)
  return Math.round(totalLength / pageConfigs.value.length)
})

const averageDescLength = computed(() => {
  if (!pageConfigs.value.length) return 0
  const totalLength = pageConfigs.value.reduce((sum, item) => sum + (item.description?.length || 0), 0)
  return Math.round(totalLength / pageConfigs.value.length)
})

const seoHealthScore = computed(() => {
  if (!pageConfigs.value.length) return 0
  let score = 100
  pageConfigs.value.forEach(item => {
    const titleLength = item.title?.length || 0
    const descLength = item.description?.length || 0
    if (titleLength < 20 || titleLength > 60) score -= 8
    if (descLength < 50 || descLength > 200) score -= 8
    if (!item.keywords?.length) score -= 5
    if (!item.is_active) score -= 3
  })
  return Math.max(0, score)
})

const seoHealthColor = computed(() => {
  if (seoHealthScore.value >= 80) return 'text-success'
  if (seoHealthScore.value >= 60) return 'text-warning'
  return 'text-danger'
})

function buildSeoParams() {
  return {
    keyword: filters.keyword?.trim() || undefined,
    is_active: filters.is_active === '' ? undefined : filters.is_active,
    page: filters.page,
    pageSize: filters.pageSize
  }
}

function formatKeywords(value) {
  if (!value) return []
  if (Array.isArray(value)) return value.slice(0, 6)
  return String(value).split(',').map(item => item.trim()).filter(Boolean).slice(0, 6)
}

function normalizeSeoConfig(item = {}) {
  const normalizedActive = Number(item.is_active ?? item.isActive ?? 1)
  const normalizedOgType = item.og_type || item.ogType || 'website'

  return {
    ...item,
    is_active: normalizedActive,
    isActive: normalizedActive,
    og_type: normalizedOgType,
    ogType: normalizedOgType,
    keywords: formatKeywords(item.keywords)
  }
}

function ensureWritable(message) {

  if (!readonlyMode.value) {
    return true
  }

  ElMessage.warning(message)
  return false
}

async function loadData() {
  loading.value = true
  try {
    const [seoRes, robotsRes] = await Promise.all([
      getSeoConfigs(buildSeoParams(), { showErrorMessage: false }),
      getRobotsConfig({ showErrorMessage: false })
    ])

    pageConfigs.value = Array.isArray(seoRes.data?.list)
      ? seoRes.data.list.map(item => normalizeSeoConfig(item))
      : []

    total.value = Number(seoRes.data?.total || 0)
    sitemap.value = seoRes.data?.sitemap || createDefaultSitemap()
    submitStatus.value = seoRes.data?.submitStatus || {}
    robotsContent.value = robotsRes.data?.content || ''
    pageError.value = null
  } catch (error) {
    pageConfigs.value = []
    total.value = 0
    sitemap.value = createDefaultSitemap()
    submitStatus.value = {}
    robotsContent.value = ''
    dialogVisible.value = false
    pageError.value = createReadonlyErrorState(error, 'SEO 管理', 'config_manage')
  } finally {
    loading.value = false
  }
}

function handleSearch() {
  if (!ensureWritable('SEO 数据尚未成功加载，当前为只读保护状态')) {
    return
  }

  filters.page = 1
  loadData()
}

function handleReset() {
  if (!ensureWritable('SEO 数据尚未成功加载，当前为只读保护状态')) {
    return
  }

  Object.assign(filters, {
    keyword: '',
    is_active: '',
    page: 1,
    pageSize: 10
  })
  loadData()
}

function handlePageChange(page) {
  if (!ensureWritable('SEO 数据尚未成功加载，当前为只读保护状态')) {
    return
  }

  filters.page = page
  loadData()
}

function handlePageSizeChange(size) {
  if (!ensureWritable('SEO 数据尚未成功加载，当前为只读保护状态')) {
    return
  }

  filters.pageSize = size
  filters.page = 1
  loadData()
}

function showAddDialog() {
  if (!ensureWritable('SEO 数据尚未成功加载，暂时无法新增配置')) {
    return
  }

  isEditing.value = false
  form.value = createDefaultForm()
  dialogVisible.value = true
}

function editConfig(row) {
  if (!ensureWritable('SEO 数据尚未成功加载，暂时无法编辑配置')) {
    return
  }

  isEditing.value = true
  form.value = {
    id: row.id,
    route: row.route,
    title: row.title,
    description: row.description,
    keywords_input: Array.isArray(row.keywords) ? row.keywords.join(', ') : row.keywords || '',
    robots: row.robots || 'index,follow',
    priority: Number(row.priority ?? 0.5),
    changefreq: row.changefreq || 'weekly',
    og_type: row.og_type || row.ogType || 'website',
    canonical: row.canonical || '',
    image: row.image || '',
    is_active: Number(row.is_active ?? row.isActive ?? 1)
  }
  dialogVisible.value = true
}

function buildSavePayload() {
  return {
    id: form.value.id || undefined,
    route: form.value.route.trim(),
    title: form.value.title.trim(),
    description: form.value.description.trim(),
    keywords: form.value.keywords_input
      .split(',')
      .map(item => item.trim())
      .filter(Boolean),
    robots: form.value.robots,
    priority: Number(form.value.priority),
    changefreq: form.value.changefreq,
    og_type: form.value.og_type,
    canonical: form.value.canonical?.trim() || '',
    image: form.value.image?.trim() || '',
    is_active: Number(form.value.is_active)
  }
}

async function handleSaveConfig() {
  if (!ensureWritable('SEO 数据尚未成功加载，暂时无法保存配置')) {
    return
  }

  const payload = buildSavePayload()
  if (!payload.route || !payload.title || !payload.description) {
    ElMessage.warning('请先填写路由、标题和描述')
    return
  }

  if (!payload.keywords.length) {
    ElMessage.warning('请至少填写一个关键词')
    return
  }

  saving.value = true
  try {
    await saveSeoConfig(payload, { showErrorMessage: false })
    ElMessage.success(isEditing.value ? 'SEO 配置已更新' : 'SEO 配置已新增')
    dialogVisible.value = false
    await loadData()
  } catch (error) {
    ElMessage.error(error.message || '保存 SEO 配置失败')
  } finally {
    saving.value = false
  }
}

async function handleDelete(row) {
  if (!ensureWritable('SEO 数据尚未成功加载，暂时无法删除配置')) {
    return
  }

  try {
    await ElMessageBox.confirm(`确定删除路由 ${row.route} 的 SEO 配置吗？`, '删除确认', {
      confirmButtonText: '删除',
      cancelButtonText: '取消',
      type: 'warning'
    })
    await deleteSeoConfig(row.id, { showErrorMessage: false })
    ElMessage.success('删除成功')
    if (pageConfigs.value.length === 1 && filters.page > 1) {
      filters.page -= 1
    }
    await loadData()
  } catch (error) {
    if (error !== 'cancel' && error !== 'close') {
      ElMessage.error(error.message || '删除失败')
    }
  }
}

async function handleSubmitSeo() {
  if (!ensureWritable('SEO 数据尚未成功加载，暂时无法提交收录')) {
    return
  }

  submitting.value = true
  try {
    await generateSitemap({
      engine: submitForm.engine,
      type: submitForm.type,
      url: submitForm.url?.trim() || undefined
    }, { showErrorMessage: false })
    ElMessage.success(`${engineLabelMap[submitForm.engine]} 收录提交成功`)
    await loadData()
  } catch (error) {
    ElMessage.error(error.message || '提交收录失败')
  } finally {
    submitting.value = false
  }
}

async function saveRobots() {
  if (!ensureWritable('SEO 数据尚未成功加载，暂时无法保存 Robots')) {
    return
  }

  if (!robotsContent.value.trim()) {
    ElMessage.warning('robots.txt 内容不能为空')
    return
  }

  savingRobots.value = true
  try {
    await saveRobotsConfig(robotsContent.value, { showErrorMessage: false })
    ElMessage.success('Robots 配置保存成功')
    await loadData()
  } catch (error) {
    ElMessage.error(error.message || '保存 Robots 失败')
  } finally {
    savingRobots.value = false
  }
}
</script>

<script>
export default {
  name: 'SeoManager'
}
</script>

<style lang="scss" scoped>
.page-state-card {
  margin-bottom: 20px;
}

.stats-row {
  margin-bottom: 20px;
}

.stats-card {
  height: 100%;
}

.stats-label {
  color: #909399;
  font-size: 14px;
  margin-bottom: 10px;
}

.stats-value {
  font-size: 24px;
  font-weight: 700;
  color: #303133;
}

.text-success {
  color: #67c23a;
}

.text-warning {
  color: #e6a23c;
}

.text-danger {
  color: #f56c6c;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
}

.simple-header {
  justify-content: flex-start;
}

.section-title {
  font-size: 16px;
  font-weight: 600;
  color: #303133;
}

.section-subtitle {
  margin-top: 6px;
  color: #909399;
  font-size: 13px;
}

.filter-form {
  margin-bottom: 16px;
}

.keyword-list {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.pagination-container {
  display: flex;
  justify-content: flex-end;
  margin-top: 20px;
}

.tool-card {
  height: 100%;
}

.robots-card {
  margin-top: 16px;
}

.submit-status {
  margin-top: 20px;
  padding-top: 16px;
  border-top: 1px solid var(--el-border-color-light);
}

.submit-status-title {
  margin-bottom: 12px;
  color: #606266;
  font-size: 14px;
  font-weight: 600;
}

.status-tag-group {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.status-tag-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
}

code {
  padding: 2px 6px;
  border-radius: 4px;
  background: #f4f4f5;
  color: #e6a23c;
}
</style>
