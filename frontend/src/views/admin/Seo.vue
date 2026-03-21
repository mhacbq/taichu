<template>
  <div class="seo-manage">
    <div class="page-header">
      <h2>SEO配置</h2>
      <el-button type="primary" :icon="Plus" @click="handleAdd">新增配置</el-button>
    </div>

    <el-card>
      <el-form :inline="true" :model="searchForm" class="search-form">
        <el-form-item label="页面">
          <el-input v-model="searchForm.page" placeholder="输入页面名称" clearable />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" :icon="Search" @click="handleSearch">搜索</el-button>
          <el-button :icon="Refresh" @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>

      <el-table :data="tableData" v-loading="loading" style="width: 100%">
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="page" label="页面名称" width="150" />
        <el-table-column prop="path" label="路径" width="180" />
        <el-table-column prop="title" label="标题" min-width="250" show-overflow-tooltip />
        <el-table-column prop="keywords" label="关键词" min-width="200" show-overflow-tooltip />
        <el-table-column prop="updated_at" label="更新时间" width="160" />
        <el-table-column label="操作" width="180" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link size="small" @click="handleEdit(row)">编辑</el-button>
            <el-button type="success" link size="small" @click="handleGenerateSitemap(row)">生成Sitemap</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <!-- 新增/编辑对话框 -->
    <el-dialog
      v-model="dialogVisible"
      :title="isEdit ? '编辑SEO' : '新增SEO'"
      width="700px"
    >
      <el-form :model="form" :rules="rules" ref="formRef" label-width="100px">
        <el-form-item label="页面名称" prop="page">
          <el-input v-model="form.page" placeholder="如：首页" />
        </el-form-item>
        <el-form-item label="路径" prop="path">
          <el-input v-model="form.path" placeholder="如：/" />
        </el-form-item>
        <el-form-item label="标题" prop="title">
          <el-input v-model="form.title" placeholder="SEO标题" />
        </el-form-item>
        <el-form-item label="描述" prop="description">
          <el-input
            v-model="form.description"
            type="textarea"
            :rows="3"
            placeholder="SEO描述"
          />
        </el-form-item>
        <el-form-item label="关键词" prop="keywords">
          <el-input
            v-model="form.keywords"
            type="textarea"
            :rows="2"
            placeholder="关键词，用逗号分隔"
          />
        </el-form-item>
        <el-form-item label="Robots">
          <el-input v-model="form.robots" placeholder="如：index,follow" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="submitting" @click="handleSubmit">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { Plus, Search, Refresh } from '@element-plus/icons-vue'

const loading = ref(false)
const tableData = ref([])
const dialogVisible = ref(false)
const isEdit = ref(false)
const submitting = ref(false)
const formRef = ref(null)

const form = reactive({
  id: null,
  page: '',
  path: '',
  title: '',
  description: '',
  keywords: '',
  robots: 'index,follow'
})

const rules = {
  page: [{ required: true, message: '请输入页面名称', trigger: 'blur' }],
  path: [{ required: true, message: '请输入路径', trigger: 'blur' }],
  title: [{ required: true, message: '请输入标题', trigger: 'blur' }]
}

const searchForm = reactive({
  page: ''
})

const fetchData = async () => {
  loading.value = true
  try {
    const response = await fetch('/api/admin/seo')
    const data = await response.json()
    
    if (data.code === 200) {
      tableData.value = data.data || []
    }
  } catch (error) {
    ElMessage.error('获取数据失败')
  } finally {
    loading.value = false
  }
}

const handleSearch = () => {
  fetchData()
}

const handleReset = () => {
  searchForm.page = ''
  fetchData()
}

const handleAdd = () => {
  isEdit.value = false
  Object.assign(form, {
    id: null,
    page: '',
    path: '',
    title: '',
    description: '',
    keywords: '',
    robots: 'index,follow'
  })
  dialogVisible.value = true
}

const handleEdit = (row) => {
  isEdit.value = true
  Object.assign(form, { ...row })
  dialogVisible.value = true
}

const handleSubmit = async () => {
  try {
    await formRef.value.validate()
    submitting.value = true
    
    const url = isEdit.value
      ? `/api/admin/seo/${form.id}`
      : '/api/admin/seo'
    const method = isEdit.value ? 'PUT' : 'POST'
    
    const response = await fetch(url, {
      method,
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(form)
    })
    
    const data = await response.json()
    
    if (data.code === 200) {
      ElMessage.success(isEdit.value ? '修改成功' : '新增成功')
      dialogVisible.value = false
      fetchData()
    } else {
      ElMessage.error(data.message || '操作失败')
    }
  } catch (error) {
    ElMessage.error('操作失败')
  } finally {
    submitting.value = false
  }
}

const handleGenerateSitemap = async () => {
  try {
    const response = await fetch('/api/admin/seo/sitemap-generate', {
      method: 'POST'
    })
    const data = await response.json()
    
    if (data.code === 200) {
      ElMessage.success('Sitemap生成成功')
    } else {
      ElMessage.error(data.message || '生成失败')
    }
  } catch (error) {
    ElMessage.error('生成失败')
  }
}

onMounted(() => {
  fetchData()
})
</script>

<style scoped>
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}
.page-header h2 {
  margin: 0;
  font-size: 20px;
  color: #333;
}
.search-form {
  margin-bottom: 20px;
}
</style>
