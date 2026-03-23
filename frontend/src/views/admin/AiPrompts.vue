
<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import request from '@/api/request'

const loading = ref(false)
const prompts = ref([])
const dialogVisible = ref(false)
const dialogTitle = ref('')
const previewVisible = ref(false)
const previewData = ref({ system_prompt: '', rendered_prompt: '' })

const pagination = ref({
  current: 1,
  pageSize: 20,
  total: 0
})

// 提示词类型列表（从后端动态获取 + 本地兜底）
const promptTypes = ref([
  { label: '八字解盘', value: 'bazi' },
  { label: '塔罗占卜', value: 'tarot' },
  { label: '每日运势', value: 'daily' },
  { label: '合婚测算', value: 'hehun' },
  { label: '六爻占卜', value: 'liuyao' },
  { label: '取名建议', value: 'qiming' },
  { label: '流年运势', value: 'yearly' },
])

const searchForm = ref({
  type: '',
  keyword: ''
})

const currentPrompt = ref({
  id: 0,
  name: '',
  key: '',
  type: 'bazi',
  system_prompt: '',
  user_prompt_template: '',
  variables: [],
  description: '',
  sort_order: 0,
  is_enabled: 1,
})

onMounted(() => {
  fetchTypes()
  fetchPrompts()
})

/** 从后端获取提示词类型 */
async function fetchTypes() {
  try {
    const res = await request.get('/api/maodou/ai-prompts/types')
    if (res.code === 0 || res.code === 200) {
      const types = res.data
      if (types && typeof types === 'object') {
        promptTypes.value = Object.entries(types).map(([value, label]) => ({ label, value }))
      }
    }
  } catch {
    // 使用本地兜底
  }
}

async function fetchPrompts() {
  loading.value = true
  try {
    const params = {
      page: pagination.value.current,
      limit: pagination.value.pageSize,
    }
    if (searchForm.value.type) params.type = searchForm.value.type
    if (searchForm.value.keyword) params.keyword = searchForm.value.keyword

    const res = await request.get('/api/maodou/ai-prompts/list', { params })
    if (res.code === 0 || res.code === 200) {
      prompts.value = res.data.list || []
      pagination.value.total = res.data.total || 0
      // 如果后端返回了类型，也更新
      if (res.data.types && typeof res.data.types === 'object') {
        promptTypes.value = Object.entries(res.data.types).map(([value, label]) => ({ label, value }))
      }
    }
  } catch {
    // 统一处理
  } finally {
    loading.value = false
  }
}

function getTypeLabel(type) {
  const found = promptTypes.value.find(t => t.value === type)
  return found ? found.label : type
}

function handleAdd() {
  dialogTitle.value = '添加提示词'
  currentPrompt.value = {
    id: 0,
    name: '',
    key: '',
    type: 'bazi',
    system_prompt: '',
    user_prompt_template: '',
    variables: [],
    description: '',
    sort_order: 0,
    is_enabled: 1,
  }
  dialogVisible.value = true
}

function handleEdit(row) {
  dialogTitle.value = '编辑提示词'
  currentPrompt.value = {
    ...row,
    variables: Array.isArray(row.variables) ? [...row.variables] : [],
  }
  dialogVisible.value = true
}

async function handleSave() {
  const data = currentPrompt.value
  if (!data.name || !data.key || !data.type) {
    return ElMessage.warning('名称、标识和类型为必填项')
  }
  if (!data.system_prompt && !data.user_prompt_template) {
    return ElMessage.warning('系统提示词和用户提示词模板至少填一个')
  }
  try {
    const payload = { ...data }
    if (data.id) payload.id = data.id
    const res = await request.post('/api/maodou/ai-prompts/save', payload)
    if (res.code === 0 || res.code === 200) {
      ElMessage.success('保存成功')
      dialogVisible.value = false
      fetchPrompts()
    }
  } catch {
    // 统一处理
  }
}

