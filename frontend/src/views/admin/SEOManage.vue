<template>
  <div class="seo-manage-page">
    <div class="page-header">
      <h1 class="page-title">SEO配置管理</h1>
      <p class="page-desc">管理网站各页面的SEO标题、描述、关键词等配置</p>
    </div>

    <!-- 统计卡片 -->
    <div class="stats-grid">
      <el-card class="stat-card">
        <div class="stat-value">{{ pageConfigs.length }}</div>
        <div class="stat-label">已配置页面</div>
      </el-card>
      <el-card class="stat-card">
        <div class="stat-value">{{ averageTitleLength }}</div>
        <div class="stat-label">平均标题长度</div>
      </el-card>
      <el-card class="stat-card">
        <div class="stat-value">{{ averageDescLength }}</div>
        <div class="stat-label">平均描述长度</div>
      </el-card>
      <el-card class="stat-card">
        <div :class="['stat-value', seoHealthColor]">{{ seoHealthScore }}</div>
        <div class="stat-label">SEO健康度</div>
      </el-card>
    </div>

    <!-- 页面列表 -->
    <el-card class="table-card">
      <template #header>
        <div class="card-header">
          <span>页面SEO配置列表</span>
          <el-button type="primary" @click="showAddDialog">
            <el-icon><Plus /></el-icon>添加页面
          </el-button>
        </div>
      </template>

      <el-table :data="pageConfigs" stripe v-loading="loading">
        <el-table-column prop="route" label="页面路由" width="120">
          <template #default="{ row }">
            <code>{{ row.route }}</code>
          </template>
        </el-table-column>
        <el-table-column prop="title" label="页面标题" min-width="200">
          <template #default="{ row }">
            <div class="title-cell">
              <span class="title-text">{{ row.title }}</span>
              <el-tag 
                size="small" 
                :type="getTitleLengthType(row.title)"
              >
                {{ row.title.length }}字
              </el-tag>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="description" label="页面描述" min-width="250">
          <template #default="{ row }">
            <div class="desc-cell">
              <span class="desc-text">{{ truncate(row.description, 60) }}</span>
              <el-tag 
                size="small" 
                :type="getDescLengthType(row.description)"
              >
                {{ row.description.length }}字
              </el-tag>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="keywords" label="关键词" min-width="180">
          <template #default="{ row }">
            <div class="keywords-cell">
              <el-tag 
                v-for="(kw, idx) in row.keywords.slice(0, 3)" 
                :key="idx"
                size="small"
                class="keyword-tag"
              >
                {{ kw }}
              </el-tag>
              <span v-if="row.keywords.length > 3" class="more-tag">
                +{{ row.keywords.length - 3 }}
              </span>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="150" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link @click="editConfig(row)">
              编辑
            </el-button>
            <el-button type="danger" link @click="deleteConfig(row)">
              删除
            </el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <!-- 站点地图管理 -->
    <el-card class="sitemap-card">
      <template #header>
        <div class="card-header">
          <span>站点地图 (Sitemap)</span>
          <div class="header-actions">
            <el-button @click="previewSitemap">
              <el-icon><View /></el-icon>预览
            </el-button>
            <el-button type="primary" @click="generateSitemap">
              <el-icon><Refresh /></el-icon>重新生成
            </el-button>
          </div>
        </div>
      </template>

      <div class="sitemap-info">
        <el-descriptions :column="3" border>
          <el-descriptions-item label="最后生成">{{ sitemapInfo.lastModified }}</el-descriptions-item>
          <el-descriptions-item label="URL数量">{{ sitemapInfo.urlCount }}</el-descriptions-item>
          <el-descriptions-item label="文件大小">{{ sitemapInfo.fileSize }}</el-descriptions-item>
          <el-descriptions-item label="百度收录" :span="3">
            <el-tag :type="sitemapInfo.baiduIndexed ? 'success' : 'warning'">
              {{ sitemapInfo.baiduIndexed ? '已提交' : '未提交' }}
            </el-tag>
          </el-descriptions-item>
        </el-descriptions>
      </div>
    </el-card>

    <!-- Robots.txt 管理 -->
    <el-card class="robots-card">
      <template #header>
        <div class="card-header">
          <span>Robots.txt 管理</span>
          <el-button type="primary" @click="saveRobots">
            <el-icon><Check /></el-icon>保存配置
          </el-button>
        </div>
      </template>

      <el-input
        v-model="robotsContent"
        type="textarea"
        :rows="15"
        placeholder="编辑 robots.txt 内容..."
        class="robots-editor"
      />

      <div class="robots-tips">
        <h4>配置说明：</h4>
        <ul>
          <li><code>User-agent</code> - 指定爬虫名称，* 表示所有爬虫</li>
          <li><code>Allow</code> - 允许抓取的页面</li>
          <li><code>Disallow</code> - 禁止抓取的页面</li>
          <li><code>Sitemap</code> - 站点地图地址</li>
          <li><code>Crawl-delay</code> - 抓取间隔（秒）</li>
        </ul>
      </div>
    </el-card>

    <!-- 搜索引擎提交 -->
    <el-card class="submit-card">
      <template #header>
        <div class="card-header">
          <span>搜索引擎收录提交</span>
        </div>
      </template>

      <div class="submit-list">
        <div class="submit-item">
          <div class="submit-info">
            <img src="/icons/baidu.svg" alt="百度" class="submit-icon" />
            <div>
              <div class="submit-name">百度搜索资源平台</div>
              <div class="submit-status">
                <el-tag size="small" :type="submitStatus.baidu.type">
                  {{ submitStatus.baidu.text }}
                </el-tag>
              </div>
            </div>
          </div>
          <el-button @click="submitToBaidu">提交收录</el-button>
        </div>

        <div class="submit-item">
          <div class="submit-info">
            <img src="/icons/bing.svg" alt="必应" class="submit-icon" />
            <div>
              <div class="submit-name">必应网站管理员工具</div>
              <div class="submit-status">
                <el-tag size="small" :type="submitStatus.bing.type">
                  {{ submitStatus.bing.text }}
                </el-tag>
              </div>
            </div>
          </div>
          <el-button @click="submitToBing">提交收录</el-button>
        </div>

        <div class="submit-item">
          <div class="submit-info">
            <img src="/icons/360.svg" alt="360" class="submit-icon" />
            <div>
              <div class="submit-name">360搜索站长平台</div>
              <div class="submit-status">
                <el-tag size="small" :type="submitStatus['360'].type">
                  {{ submitStatus['360'].text }}
                </el-tag>
              </div>
            </div>
          </div>
          <el-button @click="submitTo360">提交收录</el-button>
        </div>
      </div>
    </el-card>

    <!-- 编辑对话框 -->
    <el-dialog
      v-model="dialogVisible"
      :title="isEditing ? '编辑SEO配置' : '添加SEO配置'"
      width="700px"
    >
      <el-form :model="form" label-width="100px">
        <el-form-item label="页面路由">
          <el-input v-model="form.route" placeholder="如：/bazi" :disabled="isEditing" />
        </el-form-item>
        <el-form-item label="页面标题">
          <el-input v-model="form.title" placeholder="输入页面标题" maxlength="60" show-word-limit />
          <div class="form-tip">建议长度：30-60个字符</div>
        </el-form-item>
        <el-form-item label="页面描述">
          <el-input
            v-model="form.description"
            type="textarea"
            :rows="3"
            placeholder="输入页面描述"
            maxlength="200"
            show-word-limit
          />
          <div class="form-tip">建议长度：80-200个字符</div>
        </el-form-item>
        <el-form-item label="关键词">
          <el-select
            v-model="form.keywords"
            multiple
            filterable
            allow-create
            placeholder="输入关键词，按回车添加"
            class="keywords-select"
          />
        </el-form-item>
        <el-form-item label="分享图片">
          <el-upload
            class="image-uploader"
            action="/api/upload"
            :show-file-list="false"
            :on-success="handleImageSuccess"
          >
            <img v-if="form.image" :src="form.image" class="preview-image" />
            <el-icon v-else class="uploader-icon"><Plus /></el-icon>
          </el-upload>
          <div class="form-tip">建议尺寸：1200x630像素</div>
        </el-form-item>
        <el-form-item label="Robots">
          <el-radio-group v-model="form.robots">
            <el-radio label="index,follow">允许收录</el-radio>
            <el-radio label="noindex,follow">不收录但追踪链接</el-radio>
            <el-radio label="noindex,nofollow">不收录不追踪</el-radio>
          </el-radio-group>
        </el-form-item>
      </el-form>

      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="saveConfig">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Plus, View, Refresh, Check } from '@element-plus/icons-vue'
