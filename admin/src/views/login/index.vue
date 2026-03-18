<template>
  <div class="login-container">
    <div class="login-shell">
      <section class="login-overview">
        <div class="overview-badge">
          <el-icon><Monitor /></el-icon>
          <span>Admin Console</span>
        </div>
        <div class="overview-copy">
          <h1>太初管理后台</h1>
          <p>
            把账号错误、服务异常、代理故障拆开说明，再补上排障线索，运营登录页终于不再让人靠猜。
          </p>
        </div>

        <div class="status-grid">
          <article
            v-for="item in statusCards"
            :key="item.key"
            class="status-card"
            :class="{ 'is-active': item.active }"
          >
            <div class="status-card__icon">
              <el-icon>
                <component :is="item.icon" />
              </el-icon>
            </div>
            <div class="status-card__body">
              <strong>{{ item.title }}</strong>
              <p>{{ item.description }}</p>
            </div>
          </article>
        </div>

        <div class="overview-tip">
          <el-icon><Tools /></el-icon>
          <div>
            <strong>建议先看这三处</strong>
            <p>先确认账号凭证，再访问 /api/health，最后查看 backend 第一条报错，排障路径会清晰很多。</p>
          </div>
        </div>
      </section>

      <section class="login-panel">
        <el-form
          ref="loginFormRef"
          :model="loginForm"
          :rules="loginRules"
          class="login-form"
          auto-complete="on"
          label-position="left"
        >
          <div class="title-container">
            <h3 class="title">登录管理后台</h3>
            <p class="subtitle">统一圆角、阴影、触达尺寸和错误层级，让登录页在桌面端和移动端都更顺手。</p>
          </div>

          <div class="meta-row">
            <span class="meta-chip">
              <el-icon><CircleCheckFilled /></el-icon>
              区分账号 / 服务 / 代理问题
            </span>
            <span class="meta-chip">
              <el-icon><Connection /></el-icon>
              触摸区域不小于 44px
            </span>
          </div>

          <el-form-item prop="username">
            <el-input
              v-model="loginForm.username"
              placeholder="请输入管理员用户名"
              type="text"
              tabindex="1"
              auto-complete="username"
              size="large"
              clearable
              name="username"
              @input="resetLoginError"
            >
              <template #prefix>
                <el-icon><User /></el-icon>
              </template>
            </el-input>
          </el-form-item>

          <el-form-item prop="password">
            <el-input
              v-model="loginForm.password"
              :type="passwordVisible ? 'text' : 'password'"
              placeholder="请输入密码"
              tabindex="2"
              auto-complete="current-password"
              size="large"
              name="password"
              @input="resetLoginError"
              @keyup.enter="handleLogin"
            >
              <template #prefix>
                <el-icon><Lock /></el-icon>
              </template>
              <template #suffix>
                <button
                  type="button"
                  class="password-toggle"
                  :aria-label="passwordVisible ? '隐藏密码' : '显示密码'"
                  @click="passwordVisible = !passwordVisible"
                >
                  <el-icon>
                    <View v-if="passwordVisible" />
                    <Hide v-else />
                  </el-icon>
                </button>
              </template>
            </el-input>
          </el-form-item>

          <el-button
            :loading="loading"
            type="primary"
            size="large"
            class="login-button"
            @click="handleLogin"
          >
            {{ submitButtonText }}
          </el-button>

          <p class="login-helper">
            登录失败时会保留已输入内容，方便直接修正账号信息或排查服务状态后重试。
          </p>

          <section
            v-if="loginError.visible"
            class="login-feedback"
            :class="`is-${loginError.type}`"
          >
            <div class="login-feedback__header">
              <div class="login-feedback__badge">
                <el-icon>
                  <WarningFilled v-if="loginError.type === 'error'" />
                  <InfoFilled v-else />
                </el-icon>
                <span>{{ loginError.sceneLabel }}</span>
              </div>
              <h4>{{ loginError.title }}</h4>
            </div>

            <p class="login-feedback__description">{{ loginError.description }}</p>

            <div v-if="loginError.diagnostics.length" class="login-feedback__metrics">
              <span
                v-for="item in loginError.diagnostics"
                :key="`${item.label}-${item.value}`"
                class="metric-pill"
              >
                <span class="metric-pill__label">{{ item.label }}</span>
                <span class="metric-pill__value">{{ item.value }}</span>
              </span>
            </div>

            <p v-if="loginError.backendMessage" class="login-feedback__backend">
              后端返回：{{ loginError.backendMessage }}
            </p>

            <div v-if="loginError.guidance.length" class="login-feedback__guide">
              <strong>建议操作</strong>
              <ul>
                <li v-for="item in loginError.guidance" :key="item">{{ item }}</li>
              </ul>
            </div>
          </section>
        </el-form>
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import {
  CircleCheckFilled,
  Connection,
  Hide,
  InfoFilled,
  Lock,
  Monitor,
  Tools,
  User,
  View,
  WarningFilled
} from '@element-plus/icons-vue'
import { useUserStore } from '@/stores/user'
import { reportAdminUiError } from '@/utils/dev-error'

