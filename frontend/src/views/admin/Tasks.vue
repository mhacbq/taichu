<template>
  <div class="tasks-manage">
    <div class="page-header">
      <h2>定时任务</h2>
      <el-button type="primary" :icon="Plus" @click="handleAdd">新增任务</el-button>
    </div>

    <el-card>
      <el-table :data="tableData" v-loading="loading" style="width: 100%">
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="name" label="任务名称" width="180" />
        <el-table-column prop="command" label="执行命令" min-width="250" show-overflow-tooltip />
        <el-table-column prop="cron" label="Cron表达式" width="150" />
        <el-table-column prop="next_run_time" label="下次执行时间" width="160" />
        <el-table-column prop="last_run_time" label="上次执行时间" width="160" />
        <el-table-column prop="status" label="状态" width="80">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'danger'">
              {{ row.status === 1 ? '运行中' : '已停止' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="last_status" label="执行状态" width="100">
          <template #default="{ row }">
            <el-tag v-if="row.last_status" :type="row.last_status === 'success' ? 'success' : 'danger'">
              {{ row.last_status === 'success' ? '成功' : '失败' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="250" fixed="right">
          <template #default="{ row }">
            <el-button
              :type="row.status === 1 ? 'warning' : 'success'"
              link
              size="small"
              @click="handleToggleStatus(row)"
            >
              {{ row.status === 1 ? '停止' : '启动' }}
            </el-button>
            <el-button type="primary" link size="small" @click="handleEdit(row)">编辑</el-button>
            <el-button type="primary" link size="small" @click="handleRunNow(row)">立即执行</el-button>
            <el-button type="danger" link size="small" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <!-- 新增/编辑对话框 -->
    <el-dialog
      v-model="dialogVisible"
      :title="isEdit ? '编辑任务' : '新增任务'"
      width="600px"
    >
      <el-form :model="form" :rules="rules" ref="formRef" label-width="120px">
        <el-form-item label="任务名称" prop="name">
          <el-input v-model="form.name" placeholder="请输入任务名称" />
        </el-form-item>
        <el-form-item label="执行命令" prop="command">
          <el-input v-model="form.command" placeholder="请输入执行命令" />
        </el-form-item>
        <el-form-item label="Cron表达式" prop="cron">
          <el-input v-model="form.cron" placeholder="如：0 0 * * *" />
          <div class="cron-tip">
            <small>格式：分 时 日 月 周 | 示例：0 0 * * * (每天零点执行)</small>
          </div>
        </el-form-item>
        <el-form-item label="状态" prop="status">
          <el-radio-group v-model="form.status">
            <el-radio :label="1">启动</el-radio>
            <el-radio :label="0">停止</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="备注">
          <el-input v-model="form.remark" type="textarea" :rows="2" placeholder="请输入备注" />
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
  command: '',
  cron: '0 0 * * *',
  status: 1,
  remark: ''
})

const rules = {
  name: [{ required: true, message: '请输入任务名称', trigger: 'blur' }],
  command: [{ required: true, message: '请输入执行命令', trigger: 'blur' }],
  cron: [{ required: true, message: '请输入Cron表达式', trigger: 'blur' }]
}

const fetchData = async () => {
  loading.value = true
  try {
    const response = await fetch('/api/admin/tasks')
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
    command: '',
    cron: '0 0 * * *',
    status: 1,
    remark: ''
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
      ? `/api/admin/tasks/${form.id}`
      : '/api/admin/tasks'
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
    const response = await fetch(`/api/admin/tasks/${row.id}/toggle`, {
      method: 'POST',
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

const handleRunNow = async (row) => {
  try {
    await ElMessageBox.confirm(`确定立即执行任务 "${row.name}" 吗？`, '提示', { type: 'info' })
    
    const response = await fetch(`/api/admin/tasks/${row.id}/run`, {
      method: 'POST'
    })
    
    const data = await response.json()
    
    if (data.code === 200) {
      ElMessage.success('任务已加入执行队列')
    } else {
      ElMessage.error(data.message || '执行失败')
    }
  } catch (error) {
    if (error !== 'cancel') ElMessage.error('执行失败')
  }
}

const handleDelete = async (row) => {
  try {
    await ElMessageBox.confirm(`确定删除任务 "${row.name}" 吗？`, '警告', { type: 'warning' })
    
    const response = await fetch(`/api/admin/tasks/${row.id}`, {
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
.cron-tip {
  margin-top: 5px;
  color: #999;
}
</style>
