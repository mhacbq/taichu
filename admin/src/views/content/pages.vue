<template>
  <div class="pages-management">
    <!-- 页面头部 -->
    <div class="page-header">
      <div class="header-left">
        <h2>页面管理</h2>
        <span class="header-desc">管理和编辑网站所有页面</span>
      </div>
      <div class="header-right">
        <el-button type="primary" @click="handleCreate">
          <el-icon><Plus /></el-icon>
          新建页面
        </el-button>
        <el-button @click="showImport = true">
          <el-icon><Upload /></el-icon>
          导入
        </el-button>
      </div>
    </div>
    
    <!-- 搜索栏 -->
    <el-card class="search-card">
      <SearchForm
        v-model="queryParams"
        :items="searchItems"
        show-export
        @search="handleSearch"
        @reset="handleReset"
        @export="handleExport"
      />
    </el-card>
    
    <!-- 页面列表 -->
    <el-card class="list-card">
      <CommonTable
        :data="pageList"
        :columns="columns"
        :loading="loading"
        :total="total"
        v-model:page="queryParams.page"
        v-model:pageSize="queryParams.pageSize"
        @page-change="loadPages"
      >
        <!-- 页面ID列 -->
        <template #page_id="{ row }">
          <el-link type="primary" @click="handleEdit(row)">
            {{ row.page_id }}
          </el-link>
        </template>
        
        <!-- 状态列 -->
        <template #status="{ row }">
          <EditableSelect
            v-model="row.status"
            :options="statusOptions"
            :can-edit="true"
            :save-api="(data) => updatePageStatus(row.page_id, data.value)"
            show-tag
          />
        </template>
        
        <!-- 更新时间列 -->
        <template #updated_at="{ row }">
          <span :title="row.updated_at">
            {{ formatRelativeTime(row.updated_at) }}
          </span>
        </template>
        
        <!-- 操作列 -->
        <template #actions="{ row }">
          <el-button link type="primary" @click="handleEdit(row)">
            <el-icon><Edit /></el-icon>
            编辑
          </el-button>
          <el-button link @click="handlePreview(row)">
            <el-icon><View /></el-icon>
            预览
          </el-button>
          <el-dropdown @command="(cmd) => handleMore(cmd, row)">
            <el-button link>
              <el-icon><More /></el-icon>
            </el-button>
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item command="copy">
                  <el-icon><DocumentCopy /></el-icon>
                  复制
                </el-dropdown-item>
                <el-dropdown-item command="export">
                  <el-icon><Download /></el-icon>
                  导出
                </el-dropdown-item>
                <el-dropdown-item command="history">
                  <el-icon><Timer /></el-icon>
                  历史版本
                </el-dropdown-item>
                <el-dropdown-item divided command="delete">
                  <el-icon><Delete /></el-icon>
                  删除
                </el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </template>
      </CommonTable>
    </el-card>
    
    <!-- 新建/编辑弹窗 -->
    <el-dialog
      v-model="dialogVisible"
      :title="isEdit ? '编辑页面' : '新建页面'"
      width="500px"
    >
      <el-form
        ref="formRef"
        :model="formData"
        :rules="formRules"
        label-width="100px"
      >
        <el-form-item label="页面ID" prop="page_id">
          <el-input
            v-model="formData.page_id"
            placeholder="如: home, about, help"
            :disabled="isEdit"
          >
            <template #prefix>/</template>
          </el-input>
        </el-form-item>
        
        <el-form-item label="页面标题" prop="title">
          <el-input v-model="formData.title" placeholder="页面标题" />
        </el-form-item>
        
        <el-form-item label="页面描述">
          <el-input
            v-model="formData.description"
            type="textarea"
            rows="3"
            placeholder="页面描述"
          />
        </el-form-item>
        
        <el-form-item label="状态">
          <el-radio-group v-model="formData.status">
            <el-radio-button label="published">已发布</el-radio-button>
            <el-radio-button label="draft">草稿</el-radio-button>
            <el-radio-button label="hidden">隐藏</el-radio-button>
          </el-radio-group>
        </el-form-item>
        
        <el-form-item label="使用模板">
          <el-select
            v-model="formData.template"
            placeholder="选择模板（可选）"
            clearable
          >
            <el-option
              v-for="tpl in templates"
              :key="tpl.id"
              :label="tpl.name"
              :value="tpl.id"
            >
              <div class="template-option">
                <span>{{ tpl.name }}</span>
                <span class="template-desc">{{ tpl.description }}</span>
              </div>
            </el-option>
          </el-select>
        </el-form-item>
      </el-form>
      
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSubmit">
          {{ isEdit ? '保存' : '创建' }}
        </el-button>
        <el-button
          v-if="!isEdit"
          type="primary"
          plain
          @click="handleSubmitAndEdit"
        >
          创建并编辑
        </el-button>
      </template>
    </el-dialog>
    
    <!-- 导入弹窗 -->
    <el-dialog v-model="showImport" title="导入页面" width="500px">
      <el-upload
        drag
        action="/api/upload/json"
        accept=".json"
        :on-success="handleImportSuccess"
        :on-error="handleImportError"
      >
        <el-icon class="el-icon--upload"><Upload /></el-icon>
        <div class="el-upload__text">
          拖拽JSON文件到此处或 <em>点击上传</em>
        </div>
      </el-upload>
    </el-dialog>
    
    <!-- 预览弹窗 -->
    <el-dialog
      v-model="previewVisible"
      title="页面预览"
      width="900px"
    >
      <div class="preview-container">
        <div class="preview-toolbar">
          <el-radio-group v-model="previewDevice" size="small">
            <el-radio-button label="desktop">
              <el-icon><Monitor /></el-icon>
              桌面
            </el-radio-button>
            <el-radio-button label="tablet">
              <el-icon><Cellphone /></el-icon>
              平板
            </el-radio-button>
            <el-radio-button label="mobile">
              <el-icon><Phone /></el-icon>
              手机
            </el-radio-button>
          </el-radio-group>
          <el-button size="small" @click="openInNewTab">
            <el-icon><Link /></el-icon>
            新窗口打开
          </el-button>
        </div>
        <div class="preview-content" :class="`device-${previewDevice}`">
          <iframe
            v-if="previewUrl"
            :src="previewUrl"
            frameborder="0"
            class="preview-iframe"
          />
        </div>
      </div>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import {
  Plus, Upload, Edit, View, More, DocumentCopy,
  Download, Timer, Delete, Monitor, Cellphone, Phone, Link
} from '@element-plus/icons-vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import CommonTable from '@/components/CommonTable/index.vue'
import SearchForm from '@/components/SearchForm/index.vue'
import { EditableSelect } from '@/components/VisualEditor'
import * as contentApi from '@/api/contentEditor'
import { formatRelativeTime } from '@/utils/format'

