<template>
  <div class="shensha-manage">
    <div class="page-header">
      <h2>神煞管理</h2>
      <el-button type="primary" :icon="Plus" @click="handleAdd">新增神煞</el-button>
    </div>

    <el-card>
      <el-table :data="tableData" v-loading="loading" style="width: 100%">
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="name" label="神煞名称" width="150" />
        <el-table-column prop="type" label="类型" width="100">
          <template #default="{ row }">
            <el-tag :type="row.type === '吉' ? 'success' : 'danger'">{{ row.type }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="category" label="分类" width="120" />
        <el-table-column prop="description" label="描述" min-width="250" />
        <el-table-column prop="status" label="状态" width="80">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'info'">
              {{ row.status === 1 ? '启用' : '禁用' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="sort" label="排序" width="80" />
        <el-table-column label="操作" width="200" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link size="small" @click="handleEdit(row)">编辑</el-button>
            <el-button
              :type="row.status === 1 ? 'warning' : 'success'"
              link
              size="small"
              @click="handleToggleStatus(row)"
            >
              {{ row.status === 1 ? '禁用' : '启用' }}
            </el-button>
            <el-button type="danger" link size="small" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <!-- 新增/编辑对话框 -->
    <el-dialog
      v-model="dialogVisible"
      :title="isEdit ? '编辑神煞' : '新增神煞'"
      width="600px"
    >
      <el-form :model="form" :rules="rules" ref="formRef" label-width="100px">
        <el-form-item label="神煞名称" prop="name">
          <el-input v-model="form.name" placeholder="请输入神煞名称" />
        </el-form-item>
        <el-form-item label="类型" prop="type">
          <el-radio-group v-model="form.type">
            <el-radio label="吉">吉神</el-radio>
            <el-radio label="凶">凶煞</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="分类" prop="category">
          <el-select v-model="form.category" placeholder="选择分类">
            <el-option label="年神" value="年神" />
            <el-option label="月神" value="月神" />
            <el-option label="日神" value="日神" />
            <el-option label="时神" value="时神" />
          </el-select>
        </el-form-item>
        <el-form-item label="描述" prop="description">
          <el-input
            v-model="form.description"
            type="textarea"
            :rows="4"
            placeholder="请输入神煞描述"
          />
        </el-form-item>
        <el-form-item label="状态" prop="status">
          <el-switch v-model="form.status" :active-value="1" :inactive-value="0" />
        </el-form-item>
        <el-form-item label="排序" prop="sort">
          <el-input-number v-model="form.sort" :min="0" :max="999" />
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
import { ElMessage, ElMessageBox } from 'element-plus'
import { Plus } from '@element-plus/icons-vue'

const loading = ref(false)
const tableData = ref([])
const dialogVisible = ref(false)
const isEdit = ref(false)
const submitting = ref(false)
const formRef = ref(null)

const form = reactive({
  id: null,
  name: '',
  type: '吉',
  category: '',
  description: '',
  status: 1,
  sort: 0
})

const rules = {
  name: [{ required: true, message: '请输入神煞名称', trigger: 'blur' }],
  type: [{ required: true, message: '请选择类型', trigger: 'change' }],
  category: [{ required: true, message: '请选择分类', trigger: 'change' }],
  description: [{ required: true, message: '请输入描述', trigger: 'blur' }]
}

const fetchData = async () => {
  loading.value = true
  try {
    const response = await fetch('/api/admin/shensha')
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

const handleAdd = () => {
  isEdit.value = false
  Object.assign(form, {
    id: null,
    name: '',
    type: '吉',
    category: '',
    description: '',
    status: 1,
    sort: 0
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
      ? `/api/admin/shensha/${form.id}`
      : '/api/admin/shensha'
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

const handleToggleStatus = async (row) => {
  const newStatus = row.status === 1 ? 0 : 1
  try {
    const response = await fetch('/api/admin/shensha/toggle-status', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: row.id, status: newStatus })
    })
    
    const data = await response.json()
    
    if (data.code === 200) {
      ElMessage.success('状态已更新')
      fetchData()
    } else {
      ElMessage.error(data.message || '操作失败')
    }
  } catch (error) {
    ElMessage.error('操作失败')
  }
}

const handleDelete = async (row) => {
  try {
    await ElMessageBox.confirm(`确定删除神煞 "${row.name}" 吗？`, '警告', { type: 'warning' })
    
    const response = await fetch(`/api/admin/shensha/${row.id}`, {
      method: 'DELETE'
    })
    
    const data = await response.json()
    
    if (data.code === 200) {
      ElMessage.success('删除成功')
      fetchData()
    } else {
      ElMessage.error(data.message || '删除失败')
    }
  } catch (error) {
    if (error !== 'cancel') ElMessage.error('删除失败')
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
</style>
