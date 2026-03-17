<template>
  <div class="app-container knowledge-page">
    <div class="page-actions">
      <el-space wrap>
        <el-button @click="reloadPage" :loading="loading || categoryLoading">刷新</el-button>
        <el-button type="primary" :disabled="readonlyMode" @click="handleAddArticle">新增文章</el-button>
        <el-button type="success" :disabled="readonlyMode" @click="handleAddCategory">新增分类</el-button>
      </el-space>
    </div>

    <el-card v-if="pageError" shadow="never" class="page-state-card">
      <el-result icon="warning" :title="pageError.title" :sub-title="pageError.description">
        <template #extra>
          <el-button type="primary" :loading="loading || categoryLoading" @click="reloadPage">重新加载</el-button>
        </template>
      </el-result>
    </el-card>

    <template v-else>
      <el-row :gutter="20">
        <el-col :xs="24" :lg="8">
          <el-card shadow="never" class="category-card">
            <template #header>
              <div class="card-header">
                <div>
                  <div class="section-title">知识库分类</div>
                  <div class="section-subtitle">维护文章树、父子分类与启用状态。</div>
                </div>
                <el-tag type="info" effect="plain">{{ categoryList.length }} 个分类</el-tag>
              </div>
            </template>

            <el-table v-loading="categoryLoading" :data="categoryList" size="small" stripe>
              <el-table-column prop="name" label="分类" min-width="160">
                <template #default="{ row }">
                  <div class="category-name-cell">
                    <span :style="{ paddingLeft: `${row.depth * 16}px` }">{{ row.name }}</span>
                    <el-tag v-if="row.article_count" size="small" type="info">{{ row.article_count }}</el-tag>
                  </div>
                </template>
              </el-table-column>
              <el-table-column prop="status" label="状态" width="76" align="center">
                <template #default="{ row }">
                  <el-tag :type="row.status === 1 ? 'success' : 'info'">{{ row.status === 1 ? '启用' : '停用' }}</el-tag>
                </template>
              </el-table-column>
              <el-table-column label="操作" width="132" fixed="right">
                <template #default="{ row }">
                  <el-button link type="primary" :disabled="readonlyMode" @click="handleEditCategory(row)">编辑</el-button>
                  <el-button link type="danger" :disabled="readonlyMode" @click="handleDeleteCategory(row)">删除</el-button>
                </template>
              </el-table-column>
            </el-table>
          </el-card>
        </el-col>

        <el-col :xs="24" :lg="16">
          <el-card shadow="never">
            <template #header>
              <div class="card-header article-header">
                <div>
                  <div class="section-title">知识库文章</div>
                  <div class="section-subtitle">支持文章发布、草稿维护、热门标记与分类归档。</div>
                </div>
                <el-tag type="warning" effect="plain">共 {{ total }} 篇</el-tag>
              </div>
            </template>

            <el-form :model="queryForm" inline class="filter-form">
              <el-form-item label="关键词">
                <el-input v-model="queryForm.keyword" clearable placeholder="搜索标题 / 摘要" @keyup.enter="handleSearch" />
              </el-form-item>
              <el-form-item label="分类">
                <el-select v-model="queryForm.category_id" clearable placeholder="全部分类" style="width: 220px">
                  <el-option
                    v-for="item in categoryOptions"
                    :key="item.id"
                    :label="item.label"
                    :value="item.id"
                  />
                </el-select>
              </el-form-item>
              <el-form-item>
                <el-button type="primary" @click="handleSearch">搜索</el-button>
                <el-button @click="handleReset">重置</el-button>
              </el-form-item>
            </el-form>

            <el-table v-loading="loading" :data="articleList" stripe>
              <el-table-column prop="title" label="标题" min-width="220" show-overflow-tooltip />
              <el-table-column prop="category_name" label="分类" width="140" show-overflow-tooltip>
                <template #default="{ row }">
                  <span>{{ row.category_name || '-' }}</span>
                </template>
              </el-table-column>
              <el-table-column prop="status" label="状态" width="110">
                <template #default="{ row }">
                  <el-tag :type="getStatusType(row.status)">{{ getStatusText(row.status) }}</el-tag>
                </template>
              </el-table-column>
              <el-table-column prop="is_hot" label="热门" width="90" align="center">
                <template #default="{ row }">
                  <el-tag :type="row.is_hot === 1 ? 'danger' : 'info'">{{ row.is_hot === 1 ? '是' : '否' }}</el-tag>
                </template>
              </el-table-column>
              <el-table-column prop="author_name" label="作者" width="120" show-overflow-tooltip>
                <template #default="{ row }">
                  <span>{{ row.author_name || '-' }}</span>
                </template>
              </el-table-column>
              <el-table-column prop="published_at" label="发布时间" width="168">
                <template #default="{ row }">
                  <span>{{ row.published_at || '-' }}</span>
                </template>
              </el-table-column>
              <el-table-column prop="updated_at" label="更新时间" width="168">
                <template #default="{ row }">
                  <span>{{ row.updated_at || '-' }}</span>
                </template>
              </el-table-column>
              <el-table-column label="操作" width="180" fixed="right">
                <template #default="{ row }">
                  <el-button link type="primary" @click="handlePreview(row)">预览</el-button>
                  <el-button link type="primary" :disabled="readonlyMode" @click="handleEditArticle(row)">编辑</el-button>
                  <el-button link type="danger" :disabled="readonlyMode" @click="handleDeleteArticle(row)">删除</el-button>
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
          </el-card>
        </el-col>
      </el-row>
    </template>

    <el-dialog v-model="articleDialog.visible" :title="articleDialog.form.id ? '编辑文章' : '新增文章'" width="860px" destroy-on-close>
      <el-form ref="articleFormRef" :model="articleDialog.form" :rules="articleRules" label-width="96px" :disabled="readonlyMode">
        <el-form-item label="文章标题" prop="title">
          <el-input v-model="articleDialog.form.title" maxlength="200" show-word-limit placeholder="请输入文章标题" />
        </el-form-item>
        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="所属分类" prop="category_id">
              <el-select v-model="articleDialog.form.category_id" placeholder="请选择分类" style="width: 100%">
                <el-option
                  v-for="item in enabledCategoryOptions"
                  :key="item.id"
                  :label="item.label"
                  :value="item.id"
                />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="文章标识" prop="slug">
              <el-input v-model="articleDialog.form.slug" placeholder="留空将自动生成" />
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="发布状态" prop="status">
              <el-select v-model="articleDialog.form.status" style="width: 100%">
                <el-option v-for="item in articleStatusOptions" :key="item.value" :label="item.label" :value="item.value" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="发布时间">
              <el-date-picker
                v-model="articleDialog.form.published_at"
                type="datetime"
                value-format="YYYY-MM-DD HH:mm:ss"
                placeholder="留空则按当前时间"
                style="width: 100%"
              />
            </el-form-item>
          </el-col>
        </el-row>
        <el-form-item label="封面地址">
          <el-input v-model="articleDialog.form.thumbnail" placeholder="可选：文章封面 URL" />
        </el-form-item>
        <el-form-item label="文章摘要">
          <el-input
            v-model="articleDialog.form.summary"
            type="textarea"
            :rows="3"
            maxlength="500"
            show-word-limit
            placeholder="用于列表展示和 SEO 摘要"
          />
        </el-form-item>
        <el-form-item label="热门推荐">
          <el-switch v-model="articleDialog.form.is_hot" :active-value="1" :inactive-value="0" />
        </el-form-item>
        <el-form-item label="文章正文" prop="content">
          <el-input
            v-model="articleDialog.form.content"
            type="textarea"
            :rows="16"
            placeholder="请输入正文内容，支持直接粘贴 Markdown / 富文本源内容"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="articleDialog.visible = false">取消</el-button>
        <el-button type="primary" :loading="submittingArticle" :disabled="readonlyMode" @click="handleSubmitArticle">保存</el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="categoryDialog.visible" :title="categoryDialog.form.id ? '编辑分类' : '新增分类'" width="560px" destroy-on-close>
      <el-form ref="categoryFormRef" :model="categoryDialog.form" :rules="categoryRules" label-width="96px" :disabled="readonlyMode">
        <el-form-item label="分类名称" prop="name">
          <el-input v-model="categoryDialog.form.name" maxlength="100" show-word-limit placeholder="请输入分类名称" />
        </el-form-item>
        <el-form-item label="上级分类">
          <el-select v-model="categoryDialog.form.parent_id" clearable placeholder="顶级分类" style="width: 100%">
            <el-option label="顶级分类" :value="0" />
            <el-option
              v-for="item in availableParentOptions"
              :key="item.id"
              :label="item.label"
              :value="item.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="分类标识" prop="slug">
          <el-input v-model="categoryDialog.form.slug" placeholder="留空将自动生成" />
        </el-form-item>
        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="排序值">
              <el-input-number v-model="categoryDialog.form.sort_order" :min="0" :max="9999" style="width: 100%" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="启用状态">
              <el-radio-group v-model="categoryDialog.form.status">
                <el-radio :label="1">启用</el-radio>
                <el-radio :label="0">停用</el-radio>
              </el-radio-group>
            </el-form-item>
          </el-col>
        </el-row>
        <el-form-item label="分类描述">
          <el-input
            v-model="categoryDialog.form.description"
            type="textarea"
            :rows="4"
            maxlength="255"
            show-word-limit
            placeholder="请输入分类说明"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="categoryDialog.visible = false">取消</el-button>
        <el-button type="primary" :loading="submittingCategory" :disabled="readonlyMode" @click="handleSubmitCategory">保存</el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="previewDialog.visible" title="文章预览" width="860px" destroy-on-close>
      <template v-if="previewDialog.article">
        <div class="preview-meta">
          <div class="preview-title">{{ previewDialog.article.title }}</div>
          <div class="preview-subtitle">
            <span>分类：{{ previewDialog.article.category_name || '-' }}</span>
            <span>状态：{{ getStatusText(previewDialog.article.status) }}</span>
            <span>发布时间：{{ previewDialog.article.published_at || '-' }}</span>
          </div>
        </div>
        <el-divider />
        <div v-if="previewDialog.article.summary" class="preview-summary">{{ previewDialog.article.summary }}</div>
        <div class="preview-content" v-html="renderArticleContent(previewDialog.article.content)"></div>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { createReadonlyErrorState } from '@/utils/page-error'
