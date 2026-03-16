<template>
  <div class="knowledge-manage">
    <div class="page-header">
      <h2>命理知识库</h2>
      <el-button type="primary" @click="openDialog()">
        <el-icon><Plus /></el-icon>
        新增文章
      </el-button>
    </div>

    <!-- 分类管理 -->
    <div class="category-section">
      <div class="section-title">知识分类</div>
      <div class="category-list">
        <el-tag
          v-for="cat in categories"
          :key="cat.id"
          class="category-tag"
          :type="selectedCategory === cat.id ? 'primary' : ''"
          closable
          @click="selectCategory(cat.id)"
          @close="deleteCategory(cat)"
        >
          {{ cat.name }} ({{ cat.count }})
        </el-tag>
        <el-button type="dashed" size="small" @click="addCategory">
          <el-icon><Plus /></el-icon>
          添加分类
        </el-button>
      </div>
    </div>

    <!-- 搜索筛选 -->
    <div class="filter-bar">
      <el-input
        v-model="searchKeyword"
        placeholder="搜索文章标题/内容"
        prefix-icon="Search"
        clearable
        style="width: 300px"
      />
      <el-select v-model="filterLevel" placeholder="难度筛选" clearable style="width: 150px">
        <el-option label="入门" value="beginner" />
        <el-option label="进阶" value="intermediate" />
        <el-option label="高级" value="advanced" />
      </el-select>
      <el-select v-model="filterStatus" placeholder="状态筛选" clearable style="width: 150px">
        <el-option label="已发布" :value="1" />
        <el-option label="草稿" :value="0" />
      </el-select>
    </div>

    <!-- 文章列表 -->
    <el-table v-loading="loading" :data="filteredList" style="width: 100%" border>
      <el-table-column type="index" width="50" />
      <el-table-column label="文章信息" min-width="300">
        <template #default="{ row }">
          <div class="article-info">
            <div class="article-title">{{ row.title }}</div>
            <div class="article-meta">
              <el-tag size="small">{{ row.categoryName }}</el-tag>
              <el-tag :type="getLevelType(row.level)" size="small">{{ getLevelLabel(row.level) }}</el-tag>
              <span class="read-count">
                <el-icon><View /></el-icon>
                {{ row.readCount }}
              </span>
            </div>
          </div>
        </template>
      </el-table-column>
      <el-table-column prop="author" label="作者" width="120" />
      <el-table-column label="发布时间" width="180">
        <template #default="{ row }">
          {{ formatTime(row.publishTime) }}
        </template>
      </el-table-column>
      <el-table-column prop="status" label="状态" width="100">
        <template #default="{ row }">
          <el-switch
            v-model="row.status"
            :active-value="1"
            :inactive-value="0"
            @change="updateStatus(row)"
          />
        </template>
      </el-table-column>
      <el-table-column label="操作" width="200" fixed="right">
        <template #default="{ row }">
          <el-button type="primary" link @click="preview(row)">预览</el-button>
          <el-button type="primary" link @click="openDialog(row)">编辑</el-button>
          <el-button type="danger" link @click="deleteArticle(row)">删除</el-button>
        </template>
      </el-table-column>
    </el-table>

    <!-- 分页 -->
    <div class="pagination">
      <el-pagination
        v-model:current-page="page"
        v-model:page-size="pageSize"
        :total="total"
        :page-sizes="[10, 20, 50]"
        layout="total, sizes, prev, pager, next"
      />
    </div>

    <!-- 编辑弹窗 -->
    <el-dialog
      v-model="dialogVisible"
      :title="isEdit ? '编辑文章' : '新增文章'"
      width="900px"
      class="article-dialog"
    >
      <el-form
        ref="formRef"
        :model="form"
        :rules="rules"
        label-width="80px"
      >
        <el-form-item label="标题" prop="title">
          <el-input v-model="form.title" placeholder="请输入文章标题" />
        </el-form-item>

        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="分类" prop="categoryId">
              <el-select v-model="form.categoryId" placeholder="选择分类" style="width: 100%">
                <el-option
                  v-for="cat in categories"
                  :key="cat.id"
                  :label="cat.name"
                  :value="cat.id"
                />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="难度" prop="level">
              <el-radio-group v-model="form.level">
                <el-radio-button label="beginner">入门</el-radio-button>
                <el-radio-button label="intermediate">进阶</el-radio-button>
                <el-radio-button label="advanced">高级</el-radio-button>
              </el-radio-group>
            </el-form-item>
          </el-col>
        </el-row>

        <el-form-item label="摘要">
          <el-input
            v-model="form.summary"
            type="textarea"
            :rows="2"
            placeholder="文章摘要，用于列表展示"
          />
        </el-form-item>

        <el-form-item label="封面">
          <el-upload
            class="cover-uploader"
            action="/api/upload"
            :show-file-list="false"
            :on-success="handleCoverSuccess"
            :on-error="handleCoverError"
          >
            <img v-if="form.cover" :src="form.cover" class="cover-image" />
            <div v-else class="cover-placeholder">
              <el-icon><Plus /></el-icon>
              <span>上传封面</span>
            </div>
          </el-upload>
        </el-form-item>

        <el-form-item label="内容" prop="content">
          <div class="editor-toolbar">
            <el-button-group>
              <el-button size="small" @click="insertTag('h2')">H2</el-button>
              <el-button size="small" @click="insertTag('h3')">H3</el-button>
              <el-button size="small" @click="insertTag('bold')">加粗</el-button>
              <el-button size="small" @click="insertTag('tip')">提示框</el-button>
              <el-button size="small" @click="insertTag('warning')">警告</el-button>
              <el-button size="small" @click="insertTag('table')">表格</el-button>
            </el-button-group>
          </div>
          <el-input
            ref="contentRef"
            v-model="form.content"
            type="textarea"
            :rows="15"
            placeholder="支持Markdown格式"
          />
        </el-form-item>

        <el-form-item label="状态">
          <el-radio-group v-model="form.status">
            <el-radio :label="1">发布</el-radio>
            <el-radio :label="0">草稿</el-radio>
          </el-radio-group>
        </el-form-item>
      </el-form>

      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitForm" :loading="submitLoading">
          保存
        </el-button>
      </template>
    </el-dialog>

    <!-- 预览弹窗 -->
    <el-dialog v-model="previewVisible" title="文章预览" width="800px" class="preview-dialog">
      <div class="preview-content" v-html="previewContent"></div>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Plus, Search, View } from '@element-plus/icons-vue'
