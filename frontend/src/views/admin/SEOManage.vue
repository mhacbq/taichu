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

const loading = ref(false)
const dialogVisible = ref(false)
const isEditing = ref(false)

// 页面配置数据
const pageConfigs = ref([
  {
    route: '/',
    title: '太初命理 - 专业八字排盘_塔罗占卜_每日运势',
    description: '太初命理是专业的AI智能命理分析平台，提供八字排盘、塔罗占卜、每日运势等服务。',
    keywords: ['八字排盘', '塔罗占卜', '每日运势', '命理分析'],
    image: '/images/og-home.jpg',
    robots: 'index,follow'
  },
  {
    route: '/bazi',
    title: '免费八字排盘_在线生辰八字测算_专业命理分析',
    description: '免费在线八字排盘工具，输入出生日期即可生成专业八字命盘。',
    keywords: ['八字排盘', '免费八字', '生辰八字', '四柱八字'],
    image: '/images/og-bazi.jpg',
    robots: 'index,follow'
  },
  {
    route: '/tarot',
    title: '免费塔罗牌占卜_在线塔罗测试_AI智能解牌',
    description: '免费在线塔罗牌占卜，涵盖爱情、事业、财运等多个维度。',
    keywords: ['塔罗占卜', '塔罗牌', '塔罗测试', '免费塔罗'],
    image: '/images/og-tarot.jpg',
    robots: 'index,follow'
  },
  {
    route: '/daily',
    title: '今日运势查询_每日星座运势_黄历宜忌',
    description: '查看今日运势，包含十二星座每日运势、黄历宜忌、时辰吉凶。',
    keywords: ['今日运势', '每日运势', '星座运势', '黄历查询'],
    image: '/images/og-daily.jpg',
    robots: 'index,follow'
  }
])

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
  lastModified: '2026-03-15 10:30:00',
  urlCount: 6,
  fileSize: '2.4 KB',
  baiduIndexed: false
})

// Robots内容
const robotsContent = ref(`# robots.txt for 太初命理
User-agent: *
Allow: /
Disallow: /admin/
Disallow: /profile/
Disallow: /api/
Sitemap: https://taichu.chat/sitemap.xml
`)

// 提交状态
const submitStatus = ref({
  baidu: { type: 'info', text: '未提交' },
  bing: { type: 'info', text: '未提交' },
  '360': { type: 'info', text: '未提交' }
})

// 计算属性
const averageTitleLength = computed(() => {
  const total = pageConfigs.value.reduce((sum, p) => sum + p.title.length, 0)
  return Math.round(total / pageConfigs.value.length)
})

const averageDescLength = computed(() => {
  const total = pageConfigs.value.reduce((sum, p) => sum + p.description.length, 0)
  return Math.round(total / pageConfigs.value.length)
})

const seoHealthScore = computed(() => {
  let score = 100
  pageConfigs.value.forEach(p => {
    if (p.title.length < 20 || p.title.length > 60) score -= 5
    if (p.description.length < 50 || p.description.length > 200) score -= 5
    if (p.keywords.length < 3) score -= 3
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
  const len = title.length
  if (len >= 30 && len <= 60) return 'success'
  if (len >= 20 && len <= 70) return 'warning'
  return 'danger'
}

const getDescLengthType = (desc) => {
  const len = desc.length
  if (len >= 80 && len <= 160) return 'success'
  if (len >= 50 && len <= 200) return 'warning'
  return 'danger'
}

const truncate = (text, length) => {
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

  if (isEditing.value) {
    const index = pageConfigs.value.findIndex(p => p.route === form.value.route)
    if (index > -1) {
      pageConfigs.value[index] = { ...form.value }
    }
    ElMessage.success('配置已更新')
  } else {
    pageConfigs.value.push({ ...form.value })
    ElMessage.success('配置已添加')
  }

  dialogVisible.value = false
}

const deleteConfig = async (row) => {
  try {
    await ElMessageBox.confirm('确定要删除这个SEO配置吗？', '确认删除', {
      type: 'warning'
    })
    const index = pageConfigs.value.findIndex(p => p.route === row.route)
    if (index > -1) {
      pageConfigs.value.splice(index, 1)
    }
    ElMessage.success('配置已删除')
  } catch {
    // 用户取消
  }
}

const previewSitemap = () => {
  window.open('/sitemap.xml', '_blank')
}

const generateSitemap = async () => {
  loading.value = true
  // 模拟生成
  await new Promise(resolve => setTimeout(resolve, 1500))
  sitemapInfo.value.lastModified = new Date().toLocaleString()
  sitemapInfo.value.urlCount = pageConfigs.value.length
  loading.value = false
  ElMessage.success('站点地图已重新生成')
}

const saveRobots = async () => {
  // 模拟保存
  await new Promise(resolve => setTimeout(resolve, 500))
  ElMessage.success('robots.txt 已保存')
}

const handleImageSuccess = (response) => {
  form.value.image = response.url
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
  // 加载数据
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