import {
  createKnowledgeArticle,
  deleteKnowledgeArticle,
  deleteKnowledgeCategory,
  getKnowledgeArticleDetail,
  getKnowledgeArticles,
  getKnowledgeCategories,
  saveKnowledgeCategory,
  updateKnowledgeArticle
} from '@/api/knowledge'

const loading = ref(false)
const categoryLoading = ref(false)
const submittingArticle = ref(false)
const submittingCategory = ref(false)
const total = ref(0)
const articleList = ref([])
const categoryList = ref([])
const pageError = ref(null)
const articleFormRef = ref(null)
const categoryFormRef = ref(null)

const queryForm = reactive({
  keyword: '',
  category_id: undefined,
  page: 1,
  pageSize: 20
})

const articleDialog = reactive({
  visible: false,
  form: createDefaultArticleForm()
})

const categoryDialog = reactive({
  visible: false,
  form: createDefaultCategoryForm()
})

const previewDialog = reactive({
  visible: false,
  article: null
})

const articleStatusOptions = [
  { label: '草稿', value: 0 },
  { label: '已发布', value: 1 },
  { label: '定时发布', value: 2 },
  { label: '已归档', value: 3 }
]

const articleRules = {
  title: [{ required: true, message: '请输入文章标题', trigger: 'blur' }],
  category_id: [{ required: true, message: '请选择文章分类', trigger: 'change' }],
  content: [{ required: true, message: '请输入文章正文', trigger: 'blur' }]
}