async function handleDelete(row) {
  try {
    await ElMessageBox.confirm(`确定要删除提示词「${row.name}」吗？`, '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    })
    const res = await request.delete(`/api/maodou/ai-prompts/${row.id}`)
    if (res.code === 0 || res.code === 200) {
      ElMessage.success('删除成功')
      fetchPrompts()
    }
  } catch (e) {
    if (e !== 'cancel') {
      // 统一处理
    }
  }
}

async function handleSetDefault(row) {
  try {
    const res = await request.post(`/api/maodou/ai-prompts/${row.id}/default`)
    if (res.code === 0 || res.code === 200) {
      ElMessage.success('已设置为默认提示词')
      fetchPrompts()
    }
  } catch {
    // 统一处理
  }
}

async function handleDuplicate(row) {
  try {
    const res = await request.post(`/api/maodou/ai-prompts/${row.id}/duplicate`)
    if (res.code === 0 || res.code === 200) {
      ElMessage.success('复制成功')
      fetchPrompts()
    }
  } catch {
    // 统一处理
  }
}

async function handlePreview(row) {
  try {
    const res = await request.post(`/api/maodou/ai-prompts/${row.id}/preview`)
    if (res.code === 0 || res.code === 200) {
      previewData.value = res.data
      previewVisible.value = true
    }
  } catch {
    // 统一处理
  }
}

function handleSearch() {
  pagination.value.current = 1
  fetchPrompts()
}

function handleReset() {
  searchForm.value = { type: '', keyword: '' }
  pagination.value.current = 1
  fetchPrompts()
}

function addVariable() {
  if (!Array.isArray(currentPrompt.value.variables)) {
    currentPrompt.value.variables = []
  }
  currentPrompt.value.variables.push({ key: '', description: '' })
}

function removeVariable(index) {
  currentPrompt.value.variables.splice(index, 1)
}
</script>

<template>
  <div class="admin-ai-prompts">
    <el-card>
      <template #header>
        <div class="card-header">
          <span>AI提示词管理</span>
          <el-button type="primary" @click="handleAdd">添加提示词</el-button>
        </div>
      </template>

      <!-- 搜索区域 -->
      <el-form :inline="true" :model="searchForm" class="search-form">
        <el-form-item label="功能类型">
          <el-select v-model="searchForm.type" placeholder="全部类型" clearable style="width: 160px;">
            <el-option v-for="t in promptTypes" :key="t.value" :label="t.label" :value="t.value" />
          </el-select>
        </el-form-item>
        <el-form-item label="关键词">
          <el-input v-model="searchForm.keyword" placeholder="搜索名称/内容" clearable @keyup.enter="handleSearch" />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">搜索</el-button>
          <el-button @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>

      <!-- 列表 -->
      <el-table :data="prompts" v-loading="loading" stripe>
        <el-table-column prop="id" label="ID" width="70" />
        <el-table-column prop="name" label="名称" width="180" show-overflow-tooltip />
        <el-table-column prop="key" label="标识" width="160" show-overflow-tooltip>
          <template #default="{ row }">
            <el-tag size="small" type="info">{{ row.key }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="type" label="功能类型" width="120">
          <template #default="{ row }">
            <el-tag size="small">{{ getTypeLabel(row.type) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="description" label="说明" show-overflow-tooltip />
        <el-table-column prop="is_default" label="默认" width="70" align="center">
          <template #default="{ row }">
            <el-tag :type="row.is_default ? 'success' : 'info'" size="small">
              {{ row.is_default ? '是' : '否' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="is_enabled" label="状态" width="70" align="center">
          <template #default="{ row }">
            <el-tag :type="row.is_enabled ? 'success' : 'danger'" size="small">
              {{ row.is_enabled ? '启用' : '禁用' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="sort_order" label="排序" width="70" align="center" />
        <el-table-column label="操作" width="280" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" size="small" @click="handlePreview(row)">预览</el-button>
            <el-button link type="primary" size="small" @click="handleEdit(row)">编辑</el-button>
            <el-button link type="primary" size="small" @click="handleDuplicate(row)">复制</el-button>
            <el-button
              v-if="!row.is_default"
              link type="warning" size="small"
              @click="handleSetDefault(row)"
            >设为默认</el-button>
            <el-button link type="danger" size="small" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>

      <el-pagination
        v-model:current-page="pagination.current"
        v-model:page-size="pagination.pageSize"
        :total="pagination.total"
        :page-sizes="[10, 20, 50]"
        layout="total, sizes, prev, pager, next"
        @current-change="fetchPrompts"
        @size-change="fetchPrompts"
        style="margin-top: 20px; justify-content: center;"
      />
    </el-card>

    <!-- 编辑弹窗 -->
    <el-dialog v-model="dialogVisible" :title="dialogTitle" width="800px" destroy-on-close>
      <el-form :model="currentPrompt" label-width="130px">
        <el-form-item label="名称" required>
          <el-input v-model="currentPrompt.name" placeholder="如：八字详细解盘" />
        </el-form-item>
        <el-form-item label="唯一标识" required>
          <el-input v-model="currentPrompt.key" placeholder="如：bazi_detail（英文标识，不可重复）" />
        </el-form-item>
        <el-form-item label="功能类型" required>
          <el-select v-model="currentPrompt.type" placeholder="请选择">
            <el-option v-for="t in promptTypes" :key="t.value" :label="t.label" :value="t.value" />
          </el-select>
        </el-form-item>
        <el-form-item label="说明">
          <el-input v-model="currentPrompt.description" placeholder="简要说明该提示词用途" />
        </el-form-item>
        <el-form-item label="系统提示词">
          <el-input
            v-model="currentPrompt.system_prompt"
            type="textarea"
            :rows="5"
            placeholder="System Prompt，定义AI角色和行为规范"
          />
        </el-form-item>
        <el-form-item label="用户提示词模板">
          <el-input
            v-model="currentPrompt.user_prompt_template"
            type="textarea"
            :rows="8"
            placeholder="User Prompt模板，可使用 {{变量名}} 占位符"
          />
        </el-form-item>
        <el-form-item label="变量定义">
          <div class="variables-list">
            <div v-for="(v, i) in currentPrompt.variables" :key="i" class="variable-row">
              <el-input v-model="v.key" placeholder="变量名" style="width: 180px;" />
              <el-input v-model="v.description" placeholder="描述" style="width: 240px;" />
              <el-button link type="danger" @click="removeVariable(i)">删除</el-button>
            </div>
          </div>
          <el-button link type="primary" @click="addVariable">+ 添加变量</el-button>
        </el-form-item>
        <el-form-item label="排序值">
          <el-input-number v-model="currentPrompt.sort_order" :min="0" :max="999" />
          <span style="margin-left: 8px; color: #909399; font-size: 12px;">值越小越靠前</span>
        </el-form-item>
        <el-form-item label="启用状态">
          <el-switch v-model="currentPrompt.is_enabled" :active-value="1" :inactive-value="0" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSave">保存</el-button>
      </template>
    </el-dialog>

    <!-- 预览弹窗 -->
    <el-dialog v-model="previewVisible" title="提示词预览" width="700px">
      <div class="preview-section">
        <h4>系统提示词 (System Prompt)</h4>
        <pre class="preview-content">{{ previewData.system_prompt || '（无）' }}</pre>
      </div>
      <div class="preview-section">
        <h4>渲染后的用户提示词</h4>
        <pre class="preview-content">{{ previewData.rendered_prompt || '（无）' }}</pre>
      </div>
      <template #footer>
        <el-button @click="previewVisible = false">关闭</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<style scoped>
.admin-ai-prompts {
  padding: 24px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.search-form {
  margin-bottom: 16px;
}

.variables-list {
  margin-bottom: 8px;
}

.variable-row {
  display: flex;
  gap: 10px;
  align-items: center;
  margin-bottom: 8px;
}

.preview-section {
  margin-bottom: 20px;
}

.preview-section h4 {
  font-size: 14px;
  color: #303133;
  margin-bottom: 8px;
  font-weight: 600;
}

.preview-content {
  background: #f5f7fa;
  padding: 16px;
  border-radius: 6px;
  white-space: pre-wrap;
  word-break: break-all;
  font-size: 13px;
  line-height: 1.6;
  color: #606266;
  max-height: 300px;
  overflow-y: auto;
}
</style>
