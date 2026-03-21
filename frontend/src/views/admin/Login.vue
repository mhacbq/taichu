<template>
  <div class="admin-login">
    <div class="login-container">
      <div class="login-header">
        <div class="logo-icon">☯</div>
        <h1>太初管理后台</h1>
        <p>管理员登录</p>
      </div>

      <el-form
        ref="loginFormRef"
        :model="loginForm"
        :rules="loginRules"
        class="login-form"
        @submit.prevent="handleLogin"
      >
        <el-form-item prop="username">
          <el-input
            v-model="loginForm.username"
            placeholder="用户名"
            :prefix-icon="User"
            size="large"
          />
        </el-form-item>

        <el-form-item prop="password">
          <el-input
            v-model="loginForm.password"
            type="password"
            placeholder="密码"
            :prefix-icon="Lock"
            size="large"
            show-password
            @keyup.enter="handleLogin"
          />
        </el-form-item>

        <el-form-item>
          <el-button
            type="primary"
            size="large"
            :loading="loading"
            style="width: 100%"
            @click="handleLogin"
          >
            登录
          </el-button>
        </el-form-item>
      </el-form>

      <div class="login-footer">
        <el-link :underline="false" @click="$router.push('/')">返回前台</el-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { ElMessage } from 'element-plus'
import { User, Lock } from '@element-plus/icons-vue'

const router = useRouter()
const route = useRoute()
const loginFormRef = ref(null)
const loading = ref(false)

const loginForm = reactive({
  username: '',
  password: ''
})

const loginRules = {
  username: [{ required: true, message: '请输入用户名', trigger: 'blur' }],
  password: [{ required: true, message: '请输入密码', trigger: 'blur' }]
}

const handleLogin = async () => {
  try {
    const valid = await loginFormRef.value.validate()
    if (!valid) return

    loading.value = true
    
    // 调用管理员登录API
    const response = await fetch('/api/admin/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(loginForm)
    })

    const data = await response.json()

    if (data.code === 200) {
      localStorage.setItem('adminToken', data.data.token)
      localStorage.setItem('adminInfo', JSON.stringify(data.data.admin))
      ElMessage.success('登录成功')
      const redirect = route.query.redirect || '/admin'
      router.push(redirect)
    } else {
      ElMessage.error(data.message || '登录失败')
    }
  } catch (error) {
    ElMessage.error('登录失败，请检查网络')
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.admin-login {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.login-container {
  width: 400px;
  padding: 48px 40px;
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 4px 24px rgba(0,0,0,0.15);
}

.login-header {
  text-align: center;
  margin-bottom: 32px;
}

.logo-icon {
  font-size: 48px;
  margin-bottom: 16px;
}

.login-header h1 {
  font-size: 24px;
  color: #333;
  margin: 0 0 8px;
}

.login-header p {
  font-size: 14px;
  color: #999;
  margin: 0;
}

.login-form {
  margin-top: 24px;
}

.login-footer {
  margin-top: 24px;
  text-align: center;
}
</style>