const categoryRules = {
  name: [{ required: true, message: '请输入分类名称', trigger: 'blur' }]
}

const readonlyMode = computed(() => Boolean(pageError.value))

const categoryOptions = computed(() => categoryList.value.map(item => ({
  id: item.id,
  label: `${'　'.repeat(Math.max(item.depth, 0))}${item.name}`,
  status: item.status
})))

const enabledCategoryOptions = computed(() => categoryOptions.value.filter(item => item.status === 1))

const availableParentOptions = computed(() => {
  const currentId = Number(categoryDialog.form.id || 0)
  const disabledIds = new Set([currentId, ...collectChildCategoryIds(currentId)])
  return categoryOptions.value.filter(item => !disabledIds.has(item.id))
})

function createDefaultArticleForm() {
  return {
    id: undefined,
    title: '',
    category_id: undefined,
    slug: '',
    summary: '',
    content: '',
    thumbnail: '',
    status: 0,
    is_hot: 0,
    published_at: ''
  }
}

function createDefaultCategoryForm() {
  return {
    id: undefined,
    name: '',
    slug: '',
    description: '',
    parent_id: 0,
    sort_order: 0,
    status: 1
  }
}

function ensureWritable(message) {
  if (!readonlyMode.value) {
    return true
  }
  ElMessage.warning(message)
  return false
}