const router = useRouter()
const userStore = useUserStore()

const loginForm = reactive({
  username: '',
  password: ''
})

const loginError = reactive({
  visible: false,
  type: 'error',
  title: '',
  description: '',
  scene: '',
  sceneLabel: '',
  guidance: [],
  backendMessage: '',
  diagnostics: []
})

const loginRules = {
  username: [{ required: true, trigger: 'blur', message: '请输入用户名' }],
  password: [{ required: true, trigger: 'blur', message: '请输入密码' }]
}

const loading = ref(false)
const passwordVisible = ref(false)
const loginFormRef = ref(null)

const submitButtonText = computed(() => (
  loading.value ? '正在验证并连接后台…' : '登录后台'
))

const statusCards = computed(() => {
  const activeScene = loginError.visible ? loginError.scene : ''

  return [
    {
      key: 'auth',
      title: '账号校验',
      description: '账号或密码错误会单独提示，不再和系统故障混成一锅。',
      icon: User,
      active: activeScene === 'auth' || activeScene === 'form'
    },
    {
      key: 'service',
      title: '服务状态',
      description: '500、业务异常和超时会直接指向 backend 或数据库问题。',
      icon: Monitor,
      active: activeScene === 'service' || activeScene === 'timeout'
    },
    {
      key: 'proxy',
      title: '代理连通',
      description: '网络失败、502/503/504 会明确提醒检查代理和端口映射。',
      icon: Connection,
      active: activeScene === 'proxy'
    }
  ]
})

function resetLoginError() {
  loginError.visible = false
  loginError.type = 'error'
  loginError.title = ''
  loginError.description = ''
  loginError.scene = ''
  loginError.sceneLabel = ''
  loginError.guidance = []
  loginError.backendMessage = ''
  loginError.diagnostics = []
}

function applyLoginErrorState(meta) {
  loginError.visible = true
  loginError.type = meta.type
  loginError.title = meta.title
  loginError.description = meta.description
  loginError.scene = meta.scene
  loginError.sceneLabel = meta.sceneLabel
  loginError.guidance = meta.guidance
  loginError.backendMessage = meta.backendMessage || ''
  loginError.diagnostics = meta.diagnostics || []
}

function normalizeRequestPath(url = '') {
  if (!url) {
    return ''
  }

  try {
    return new URL(url, window.location.origin).pathname
  } catch {
    return String(url).split('?')[0]
  }
}

function buildDiagnostics(error, requestPath) {
  const diagnostics = []
  const httpStatus = Number(error?.httpStatus || 0)
  const businessCode = Number(error?.businessCode || error?.code || 0)
  const requestId = typeof error?.requestId === 'string' ? error.requestId.trim() : ''

  diagnostics.push({
    label: '接口',
    value: requestPath || '/auth/login'
  })

  if (httpStatus) {
    diagnostics.push({
      label: 'HTTP',
      value: String(httpStatus)
    })
  }

  if (businessCode) {
    diagnostics.push({
      label: '业务码',
      value: String(businessCode)
    })
  }

  if (requestId) {
    diagnostics.push({
      label: 'Request ID',
      value: requestId
    })
  }

  return diagnostics
}