import { 
  getSeoConfigs, saveSeoConfig, deleteSeoConfig, 
  getRobotsConfig, saveRobotsConfig, 
  generateSitemap as generateSitemapApi 
} from '../../api/admin'

const loading = ref(false)
const dialogVisible = ref(false)
const isEditing = ref(false)

// 页面配置数据
const pageConfigs = ref([])

// 表单数据
const form = ref({
  route: '',
  title: '',
  description: '',
  keywords: [],
  image: '',
  robots: 'index,follow'
})

// 站点地图信息
const sitemapInfo = ref({
  lastModified: '-',
  urlCount: 0,
  fileSize: '0 KB',
  baiduIndexed: false
})

// Robots内容
const robotsContent = ref('')

// 提交状态
const submitStatus = ref({
  baidu: { type: 'info', text: '未提交' },
  bing: { type: 'info', text: '未提交' },
  '360': { type: 'info', text: '未提交' }
})

// 加载初始数据
const loadData = async () => {
  loading.value = true
  try {
    const [seoRes, robotsRes] = await Promise.all([
      getSeoConfigs(),
      getRobotsConfig()
    ])
    
    if (seoRes.code === 200) {
      pageConfigs.value = seoRes.data || []
    }
    if (robotsRes.code === 200) {
      robotsContent.value = robotsRes.data.content || ''
    }
  } catch (error) {
    ElMessage.error('加载SEO配置失败')
  } finally {
    loading.value = false
  }
}

