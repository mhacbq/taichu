<template>
  <div class="app-container">
    <!-- 统计卡片 -->
    <el-row :gutter="20" class="mb-4">
      <el-col :span="6">
        <el-card shadow="hover" class="stat-card">
          <div class="stat-value">{{ pageConfigs.length }}</div>
          <div class="stat-label">已配置页面</div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover" class="stat-card">
          <div class="stat-value">{{ averageTitleLength }}</div>
          <div class="stat-label">平均标题长度</div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover" class="stat-card">
          <div class="stat-value">{{ averageDescLength }}</div>
          <div class="stat-label">平均描述长度</div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover" class="stat-card">
          <div :class="['stat-value', seoHealthColor]">{{ seoHealthScore }}</div>
          <div class="stat-label">SEO健康度</div>
        </el-card>
      </el-col>
    </el-row>

    <el-tabs v-model="activeTab">
      <el-tab-pane label="页面SEO配置" name="pages">
        <el-card shadow="never">
          <template #header>
            <div class="card-header">
              <span>配置列表</span>
              <el-button type="primary" @click="showAddDialog">
                <el-icon><Plus /></el-icon>添加页面
              </el-button>
            </div>
          </template>

          <el-table :data="pageConfigs" stripe v-loading="loading" border>
            <el-table-column prop="route" label="页面路由" width="150">
              <template #default="{ row }">
                <code>{{ row.route }}</code>
              </template>
            </el-table-column>
            <el-table-column prop="title" label="页面标题" min-width="200" show-overflow-tooltip />
            <el-table-column prop="description" label="页面描述" min-width="250" show-overflow-tooltip />
            <el-table-column prop="keywords" label="关键词" min-width="180">
              <template #default="{ row }">
                <el-tag 
                  v-for="(kw, idx) in formatKeywords(row.keywords)" 
                  :key="idx"
                  size="small"
                  class="mr-1"
                >
                  {{ kw }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column label="操作" width="150" fixed="right">
              <template #default="{ row }">
                <el-button link type="primary" @click="editConfig(row)">编辑</el-button>
                <el-button link type="danger" @click="deleteConfig(row)">删除</el-button>
              </template>
            </el-table-column>
          </el-table>
        </el-card>
      </el-tab-pane>

      <el-tab-pane label="站点地图 & Robots" name="tools">
        <el-row :gutter="20">
          <el-col :span="12">
            <el-card shadow="never" class="tool-card">
              <template #header>
                <div class="card-header">
                  <span>Sitemap 管理</span>
                  <el-button type="primary" size="small" @click="handleGenerateSitemap">生成地图</el-button>
                </div>
              </template>
              <div class="tool-info">
                <p>站点地图地址: <a href="/sitemap.xml" target="_blank">/sitemap.xml</a></p>
                <p>建议在每次大规模内容更新后重新生成。</p>
              </div>
            </el-card>
          </el-col>
          <el-col :span="12">
            <el-card shadow="never" class="tool-card">
              <template #header>
                <div class="card-header">
                  <span>Robots.txt 管理</span>
                  <el-button type="primary" size="small" @click="saveRobots">保存 Robots</el-button>
                </div>
              </template>
              <el-input
                v-model="robotsContent"
                type="textarea"
                :rows="8"
                placeholder="编辑 robots.txt 内容..."
              />
            </el-card>
          </el-col>
        </el-row>
      </el-tab-pane>
    </el-tabs>

    <!-- 编辑对话框 -->
    <el-dialog
      v-model="dialogVisible"
      :title="isEditing ? '编辑SEO配置' : '添加SEO配置'"
      width="650px"
    >
      <el-form :model="form" label-width="100px">
        <el-form-item label="页面路由" required>
          <el-input v-model="form.route" placeholder="如：/bazi" :disabled="isEditing" />
        </el-form-item>
        <el-form-item label="页面标题" required>
          <el-input v-model="form.title" placeholder="建议30-60字符" maxlength="100" show-word-limit />
        </el-form-item>
        <el-form-item label="页面描述">
          <el-input
            v-model="form.description"
            type="textarea"
            :rows="3"
            placeholder="建议80-200字符"
            maxlength="255"
            show-word-limit
          />
        </el-form-item>
        <el-form-item label="关键词">
          <el-input v-model="form.keywords_input" placeholder="多个关键词用英文逗号分隔" />
        </el-form-item>
        <el-form-item label="Robots">
          <el-radio-group v-model="form.robots">
            <el-radio label="index,follow">允许收录</el-radio>
            <el-radio label="noindex,follow">不收录但追踪链接</el-radio>
          </el-radio-group>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="saveConfig" :loading="saving">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Plus, Search } from '@element-plus/icons-vue'
