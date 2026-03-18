<template>
  <div class="app-container">
    <div class="table-operations">
      <el-button type="primary" @click="handleAdd">
        <el-icon><Plus /></el-icon>新增管理员
      </el-button>
      <el-button :loading="loading" @click="loadAdminList">刷新</el-button>
    </div>

    <el-card shadow="never">
      <el-table :data="adminList" v-loading="loading" stripe>
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="username" label="用户名" width="140" />
        <el-table-column prop="nickname" label="昵称" width="140" />
        <el-table-column prop="role_name" label="角色" width="140">
          <template #default="{ row }">
            <el-tag>{{ row.role_name }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="last_login_at" label="最后登录" min-width="180" />
        <el-table-column prop="status" label="状态" width="100">
          <template #default="{ row }">
            <el-switch
              v-model="row.status"
              :active-value="1"
              :inactive-value="0"
              @change="value => handleToggleStatus(row, value)"
            />
          </template>
        </el-table-column>
        <el-table-column label="操作" width="180" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" @click="handleEdit(row)">编辑</el-button>
            <el-button link type="danger" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <el-dialog v-model="dialog.visible" :title="dialog.isEdit ? '编辑管理员' : '新增管理员'" width="500px">
      <el-form :model="dialog.form" label-width="80px">
        <el-form-item label="用户名">
          <el-input v-model="dialog.form.username" :disabled="dialog.isEdit" />
        </el-form-item>
        <el-form-item label="昵称">
          <el-input v-model="dialog.form.nickname" />
        </el-form-item>
        <el-form-item :label="dialog.isEdit ? '重置密码' : '密码'">
          <el-input v-model="dialog.form.password" type="password" show-password placeholder="编辑时留空则不修改密码" />
        </el-form-item>
        <el-form-item label="角色">
          <el-select v-model="dialog.form.role" style="width: 100%">
            <el-option label="超级管理员" value="admin" />
            <el-option label="运营人员" value="operator" />
          </el-select>
        </el-form-item>
        <el-form-item label="状态">
          <el-radio-group v-model="dialog.form.status">
            <el-radio :label="1">启用</el-radio>
            <el-radio :label="0">禁用</el-radio>
          </el-radio-group>

        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialog.visible = false">取消</el-button>
        <el-button type="primary" :loading="submitLoading" @click="handleSubmit">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { deleteAdminUser, getAdminUsers, saveAdminUser } from '@/api/system'

const loading = ref(false)
const submitLoading = ref(false)
const adminList = ref([])
const dialog = reactive({
  visible: false,
  isEdit: false,
  form: createDefaultForm()
})

onMounted(() => {
  loadAdminList()
})

function createDefaultForm() {
  return {
    id: 0,
    username: '',
    nickname: '',
    password: '',
    role: 'operator',
    status: 1
  }
}

function normalizeRole(row) {
  return row.role || row.role_code || 'operator'
}

async function loadAdminList() {
  loading.value = true
  try {
    const { data } = await getAdminUsers()
    adminList.value = (data.list || []).map(item => ({
      ...item,
      role: normalizeRole(item),
      status: Number(item.status ?? 0)
    }))
  } finally {
    loading.value = false
  }
}

function handleAdd() {
  dialog.isEdit = false
  dialog.form = createDefaultForm()
  dialog.visible = true
}

function handleEdit(row) {
  dialog.isEdit = true
  dialog.form = {
    id: row.id,
    username: row.username,
    nickname: row.nickname || '',
    password: '',
    role: normalizeRole(row),
    status: Number(row.status ?? 1)
  }
  dialog.visible = true
}

async function handleSubmit() {
  submitLoading.value = true
  try {
    await saveAdminUser({ ...dialog.form })
    ElMessage.success(dialog.isEdit ? '管理员已更新' : '管理员已创建')
    dialog.visible = false
    await loadAdminList()
  } finally {
    submitLoading.value = false
  }
}

async function handleToggleStatus(row, value) {
  const previousStatus = value === 1 ? 0 : 1
  try {
    await saveAdminUser({
      id: row.id,
      username: row.username,
      nickname: row.nickname,
      role: normalizeRole(row),
      status: value
    })
    row.status = value
    ElMessage.success('状态已更新')
  } catch (error) {
    row.status = previousStatus
  }
}

async function handleDelete(row) {
  try {
    await ElMessageBox.confirm(`确定删除管理员“${row.username}”吗？`, '警告', { type: 'warning' })
    await deleteAdminUser(row.id)
    ElMessage.success('管理员已删除')
    await loadAdminList()
  } catch (error) {
    if (error !== 'cancel') {
      // 统一由请求层提示错误
    }
  }
}
</script>

<style scoped>
.app-container { padding: 20px; }
.table-operations { margin-bottom: 20px; display: flex; gap: 12px; }
</style>