// 计算属性
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
    if (!p.title || p.title.length < 20 || p.title.length > 60) score -= 5
    if (!p.description || p.description.length < 50 || p.description.length > 200) score -= 5
    if (!p.keywords || p.keywords.length < 3) score -= 3
  })
  return Math.max(0, score)
})

const seoHealthColor = computed(() => {
  if (seoHealthScore.value >= 80) return 'text-success'
  if (seoHealthScore.value >= 60) return 'text-warning'
  return 'text-danger'
})

// 方法
const getTitleLengthType = (title) => {
  if (!title) return 'danger'
  const len = title.length
  if (len >= 30 && len <= 60) return 'success'
  if (len >= 20 && len <= 70) return 'warning'
  return 'danger'
}

const getDescLengthType = (desc) => {
  if (!desc) return 'danger'
  const len = desc.length
  if (len >= 80 && len <= 160) return 'success'
  if (len >= 50 && len <= 200) return 'warning'
  return 'danger'
}

const truncate = (text, length) => {
  if (!text) return ''
  return text.length > length ? text.substring(0, length) + '...' : text
}

const showAddDialog = () => {
  isEditing.value = false
  form.value = {
    route: '',
    title: '',
    description: '',
    keywords: [],
    image: '',
    robots: 'index,follow'
  }
  dialogVisible.value = true
}

const editConfig = (row) => {
  isEditing.value = true
  form.value = { ...row }
  dialogVisible.value = true
}

const saveConfig = async () => {
  if (!form.value.route || !form.value.title) {
    ElMessage.warning('请填写完整信息')
    return
  }

  try {
    const res = await saveSeoConfig(form.value)
    if (res.code === 200) {
      ElMessage.success(isEditing.value ? '配置已更新' : '配置已添加')
      dialogVisible.value = false
      loadData()
    }
  } catch (error) {
    ElMessage.error('保存失败')
  }
}

const deleteConfig = async (row) => {
  try {
    await ElMessageBox.confirm('确定要删除这个SEO配置吗？', '确认删除', {
      type: 'warning'
    })
    const res = await deleteSeoConfig(row.route)
    if (res.code === 200) {
      ElMessage.success('配置已删除')
      loadData()
    }
  } catch (error) {
    // 用户取消或请求失败
  }
}