function normalizeArticleRow(row = {}) {
  return {
    id: Number(row.id) || undefined,
    category_id: Number(row.category_id) || undefined,
    category_name: String(row.category_name || ''),
    title: String(row.title || ''),
    slug: String(row.slug || ''),
    summary: String(row.summary || ''),
    content: String(row.content || ''),
    thumbnail: String(row.thumbnail || ''),
    status: Number(row.status ?? 0),
    is_hot: Number(row.is_hot ?? 0) === 1 ? 1 : 0,
    author_name: String(row.author_name || ''),
    published_at: String(row.published_at || ''),
    created_at: String(row.created_at || ''),
    updated_at: String(row.updated_at || '')
  }
}

function flattenCategoryTree(tree = [], depth = 0, container = []) {
  tree.forEach(item => {
    const normalized = {
      id: Number(item.id) || 0,
      name: String(item.name || ''),
      slug: String(item.slug || ''),
      description: String(item.description || ''),
      parent_id: Number(item.parent_id || 0),
      sort_order: Number(item.sort_order || 0),
      status: Number(item.status || 0),
      article_count: Number(item.article_count || 0),
      depth,
      updated_at: String(item.updated_at || ''),
      created_at: String(item.created_at || '')
    }
    container.push(normalized)
    if (Array.isArray(item.children) && item.children.length > 0) {
      flattenCategoryTree(item.children, depth + 1, container)
    }
  })
  return container
}

function collectChildCategoryIds(parentId) {
  if (!parentId) {
    return []
  }

  const ids = []
  const childrenMap = new Map()
  categoryList.value.forEach(item => {
    const key = Number(item.parent_id || 0)
    if (!childrenMap.has(key)) {
      childrenMap.set(key, [])
    }
    childrenMap.get(key).push(item.id)
  })

  const queue = [...(childrenMap.get(parentId) || [])]
  while (queue.length > 0) {
    const current = queue.shift()
    ids.push(current)
    queue.push(...(childrenMap.get(current) || []))
  }

  return ids
}

function getStatusType(status) {
  return {
    0: 'info',
    1: 'success',
    2: 'warning',
    3: 'danger'
  }[Number(status)] || 'info'
}

function getStatusText(status) {
  return {
    0: '草稿',
    1: '已发布',
    2: '定时发布',
    3: '已归档'
  }[Number(status)] || '未知状态'
}

async function loadCategories() {
  categoryLoading.value = true
  try {
    const res = await getKnowledgeCategories({ include_disabled: 1 }, { showErrorMessage: false })
    categoryList.value = flattenCategoryTree(Array.isArray(res?.data?.tree) ? res.data.tree : [])
  } finally {
    categoryLoading.value = false
  }
}