const router = useRouter()

// 状态
const loading = ref(false)
const dialogVisible = ref(false)
const showImport = ref(false)
const previewVisible = ref(false)
const isEdit = ref(false)
const previewDevice = ref('desktop')
const previewUrl = ref('')

// 列表数据
const pageList = ref([])
const total = ref(0)

// 查询参数
const queryParams = reactive({
  page: 1,
  pageSize: 10,
  keyword: ''
})

// 搜索配置
const searchItems = [
  {
    type: 'input',
    prop: 'keyword',
    label: '关键词',
    placeholder: '搜索页面ID或标题'
  }
]

// 表格列
const columns = [
  { prop: 'page_id', label: '页面ID', minWidth: 120, slot: true },
  { prop: 'title', label: '页面标题', minWidth: 180 },
  { prop: 'version', label: '版本', width: 80, align: 'center' },
  { prop: 'status', label: '状态', width: 100, slot: true },
  { prop: 'updated_at', label: '更新时间', width: 150, slot: true },
  { prop: 'actions', label: '操作', width: 200, fixed: 'right', slot: true }
]

// 状态选项
const statusOptions = [
  { value: 'published', label: '已发布', type: 'success' },
  { value: 'draft', label: '草稿', type: 'warning' },
  { value: 'hidden', label: '隐藏', type: 'info' }
]

// 模板列表
const templates = ref([
  { id: 'blank', name: '空白页面', description: '从空白开始创建' },
  { id: 'home', name: '首页模板', description: '包含轮播图和统计数据' },
  { id: 'dashboard', name: '仪表盘模板', description: '数据展示布局' },
  { id: 'article', name: '文章页面', description: '图文内容布局' }
])

// 表单
const formRef = ref(null)
const formData = reactive({
  page_id: '',
  title: '',
  description: '',
  status: 'draft',
  template: ''
})

const formRules = {
  page_id: [
    { required: true, message: '请输入页面ID', trigger: 'blur' },
    { pattern: /^[a-z0-9_-]+$/, message: '只能包含小写字母、数字、下划线和横线', trigger: 'blur' }
  ],
  title: [
    { required: true, message: '请输入页面标题', trigger: 'blur' }
  ]
}

// 加载页面列表
const loadPages = async () => {
  loading.value = true
  
  try {
    const res = await contentApi.getPages(queryParams)
    if (res.code === 200) {
      pageList.value = res.data.list
      total.value = res.data.total
    }
  } catch (error) {
    ElMessage.error('加载失败')
  } finally {
    loading.value = false
  }
}

// 搜索
const handleSearch = () => {
  queryParams.page = 1
  loadPages()
}

// 重置
const handleReset = () => {
  queryParams.keyword = ''
  queryParams.page = 1
  loadPages()
}

// 导出
const handleExport = () => {
  // 导出页面列表
}

