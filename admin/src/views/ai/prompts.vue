<template>
  <div class="ai-prompts-manager">
    <el-card class="mb-4">
      <template #header>
        <div class="card-header">
          <div>
            <span>AI提示词管理</span>
            <el-text type="info" class="ml-2">配置AI解盘的提示词模板</el-text>
          </div>
          <el-button type="primary" @click="handleAdd">
            <el-icon><Plus /></el-icon>创建提示词
          </el-button>
        </div>
      </template>

      <!-- 搜索筛选 -->
      <el-form :inline="true" :model="queryForm" class="mb-4">
        <el-form-item label="类型">
          <el-select v-model="queryForm.type" placeholder="全部类型" clearable style="width: 150px;">
            <el-option label="八字解盘" value="bazi" />
            <el-option label="塔罗解读" value="tarot" />
            <el-option label="每日运势" value="daily" />
            <el-option label="通用对话" value="general" />
          </el-select>
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="queryForm.is_enabled" placeholder="全部" clearable style="width: 100px;">
            <el-option label="启用" :value="1" />
            <el-option label="禁用" :value="0" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">
            <el-icon><Search /></el-icon>搜索
          </el-button>
        </el-form-item>
      </el-form>

      <!-- 提示词列表 -->
      <el-table :data="tableData" v-loading="loading" border>
        <el-table-column type="index" label="序号" width="60" align="center" />
        <el-table-column prop="name" label="提示词名称" min-width="180">
          <template #default="{ row }">
            <div class="prompt-name">
              <span>{{ row.name }}</span>
              <el-tag v-if="row.is_default" type="success" size="small" class="ml-2">默认</el-tag>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="key" label="标识" width="150" show-overflow-tooltip />
        <el-table-column prop="type" label="类型" width="100">
          <template #default="{ row }">
            <el-tag :type="getTypeType(row.type)" size="small">
              {{ getTypeName(row.type) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="description" label="说明" min-width="200" show-overflow-tooltip />
        <el-table-column prop="usage_count" label="使用次数" width="90" align="center" />
        <el-table-column prop="is_enabled" label="状态" width="80" align="center">
          <template #default="{ row }">
            <el-tag :type="row.is_enabled ? 'success' : 'danger'">
              {{ row.is_enabled ? '启用' : '禁用' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="280" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link @click="handleEdit(row)">
              <el-icon><Edit /></el-icon>编辑
            </el-button>
            <el-button type="success" link @click="handlePreview(row)">
              <el-icon><View /></el-icon>预览
            </el-button>
            <el-button 
              v-if="!row.is_default" 
              type="warning" 
              link 
              @click="handleSetDefault(row)"
            >
              <el-icon><Star /></el-icon>设默认
            </el-button>
            <el-button type="info" link @click="handleDuplicate(row)">
              <el-icon><CopyDocument /></el-icon>复制
            </el-button>
            <el-button type="danger" link @click="handleDelete(row)">
              <el-icon><Delete /></el-icon>删除
            </el-button>
          </template>
        </el-table-column>
      </el-table>

      <!-- 分页 -->
      <div class="pagination-container">
        <el-pagination
          v-model:current-page="queryForm.page"
          v-model:page-size="queryForm.limit"
          :total="total"
          :page-sizes="[10, 20, 50]"
          layout="total, sizes, prev, pager, next"
          @size-change="handleSizeChange"
          @current-change="handleCurrentChange"
        />
      </div>
    </el-card>

    <!-- 编辑对话框 -->
    <el-dialog
      v-model="dialogVisible"
      :title="dialogTitle"
      width="800px"
      destroy-on-close
      class="prompt-dialog"
    >
      <el-form ref="formRef" :model="form" :rules="rules" label-width="100px">
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="名称" prop="name">
              <el-input v-model="form.name" placeholder="如：八字专业解读" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="标识" prop="key">
              <el-input v-model="form.key" placeholder="如：bazi_professional" />
            </el-form-item>
          </el-col>
        </el-row>
        
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="类型" prop="type">
              <el-select v-model="form.type" placeholder="选择类型" style="width: 100%;">
                <el-option label="八字解盘" value="bazi" />
                <el-option label="塔罗解读" value="tarot" />
                <el-option label="每日运势" value="daily" />
                <el-option label="通用对话" value="general" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="排序">
              <el-input-number v-model="form.sort_order" :min="0" :max="999" style="width: 100%;" />
            </el-form-item>
          </el-col>
        </el-row>

        <el-form-item label="说明">
          <el-input v-model="form.description" placeholder="简要说明此提示词的用途" />
        </el-form-item>

        <el-divider content-position="left">提示词内容</el-divider>

        <el-form-item label="系统提示词" prop="system_prompt">
          <el-input
            v-model="form.system_prompt"
            type="textarea"
            :rows="6"
            placeholder="设置AI的角色和任务要求，例如：你是一位资深的八字命理大师..."
          />
          <el-text type="info" size="small">定义AI的角色、能力和回答风格</el-text>
        </el-form-item>

        <el-form-item label="用户提示模板">
          <el-input
            v-model="form.user_prompt_template"
            type="textarea"
            :rows="8"
            placeholder="输入提示词模板，使用 {{变量名}} 作为占位符，例如：请解读八字：{{year_gan}}{{year_zhi}}..."
          />
          <el-text type="info" size="small">使用 {{变量名}} 作为占位符，系统会自动替换为实际数据</el-text>
        </el-form-item>

        <el-form-item label="变量定义">
          <el-input
            v-model="form.variablesText"
            type="textarea"
            :rows="4"
            placeholder='{"year_gan": "年干", "year_zhi": "年支"}'
          />
          <el-text type="info" size="small">JSON格式，定义模板中使用的变量及其说明</el-text>
        </el-form-item>

        <el-form-item label="模型参数">
          <el-input
            v-model="form.modelParamsText"
            type="textarea"
            :rows="3"
            placeholder='{"temperature": 0.7, "max_tokens": 2048}'
          />
          <el-text type="info" size="small">JSON格式，可选的温度、最大token等参数</el-text>
        </el-form-item>

        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="启用状态">
              <el-switch
                v-model="form.is_enabled"
                :active-value="1"
                :inactive-value="0"
                active-text="启用"
                inactive-text="禁用"
              />
            </el-form-item>
          </el-col>

        </el-row>
      </el-form>

      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSubmit" :loading="submitLoading">
          保存
        </el-button>
      </template>
    </el-dialog>

    <!-- 预览对话框 -->
    <el-dialog
      v-model="previewVisible"
      title="提示词预览"
      width="700px"
      destroy-on-close
    >
      <div v-loading="previewLoading" class="preview-content">
        <div v-if="previewData" class="preview-sections">
          <div class="preview-section">
            <h4>系统提示词</h4>
            <pre class="code-block">{{ previewData.system_prompt }}</pre>
          </div>
          <div class="preview-section">
            <h4>用户提示模板</h4>
            <pre class="code-block">{{ previewData.user_prompt_template }}</pre>
          </div>
          <div class="preview-section">
            <h4>渲染后的效果</h4>
            <div class="rendered-content">{{ previewData.rendered_prompt }}</div>
          </div>
          <div class="preview-section">
            <h4>测试变量</h4>
            <el-descriptions :column="3" size="small" border>
              <el-descriptions-item 
                v-for="(value, key) in previewData.variables" 
                :key="key"
                :label="key"
              >
                {{ value }}
              </el-descriptions-item>
            </el-descriptions>
          </div>
        </div>
      </div>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Plus, Search, Edit, Delete, View, Star, CopyDocument } from '@element-plus/icons-vue'
import {
  getPromptList,
  savePrompt,
  deletePrompt,
  setDefaultPrompt,
  previewPrompt,
  duplicatePrompt
} from '@/api/aiPrompt'

const loading = ref(false)
const submitLoading = ref(false)
const previewLoading = ref(false)
const dialogVisible = ref(false)
const previewVisible = ref(false)
const dialogTitle = ref('创建提示词')
const formRef = ref(null)
const tableData = ref([])
const total = ref(0)
const previewData = ref(null)

const queryForm = reactive({
  page: 1,
  limit: 10,
  type: null,
  is_enabled: null
})

const form = reactive({
  id: null,
  name: '',
  key: '',
  type: 'bazi',
  system_prompt: '',
  user_prompt_template: '',
  variablesText: '',
  modelParamsText: '',
  description: '',
  sort_order: 0,
  is_enabled: 1,
  is_default: 0
})

const rules = {
  name: [{ required: true, message: '请输入提示词名称', trigger: 'blur' }],
  key: [{ required: true, message: '请输入标识', trigger: 'blur' }],
  type: [{ required: true, message: '请选择类型', trigger: 'change' }],
  system_prompt: [{ required: true, message: '请输入系统提示词', trigger: 'blur' }]
}

const typeMap = {
  bazi: '八字解盘',
  tarot: '塔罗解读',
  daily: '每日运势',
  general: '通用对话'
}

const typeTypeMap = {
  bazi: 'primary',
  tarot: 'success',
  daily: 'warning',
  general: 'info'
}

const getTypeName = (type) => typeMap[type] || '其他'
const getTypeType = (type) => typeTypeMap[type] || 'info'

// 加载数据
const loadData = async () => {
  loading.value = true
  try {
    const res = await getPromptList(queryForm)
    if (res.code === 200) {
      tableData.value = res.data.list
      total.value = res.data.total
    }
  } catch (error) {
    ElMessage.error('加载数据失败')
  } finally {
    loading.value = false
  }
}

// 搜索
const handleSearch = () => {
  queryForm.page = 1
  loadData()
}

// 分页
const handleSizeChange = (val) => {
  queryForm.limit = val
  loadData()
}

const handleCurrentChange = (val) => {
  queryForm.page = val
  loadData()
}

// 添加
const handleAdd = () => {
  dialogTitle.value = '创建提示词'
  Object.assign(form, {
    id: null,
    name: '',
    key: '',
    type: 'bazi',
    system_prompt: '',
    user_prompt_template: '',
    variablesText: '',
    modelParamsText: '',
    description: '',
    sort_order: 0,
    is_enabled: 1,
    is_default: 0
  })
  dialogVisible.value = true
}

// 编辑
const handleEdit = (row) => {
  dialogTitle.value = '编辑提示词'
  Object.assign(form, {
    ...row,
    variablesText: row.variables ? JSON.stringify(row.variables, null, 2) : '',
    modelParamsText: row.model_params ? JSON.stringify(row.model_params, null, 2) : ''
  })
  dialogVisible.value = true
}

// 删除
const handleDelete = async (row) => {
  try {
    await ElMessageBox.confirm('确定要删除这个提示词吗？', '提示', {
      type: 'warning'
    })
    const res = await deletePrompt(row.id)
    if (res.code === 200) {
      ElMessage.success('删除成功')
      loadData()
    } else {
      ElMessage.error(res.message || '删除失败')
    }
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('删除失败')
    }
  }
}

// 设置默认
const handleSetDefault = async (row) => {
  try {
    const res = await setDefaultPrompt(row.id)
    if (res.code === 200) {
      ElMessage.success('设置成功')
      loadData()
    } else {
      ElMessage.error(res.message || '设置失败')
    }
  } catch (error) {
    ElMessage.error('设置失败')
  }
}

// 预览
const handlePreview = async (row) => {
  previewVisible.value = true
  previewLoading.value = true
  try {
    const res = await previewPrompt(row.id)
    if (res.code === 200) {
      previewData.value = res.data
    } else {
      ElMessage.error(res.message || '预览失败')
    }
  } catch (error) {
    ElMessage.error('预览失败')
  } finally {
    previewLoading.value = false
  }
}

// 复制
const handleDuplicate = async (row) => {
  try {
    const res = await duplicatePrompt(row.id)
    if (res.code === 200) {
      ElMessage.success('复制成功')
      loadData()
    } else {
      ElMessage.error(res.message || '复制失败')
    }
  } catch (error) {
    ElMessage.error('复制失败')
  }
}

// 提交
const handleSubmit = async () => {
  const valid = await formRef.value.validate().catch(() => false)
  if (!valid) return

  // 验证JSON格式
  let variables = null
  let modelParams = null

  if (form.variablesText) {
    try {
      variables = JSON.parse(form.variablesText)
    } catch (e) {
      ElMessage.error('变量定义JSON格式错误')
      return
    }
  }

  if (form.modelParamsText) {
    try {
      modelParams = JSON.parse(form.modelParamsText)
    } catch (e) {
      ElMessage.error('模型参数JSON格式错误')
      return
    }
  }

  const data = {
    id: form.id,
    name: form.name,
    key: form.key,
    type: form.type,
    system_prompt: form.system_prompt,
    user_prompt_template: form.user_prompt_template,
    description: form.description,
    sort_order: form.sort_order,
    is_enabled: form.is_enabled,
    variables,
    model_params: modelParams
  }


  submitLoading.value = true
  try {
    const res = await savePrompt(data)
    if (res.code === 200) {
      ElMessage.success('保存成功')
      dialogVisible.value = false
      loadData()
    } else {
      ElMessage.error(res.message || '保存失败')
    }
  } catch (error) {
    ElMessage.error('保存失败')
  } finally {
    submitLoading.value = false
  }
}

onMounted(() => {
  loadData()
})
</script>

<style scoped>
.ai-prompts-manager {
  padding: 20px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.prompt-name {
  display: flex;
  align-items: center;
}

.pagination-container {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
}

.preview-content {
  min-height: 200px;
}

.preview-sections {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.preview-section h4 {
  margin-bottom: 10px;
  color: #303133;
  font-size: 14px;
}

.code-block {
  background: #f5f7fa;
  padding: 15px;
  border-radius: 4px;
  font-family: 'Courier New', monospace;
  font-size: 13px;
  line-height: 1.6;
  white-space: pre-wrap;
  word-break: break-word;
  color: #606266;
  max-height: 200px;
  overflow-y: auto;
}

.rendered-content {
  background: #f0f9eb;
  padding: 15px;
  border-radius: 4px;
  line-height: 1.6;
  white-space: pre-wrap;
  word-break: break-word;
  color: #606266;
}

:deep(.prompt-dialog .el-dialog__body) {
  max-height: 60vh;
  overflow-y: auto;
}
</style>