async function loadArticles() {
  loading.value = true
  try {
    const res = await getKnowledgeArticles({
      keyword: queryForm.keyword?.trim() || undefined,
      category_id: queryForm.category_id || undefined,
      page: queryForm.page,
      pageSize: queryForm.pageSize
    }, { showErrorMessage: false })
    articleList.value = Array.isArray(res?.data?.list) ? res.data.list.map(normalizeArticleRow) : []
    total.value = Number(res?.data?.total || 0)
  } finally {
    loading.value = false
  }
}

async function reloadPage() {
  try {
    await Promise.all([loadCategories(), loadArticles()])
    pageError.value = null
  } catch (error) {
    articleList.value = []
    categoryList.value = []
    total.value = 0
    articleDialog.visible = false
    categoryDialog.visible = false
    previewDialog.visible = false
    pageError.value = createReadonlyErrorState(error, '知识库文章', 'content_manage')
  }
}

function handleSearch() {
  queryForm.page = 1
  reloadPage()
}

function handleReset() {
  Object.assign(queryForm, {
    keyword: '',
    category_id: undefined,
    page: 1,
    pageSize: 20
  })
  reloadPage()
}

function handleSizeChange(pageSize) {
  queryForm.pageSize = pageSize
  queryForm.page = 1
  loadArticles().catch(handleReadonlyFailure)
}

function handleCurrentChange(page) {
  queryForm.page = page
  loadArticles().catch(handleReadonlyFailure)
}

function handleReadonlyFailure(error) {
  pageError.value = createReadonlyErrorState(error, '知识库文章', 'content_manage')
  articleDialog.visible = false
  categoryDialog.visible = false
}

function handleAddArticle() {
  if (!ensureWritable('知识库文章尚未成功加载，当前为只读保护状态')) {
    return
  }
  articleDialog.form = createDefaultArticleForm()
  articleDialog.visible = true
}

async function handleEditArticle(row) {
  if (!ensureWritable('知识库文章尚未成功加载，当前暂时无法编辑')) {
    return
  }

  try {
    const res = await getKnowledgeArticleDetail(row.id, { showErrorMessage: false })
    articleDialog.form = normalizeArticleRow(res?.data || row)
    articleDialog.visible = true
  } catch (error) {
    ElMessage.error(error.message || '加载文章详情失败')
  }
}

async function handleSubmitArticle() {
  if (!ensureWritable('知识库文章尚未成功加载，当前暂时无法保存')) {
    return
  }

  const valid = await articleFormRef.value?.validate().catch(() => false)
  if (!valid) {
    return
  }

  submittingArticle.value = true
  try {
    const payload = {
      title: articleDialog.form.title.trim(),
      category_id: articleDialog.form.category_id,
      slug: articleDialog.form.slug.trim(),
      summary: articleDialog.form.summary.trim(),
      content: articleDialog.form.content,
      thumbnail: articleDialog.form.thumbnail.trim(),
      status: articleDialog.form.status,
      is_hot: articleDialog.form.is_hot,
      published_at: articleDialog.form.published_at || undefined
    }

    if (articleDialog.form.id) {
      await updateKnowledgeArticle(articleDialog.form.id, payload, { showErrorMessage: false })
    } else {
      await createKnowledgeArticle(payload, { showErrorMessage: false })
    }

    ElMessage.success(articleDialog.form.id ? '文章更新成功' : '文章创建成功')
    articleDialog.visible = false
    await reloadPage()
  } catch (error) {
    ElMessage.error(error.message || '保存文章失败')
  } finally {
    submittingArticle.value = false
  }
}

async function handleDeleteArticle(row) {
  if (!ensureWritable('知识库文章尚未成功加载，当前暂时无法删除')) {
    return
  }

  try {
    await ElMessageBox.confirm(`确定删除文章「${row.title}」吗？`, '删除确认', {
      type: 'warning',
      confirmButtonText: '删除',
      cancelButtonText: '取消'
    })
    await deleteKnowledgeArticle(row.id, { showErrorMessage: false })
    ElMessage.success('文章已删除')
    if (articleList.value.length === 1 && queryForm.page > 1) {
      queryForm.page -= 1
    }
    await reloadPage()
  } catch (error) {
    if (error !== 'cancel' && error !== 'close') {
      ElMessage.error(error.message || '删除文章失败')
    }
  }
}