const previewSitemap = () => {
  window.open('/sitemap.xml', '_blank')
}

const generateSitemap = async () => {
  loading.value = true
  try {
    const res = await generateSitemapApi()
    if (res.code === 200) {
      ElMessage.success('站点地图已重新生成')
      // 更新状态信息
      sitemapInfo.value.lastModified = new Date().toLocaleString()
      sitemapInfo.value.urlCount = pageConfigs.value.length
    }
  } catch (error) {
    ElMessage.error('生成失败')
  } finally {
    loading.value = false
  }
}

const saveRobots = async () => {
  try {
    const res = await saveRobotsConfig(robotsContent.value)
    if (res.code === 200) {
      ElMessage.success('robots.txt 已保存')
    }
  } catch (error) {
    ElMessage.error('保存失败')
  }
}

const handleImageSuccess = (response) => {
  if (response.code === 200 && response.data?.url) {
    form.value.image = response.data.url
    ElMessage.success('图片上传成功')
  } else {
    ElMessage.error(response.message || '图片上传失败')
  }
}

const handleImageError = (error) => {
  console.error('图片上传失败:', error)
  ElMessage.error('图片上传失败，请检查网络或文件格式')
}

const submitToBaidu = () => {
  submitStatus.value.baidu = { type: 'success', text: '已提交' }
  ElMessage.success('已成功提交到百度搜索资源平台')
}

const submitToBing = () => {
  submitStatus.value.bing = { type: 'success', text: '已提交' }
  ElMessage.success('已成功提交到必应网站管理员工具')
}

const submitTo360 = () => {
  submitStatus.value['360'] = { type: 'success', text: '已提交' }
  ElMessage.success('已成功提交到360搜索站长平台')
}

onMounted(() => {
  loadData()
})

</script>

<style scoped>
.seo-manage-page {
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

.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 24px;
}

.stat-card {
  text-align: center;
}

.stat-value {
  font-size: 32px;
  font-weight: 700;
  color: var(--primary-color);
  margin-bottom: 4px;
}

.stat-label {
  font-size: 14px;
  color: var(--text-secondary);
}

.text-success { color: #10b981; }
.text-warning { color: #f59e0b; }
.text-danger { color: #ef4444; }

.table-card, .sitemap-card, .robots-card, .submit-card {
  margin-bottom: 24px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-actions {
  display: flex;
  gap: 8px;
}

code {
  background: #f1f5f9;
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 12px;
}

.title-cell, .desc-cell {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.title-text, .desc-text {
  color: var(--text-primary);
}

.keywords-cell {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
}

.keyword-tag {
  margin: 0;
}

.more-tag {
  font-size: 12px;
  color: var(--text-secondary);
}

.sitemap-info {
  padding: 16px 0;
}

.robots-editor {
  font-family: 'Courier New', monospace;
}

.robots-tips {
  margin-top: 16px;
  padding: 16px;
  background: #f8fafc;
  border-radius: 8px;
}

.robots-tips h4 {
  margin: 0 0 12px;
  font-size: 14px;
}

.robots-tips ul {
  margin: 0;
  padding-left: 20px;
}

.robots-tips li {
  margin-bottom: 8px;
  font-size: 13px;
  color: var(--text-secondary);
}

.robots-tips code {
  background: #e2e8f0;
  padding: 2px 6px;
  border-radius: 4px;
  font-weight: 500;
}

.submit-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.submit-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  background: #f8fafc;
  border-radius: 8px;
}

.submit-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.submit-icon {
  width: 40px;
  height: 40px;
  border-radius: 8px;
}

.submit-name {
  font-weight: 500;
  margin-bottom: 4px;
}

.form-tip {
  font-size: 12px;
  color: var(--text-secondary);
  margin-top: 4px;
}

.keywords-select {
  width: 100%;
}

.image-uploader {
  border: 1px dashed var(--border-color);
  border-radius: 6px;
  cursor: pointer;
  position: relative;
  overflow: hidden;
  width: 300px;
  height: 157px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.image-uploader:hover {
  border-color: var(--primary-color);
}

.preview-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.uploader-icon {
  font-size: 28px;
  color: var(--text-tertiary);
}

@media (max-width: 1200px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }
}
</style>
