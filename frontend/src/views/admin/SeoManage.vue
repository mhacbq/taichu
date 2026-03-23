<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Plus, Refresh, Document } from '@element-plus/icons-vue'
import { getSeoConfigs, saveSeoConfig, deleteSeoConfig, getSeoStats, getSeoPageTypes, getRobotsConfig, saveRobotsConfig, generateSitemap, batchUpdateSeoStatus } from '../../api/admin'

// ==================== 状态 ====================
const loading = ref(false)
const seoList = ref([])
const pageTypes = ref({})
const stats = ref({ total: 0, active: 0, inactive: 0, coverage: 0, unconfigured: [] })
const selectedIds = ref([])
const activeTab = ref('configs') // configs | robots | preview

// 编辑对话框
const dialogVisible = ref(false)
const dialogTitle = ref('新增SEO配置')
const formRef = ref(null)
const form = reactive({
  id: 0,
  page_type: '',
  route_path: '',
  title: '',
  keywords: '',
  description: '',
  og_image: '',
  robots: 'index,follow',
  structured_data: '',
  status: 1,
  sort_order: 0
})

// robots.txt 编辑
const robotsContent = ref('')
const robotsLoading = ref(false)

// 表单验证规则
const formRules = {
  page_type: [{ required: true, message: '请选择页面类型', trigger: 'change' }],
  title: [
    { required: true, message: '请输入页面标题', trigger: 'blur' },
    { max: 80, message: '标题建议不超过80个字符', trigger: 'blur' }
  ],
  description: [
    { max: 200, message: '描述建议不超过200个字符', trigger: 'blur' }
  ],
  keywords: [
    { max: 300, message: '关键词建议不超过300个字符', trigger: 'blur' }
  ]
}

// ==================== 计算属性 ====================
const titleLength = computed(() => form.title?.length || 0)
const descLength = computed(() => form.description?.length || 0)
const keywordsCount = computed(() => {
  if (!form.keywords) return 0
  return form.keywords.split(',').filter(k => k.trim()).length
})

// 页面类型选项列表
const pageTypeOptions = computed(() => {
  return Object.entries(pageTypes.value).map(([key, val]) => ({
    value: key,
    label: val.name,
    route: val.route,
    icon: val.icon
  }))
})

// 获取状态标签类型
const getStatusType = (status) => status === 1 ? 'success' : 'info'
const getStatusText = (status) => status === 1 ? '已启用' : '已禁用'

// ==================== 加载数据 ====================
const loadData = async () => {
  loading.value = true
  try {
    const [configsRes, typesRes, statsRes] = await Promise.all([
      getSeoConfigs(),
      getSeoPageTypes(),
      getSeoStats()
    ])

    if (configsRes.data?.code === 200) {
      seoList.value = configsRes.data.data?.list || []
    }
    if (typesRes.data?.code === 200) {
      pageTypes.value = typesRes.data.data || {}
    }
    if (statsRes.data?.code === 200) {
      stats.value = statsRes.data.data || stats.value
    }
  } catch (e) {
    ElMessage.error('加载数据失败')
  } finally {
    loading.value = false
  }
}

const loadRobots = async () => {
  robotsLoading.value = true
  try {
    const res = await getRobotsConfig()
    if (res.data?.code === 200) {
      robotsContent.value = res.data.data?.content || ''
    }
  } catch (e) {
    ElMessage.error('加载robots.txt失败')
  } finally {
    robotsLoading.value = false
  }
}

// ==================== 操作方法 ====================
const handleAdd = () => {
  dialogTitle.value = '新增SEO配置'
  Object.assign(form, {
    id: 0, page_type: '', route_path: '', title: '',
    keywords: '', description: '', og_image: '',
    robots: 'index,follow', structured_data: '', status: 1, sort_order: 0
  })
  dialogVisible.value = true
}

const handleEdit = (row) => {
  dialogTitle.value = '编辑SEO配置'
  Object.assign(form, {
    id: row.id,
    page_type: row.page_type,
    route_path: row.route_path || '',
    title: row.title || '',
    keywords: row.keywords || '',
    description: row.description || '',
    og_image: row.og_image || '',
    robots: row.robots || 'index,follow',
    structured_data: row.structured_data || '',
    status: row.status,
    sort_order: row.sort_order || 0
  })
  dialogVisible.value = true
}