function classifyLoginError(error) {
  const businessCode = Number(error?.businessCode || error?.code || 0)
  const httpStatus = Number(error?.httpStatus || 0)
  const transportCode = String(error?.transportCode || '').toUpperCase()
  const rawMessage = typeof error?.rawMessage === 'string' && error.rawMessage.trim()
    ? error.rawMessage.trim()
    : (typeof error?.message === 'string' ? error.message.trim() : '')
  const lowerRawMessage = rawMessage.toLowerCase()
  const requestPath = normalizeRequestPath(error?.requestConfig?.url || '')
  const diagnostics = buildDiagnostics(error, requestPath)
  const isLoginRequest = requestPath === '' || requestPath.endsWith('/auth/login')

  if (isLoginRequest && businessCode === 422) {
    return {
      type: 'warning',
      scene: 'form',
      sceneLabel: '表单信息未补全',
      title: '请先补全登录信息',
      description: rawMessage || '用户名和密码不能为空，请补全后重试。',
      guidance: ['请确认用户名和密码都已经填写完整。'],
      backendMessage: '',
      diagnostics,
      toastType: 'warning',
      toastMessage: rawMessage || '请先输入用户名和密码'
    }
  }

  if (isLoginRequest && businessCode === 401) {
    return {
      type: 'warning',
      scene: 'auth',
      sceneLabel: '账号校验未通过',
      title: '账号或密码不正确',
      description: '登录请求已到达后台，但账号校验未通过，请检查后重试。',
      guidance: [
        '请确认是否输入了正确的管理员账号和密码。',
        '如密码最近变更，请使用最新密码重新登录。'
      ],
      backendMessage: '',
      diagnostics,
      toastType: 'warning',
      toastMessage: rawMessage || '用户名或密码错误'
    }
  }

  if (httpStatus === 502 || httpStatus === 503 || httpStatus === 504) {
    return {
      type: 'error',
      scene: 'proxy',
      sceneLabel: '代理或网关异常',
      title: '代理或网关异常',
      description: rawMessage || '管理后台代理已收到请求，但后端服务当前不可用。',
      guidance: [
        '确认本地代理目标是否仍指向正确的 backend 地址（默认 http://localhost:8080）。',
        '检查 backend 服务或容器是否已经启动并通过健康检查。',
        '若最近执行过数据库初始化，请优先查看启动日志是否存在 SQL 冲突。'
      ],
      backendMessage: rawMessage,
      diagnostics,
      toastType: 'error',
      toastMessage: '后台服务当前不可用，请检查代理或 backend 状态'
    }
  }

  if (transportCode === 'ECONNABORTED' || transportCode === 'ETIMEDOUT' || lowerRawMessage.includes('timeout')) {
    return {
      type: 'error',
      scene: 'timeout',
      sceneLabel: '后台响应超时',
      title: '登录请求超时',
      description: '登录请求已发出，但后台长时间没有返回结果。',
      guidance: [
        '检查 backend 是否卡在启动、迁移或数据库连接阶段。',
        '先访问 /api/health，确认服务是否仍可正常响应。',
        '如使用容器运行，请查看 backend 最近日志并处理首个报错。'
      ],
      backendMessage: rawMessage,
      diagnostics,
      toastType: 'error',
      toastMessage: '登录超时，请检查后台服务后重试'
    }
  }

  if (transportCode === 'ERR_NETWORK' || error?.isNetworkError || (!httpStatus && !businessCode)) {
    return {
      type: 'error',
      scene: 'proxy',
      sceneLabel: '后台连接失败',
      title: '无法连接后台服务',
      description: '登录请求没有到达后台，通常是代理未连通、端口错误或 backend 未启动。',
      guidance: [
        '确认本地代理目标配置正确，并且目标端口已有服务监听。',
        '检查 backend 服务是否启动，可直接访问 /api/health 验证。',
        '若 backend 正在反复重启，请先修复启动日志中的首个错误。'
      ],
      backendMessage: rawMessage,
      diagnostics,
      toastType: 'error',
      toastMessage: '无法连接后台服务，请检查代理或 backend 是否启动'
    }
  }

  if (businessCode >= 500 || httpStatus >= 500) {
    return {
      type: 'error',
      scene: 'service',
      sceneLabel: '后台服务异常',
      title: '后台服务异常',
      description: '登录接口已返回服务异常，请先排查后端。',
      guidance: [
        '请优先查看 backend 日志，确认是否存在缺表、配置缺失或 bootstrap SQL 冲突。',
        '如果错误信息里包含 SQL 文件名，请先执行对应补丁或检查初始化流程。',
        '确认 /api/admin/auth/login 返回的业务码与错误信息后再继续重试。'
      ],
      backendMessage: rawMessage,
      diagnostics,
      toastType: 'error',
      toastMessage: '后台服务异常，请先排查后端'
    }
  }

  return {
    type: 'error',
    scene: 'service',
    sceneLabel: '登录请求未完成',
    title: '登录失败',
    description: rawMessage || '登录请求未成功完成，请稍后重试。',
    guidance: ['请结合浏览器网络面板中的 HTTP 状态码、请求路径和返回体继续排查。'],
    backendMessage: '',
    diagnostics,
    toastType: 'error',
    toastMessage: rawMessage || '登录失败，请稍后重试'
  }
}