import { marked } from 'marked'

// 防抖函数
const debounce = (fn, delay) => {
  let timer = null
  return function(...args) {
    if (timer) clearTimeout(timer)
    timer = setTimeout(() => fn.apply(this, args), delay)
  }
}

const loading = ref(false)
const submitLoading = ref(false)
const dialogVisible = ref(false)
const previewVisible = ref(false)
const isEdit = ref(false)
const formRef = ref(null)
const contentRef = ref(null)

// 分类
const categories = ref([
  { id: 1, name: '八字基础', count: 12 },
  { id: 2, name: '十神详解', count: 10 },
  { id: 3, name: '格局分析', count: 8 },
  { id: 4, name: '大运流年', count: 6 },
  { id: 5, name: '塔罗入门', count: 5 },
  { id: 6, name: '风水基础', count: 4 }
])

// 筛选
const searchKeyword = ref('')
const filterLevel = ref('')
const filterStatus = ref('')
const selectedCategory = ref(null)

// 分页
const page = ref(1)
const pageSize = ref(20)
const total = ref(100)

// 文章列表
const articleList = ref([
  {
    id: 1,
    title: '如何看懂自己的八字命盘',
    categoryId: 1,
    categoryName: '八字基础',
    level: 'beginner',
    author: '命理大师',
    publishTime: Date.now() - 86400000,
    readCount: 1234,
    status: 1,
    summary: '八字入门指南，教你快速读懂命盘信息',
    cover: '',
    content: '# 如何看懂自己的八字命盘\n\n## 什么是八字\n\n八字，又称四柱...'
  }
])

// 表单
const form = ref({
  id: null,
  title: '',
  categoryId: '',
  level: 'beginner',
  summary: '',
  cover: '',
  content: '',
  status: 1
})

const rules = {
  title: [{ required: true, message: '请输入标题', trigger: 'blur' }],
  categoryId: [{ required: true, message: '请选择分类', trigger: 'change' }],
  level: [{ required: true, message: '请选择难度', trigger: 'change' }],
  content: [{ required: true, message: '请输入内容', trigger: 'blur' }]
}

const previewContent = ref('')
const debouncedSearchKeyword = ref('')

// 搜索防抖处理
watch(searchKeyword, debounce((val) => {
  debouncedSearchKeyword.value = val
}, 300))

const filteredList = computed(() => {
  let list = articleList.value
  
  if (debouncedSearchKeyword.value) {
    list = list.filter(item => 
      item.title.includes(debouncedSearchKeyword.value) ||
      item.summary?.includes(debouncedSearchKeyword.value)
    )
  }
  
  if (filterLevel.value) {
    list = list.filter(item => item.level === filterLevel.value)
  }
  
  if (filterStatus.value !== '') {
    list = list.filter(item => item.status === filterStatus.value)
  }
  
  if (selectedCategory.value) {
    list = list.filter(item => item.categoryId === selectedCategory.value)
  }
  
  return list
})

const getLevelType = (level) => {
  const map = { beginner: 'success', intermediate: 'warning', advanced: 'danger' }
  return map[level] || ''
}

const getLevelLabel = (level) => {
  const map = { beginner: '入门', intermediate: '进阶', advanced: '高级' }
  return map[level] || level
}

const formatTime = (timestamp) => {
  return new Date(timestamp).toLocaleString()
}

const selectCategory = (id) => {
  selectedCategory.value = selectedCategory.value === id ? null : id
}