const handleDelete = async (row) => {
  try {
    await ElMessageBox.confirm(`确认删除「${getPageTypeName(row.page_type)}」的SEO配置？`, '删除确认', {
      confirmButtonText: '删除',
      cancelButtonText: '取消',
      type: 'warning'
    })

    const res = await deleteSeoConfig(row.route_path || row.page_type)
    if (res.data?.code === 200) {
      ElMessage.success('删除成功')
      loadData()
    } else {
      ElMessage.error(res.data?.message || '删除失败')
    }
  } catch (e) {
    if (e !== 'cancel') ElMessage.error('删除失败')
  }
}

// 页面类型选择变化时自动填充路由路径
const handlePageTypeChange = (val) => {
  const typeInfo = pageTypes.value[val]
  if (typeInfo) {
    form.route_path = typeInfo.route || ''
  }
}

const handleSave = async () => {
  try {
    await formRef.value?.validate()
  } catch {
    return
  }

  try {
    const res = await saveSeoConfig({ ...form })
    if (res.data?.code === 200) {
      ElMessage.success('保存成功')
      dialogVisible.value = false
      loadData()
    } else {
      ElMessage.error(res.data?.message || '保存失败')
    }
  } catch (e) {
    ElMessage.error('保存失败')
  }
}

const handleBatchStatus = async (status) => {
  if (selectedIds.value.length === 0) {
    ElMessage.warning('请选择要操作的配置')
    return
  }
  const action = status === 1 ? '启用' : '禁用'
  try {
    await ElMessageBox.confirm(`确认${action}选中的 ${selectedIds.value.length} 项配置？`, '批量操作', {
      confirmButtonText: '确认',
      cancelButtonText: '取消',
      type: 'warning'
    })

    const res = await batchUpdateSeoStatus(selectedIds.value, status)
    if (res.data?.code === 200) {
      ElMessage.success(`${action}成功`)
      selectedIds.value = []
      loadData()
    } else {
      ElMessage.error(res.data?.message || `${action}失败`)
    }
  } catch (e) {
    if (e !== 'cancel') ElMessage.error(`${action}失败`)
  }
}

const handleSelectionChange = (selection) => {
  selectedIds.value = selection.map(item => item.id)
}

const handleSaveRobots = async () => {
  try {
    const res = await saveRobotsConfig(robotsContent.value)
    if (res.data?.code === 200) {
      ElMessage.success('robots.txt已保存')
    } else {
      ElMessage.error(res.data?.message || '保存失败')
    }
  } catch (e) {
    ElMessage.error('保存失败')
  }
}

const handleGenerateSitemap = async () => {
  try {
    await ElMessageBox.confirm('确认生成sitemap.xml？将覆盖现有文件', '生成Sitemap', {
      confirmButtonText: '生成',
      cancelButtonText: '取消',
      type: 'info'
    })

    const res = await generateSitemap()
    if (res.data?.code === 200) {
      const data = res.data.data || {}
      ElMessage.success(`Sitemap已生成，包含 ${data.url_count || 0} 个URL`)
    } else {
      ElMessage.error(res.data?.message || '生成失败')
    }
  } catch (e) {
    if (e !== 'cancel') ElMessage.error('生成Sitemap失败')
  }
}

// 辅助方法
const getPageTypeName = (type) => {
  return pageTypes.value[type]?.name || type
}

// Tab切换时加载对应数据
const handleTabChange = (tab) => {
  if (tab === 'robots') {
    loadRobots()
  }
}

// ==================== 生命周期 ====================
onMounted(() => {
  loadData()
})
</script>