async function handleLogin() {
  const valid = await loginFormRef.value.validate().catch(() => false)
  if (!valid) return

  resetLoginError()
  loading.value = true
  try {
    await userStore.loginAction(loginForm, { showErrorMessage: false })
    await userStore.getUserInfo()
    ElMessage.success('登录成功')
    router.push('/')
  } catch (error) {
    const loginErrorMeta = classifyLoginError(error)

    applyLoginErrorState(loginErrorMeta)
    reportAdminUiError('login', 'submit_failed', error, {
      has_username: Boolean(loginForm.username),
      login_error_title: loginErrorMeta.title,
      login_error_code: Number(error?.businessCode || error?.code || 0),
      login_http_status: Number(error?.httpStatus || 0),
      login_transport_code: error?.transportCode || '',
      login_request_path: normalizeRequestPath(error?.requestConfig?.url || ''),
      login_request_id: error?.requestId || ''
    })
    ElMessage({
      type: loginErrorMeta.toastType,
      message: loginErrorMeta.toastMessage
    })
  } finally {
    loading.value = false
  }
}
</script>

<style lang="scss" scoped>
.login-container {
  min-height: 100%;
  width: 100%;
  padding: clamp(20px, 4vw, 40px);
  background:
    radial-gradient(circle at top left, rgba(96, 165, 250, 0.34), transparent 32%),
    radial-gradient(circle at bottom right, rgba(129, 140, 248, 0.28), transparent 28%),
    linear-gradient(145deg, #0f172a 0%, #1d4ed8 52%, #312e81 100%);
  overflow: auto;
}

.login-shell {
  min-height: calc(100vh - clamp(40px, 8vw, 80px));
  display: grid;
  grid-template-columns: minmax(300px, 420px) minmax(340px, 480px);
  align-items: center;
  justify-content: center;
  gap: 24px;
  max-width: 1040px;
  margin: 0 auto;
}

.login-overview,
.login-panel {
  border: 1px solid rgba(255, 255, 255, 0.14);
  border-radius: 28px;
  backdrop-filter: blur(18px);
  box-shadow: 0 28px 80px rgba(15, 23, 42, 0.22);
}

.login-overview {
  padding: clamp(24px, 4vw, 36px);
  color: #eff6ff;
  background: linear-gradient(180deg, rgba(15, 23, 42, 0.78), rgba(30, 41, 59, 0.62));
}

.overview-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 14px;
  border-radius: 999px;
  background: rgba(96, 165, 250, 0.16);
  border: 1px solid rgba(191, 219, 254, 0.24);
  font-size: 13px;
  color: #dbeafe;
}