const addCategory = () => {
  ElMessageBox.prompt('请输入分类名称', '添加分类', {
    confirmButtonText: '确定',
    cancelButtonText: '取消'
  }).then(({ value }) => {
    if (value) {
      categories.value.push({
        id: Date.now(),
        name: value,
        count: 0
      })
      ElMessage.success('添加成功')
    }
  })
}

const deleteCategory = (cat) => {
  ElMessageBox.confirm(`确定要删除分类"${cat.name}"吗？`, '提示', {
    type: 'warning'
  }).then(() => {
    const index = categories.value.findIndex(c => c.id === cat.id)
    if (index > -1) {
      categories.value.splice(index, 1)
    }
    ElMessage.success('删除成功')
  })
}

const openDialog = (row = null) => {
  if (row) {
    isEdit.value = true
    form.value = { ...row }
  } else {
    isEdit.value = false
    form.value = {
      id: null,
      title: '',
      categoryId: '',
      level: 'beginner',
      summary: '',
      cover: '',
      content: '',
      status: 1
    }
  }
  dialogVisible.value = true
}

const handleCoverSuccess = (res) => {
  form.value.cover = res.url
}

const handleCoverError = (err) => {
  console.error('封面上传失败:', err)
  ElMessage.error('封面上传失败，请重试')
}

const insertTag = (tag) => {
  const textarea = contentRef.value?.$refs?.textarea
  if (!textarea) return

  const start = textarea.selectionStart
  const end = textarea.selectionEnd
  const text = form.value.content

  let insertText = ''
  switch (tag) {
    case 'h2':
      insertText = '\n## 标题\n'
      break
    case 'h3':
      insertText = '\n### 小标题\n'
      break
    case 'bold':
      insertText = '**加粗文字**'
      break
    case 'tip':
      insertText = '\n> 💡 提示：提示内容\n'
      break
    case 'warning':
      insertText = '\n> ⚠️ 注意：警告内容\n'
      break
    case 'table':
      insertText = '\n| 列1 | 列2 | 列3 |\n|-----|-----|-----|\n| 内容 | 内容 | 内容 |\n'
      break
  }

  form.value.content = text.substring(0, start) + insertText + text.substring(end)
}

const submitForm = async () => {
  const valid = await formRef.value.validate().catch(() => false)
  if (!valid) return

  submitLoading.value = true
  try {
    if (isEdit.value) {
      const index = articleList.value.findIndex(item => item.id === form.value.id)
      if (index > -1) {
        articleList.value[index] = { ...form.value }
      }
      ElMessage.success('更新成功')
    } else {
      form.value.id = Date.now()
      form.value.publishTime = Date.now()
      form.value.categoryName = categories.value.find(c => c.id === form.value.categoryId)?.name
      articleList.value.unshift({ ...form.value })
      ElMessage.success('发布成功')
    }
    dialogVisible.value = false
  } finally {
    submitLoading.value = false
  }
}

const preview = (row) => {
  previewContent.value = marked(row.content)
  previewVisible.value = true
}

const deleteArticle = async (row) => {
  try {
    await ElMessageBox.confirm('确定要删除此文章吗？', '提示', { type: 'warning' })
    const index = articleList.value.findIndex(item => item.id === row.id)
    if (index > -1) {
      articleList.value.splice(index, 1)
    }
    ElMessage.success('删除成功')
  } catch {}
}

const updateStatus = (row) => {
  ElMessage.success(row.status === 1 ? '已发布' : '已设为草稿')
}

onMounted(() => {
  // 加载数据
})
</script>

<style scoped>
.knowledge-manage {
  padding: 20px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.category-section {
  margin-bottom: 20px;
}

.section-title {
  font-size: 14px;
  color: #666;
  margin-bottom: 12px;
}

.category-list {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}

.category-tag {
  cursor: pointer;
}

.filter-bar {
  display: flex;
  gap: 12px;
  margin-bottom: 20px;
}

.article-info {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.article-title {
  font-size: 15px;
  font-weight: 500;
  color: #333;
}

.article-meta {
  display: flex;
  gap: 8px;
  align-items: center;
}

.read-count {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 12px;
  color: #999;
}

.pagination {
  display: flex;
  justify-content: flex-end;
  margin-top: 20px;
}

/* 封面上传 */
.cover-uploader {
  border: 1px dashed #d9d9d9;
  border-radius: 6px;
  cursor: pointer;
  position: relative;
  overflow: hidden;
  width: 200px;
  height: 120px;
}

.cover-uploader:hover {
  border-color: #409eff;
}

.cover-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #8c939d;
}

.cover-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.editor-toolbar {
  margin-bottom: 8px;
}

/* 预览 */
.preview-content {
  padding: 20px;
  line-height: 1.8;
}

.preview-content h2 {
  margin: 24px 0 16px;
  padding-bottom: 8px;
  border-bottom: 1px solid #eee;
}

.preview-content h3 {
  margin: 20px 0 12px;
}

.preview-content blockquote {
  padding: 12px 16px;
  background: #f5f5f5;
  border-left: 4px solid #1890ff;
  margin: 16px 0;
}
</style>