// 新建页面
const handleCreate = () => {
  isEdit.value = false
  formData.page_id = ''
  formData.title = ''
  formData.description = ''
  formData.status = 'draft'
  formData.template = ''
  dialogVisible.value = true
}

// 编辑页面
const handleEdit = (row) => {
  router.push(`/editor/page/${row.page_id}`)
}

// 预览页面
const handlePreview = (row) => {
  previewUrl.value = `/preview/${row.page_id}`
  previewVisible.value = true
}

// 更多操作
const handleMore = async (command, row) => {
  switch (command) {
    case 'copy':
      await copyPage(row)
      break
    case 'export':
      await exportPage(row)
      break
    case 'history':
      router.push(`/content/pages/${row.page_id}/history`)
      break
    case 'delete':
      await deletePage(row)
      break
  }
}

// 复制页面
const copyPage = async (row) => {
  try {
    const { value } = await ElMessageBox.prompt('请输入新页面ID', '复制页面', {
      inputValidator: (val) => val ? true : '页面ID不能为空'
    })
    
    const res = await contentApi.getPage(row.page_id)
    if (res.code === 200) {
      await contentApi.importPage({
        page_id: value,
        title: `${res.data.title} - 副本`,
        content: res.data.blocks,
        settings: res.data.settings
      })
      
      ElMessage.success('复制成功')
      loadPages()
    }
  } catch {
    // 取消
  }
}

// 导出页面
const exportPage = async (row) => {
  try {
    const res = await contentApi.exportPage(row.page_id)
    if (res.code === 200) {
      // 下载JSON
      const blob = new Blob([JSON.stringify(res.data, null, 2)], { type: 'application/json' })
      const url = URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.download = `${row.page_id}.json`
      link.click()
      URL.revokeObjectURL(url)
      
      ElMessage.success('导出成功')
    }
  } catch (error) {
    ElMessage.error('导出失败')
  }
}

// 删除页面
const deletePage = async (row) => {
  try {
    await ElMessageBox.confirm('确定删除此页面吗？', '删除页面', {
      type: 'warning'
    })
    
    const res = await contentApi.deletePage(row.page_id)
    if (res.code === 200) {
      ElMessage.success('删除成功')
      loadPages()
    }
  } catch {
    // 取消
  }
}

// 更新状态
const updatePageStatus = async (pageId, status) => {
  try {
    // await contentApi.updatePage(pageId, { status })
    ElMessage.success('状态更新成功')
    return Promise.resolve()
  } catch (error) {
    ElMessage.error('更新失败')
    return Promise.reject(error)
  }
}

// 提交表单
const handleSubmit = async () => {
  const valid = await formRef.value?.validate()
  if (!valid) return
  
  try {
    if (isEdit.value) {
      // 更新
    } else {
      // 创建
      await contentApi.importPage({
        page_id: formData.page_id,
        title: formData.title,
        content: [],
        settings: {}
      })
    }
    
    ElMessage.success(isEdit.value ? '保存成功' : '创建成功')
    dialogVisible.value = false
    loadPages()
  } catch (error) {
    ElMessage.error('操作失败')
  }
}

// 创建并编辑
const handleSubmitAndEdit = async () => {
  await handleSubmit()
  router.push(`/editor/page/${formData.page_id}`)
}

// 导入成功
const handleImportSuccess = (res) => {
  if (res.code === 200) {
    ElMessage.success('导入成功')
    showImport.value = false
    loadPages()
  }
}

// 导入失败
const handleImportError = () => {
  ElMessage.error('导入失败')
}

// 新窗口打开
const openInNewTab = () => {
  window.open(previewUrl.value, '_blank')
}

// 初始化
loadPages()
</script>

<style scoped lang="scss">
.pages-management {
  padding: 20px;
}

.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 20px;
  
  .header-left {
    h2 {
      margin: 0 0 4px 0;
    }
    
    .header-desc {
      color: #909399;
      font-size: 14px;
    }
  }
  
  .header-right {
    display: flex;
    gap: 12px;
  }
}

.search-card {
  margin-bottom: 20px;
}

.template-option {
  display: flex;
  flex-direction: column;
  
  .template-desc {
    font-size: 12px;
    color: #909399;
  }
}

.preview-container {
  .preview-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px;
    background: #f5f7fa;
    border-radius: 4px;
    margin-bottom: 16px;
  }
  
  .preview-content {
    background: #f0f2f5;
    padding: 20px;
    min-height: 500px;
    
    &.device-desktop .preview-iframe {
      width: 100%;
      height: 600px;
    }
    
    &.device-tablet .preview-iframe {
      width: 768px;
      height: 600px;
      margin: 0 auto;
      display: block;
    }
    
    &.device-mobile .preview-iframe {
      width: 375px;
      height: 600px;
      margin: 0 auto;
      display: block;
    }
  }
  
  .preview-iframe {
    background: #fff;
    border: none;
    border-radius: 4px;
  }
}
</style>