<template>
  <div class="admin-seo-manage" v-loading="loading">
    <!-- 页面标题 -->
    <div class="page-header">
      <h2>SEO管理</h2>
      <p class="page-desc">管理各页面的标题、描述、关键词等SEO配置，优化搜索引擎排名</p>
    </div>

    <!-- 统计卡片 -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-value">{{ stats.total }}</div>
        <div class="stat-label">总配置数</div>
      </div>
      <div class="stat-card stat-active">
        <div class="stat-value">{{ stats.active }}</div>
        <div class="stat-label">已启用</div>
      </div>
      <div class="stat-card stat-inactive">
        <div class="stat-value">{{ stats.inactive }}</div>
        <div class="stat-label">已禁用</div>
      </div>
      <div class="stat-card stat-coverage">
        <div class="stat-value">{{ stats.coverage }}%</div>
        <div class="stat-label">覆盖率</div>
      </div>
    </div>

    <!-- 未配置提示 -->
    <el-alert 
      v-if="stats.unconfigured && stats.unconfigured.length > 0"
      type="warning"
      :closable="false"
      style="margin-bottom: 16px;"
    >
      <template #title>
        以下页面尚未配置SEO：{{ stats.unconfigured.map(t => getPageTypeName(t)).join('、') }}
      </template>
    </el-alert>

    <!-- Tab切换 -->
    <el-tabs v-model="activeTab" @tab-change="handleTabChange">
      <!-- SEO配置列表 -->
      <el-tab-pane label="页面SEO配置" name="configs">
        <!-- 工具栏 -->
        <div class="toolbar">
          <div class="toolbar-left">
            <el-button type="primary" @click="handleAdd">
              <el-icon><Plus /></el-icon> 新增配置
            </el-button>
            <el-button @click="handleBatchStatus(1)" :disabled="selectedIds.length === 0">
              批量启用
            </el-button>
            <el-button @click="handleBatchStatus(0)" :disabled="selectedIds.length === 0">
              批量禁用
            </el-button>
          </div>
          <div class="toolbar-right">
            <el-button @click="handleGenerateSitemap" type="success" plain>
              <el-icon><Document /></el-icon> 生成Sitemap
            </el-button>
            <el-button @click="loadData" :icon="Refresh" circle />
          </div>
        </div>

        <!-- 配置表格 -->
        <el-table 
          :data="seoList" 
          @selection-change="handleSelectionChange"
          stripe
          style="width: 100%"
        >
          <el-table-column type="selection" width="50" />
          <el-table-column label="页面" width="140">
            <template #default="{ row }">
              <div class="page-type-cell">
                <span class="type-name">{{ getPageTypeName(row.page_type) }}</span>
                <span class="type-route">{{ row.route_path || '-' }}</span>
              </div>
            </template>
          </el-table-column>
          <el-table-column label="标题" min-width="240">
            <template #default="{ row }">
              <div class="title-cell">
                <div class="seo-title">{{ row.title || '-' }}</div>
                <div class="seo-desc">{{ row.description || '暂无描述' }}</div>
              </div>
            </template>
          </el-table-column>
          <el-table-column label="关键词" min-width="180">
            <template #default="{ row }">
              <div class="keywords-cell" v-if="row.keywords">
                <el-tag 
                  v-for="kw in row.keywords.split(',').slice(0, 3)" 
                  :key="kw" 
                  size="small" 
                  type="info"
                  style="margin: 2px;"
                >
                  {{ kw.trim() }}
                </el-tag>
                <span v-if="row.keywords.split(',').length > 3" class="more-tag">
                  +{{ row.keywords.split(',').length - 3 }}
                </span>
              </div>
              <span v-else class="text-muted">-</span>
            </template>
          </el-table-column>
          <el-table-column label="Robots" width="120">
            <template #default="{ row }">
              <el-tag :type="row.robots?.includes('noindex') ? 'danger' : 'success'" size="small">
                {{ row.robots || 'index,follow' }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column label="排序" width="70" prop="sort_order" />
          <el-table-column label="状态" width="80">
            <template #default="{ row }">
              <el-tag :type="getStatusType(row.status)" size="small">
                {{ getStatusText(row.status) }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column label="操作" width="150" fixed="right">
            <template #default="{ row }">
              <el-button link type="primary" @click="handleEdit(row)">编辑</el-button>
              <el-button link type="danger" @click="handleDelete(row)">删除</el-button>
            </template>
          </el-table-column>
        </el-table>
      </el-tab-pane>

      <!-- Robots.txt管理 -->
      <el-tab-pane label="Robots.txt" name="robots">
        <div class="robots-section" v-loading="robotsLoading">
          <div class="robots-header">
            <h3>robots.txt 配置</h3>
            <div>
              <el-button type="primary" @click="handleSaveRobots">保存</el-button>
            </div>
          </div>
          <el-input
            v-model="robotsContent"
            type="textarea"
            :rows="15"
            placeholder="输入robots.txt内容..."
            class="robots-editor"
          />
          <div class="robots-tips">
            <p><strong>提示：</strong></p>
            <ul>
              <li><code>User-agent: *</code> — 适用于所有搜索引擎</li>
              <li><code>Disallow: /maodou/</code> — 禁止爬取管理后台</li>
              <li><code>Sitemap: https://taichu.chat/sitemap.xml</code> — 指定站点地图</li>
            </ul>
          </div>
        </div>
      </el-tab-pane>

      <!-- 搜索预览 -->
      <el-tab-pane label="搜索结果预览" name="preview">
        <div class="preview-section">
          <p class="preview-tip">以下是各页面在Google搜索结果中的大致效果：</p>
          <div 
            v-for="item in seoList.filter(s => s.status === 1 && !s.robots?.includes('noindex'))" 
            :key="item.id" 
            class="search-preview-item"
          >
            <div class="preview-url">https://taichu.chat{{ item.route_path || '/' }}</div>
            <div class="preview-title">{{ item.title || '太初命理' }}</div>
            <div class="preview-desc">{{ item.description || '暂无描述' }}</div>
          </div>
          <el-empty v-if="seoList.filter(s => s.status === 1 && !s.robots?.includes('noindex')).length === 0" description="暂无已启用的SEO配置" />
        </div>
      </el-tab-pane>
    </el-tabs>

    <!-- 编辑对话框 -->
    <el-dialog 
      v-model="dialogVisible" 
      :title="dialogTitle" 
      width="700px"
      destroy-on-close
    >
      <el-form ref="formRef" :model="form" :rules="formRules" label-width="100px">
        <el-form-item label="页面类型" prop="page_type">
          <el-select 
            v-model="form.page_type" 
            placeholder="选择页面类型" 
            :disabled="form.id > 0"
            @change="handlePageTypeChange"
            style="width: 100%;"
          >
            <el-option 
              v-for="opt in pageTypeOptions" 
              :key="opt.value" 
              :label="`${opt.label} (${opt.route})`" 
              :value="opt.value" 
            />
          </el-select>
        </el-form-item>

        <el-form-item label="路由路径">
          <el-input v-model="form.route_path" placeholder="如 /bazi、/tarot">
            <template #prepend>https://taichu.chat</template>
          </el-input>
        </el-form-item>

        <el-form-item label="页面标题" prop="title">
          <el-input v-model="form.title" placeholder="建议30-60个字符，包含核心关键词" maxlength="80" show-word-limit />
          <div class="form-tip" :class="{ 'tip-warning': titleLength > 60, 'tip-good': titleLength >= 30 && titleLength <= 60 }">
            {{ titleLength }}/80 字符 · {{ titleLength >= 30 && titleLength <= 60 ? '✅ 最佳长度' : titleLength > 60 ? '⚠️ 偏长' : '💡 建议30-60字符' }}
          </div>
        </el-form-item>

        <el-form-item label="页面描述" prop="description">
          <el-input v-model="form.description" type="textarea" :rows="3" placeholder="建议80-160个字符，简洁描述页面内容" maxlength="200" show-word-limit />
          <div class="form-tip" :class="{ 'tip-warning': descLength > 160, 'tip-good': descLength >= 80 && descLength <= 160 }">
            {{ descLength }}/200 字符 · {{ descLength >= 80 && descLength <= 160 ? '✅ 最佳长度' : descLength > 160 ? '⚠️ 偏长' : '💡 建议80-160字符' }}
          </div>
        </el-form-item>

        <el-form-item label="关键词" prop="keywords">
          <el-input v-model="form.keywords" placeholder="用英文逗号分隔，如：八字排盘,八字测算,生辰八字" maxlength="300" show-word-limit />
          <div class="form-tip">已输入 {{ keywordsCount }} 个关键词 · 建议5-10个</div>
        </el-form-item>

        <el-form-item label="分享图片">
          <el-input v-model="form.og_image" placeholder="Open Graph分享图片URL（1200x630推荐）" />
        </el-form-item>

        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="Robots">
              <el-select v-model="form.robots" style="width: 100%;">
                <el-option value="index,follow" label="index,follow（允许索引）" />
                <el-option value="noindex,follow" label="noindex,follow（不索引）" />
                <el-option value="index,nofollow" label="index,nofollow（不跟踪）" />
                <el-option value="noindex,nofollow" label="noindex,nofollow（完全禁止）" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="排序">
              <el-input-number v-model="form.sort_order" :min="0" :max="999" />
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="状态">
              <el-switch v-model="form.status" :active-value="1" :inactive-value="0" />
            </el-form-item>
          </el-col>
        </el-row>

        <!-- 搜索预览 -->
        <el-form-item label="搜索预览">
          <div class="search-preview-item preview-in-dialog">
            <div class="preview-url">https://taichu.chat{{ form.route_path || '/' }}</div>
            <div class="preview-title">{{ form.title || '页面标题' }}</div>
            <div class="preview-desc">{{ form.description || '页面描述将显示在这里...' }}</div>
          </div>
        </el-form-item>
      </el-form>

      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSave">保存</el-button>
      </template>
</el-dialog>
  </div>
</template>

<style scoped>
.admin-seo-manage {
  padding: 24px;
}

.page-header {
  margin-bottom: 20px;
}

.page-header h2 {
  margin: 0 0 4px;
  font-size: 20px;
}

.page-desc {
  color: #909399;
  font-size: 13px;
  margin: 0;
}

/* 统计卡片 */
.stats-row {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 20px;
}

.stat-card {
  background: #fff;
  border-radius: 8px;
  padding: 20px;
  text-align: center;
  border: 1px solid #ebeef5;
  transition: box-shadow 0.2s;
}

.stat-card:hover {
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
}

.stat-value {
  font-size: 28px;
  font-weight: 600;
  color: #303133;
  line-height: 1.2;
}

.stat-label {
  font-size: 13px;
  color: #909399;
  margin-top: 4px;
}

.stat-active .stat-value { color: #67c23a; }
.stat-inactive .stat-value { color: #909399; }
.stat-coverage .stat-value { color: #409eff; }

/* 工具栏 */
.toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.toolbar-left, .toolbar-right {
  display: flex;
  gap: 8px;
  align-items: center;
}

/* 表格单元格样式 */
.page-type-cell {
  display: flex;
  flex-direction: column;
}

.type-name {
  font-weight: 500;
  color: #303133;
}

.type-route {
  font-size: 12px;
  color: #909399;
  font-family: monospace;
}

.title-cell {
  line-height: 1.4;
}

.seo-title {
  color: #1a0dab;
  font-size: 14px;
  font-weight: 500;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  max-width: 320px;
}

.seo-desc {
  color: #545454;
  font-size: 12px;
  margin-top: 2px;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

.keywords-cell {
  display: flex;
  flex-wrap: wrap;
  gap: 2px;
  align-items: center;
}

.more-tag {
  font-size: 12px;
  color: #909399;
  margin-left: 4px;
}

.text-muted {
  color: #c0c4cc;
}

/* 表单提示 */
.form-tip {
  font-size: 12px;
  color: #909399;
  margin-top: 4px;
}

.tip-good { color: #67c23a; }
.tip-warning { color: #e6a23c; }

/* 搜索预览样式（模拟Google） */
.search-preview-item {
  background: #fff;
  padding: 16px 20px;
  border-radius: 8px;
  margin-bottom: 12px;
  border: 1px solid #ebeef5;
}

.preview-in-dialog {
  background: #f8f9fa;
}

.preview-url {
  font-size: 12px;
  color: #202124;
  margin-bottom: 2px;
  font-family: Arial, sans-serif;
}

.preview-title {
  font-size: 18px;
  color: #1a0dab;
  line-height: 1.3;
  margin-bottom: 4px;
  cursor: pointer;
}

.preview-title:hover {
  text-decoration: underline;
}

.preview-desc {
  font-size: 13px;
  color: #4d5156;
  line-height: 1.5;
}

.preview-tip {
  color: #909399;
  font-size: 13px;
  margin-bottom: 16px;
}

/* Robots编辑区 */
.robots-section {
  background: #fff;
  padding: 24px;
  border-radius: 8px;
  border: 1px solid #ebeef5;
}

.robots-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.robots-header h3 {
  margin: 0;
  font-size: 16px;
}

.robots-editor :deep(.el-textarea__inner) {
  font-family: 'Courier New', monospace;
  font-size: 14px;
  line-height: 1.6;
}

.robots-tips {
  margin-top: 12px;
  padding: 12px;
  background: #f4f4f5;
  border-radius: 4px;
  font-size: 13px;
  color: #606266;
}

.robots-tips p {
  margin: 0 0 4px;
}

.robots-tips ul {
  margin: 0;
  padding-left: 20px;
}

.robots-tips li {
  margin: 2px 0;
}

.robots-tips code {
  background: #e6e8eb;
  padding: 1px 4px;
  border-radius: 3px;
  font-size: 12px;
}

.preview-section {
  padding: 16px 0;
}
</style>