.overview-copy {
  margin-top: 24px;

  h1 {
    margin: 0;
    font-size: clamp(30px, 5vw, 42px);
    line-height: 1.14;
    font-weight: 700;
  }

  p {
    margin: 16px 0 0;
    color: rgba(226, 232, 240, 0.9);
    font-size: 15px;
    line-height: 1.7;
  }
}

.status-grid {
  display: grid;
  gap: 14px;
  margin-top: 28px;
}

.status-card {
  display: flex;
  align-items: flex-start;
  gap: 14px;
  padding: 18px;
  border-radius: 20px;
  background: rgba(15, 23, 42, 0.22);
  border: 1px solid rgba(191, 219, 254, 0.12);
  transition: transform 0.2s ease, border-color 0.2s ease, background 0.2s ease, box-shadow 0.2s ease;

  &.is-active {
    transform: translateY(-2px);
    background: rgba(59, 130, 246, 0.18);
    border-color: rgba(191, 219, 254, 0.4);
    box-shadow: 0 14px 28px rgba(15, 23, 42, 0.16);
  }
}

.status-card__icon {
  width: 44px;
  height: 44px;
  flex-shrink: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 16px;
  background: rgba(255, 255, 255, 0.08);
  color: #bfdbfe;
  font-size: 20px;
}

.status-card__body {
  strong {
    display: block;
    font-size: 15px;
    font-weight: 600;
    color: #f8fafc;
  }

  p {
    margin: 6px 0 0;
    color: rgba(226, 232, 240, 0.82);
    font-size: 13px;
    line-height: 1.65;
  }
}

.overview-tip {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  margin-top: 26px;
  padding: 18px;
  border-radius: 20px;
  background: rgba(239, 246, 255, 0.1);
  border: 1px solid rgba(191, 219, 254, 0.18);

  .el-icon {
    margin-top: 2px;
    color: #bfdbfe;
    font-size: 18px;
  }

  strong {
    display: block;
    font-size: 14px;
    color: #f8fafc;
  }

  p {
    margin: 6px 0 0;
    color: rgba(226, 232, 240, 0.84);
    font-size: 13px;
    line-height: 1.65;
  }
}

.login-panel {
  padding: clamp(24px, 4vw, 36px);
  background: rgba(255, 255, 255, 0.96);
}

.login-form {
  width: 100%;
}

.title-container {
  margin-bottom: 20px;

  .title {
    margin: 0;
    font-size: 28px;
    line-height: 1.2;
    font-weight: 700;
    color: #0f172a;
  }

  .subtitle {
    margin: 10px 0 0;
    color: #64748b;
    font-size: 14px;
    line-height: 1.7;
  }
}

.meta-row {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-bottom: 20px;
}

.meta-chip {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  min-height: 36px;
  padding: 8px 12px;
  border-radius: 999px;
  background: #eff6ff;
  color: #1d4ed8;
  font-size: 12px;
  font-weight: 600;
}

:deep(.el-form-item) {
  margin-bottom: 18px;
}

:deep(.el-input__wrapper) {
  min-height: 52px;
  padding: 0 16px;
  border-radius: 16px;
  box-shadow: 0 0 0 1px #dbe3f5 inset, 0 10px 20px rgba(15, 23, 42, 0.06);
  transition: box-shadow 0.2s ease, transform 0.2s ease;
}

:deep(.el-input__wrapper:hover) {
  box-shadow: 0 0 0 1px #93c5fd inset, 0 12px 24px rgba(37, 99, 235, 0.12);
}

