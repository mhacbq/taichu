<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import request from '@/api/request'

const loading = ref(false)
const prompts = ref([])
const dialogVisible = ref(false)
const dialogTitle = ref('')
const currentPrompt = ref({
  id: 0,
  title: '',
  type: 'bazi',
  content: '',
  variables: [],
  is_default: 0
})

const pagination = ref({
  current: 1,
  pageSize: 20,
  total: 0
})

const promptTypes = ref([
  { label: '八字解盘', value: 'bazi' },
  { label: '塔罗占卜', value: 'tarot' },
  { label: '每日运势', value: 'daily' },
  { label: '合姻测算', value: 'hehun' },
  { label: '六爻占卜', value: 'liuyao' }
])

const searchForm = ref({
  type: '',
  keyword: ''
})

onMounted(() => {
  fetchPrompts()
})

async function fetchPrompts() {
  loading.value = true
  try {
    const params = {
      page: pagination.value.current,
      page_size: pagination.value.pageSize,
      ...searchForm.value
    }
    const res = await request.get('/ai-prompts/list', { params })
    prompts.value = res.data.list
    pagination.value.total = res.data.total
  } catch (error) {
    ElMessage.error('获取提示词列表失败')
  } finally {
    loading.value = false
  }
}

function handleAdd() {
  dialogTitle.value = '添加提示词'
  currentPrompt.value = {
    id: 0,
    title: '',
    type: 'bazi',
    content: '',
    variables: [],
    is_default: 0
  }
  dialogVisible.value = true
}

function handleEdit(row) {
  dialogTitle.value = '编辑提示词'
  currentPrompt.value = { ...row }
  dialogVisible.value = true
}

async function handleDelete(row) {
  try {
    await ElMessageBox.confirm('确定要删除该提示词吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    })

    await request.delete(`/ai-prompts/${row.id}`)
    ElMessage.success('删除成功')
    fetchPrompts()
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('删除失败')
    }
  }
}

async function handleSetDefault(row) {
  try {
    await request.post(`/ai-prompts/${row.id}/default`)
    ElMessage.success('设置成功')
    fetchPrompts()
  } catch (error) {
    ElMessage.error('设置失败')
  }
}

async function handleDuplicate(row) {
  try {
    await request.post(`/ai-prompts/${row.id}/duplicate`)
    ElMessage.success('复制成功')
    fetchPrompts()
  } catch (error) {
    ElMessage.error('复制失败')
  }
}

async function handlePreview(row) {
  try {
    const res = await request.post(`/ai-prompts/${row.id}/preview`)
    ElMessageBox.alert(res.data.content, '提示词预览', {
      confirmButtonText: '关闭'
    })
  } catch (error) {
    ElMessage.error('预览失败')
  }
}

async function handleSave() {
  try {
    await request.post('/ai-prompts/save', currentPrompt.value)
    ElMessage.success('保存成功')
    dialogVisible.value = false
    fetchPrompts()
  } catch (error) {
    ElMessage.error('保存失败')
  }
}

function handlePageChange(page) {
  pagination.value.current = page
  fetchPrompts()
}

function handleSearch() {
  pagination.value.current = 1
  fetchPrompts()
}

function handleReset() {
  searchForm.value = {
    type: '',
    keyword: ''
  }
  pagination.value.current = 1
  fetchPrompts()
}

function addVariable() {
  if (!currentPrompt.value.variables) {
    currentPrompt.value.variables = []
  }
  currentPrompt.value.variables.push({
    key: '',
    description: ''
  })
}

function removeVariable(index) {
  currentPrompt.value.variables.splice(index, 1)
}
</script>

<template>
  <div class="app-container">
    <el-card>
      <template #header>
        <div class="card-header">
          <span>AI提示词管理</span>
          <el-button type="primary" @click="handleAdd">添加提示词</el-button>
        </div>
      </template>

      <el-form :inline="true" :model="searchForm">
        <el-form-item label="类型">
          <el-select v-model="searchForm.type" placeholder="请选择" clearable>
            <el-option
              v-for="type in promptTypes"
              :key="type.value"
              :label="type.label"
              :value="type.value"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="关键词">
          <el-input v-model="searchForm.keyword" placeholder="请输入关键词" clearable />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">搜索</el-button>
          <el-button @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>

      <el-table :data="prompts" v-loading="loading" stripe>
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="title" label="标题" width="200" />
        <el-table-column prop="type" label="类型" width="120">
          <template #default="{ row }">
            <el-tag size="small">{{ row.type }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="content" label="内容" show-overflow-tooltip />
        <el-table-column prop="is_default" label="默认" width="80">
          <template #default="{ row }">
            <el-tag :type="row.is_default ? 'success' : 'info'" size="small">
              {{ row.is_default ? '是' : '否' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="创建时间" width="180" />
        <el-table-column label="操作" width="250" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" size="small" @click="handlePreview(row)">预览</el-button>
            <el-button link type="primary" size="small" @click="handleEdit(row)">编辑</el-button>
            <el-button link type="primary" size="small" @click="handleDuplicate(row)">复制</el-button>
            <el-button
              v-if="!row.is_default"
              link
              type="warning"
              size="small"
              @click="handleSetDefault(row)"
            >
              设为默认
            </el-button>
            <el-button link type="danger" size="small" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>

      <el-pagination
        v-model:current-page="pagination.current"
        v-model:page-size="pagination.pageSize"
        :total="pagination.total"
        :page-sizes="[10, 20, 50, 100]"
        layout="total, sizes, prev, pager, next, jumper"
        @current-change="handlePageChange"
        @size-change="fetchPrompts"
        style="margin-top: 20px; justify-content: center"
      />
    </el-card>

    <el-dialog v-model="dialogVisible" :title="dialogTitle" width="800px">
      <el-form :model="currentPrompt" label-width="100px">
        <el-form-item label="标题" required>
          <el-input v-model="currentPrompt.title" placeholder="请输入标题" />
        </el-form-item>
        <el-form-item label="类型" required>
          <el-select v-model="currentPrompt.type" placeholder="请选择类型">
            <el-option
              v-for="type in promptTypes"
              :key="type.value"
              :label="type.label"
              :value="type.value"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="内容" required>
          <el-input
            v-model="currentPrompt.content"
            type="textarea"
            :rows="10"
            placeholder="请输入提示词内容"
          />
        </el-form-item>
        <el-form-item label="变量">
          <div v-if="currentPrompt.variables && currentPrompt.variables.length > 0">
            <div
              v-for="(variable, index) in currentPrompt.variables"
              :key="index"
              class="variable-item"
            >
              <el-input v-model="variable.key" placeholder="变量名" style="width: 200px" />
              <el-input v-model="variable.description" placeholder="描述" style="width: 200px" />
              <el-button link type="danger" @click="removeVariable(index)">删除</el-button>
            </div>
          </div>
          <el-button link type="primary" @click="addVariable">添加变量</el-button>
        </el-form-item>
        <el-form-item label="设为默认">
          <el-switch v-model="currentPrompt.is_default" :active-value="1" :inactive-value="0" />
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
.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.variable-item {
  margin-bottom: 10px;
  display: flex;
  gap: 10px;
}
</style>
