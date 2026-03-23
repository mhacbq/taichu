<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { User, Lock } from '@element-plus/icons-vue'
import request from '../../api/request'

const router = useRouter()
const loading = ref(false)
const form = ref({
  username: '',
  password: ''
})

const handleLogin = async () => {
  if (!form.value.username || !form.value.password) {
    ElMessage.warning('请输入用户名和密码')
    return
  }

  loading.value = true
  try {
    const response = await request.post('/maodou/auth/login', form.value)
    
    if (response.code === 200) {
      localStorage.setItem('adminToken', response.data.token)
      localStorage.setItem('adminUserInfo', JSON.stringify(response.data.user))
      ElMessage.success('登录成功')
      
      const redirect = router.currentRoute.value.query.redirect || '/maodou/dashboard'
      router.push(redirect)
    } else {
      ElMessage.error(response.message || '登录失败')
    }
  } catch (error) {
    ElMessage.error('登录失败，请检查网络连接')
    console.error('Login error:', error)
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="admin-login-container">
    <div class="login-card">
      <h1 class="login-title">太初命理 - 管理后台</h1>
      
      <el-form class="login-form" @submit.prevent="handleLogin">
        <el-form-item>
          <el-input
            v-model="form.username"
            placeholder="请输入管理员账号"
            size="large"
            clearable
            @keyup.enter="handleLogin"
          </el-input>
        </el-form-item>
        
        <el-form-item>
          <el-input
            v-model="form.password"
            type="password"
            placeholder="请输入密码"
            size="large"
            show-password
            @keyup.enter="handleLogin"
          </el-input>
        </el-form-item>
        
        <el-form-item>
          <el-button
            type="primary"
            size="large"
            :loading="loading"
            @click="handleLogin"
            style="width: 100%"
          >
            登录
          </el-button>
        </el-form-item>
      </el-form>
    </div>
  </div>
</template>

<style scoped>
.admin-login-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.login-card {
  width: 400px;
  padding: 40px;
  background: white;
  border-radius: 8px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
}

.login-title {
  text-align: center;
  margin-bottom: 30px;
  font-size: 24px;
  color: #333;
}

.login-form .el-form-item {
  margin-bottom: 20px;
}
</style>