:deep(.el-input__wrapper.is-focus) {
  transform: translateY(-1px);
  box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.18), 0 14px 28px rgba(37, 99, 235, 0.16);
}

:deep(.el-input__prefix-inner),
:deep(.el-input__suffix-inner) {
  display: flex;
  align-items: center;
}

:deep(.el-input__prefix-inner .el-icon) {
  color: #64748b;
  font-size: 18px;
}

.password-toggle {
  width: 40px;
  height: 40px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 0;
  border-radius: 12px;
  background: transparent;
  color: #64748b;
  cursor: pointer;
  transition: background 0.2s ease, color 0.2s ease;

  &:hover {
    background: rgba(59, 130, 246, 0.1);
    color: #2563eb;
  }
}

.login-button {
  width: 100%;
  min-height: 52px;
  margin-top: 6px;
  border: 0;
  border-radius: 16px;
  background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%);
  box-shadow: 0 16px 32px rgba(37, 99, 235, 0.22);
  font-size: 15px;
  font-weight: 600;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.login-button:not(.is-disabled):hover {
  transform: translateY(-1px);
  box-shadow: 0 18px 36px rgba(79, 70, 229, 0.24);
}

.login-helper {
  margin-top: 14px;
  color: #64748b;
  font-size: 13px;
  line-height: 1.7;
}

.login-feedback {
  margin-top: 20px;
  padding: 20px;
  border-radius: 20px;
  border: 1px solid transparent;
  box-shadow: 0 18px 32px rgba(15, 23, 42, 0.08);

  &.is-error {
    background: linear-gradient(180deg, #fff7f7 0%, #fff1f2 100%);
    border-color: rgba(244, 63, 94, 0.18);
  }

  &.is-warning {
    background: linear-gradient(180deg, #fffaf0 0%, #fff7e6 100%);
    border-color: rgba(245, 158, 11, 0.24);
  }
}

.login-feedback__header {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 12px;

  h4 {
    margin: 0;
    font-size: 17px;
    line-height: 1.4;
    color: #0f172a;
  }
}

.login-feedback__badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  min-height: 34px;
  padding: 7px 12px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 700;
  color: #1e3a8a;
  background: rgba(255, 255, 255, 0.7);
}

.login-feedback__description,
.login-feedback__backend {
  margin: 14px 0 0;
  color: #334155;
  font-size: 14px;
  line-height: 1.7;
}

.login-feedback__backend {
  font-weight: 600;
  word-break: break-word;
}

.login-feedback__metrics {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 16px;
}

.metric-pill {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  min-height: 34px;
  padding: 7px 12px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.78);
  border: 1px solid rgba(148, 163, 184, 0.18);
  color: #1e293b;
  font-size: 12px;
}

.metric-pill__label {
  color: #64748b;
}

.metric-pill__value {
  font-weight: 700;
}

.login-feedback__guide {
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid rgba(148, 163, 184, 0.18);

  strong {
    display: block;
    color: #0f172a;
    font-size: 14px;
    font-weight: 700;
  }

  ul {
    margin: 10px 0 0;
    padding-left: 18px;
    color: #475569;
    line-height: 1.75;
  }
}

@media (max-width: 980px) {
  .login-container {
    padding: 16px;
  }

  .login-shell {
    min-height: auto;
    grid-template-columns: 1fr;
    align-items: stretch;
    gap: 16px;
  }

  .login-overview {
    order: 2;
  }

  .login-panel {
    order: 1;
  }
}

@media (max-width: 640px) {
  .login-overview,
  .login-panel {
    border-radius: 24px;
    padding: 20px;
  }

  .title-container .title {
    font-size: 24px;
  }

  .status-card,
  .overview-tip,
  .login-feedback {
    border-radius: 18px;
  }

  .status-card {
    padding: 16px;
  }

  .login-feedback__header {
    align-items: flex-start;
    flex-direction: column;
  }

  .metric-pill {
    width: 100%;
    justify-content: space-between;
  }
}
</style>
