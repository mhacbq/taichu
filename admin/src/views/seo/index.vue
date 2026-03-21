<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'

const loading = ref(false)
const dialogVisible = ref(false)
const dialogTitle = ref('')
const formRef = ref()

const list = ref([])
const total = ref(0)
const currentPage = ref(1)
const pageSize = ref(20)
const pageTypes = ref<any[]>([])

const queryParams = ref({
  page_type: ''
})

const formData = ref({
  id: 0,
  page_type: '',
  title: '',
  keywords: '',
  description: '',
  status: 1
})

onMounted(() => {
  fetchList()
  fetchPageTypes()
})

async function fetchList() {
  loading.value = true
  try {
    const res = await window.$api.get('/api/maodou/seo/list', {
      params: {
        ...queryParams.value,
        page: currentPage.value,
        page_size: pageSize.value
      }
    })
    list.value = res.data.list
    total.value = res.data.total
  } catch (error) {
    ElMessage.error('获取SEO配置失败')
  } finally {
    loading.value = false
  }
}

async function fetchPageTypes() {
  try {
    const res = await window.$api.get('/api/maodou/seo/page-types')
    pageTypes.value = Object.entries(res.data).map(([key, value]: [string, any]) => ({
      key,
      ...value
    }))
  } catch (error) {
    console.error('获取页面类型失败', error)
  }
}

function handleSearch() {
  currentPage.value = 1
  fetchList()
}

function handleReset() {
  queryParams.value = {
    page_type: ''
  }
  currentPage.value = 1
  fetchList()
}

function handleAdd() {
  dialogTitle.value = '新增SEO配置'
  formData.value = {
    id: 0,
    page_type: '',
    title: '',
    keywords: '',
    description: '',
    status: 1
  }
  dialogVisible.value = true
}

function handleEdit(row: any) {
  dialogTitle.value = '编辑SEO配置'
  formData.value = { ...row }
  dialogVisible.value = true
}

async function handleDelete(row: any) {
  try {
    await ElMessageBox.confirm('确定要删除该配置吗？', '提示', {
      type: 'warning'
    })
    
    await window.$api.delete(`/api/maodou/seo/${row.id}`)
    ElMessage.success('删除成功')
    fetchList()
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('删除失败')
    }
  }
}

async function handleSubmit() {
  if (!formRef.value) return
  
  try {
    await formRef.value.validate()
    
    if (formData.value.id) {
      await window.$api.post('/api/maodou/seo/save', formData.value)
      ElMessage.success('更新成功')
    } else {
      await window.$api.post('/api/maodou/seo/save', formData.value)
      ElMessage.success('创建成功')
    }
    
    dialogVisible.value = false
    fetchList()
  } catch (error) {
    ElMessage.error('操作失败')
  }
}

function handlePageChange(page: number) {
  currentPage.value = page
  fetchList()
}

const rules = {
  page_type: [
    { required: true, message: '请选择页面类型', trigger: 'change' }
  ],
  title: [
    { required: true, message: '请输入页面标题', trigger: 'blur' }
  ],
  keywords: [
    { required: true, message: '请输入关键词', trigger: 'blur' }
  ],
  description: [
    { required: true, message: '请输入页面描述', trigger: 'blur' }
  ]
}
</script>

<template>
  <div class="app-container">
    <el-card v-loading="loading">
      <template #header>
        <div class="card-header">
          <span>SEO配置管理</span>
          <el-button type="primary" @click="handleAdd">新增配置</el-button>
        </div>
      </template>

      <el-form :inline="true" :model="queryParams">
        <el-form-item label="页面类型">
          <el-select v-model="queryParams.page_type" placeholder="全部" clearable>
            <el-option
              v-for="type in pageTypes"
              :key="type.key"
              :label="type.name"
              :value="type.key"
            />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">查询</el-button>
          <el-button @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>

      <el-table :data="list" border>
        <el-table-column prop="page_type" label="页面类型" width="150" />
        <el-table-column prop="title" label="页面标题" />
        <el-table-column prop="keywords" label="关键词" show-overflow-tooltip />
        <el-table-column prop="description" label="描述" show-overflow-tooltip />
        <el-table-column prop="status" label="状态" width="80">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'info'">
              {{ row.status === 1 ? '启用' : '禁用' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="创建时间" width="180" />
        <el-table-column label="操作" width="150" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link @click="handleEdit(row)">编辑</el-button>
            <el-button type="danger" link @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>

      <el-pagination
        v-model:current-page="currentPage"
        v-model:page-size="pageSize"
        :total="total"
        :page-sizes="[10, 20, 50, 100]"
        layout="total, sizes, prev, pager, next, jumper"
        @current-change="handlePageChange"
        @size-change="fetchList"
      />
    </el-card>

    <el-dialog
      v-model="dialogVisible"
      :title="dialogTitle"
      width="600px"
    >
      <el-form
        ref="formRef"
        :model="formData"
        :rules="rules"
        label-width="100px"
      >
        <el-form-item label="页面类型" prop="page_type">
          <el-select v-model="formData.page_type" placeholder="请选择页面类型">
            <el-option
              v-for="type in pageTypes"
              :key="type.key"
              :label="type.name"
              :value="type.key"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="页面标题" prop="title">
          <el-input v-model="formData.title" placeholder="请输入页面标题" />
        </el-form-item>
        <el-form-item label="关键词" prop="keywords">
          <el-input
            v-model="formData.keywords"
            type="textarea"
            :rows="2"
            placeholder="多个关键词用英文逗号分隔"
          />
        </el-form-item>
        <el-form-item label="页面描述" prop="description">
          <el-input
            v-model="formData.description"
            type="textarea"
            :rows="3"
            placeholder="请输入页面描述"
          />
        </el-form-item>
        <el-form-item label="状态">
          <el-switch v-model="formData.status" :active-value="1" :inactive-value="0" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSubmit">确定</el-button>
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

.el-pagination {
  margin-top: 20px;
  text-align: right;
}
</style>
