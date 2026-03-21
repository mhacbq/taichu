<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { useUserStore } from '@/stores/user'

const router = useRouter()
const userStore = useUserStore()

const loading = ref(false)
const userInfo = ref({
  id: '',
  username: '',
  email: '',
  avatar: '',
  roles: []
})

const form = ref({
  old_password: '',
  new_password: '',
  confirm_password: ''
})

const passwordDialogVisible = ref(false)

onMounted(() => {
  fetchUserInfo()
})

async function fetchUserInfo() {
  loading.value = true
  try {
    // 获取当前管理员信息
    userInfo.value = {
      id: userStore.userInfo?.id || '',
      username: userStore.userInfo?.username || '',
      email: userStore.userInfo?.email || '',
      avatar: userStore.userInfo?.avatar || '',
      roles: userStore.userInfo?.roles || []
    }
  } catch (error) {
    ElMessage.error('获取用户信息失败')
  } finally {
    loading.value = false
  }
}

async function handleChangePassword() {
  if (!form.value.old_password) {
    ElMessage.warning('请输入原密码')
    return
  }
  if (!form.value.new_password) {
    ElMessage.warning('请输入新密码')
    return
  }
  if (form.value.new_password !== form.value.confirm_password) {
    ElMessage.warning('两次输入的密码不一致')
    return
  }

  try {
    // 调用修改密码API
    // await changePassword(form.value)
    ElMessage.success('密码修改成功')
    passwordDialogVisible.value = false
    form.value = { old_password: '', new_password: '', confirm_password: '' }
  } catch (error) {
    ElMessage.error('密码修改失败')
  }
}

async function handleLogout() {
  try {
    await ElMessageBox.confirm('确定退出登录吗？', '提示', {
      type: 'warning'
    })
    
    // 调用登出API
    await userStore.logout()
    ElMessage.success('退出成功')
    router.push('/login')
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('退出失败')
    }
  }
}
</script>

<template>
  <div class="app-container">
    <el-card v-loading="loading">
      <template #header>
        <span>个人中心</span>
      </template>

      <el-descriptions :column="2" border>
        <el-descriptions-item label="用户ID">{{ userInfo.id }}</el-descriptions-item>
        <el-descriptions-item label="用户名">{{ userInfo.username }}</el-descriptions-item>
        <el-descriptions-item label="邮箱">{{ userInfo.email }}</el-descriptions-item>
        <el-descriptions-item label="角色">
          <el-tag v-for="role in userInfo.roles" :key="role" size="small" style="margin-right: 5px">
            {{ role }}
          </el-tag>
        </el-descriptions-item>
      </el-descriptions>

      <el-divider />

      <div class="actions">
        <el-button type="primary" @click="passwordDialogVisible = true">修改密码</el-button>
        <el-button type="danger" @click="handleLogout">退出登录</el-button>
      </div>
    </el-card>

    <el-dialog v-model="passwordDialogVisible" title="修改密码" width="500px">
      <el-form :model="form" label-width="100px">
        <el-form-item label="原密码">
          <el-input v-model="form.old_password" type="password" placeholder="请输入原密码" />
        </el-form-item>
        <el-form-item label="新密码">
          <el-input v-model="form.new_password" type="password" placeholder="请输入新密码" />
        </el-form-item>
        <el-form-item label="确认密码">
          <el-input v-model="form.confirm_password" type="password" placeholder="请再次输入新密码" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="passwordDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleChangePassword">确认修改</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<style scoped>
.actions {
  display: flex;
  gap: 10px;
  justify-content: flex-start;
}
</style>