async function handlePreview(row) {
  try {
    const res = await getKnowledgeArticleDetail(row.id, { showErrorMessage: false })
    previewDialog.article = normalizeArticleRow(res?.data || row)
    previewDialog.visible = true
  } catch (error) {
    previewDialog.article = normalizeArticleRow(row)
    previewDialog.visible = true
  }
}

function handleAddCategory() {
  if (!ensureWritable('知识库分类尚未成功加载，当前为只读保护状态')) {
    return
  }
  categoryDialog.form = createDefaultCategoryForm()
  categoryDialog.visible = true
}

function handleEditCategory(row) {
  if (!ensureWritable('知识库分类尚未成功加载，当前暂时无法编辑')) {
    return
  }
  categoryDialog.form = {
    id: row.id,
    name: row.name,
    slug: row.slug,
    description: row.description,
    parent_id: row.parent_id,
    sort_order: row.sort_order,
    status: row.status
  }
  categoryDialog.visible = true
}

async function handleSubmitCategory() {
  if (!ensureWritable('知识库分类尚未成功加载，当前暂时无法保存')) {
    return
  }

  const valid = await categoryFormRef.value?.validate().catch(() => false)
  if (!valid) {
    return
  }

  submittingCategory.value = true
  try {
    await saveKnowledgeCategory({
      id: categoryDialog.form.id,
      name: categoryDialog.form.name.trim(),
      slug: categoryDialog.form.slug.trim(),
      description: categoryDialog.form.description.trim(),
      parent_id: Number(categoryDialog.form.parent_id || 0),
      sort_order: Number(categoryDialog.form.sort_order || 0),
      status: Number(categoryDialog.form.status || 0)
    }, { showErrorMessage: false })
    ElMessage.success(categoryDialog.form.id ? '分类更新成功' : '分类创建成功')
    categoryDialog.visible = false
    await reloadPage()
  } catch (error) {
    ElMessage.error(error.message || '保存分类失败')
  } finally {
    submittingCategory.value = false
  }
}

async function handleDeleteCategory(row) {
  if (!ensureWritable('知识库分类尚未成功加载，当前暂时无法删除')) {
    return
  }

  try {
    await ElMessageBox.confirm(`确定删除分类「${row.name}」吗？`, '删除确认', {
      type: 'warning',
      confirmButtonText: '删除',
      cancelButtonText: '取消'
    })
    await deleteKnowledgeCategory(row.id, { showErrorMessage: false })
    ElMessage.success('分类已删除')
    await reloadPage()
  } catch (error) {
    if (error !== 'cancel' && error !== 'close') {
      ElMessage.error(error.message || '删除分类失败')
    }
  }
}

function escapeHtml(content = '') {
  return String(content)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#39;')
}

function renderArticleContent(content = '') {
  return escapeHtml(content).replace(/\n/g, '<br />')
}

onMounted(() => {
  reloadPage()
})
</script>

<style scoped>
.knowledge-page {
  padding: 20px;
}

.page-actions {
  margin-bottom: 20px;
}

.page-state-card {
  margin-bottom: 20px;
}

.card-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.article-header {
  margin-bottom: 0;
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

.category-card {
  margin-bottom: 20px;
}

.category-name-cell {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.filter-form {
  margin-bottom: 16px;
}

.pagination-container {
  display: flex;
  justify-content: flex-end;
  margin-top: 20px;
}

.preview-meta {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.preview-title {
  color: #303133;
  font-size: 22px;
  font-weight: 600;
}

.preview-subtitle {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  color: #909399;
  font-size: 13px;
}

.preview-summary {
  margin-bottom: 16px;
  padding: 12px 16px;
  border-radius: 8px;
  background: #f5f7fa;
  color: #606266;
  line-height: 1.8;
}

.preview-content {
  color: #303133;
  line-height: 1.9;
  white-space: normal;
  word-break: break-word;
}
</style>