import { 
  getSeoConfigs, saveSeoConfig, deleteSeoConfig, 
  getRobotsConfig, saveRobotsConfig, 
  generateSitemap 
} from '@/api/siteContent'

const loading = ref(false)
const saving = ref(false)
const dialogVisible = ref(false)
const isEditing = ref(false)
const activeTab = ref('pages')
const pageConfigs = ref([])
const robotsContent = ref('')

const form = ref({
  route: '',
  title: '',
  description: '',
  keywords_input: '',
  robots: 'index,follow'
})

onMounted(() => {
  loadData()
})

async function loadData() {
  loading.value = true
  try {
    const [seoRes, robotsRes] = await Promise.all([
      getSeoConfigs(),
      getRobotsConfig()
    ])
    pageConfigs.value = seoRes.data || []
    robotsContent.value = robotsRes.data?.content || ''
  } catch (error) {
    ElMessage.error('加载SEO数据失败')
  } finally {
    loading.value = false
  }
}

const averageTitleLength = computed(() => {
  if (pageConfigs.value.length === 0) return 0
  const total = pageConfigs.value.reduce((sum, p) => sum + (p.title?.length || 0), 0)
  return Math.round(total / pageConfigs.value.length)
})

const averageDescLength = computed(() => {
  if (pageConfigs.value.length === 0) return 0
  const total = pageConfigs.value.reduce((sum, p) => sum + (p.description?.length || 0), 0)
  return Math.round(total / pageConfigs.value.length)
})

const seoHealthScore = computed(() => {
  if (pageConfigs.value.length === 0) return 0
  let score = 100
  pageConfigs.value.forEach(p => {
    if (!p.title || p.title.length < 20) score -= 5
    if (!p.description || p.description.length < 50) score -= 5
  })
  return Math.max(0, score)
})

const seoHealthColor = computed(() => {
  if (seoHealthScore.value >= 80) return 'text-success'
  if (seoHealthScore.value >= 60) return 'text-warning'
  return 'text-danger'
})

function formatKeywords(kw) {
  if (!kw) return []
  if (Array.isArray(kw)) return kw.slice(0, 5)
  return kw.split(',').slice(0, 5)
}

function showAddDialog() {
  isEditing.value = false
  form.value = {
    route: '',
    title: '',
    description: '',
    keywords_input: '',
    robots: 'index,follow'
  }
  dialogVisible.value = true
}

function editConfig(row) {
  isEditing.value = true
  form.value = { 
    ...row, 
    keywords_input: Array.isArray(row.keywords) ? row.keywords.join(',') : (row.keywords || '')
  }
  dialogVisible.value = true
}

async function saveConfig() {
  if (!form.value.route || !form.value.title) {
    ElMessage.warning('请填写路由和标题')
    return
  }
  
  saving.value = true
  try {
    const submitData = { ...form.value }
    submitData.keywords = submitData.keywords_input.split(',').map(k => k.trim()).filter(Boolean)
    delete submitData.keywords_input
    
    await saveSeoConfig(submitData)
    ElMessage.success('保存成功')
    dialogVisible.value = false
    loadData()
  } finally {
    saving.value = false
  }
}

async function deleteConfig(row) {
  try {
    await ElMessageBox.confirm('确定要删除该SEO配置吗？', '提示', { type: 'warning' })
    await deleteSeoConfig(row.route)
    ElMessage.success('删除成功')
    loadData()
  } catch (error) { /* cancel */ }
}

async function handleGenerateSitemap() {
  try {
    await generateSitemap()
    ElMessage.success('站点地图生成成功')
  } catch (error) {
    ElMessage.error('生成失败')
  }
}

async function saveRobots() {
  try {
    await saveRobotsConfig(robotsContent.value)
    ElMessage.success('Robots配置保存成功')
  } catch (error) {
    ElMessage.error('保存失败')
  }
}
</script>

<script>
export default {
  name: 'SeoManager'
}
</script>

<style lang="scss" scoped>
.stat-card {
  text-align: center;
}
.stat-value {
  font-size: 28px;
  font-weight: bold;
  margin-bottom: 5px;
}
.stat-label {
  color: #909399;
  font-size: 14px;
}
.text-success { color: #67c23a; }
.text-warning { color: #e6a23c; }
.text-danger { color: #f56c6c; }

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.tool-card {
  height: 100%;
}

.tool-info {
  margin-bottom: 15px;
  color: #606266;
  font-size: 14px;
}

code {
  background: #f4f4f5;
  padding: 2px 4px;
  border-radius: 4px;
  color: #e6a23c;
}
</style>
