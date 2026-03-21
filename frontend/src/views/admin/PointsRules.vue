<template>
  <div class="points-rules-manage">
    <div class="page-header">
      <h2>积分规则</h2>
      <el-button type="primary" :icon="Plus" @click="handleAdd">新增规则</el-button>
    </div>

    <el-card>
      <el-table :data="tableData" v-loading="loading" style="width: 100%">
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="name" label="规则名称" width="180" />
        <el-table-column prop="code" label="规则代码" width="150" />
        <el-table-column prop="points" label="积分值" width="100" />
        <el-table-column prop="type" label="类型" width="100">
          <template #default="{ row }">
            <el-tag :type="row.type === 'reward' ? 'success' : 'danger'">
              {{ row.type === 'reward' ? '奖励' : '扣除' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="description" label="描述" min-width="200" />
        <el-table-column prop="status" label="状态" width="80">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'info'">
              {{ row.status === 1 ? '启用' : '禁用' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="创建时间" width="160" />
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
      :title="isEdit ? '编辑规则' : '新增规则'"
      width="600px"
    >
      <el-form :model="form" :rules="rules" ref="formRef" label-width="100px">
        <el-form-item label="规则名称" prop="name">
          <el-input v-model="form.name" placeholder="请输入规则名称" />
        </el-form-item>
        <el-form-item label="规则代码" prop="code">
          <el-input v-model="form.code" placeholder="请输入规则代码（英文）" />
        </el-form-item>
        <el-form-item label="积分值" prop="points">
          <el-input-number v-model="form.points" :min="-99999" :max="99999" />
        </el-form-item>
        <el-form-item label="类型" prop="type">
          <el-radio-group v-model="form.type">
            <el-radio label="reward">奖励</el-radio>
            <el-radio label="deduct">扣除</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="描述" prop="description">
          <el-input
            v-model="form.description"
            type="textarea"
            :rows="3"
            placeholder="请输入描述"
          />
        </el-form-item>
        <el-form-item label="状态" prop="status">
          <el-switch v-model="form.status" :active-value="1" :inactive-value="0" />
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
  code: '',
  points: 0,
  type: 'reward',
  description: '',
  status: 1
})

const rules = {
  name: [{ required: true, message: '请输入规则名称', trigger: 'blur' }],
  code: [{ required: true, message: '请输入规则代码', trigger: 'blur' }],
  points: [{ required: true, message: '请输入积分值', trigger: 'blur' }],
  type: [{ required: true, message: '请选择类型', trigger: 'change' }]
}

const fetchData = async () => {
  loading.value = true
  try {
    const response = await fetch('/api/admin/points/rules')
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
    code: '',
    points: 0,
    type: 'reward',
    description: '',
    status: 1
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
      ? `/api/admin/points/rules/${form.id}`
      : '/api/admin/points/rules'
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
    const response = await fetch(`/api/admin/points/rules/${row.id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ status: newStatus })
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
    await ElMessageBox.confirm(`确定删除规则 "${row.name}" 吗？`, '警告', { type: 'warning' })
    
    const response = await fetch(`/api/admin/points/rules/${row.id}`, {
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
