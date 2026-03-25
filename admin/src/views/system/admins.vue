<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getAdminUsers, saveAdminUser, deleteAdminUser, resetAdminPassword } from '@/api/system'

const loading = ref(false)
const adminsList = ref([])

// 新增/编辑弹窗
const dialogVisible = ref(false)
const dialogTitle = ref('新增管理员')
const formLoading = ref(false)
const formRef = ref(null)

const form = reactive({
  id: null,
  username: '',
  nickname: '',
  email: '',
  password: '',
  roles: ['operator'],
  status: 1
})

const formRules = {
  username: [{ required: true, message: '请输入用户名', trigger: 'blur' }],
  password: [{ required: true, message: '请输入密码', trigger: 'blur', validator: (rule, value, callback) => {
    if (!form.id && !value) {
      callback(new Error('新增管理员时密码不能为空'))
    } else if (value && value.length < 6) {
      callback(new Error('密码长度不能少于6位'))
    } else {
      callback()
    }
  }}]
}

// 重置密码弹窗
const resetPwdVisible = ref(false)
const resetPwdLoading = ref(false)
const resetPwdForm = reactive({ id: null, username: '', new_password: '' })
const resetPwdRef = ref(null)

onMounted(() => {
  fetchAdminsList()
})

async function fetchAdminsList() {
  loading.value = true
  try {
    const res = await getAdminUsers()
    if (res.code === 0) {
      adminsList.value = res.data.list || []
    }
  } catch (error) {
    ElMessage.error('获取管理员列表失败')
  } finally {
    loading.value = false
  }
}

// 新增管理员
function handleAdd() {
  dialogTitle.value = '新增管理员'
  Object.assign(form, {
    id: null,
    username: '',
    nickname: '',
    email: '',
    password: '',
    roles: ['operator'],
    status: 1
  })
  dialogVisible.value = true
}

// 编辑管理员
function handleEdit(row) {
  dialogTitle.value = '编辑管理员'
  Object.assign(form, {
    id: row.id,
    username: row.username,
    nickname: row.nickname || '',
    email: row.email || '',
    password: '',
    roles: row.roles || ['operator'],
    status: row.status
  })
  dialogVisible.value = true
}

// 提交新增/编辑
async function handleSubmit() {
  if (!formRef.value) return
  await formRef.value.validate(async (valid) => {
    if (!valid) return
    formLoading.value = true
    try {
      const submitData = { ...form }
      if (!submitData.password) delete submitData.password
      const res = await saveAdminUser(submitData)
      if (res.code === 0) {
        ElMessage.success(form.id ? '更新成功' : '创建成功')
        dialogVisible.value = false
        fetchAdminsList()
      } else {
        ElMessage.error(res.msg || '操作失败')
      }
    } catch (error) {
      ElMessage.error('操作失败')
    } finally {
      formLoading.value = false
    }
  })
}

// 打开重置密码弹窗
function handleResetPassword(row) {
  resetPwdForm.id = row.id
  resetPwdForm.username = row.username
  resetPwdForm.new_password = ''
  resetPwdVisible.value = true
}

// 提交重置密码
async function handleResetPwdSubmit() {
  if (!resetPwdForm.new_password) {
    ElMessage.warning('请输入新密码')
    return
  }
  if (resetPwdForm.new_password.length < 6) {
    ElMessage.warning('密码长度不能少于6位')
    return
  }
  resetPwdLoading.value = true
  try {
    const res = await resetAdminPassword(resetPwdForm.id, resetPwdForm.new_password)
    if (res.code === 0) {
      ElMessage.success('密码重置成功')
      resetPwdVisible.value = false
    } else {
      ElMessage.error(res.msg || '重置失败')
    }
  } catch (error) {
    ElMessage.error('重置失败')
  } finally {
    resetPwdLoading.value = false
  }
}

// 删除管理员
async function handleDelete(row) {
  try {
    await ElMessageBox.confirm(`确定要删除管理员"${row.username}"吗？此操作不可恢复！`, '警告', { type: 'warning' })
    const res = await deleteAdminUser(row.id)
    if (res.code === 0) {
      ElMessage.success('删除成功')
      fetchAdminsList()
    } else {
      ElMessage.error(res.msg || '删除失败')
    }
  } catch (e) {
    if (e !== 'cancel') ElMessage.error('删除失败')
  }
}
</script>

<template>
  <div class="app-container">
    <el-card v-loading="loading">
      <template #header>
        <div class="card-header">
          <span>管理员列表</span>
          <el-button type="primary" @click="handleAdd">新增管理员</el-button>
        </div>
      </template>

      <el-table :data="adminsList" stripe>
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="username" label="用户名" width="150" />
        <el-table-column prop="nickname" label="昵称" width="120" />
        <el-table-column prop="email" label="邮箱" width="200" />
        <el-table-column prop="roles" label="角色" width="150">
          <template #default="{ row }">
            <el-tag v-for="role in row.roles" :key="role" size="small" style="margin-right: 5px">
              {{ role }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="status" label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'danger'" size="small">
              {{ row.status === 1 ? '启用' : '禁用' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="创建时间" width="180" />
        <el-table-column label="操作" width="220" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" size="small" @click="handleEdit(row)">编辑</el-button>
            <el-button link type="warning" size="small" @click="handleResetPassword(row)">重置密码</el-button>
            <el-button link type="danger" size="small" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <!-- 新增/编辑弹窗 -->
    <el-dialog v-model="dialogVisible" :title="dialogTitle" width="500px" destroy-on-close>
      <el-form ref="formRef" :model="form" :rules="formRules" label-width="100px">
        <el-form-item label="用户名" prop="username">
          <el-input v-model="form.username" placeholder="请输入用户名" :disabled="!!form.id" />
        </el-form-item>
        <el-form-item label="昵称">
          <el-input v-model="form.nickname" placeholder="请输入昵称" />
        </el-form-item>
        <el-form-item label="邮箱">
          <el-input v-model="form.email" placeholder="请输入邮箱" />
        </el-form-item>
        <el-form-item :label="form.id ? '新密码' : '密码'" prop="password">
          <el-input
            v-model="form.password"
            type="password"
            :placeholder="form.id ? '不填则不修改密码' : '请输入密码'"
            show-password
          />
        </el-form-item>
        <el-form-item label="角色">
          <el-checkbox-group v-model="form.roles">
            <el-checkbox label="admin">超级管理员</el-checkbox>
            <el-checkbox label="operator">运营</el-checkbox>
          </el-checkbox-group>
        </el-form-item>
        <el-form-item label="状态">
          <el-radio-group v-model="form.status">
            <el-radio :label="1">启用</el-radio>
            <el-radio :label="0">禁用</el-radio>
          </el-radio-group>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="formLoading" @click="handleSubmit">确定</el-button>
      </template>
    </el-dialog>

    <!-- 重置密码弹窗 -->
    <el-dialog v-model="resetPwdVisible" title="重置密码" width="400px" destroy-on-close>
      <el-form label-width="100px">
        <el-form-item label="管理员">
          <span>{{ resetPwdForm.username }}</span>
        </el-form-item>
        <el-form-item label="新密码">
          <el-input
            v-model="resetPwdForm.new_password"
            type="password"
            placeholder="请输入新密码（至少6位）"
            show-password
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="resetPwdVisible = false">取消</el-button>
        <el-button type="primary" :loading="resetPwdLoading" @click="handleResetPwdSubmit">确定重置</el-button>
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
</style>
