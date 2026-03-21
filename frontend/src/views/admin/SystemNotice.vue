<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getNotices, saveNotice, deleteNotice } from '@/api/admin'

const loading = ref(false)
const dialogVisible = ref(false)
const dialogMode = ref('add')
const notices = ref([])
const total = ref(0)
const currentPage = ref(1)
const pageSize = ref(20)
const statusFilter = ref('')

const formRef = ref()
const formData = ref({
  id: 0,
  title: '',
  content: '',
  type: 'normal',
  status: 1
})

const rules = {
  title: [{ required: true, message: '请输入公告标题', trigger: 'blur' }],
  content: [{ required: true, message: '请输入公告内容', trigger: 'blur' }]
}

const loadNotices = async () => {
  loading.value = true
  try {
    const response = await getNotices({
      page: currentPage.value,
      pageSize: pageSize.value,
      status: statusFilter.value
    })
    if (response.code === 200) {
      notices.value = response.data.list || []
      total.value = response.data.total || 0
    } else {
      ElMessage.error(response.message || '获取公告列表失败')
    }
  } catch (error) {
    console.error('获取公告列表失败:', error)
    ElMessage.error('获取公告列表失败')
  } finally {
    loading.value = false
  }
}

const handleSearch = () => {
  currentPage.value = 1
  loadNotices()
}

const handleReset = () => {
  statusFilter.value = ''
  currentPage.value = 1
  loadNotices()
}

const handleAdd = () => {
  dialogMode.value = 'add'
  formData.value = {
    id: 0,
    title: '',
    content: '',
    type: 'normal',
    status: 1
  }
  dialogVisible.value = true
}

const handleEdit = (row) => {
  dialogMode.value = 'edit'
  formData.value = {
    id: row.id,
    title: row.title,
    content: row.content,
    type: row.type,
    status: row.status
  }
  dialogVisible.value = true
}

const handleDelete = async (row) => {
  try {
    await ElMessageBox.confirm(`确定要删除公告"${row.title}"吗？`, '确认删除', {
      type: 'warning'
    })
    const response = await deleteNotice(row.id)
    if (response.code === 200) {
      ElMessage.success('删除成功')
      loadNotices()
    } else {
      ElMessage.error(response.message || '删除失败')
    }
  } catch (error) {
    if (error !== 'cancel') {
      console.error('删除公告失败:', error)
      ElMessage.error('删除失败')
    }
  }
}

const handleSubmit = async () => {
  try {
    await formRef.value.validate()
    const response = await saveNotice(formData.value)
    if (response.code === 200) {
      ElMessage.success('保存成功')
      dialogVisible.value = false
      loadNotices()
    } else {
      ElMessage.error(response.message || '保存失败')
    }
  } catch (error) {
    if (error !== false) {
      console.error('保存公告失败:', error)
      ElMessage.error('保存失败')
    }
  }
}

const handlePageChange = (page) => {
  currentPage.value = page
  loadNotices()
}

const getTypeText = (type) => {
  return type === 'important' ? '重要' : '普通'
}

const getTypeClass = (type) => {
  return type === 'important' ? 'danger' : ''
}

const toggleStatus = async (row) => {
  const newStatus = row.status === 1 ? 0 : 1
  const response = await saveNotice({
    id: row.id,
    title: row.title,
    content: row.content,
    type: row.type,
    status: newStatus
  })
  if (response.code === 200) {
    ElMessage.success(newStatus === 1 ? '已启用' : '已禁用')
    loadNotices()
  } else {
    ElMessage.error(response.message || '操作失败')
  }
}

onMounted(() => {
  loadNotices()
})
</script>

<template>
  <div class="admin-system-notice">
    <div class="page-header">
      <h2>系统公告管理</h2>
    </div>

    <el-card class="filter-card">
      <el-form inline>
        <el-form-item label="状态">
          <el-select v-model="statusFilter" placeholder="全部状态" clearable @change="handleSearch">
            <el-option label="全部" value="" />
            <el-option label="已启用" :value="1" />
            <el-option label="已禁用" :value="0" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">查询</el-button>
          <el-button @click="handleReset">重置</el-button>
          <el-button type="primary" @click="handleAdd">新增公告</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <el-card class="table-card">
      <el-table :data="notices" v-loading="loading" stripe>
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="title" label="公告标题" width="250" show-overflow-tooltip />
        <el-table-column label="类型" width="100">
          <template #default="{ row }">
            <el-tag :type="getTypeClass(row.type)" size="small">
              {{ getTypeText(row.type) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="content" label="公告内容" show-overflow-tooltip />
        <el-table-column label="状态" width="100">
          <template #default="{ row }">
            <el-switch
              v-model="row.status"
              :active-value="1"
              :inactive-value="0"
              @change="toggleStatus(row)"
            />
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="创建时间" width="180" />
        <el-table-column label="操作" width="150" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link size="small" @click="handleEdit(row)">编辑</el-button>
            <el-button type="danger" link size="small" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>

      <el-pagination
        v-model:current-page="currentPage"
        v-model:page-size="pageSize"
        :total="total"
        :page-sizes="[20, 50, 100]"
        layout="total, sizes, prev, pager, next, jumper"
        @size-change="loadNotices"
        @current-change="handlePageChange"
        style="margin-top: 20px; justify-content: flex-end"
      />
    </el-card>

    <el-dialog
      v-model="dialogVisible"
      :title="dialogMode === 'add' ? '新增公告' : '编辑公告'"
      width="600px"
    >
      <el-form ref="formRef" :model="formData" :rules="rules" label-width="100px">
        <el-form-item label="公告标题" prop="title">
          <el-input v-model="formData.title" placeholder="请输入公告标题" />
        </el-form-item>
        <el-form-item label="公告类型" prop="type">
          <el-radio-group v-model="formData.type">
            <el-radio label="normal">普通公告</el-radio>
            <el-radio label="important">重要公告</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="公告内容" prop="content">
          <el-input
            v-model="formData.content"
            type="textarea"
            :rows="6"
            placeholder="请输入公告内容"
          />
        </el-form-item>
        <el-form-item label="状态">
          <el-switch
            v-model="formData.status"
            :active-value="1"
            :inactive-value="0"
            active-text="启用"
            inactive-text="禁用"
          />
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
.admin-system-notice {
  padding: 24px;
}

.page-header {
  margin-bottom: 20px;
}

.filter-card {
  margin-bottom: 20px;
}

.table-card {
  margin-bottom: 20px;
}
</style>
