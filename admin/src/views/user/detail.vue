<template>
  <div class="app-container">
    <el-page-header @back="$router.back()" title="用户详情" />

    <div class="detail-body" v-loading="loadingDetail">
      <el-result v-if="detailError && !loadingDetail" icon="warning" :title="detailError.title" :sub-title="detailError.description">
        <template #extra>
          <el-button type="primary" @click="loadUserDetail">重新加载</el-button>
          <el-button @click="router.push('/user/list')">返回用户列表</el-button>
        </template>
      </el-result>

      <el-row v-else-if="hasUserData" :gutter="20" style="margin-top: 20px;">
        <el-col :lg="8">
          <el-card>
            <template #header>
              <span>基本信息</span>
            </template>
            <div class="user-profile">
              <el-avatar :size="80" :src="userInfo.avatar" />
              <h3>{{ userInfo.nickname }}</h3>
              <p class="text-gray">ID: {{ userInfo.id }}</p>
            </div>
            <el-descriptions :column="1" border>
              <el-descriptions-item label="用户名">{{ userInfo.username || '-' }}</el-descriptions-item>
              <el-descriptions-item label="昵称">{{ userInfo.nickname || '-' }}</el-descriptions-item>
              <el-descriptions-item label="性别">{{ formatGender(userInfo.gender) }}</el-descriptions-item>
              <el-descriptions-item label="手机号">{{ userInfo.phone || '-' }}</el-descriptions-item>
              <el-descriptions-item label="邮箱">{{ userInfo.email || '-' }}</el-descriptions-item>
              <el-descriptions-item label="注册时间">{{ userInfo.created_at || '-' }}</el-descriptions-item>
              <el-descriptions-item label="状态">
                <el-tag :type="userInfo.status === 1 ? 'success' : 'danger'">
                  {{ userInfo.status === 1 ? '正常' : '禁用' }}
                </el-tag>
              </el-descriptions-item>
            </el-descriptions>
          </el-card>
        </el-col>

        <el-col :lg="16">
          <el-card>
            <template #header>
              <div class="card-header">
                <span>使用统计</span>
                <div class="action-group">
                  <el-button type="primary" plain :disabled="!canEditProfile" @click="openProfileDialog">编辑资料</el-button>
                  <el-button type="primary" :disabled="!canAdjustPoints" @click="openPointsDialog">手动调积分</el-button>
                </div>
              </div>
            </template>

            <el-row :gutter="20">
              <el-col :span="8">
                <div class="stat-item">
                  <div class="stat-value">{{ userInfo.points }}</div>
                  <div class="stat-label">当前积分</div>
                </div>
              </el-col>
              <el-col :span="8">
                <div class="stat-item">
                  <div class="stat-value">{{ userInfo.bazi_count }}</div>
                  <div class="stat-label">八字排盘次数</div>
                </div>
              </el-col>
              <el-col :span="8">
                <div class="stat-item">
                  <div class="stat-value">{{ userInfo.tarot_count }}</div>
                  <div class="stat-label">塔罗占卜次数</div>
                </div>
              </el-col>
            </el-row>
          </el-card>

          <el-card style="margin-top: 20px;">
            <template #header>
              <div class="card-header simple-header">
                <span>最近活动</span>
                <el-text type="info" size="small">仅展示真实后端记录</el-text>
              </div>
            </template>

            <el-timeline v-if="activities.length">
              <el-timeline-item
                v-for="(activity, index) in activities"
                :key="`${activity.time}-${index}`"
                :timestamp="activity.time"
              >
                {{ activity.content }}
              </el-timeline-item>
            </el-timeline>
            <el-empty v-else description="暂无可核验的真实活动记录" />
          </el-card>
        </el-col>
      </el-row>
    </div>

    <el-dialog v-model="profileDialog.visible" title="编辑用户资料" width="520px">
      <el-form :model="profileDialog.form" label-width="96px" :disabled="!canEditProfile || submittingProfile">
        <el-form-item label="昵称" required>
          <el-input
            v-model="profileDialog.form.nickname"
            maxlength="50"
            show-word-limit
            placeholder="请输入用户昵称"
          />
        </el-form-item>
        <el-form-item label="邮箱">
          <el-input v-model="profileDialog.form.email" placeholder="请输入邮箱地址" clearable />
        </el-form-item>
        <el-form-item label="手机号">
          <el-input v-model="profileDialog.form.phone" placeholder="请输入手机号" clearable />
        </el-form-item>
        <el-form-item label="性别">
          <el-radio-group v-model="profileDialog.form.gender">
            <el-radio :label="0">未知</el-radio>
            <el-radio :label="1">男</el-radio>
            <el-radio :label="2">女</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="头像链接">
          <el-input v-model="profileDialog.form.avatar" placeholder="https://example.com/avatar.png" clearable />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="profileDialog.visible = false">取消</el-button>
        <el-button type="primary" :loading="submittingProfile" :disabled="!canEditProfile" @click="submitProfile">保存资料</el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="pointsDialog.visible" title="手动调整积分" width="420px">
      <el-form :model="pointsDialog.form" label-width="90px" :disabled="!canAdjustPoints">
        <el-form-item label="当前积分">
          <el-tag type="warning" effect="plain">{{ userInfo.points }}</el-tag>
        </el-form-item>
        <el-form-item label="调整类型">
          <el-radio-group v-model="pointsDialog.form.type">
            <el-radio label="add">增加</el-radio>
            <el-radio label="sub">扣减</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="调整数量">
          <el-input-number v-model="pointsDialog.form.amount" :min="1" :max="99999" />
        </el-form-item>
        <el-form-item label="调整原因">
          <el-input
            v-model="pointsDialog.form.reason"
            type="textarea"
            :rows="3"
            maxlength="100"
            show-word-limit
            placeholder="请输入积分调整原因"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="pointsDialog.visible = false">取消</el-button>
        <el-button type="primary" :loading="submitting" :disabled="!canAdjustPoints" @click="submitAdjustPoints">确认调整</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { getUserDetail, updateUserProfile } from '@/api/user'
import { adjustPoints } from '@/api/points'
import { createReadonlyErrorState } from '@/utils/page-error'

const route = useRoute()
const router = useRouter()
const loadingDetail = ref(false)
const submitting = ref(false)
const submittingProfile = ref(false)
const detailError = ref(null)
const userInfo = ref(createDefaultUserInfo())
const profileDialog = reactive({
  visible: false,
  form: createDefaultProfileForm()
})
const pointsDialog = reactive({
  visible: false,
  form: {
    type: 'add',
    amount: 10,
    reason: ''
  }
})

const hasUserData = computed(() => userInfo.value.id > 0)
const canAdjustPoints = computed(() => {
  if (detailError.value) {
    return false
  }

  return Boolean(userInfo.value.can_adjust_points || userInfo.value.actions?.can_adjust_points)
})
const canEditProfile = computed(() => {
  if (detailError.value) {
    return false
  }

  return Boolean(userInfo.value.can_edit_profile || userInfo.value.actions?.can_edit_profile)
})
const activities = computed(() => buildRecentActivities(userInfo.value))

function createDefaultProfileForm() {
  return {
    nickname: '',
    email: '',
    phone: '',
    gender: 0,
    avatar: ''
  }
}

function createDefaultUserInfo() {
  return {
    id: 0,
    avatar: '',
    nickname: '',
    username: '',
    phone: '',
    email: '',
    gender: 0,
    created_at: '',
    status: 0,
    points: 0,
    bazi_count: 0,
    tarot_count: 0,
    liuyao_count: 0,
    points_records: [],
    vip_orders: [],
    recharge_orders: [],
    can_adjust_points: false,
    can_edit_profile: false,
    actions: {}
  }
}

function isPhoneLike(value) {
  return /^1[3-9]\d{9}$/.test(String(value || '').trim())
}

function resolveDisplayUsername(...values) {
  for (const value of values) {
    const normalized = String(value ?? '').trim()
    if (!normalized || isPhoneLike(normalized)) {
      continue
    }
    return normalized
  }
  return ''
}

function normalizeUserDetail(data = {}) {
  const baseUser = data.user && typeof data.user === 'object' ? data.user : {}
  const id = Number(data.id ?? baseUser.id ?? 0) || 0
  const username = resolveDisplayUsername(data.username, baseUser.username, data.nickname, baseUser.nickname) || `用户#${id}`
  const nickname = String(data.nickname ?? baseUser.nickname ?? username ?? data.phone ?? baseUser.phone ?? `用户#${id}`)

  return {
    id,
    avatar: String(data.avatar ?? baseUser.avatar ?? ''),
    nickname,
    username,
    phone: String(data.phone ?? baseUser.phone ?? ''),
    email: String(data.email ?? baseUser.email ?? ''),
    gender: Number(data.gender ?? baseUser.gender ?? 0),
    created_at: String(data.created_at ?? baseUser.created_at ?? ''),
    status: Number(data.status ?? baseUser.status ?? 0),
    points: Number(data.points ?? baseUser.points ?? 0),
    bazi_count: Number(data.bazi_count ?? data.stats?.bazi_count ?? 0),
    tarot_count: Number(data.tarot_count ?? data.stats?.tarot_count ?? 0),
    liuyao_count: Number(data.liuyao_count ?? data.stats?.liuyao_count ?? 0),
    points_records: Array.isArray(data.points_records) ? data.points_records : (Array.isArray(data.stats?.points_records) ? data.stats.points_records : []),
    vip_orders: Array.isArray(data.vip_orders) ? data.vip_orders : (Array.isArray(data.stats?.vip_orders) ? data.stats.vip_orders : []),
    recharge_orders: Array.isArray(data.recharge_orders) ? data.recharge_orders : (Array.isArray(data.stats?.recharge_orders) ? data.stats.recharge_orders : []),
    can_adjust_points: Boolean(data.can_adjust_points ?? data.actions?.can_adjust_points ?? data.stats?.points_summary?.can_adjust),
    can_edit_profile: Boolean(data.can_edit_profile ?? data.actions?.can_edit_profile),
    actions: data.actions && typeof data.actions === 'object' ? data.actions : {}
  }
}

function createProfilePayload(user = {}) {
  return {
    nickname: String(user.nickname || '').trim(),
    email: String(user.email || '').trim(),
    phone: String(user.phone || '').trim(),
    gender: Number.isFinite(Number(user.gender)) ? Number(user.gender) : 0,
    avatar: String(user.avatar || '').trim()
  }
}

function formatGender(value) {
  switch (Number(value)) {
    case 1:
      return '男'
    case 2:
      return '女'
    default:
      return '未知'
  }
}

function formatActivityTime(value) {
  return String(value || '').trim()
}

function formatOrderAmount(order = {}) {
  const amount = Number(order.pay_amount ?? order.total_amount ?? order.amount ?? order.price ?? 0)
  if (!Number.isFinite(amount) || amount <= 0) {
    return ''
  }

  return `¥${amount.toFixed(2)}`
}

function formatOrderStatus(status) {
  const normalized = String(status ?? '').trim().toLowerCase()
  if (!normalized) {
    return ''
  }

  const statusMap = {
    '0': '待支付',
    '1': '已完成',
    '2': '已关闭',
    '3': '已退款',
    pending: '待处理',
    paid: '已支付',
    success: '已完成',
    completed: '已完成',
    failed: '失败',
    cancelled: '已取消',
    closed: '已关闭',
    refunded: '已退款'
  }

  return statusMap[normalized] || String(status)
}

function isReducePointsType(value) {
  return ['reduce', 'sub', 'subtract', 'minus', 'consume', 'deduct', 'expense', 'cost', 'exchange', 'redeem'].includes(String(value || '').trim().toLowerCase())
}

function formatPointsActivity(record = {}) {
  const signedDelta = Number(record.points ?? record.change_amount ?? record.change_points ?? 0)
  const absoluteAmount = Number(record.amount ?? Math.abs(signedDelta) ?? 0)
  const isReduce = signedDelta < 0 || (signedDelta === 0 && isReducePointsType(record.direction || record.type))
  const direction = signedDelta === 0 && absoluteAmount === 0 ? '调整' : (isReduce ? '扣减' : '增加')
  const amountText = absoluteAmount > 0 ? `${Math.abs(absoluteAmount)} 积分` : '积分'
  const reason = String(record.reason || record.remark || record.description || record.type_name || record.action || record.type || '后台积分变动').trim()

  return `积分${direction} ${amountText}：${reason}`
}

function formatVipOrderActivity(order = {}) {
  const packageName = String(order.package_name || order.package_title || order.title || 'VIP 套餐').trim()
  const amount = formatOrderAmount(order)
  const status = formatOrderStatus(order.status)
  return `创建 ${packageName} 订单${amount ? `，金额 ${amount}` : ''}${status ? `（${status}）` : ''}`
}

function formatRechargeOrderActivity(order = {}) {
  const amount = formatOrderAmount(order)
  const orderNo = String(order.order_no || order.out_trade_no || '').trim()
  const status = formatOrderStatus(order.status)
  return `创建充值订单${amount ? `，金额 ${amount}` : ''}${orderNo ? `，单号 ${orderNo}` : ''}${status ? `（${status}）` : ''}`
}

function buildRecentActivities(user) {
  const items = []

  ;(user.points_records || []).forEach(record => {
    const time = formatActivityTime(record.created_at || record.create_time || record.updated_at)
    if (!time) return
    items.push({
      time,
      content: formatPointsActivity(record)
    })
  })

  ;(user.vip_orders || []).forEach(order => {
    const time = formatActivityTime(order.created_at || order.pay_time || order.updated_at)
    if (!time) return
    items.push({
      time,
      content: formatVipOrderActivity(order)
    })
  })

  ;(user.recharge_orders || []).forEach(order => {
    const time = formatActivityTime(order.created_at || order.pay_time || order.updated_at)
    if (!time) return
    items.push({
      time,
      content: formatRechargeOrderActivity(order)
    })
  })

  if (user.created_at) {
    items.push({
      time: formatActivityTime(user.created_at),
      content: '注册账号'
    })
  }

  return items
    .filter(item => item.time && item.content)
    .sort((a, b) => String(b.time).localeCompare(String(a.time)))
    .slice(0, 10)
}

async function loadUserDetail() {
  loadingDetail.value = true
  try {
    const userId = route.params.id
    const { data } = await getUserDetail(userId, { showErrorMessage: false })
    userInfo.value = normalizeUserDetail(data)
    profileDialog.form = createProfilePayload(userInfo.value)
    detailError.value = null
  } catch (error) {
    userInfo.value = createDefaultUserInfo()
    profileDialog.visible = false
    pointsDialog.visible = false
    detailError.value = createReadonlyErrorState(error, '用户详情', 'user_view')
  } finally {
    loadingDetail.value = false
  }
}

function openProfileDialog() {
  if (detailError.value) {
    ElMessage.warning('用户详情尚未成功加载，已禁止继续编辑资料')
    return
  }

  if (!canEditProfile.value) {
    ElMessage.warning('当前账号无用户资料编辑权限')
    return
  }

  profileDialog.form = createProfilePayload(userInfo.value)
  profileDialog.visible = true
}

function validateProfileForm(form) {
  const nickname = String(form.nickname || '').trim()
  if (!nickname) {
    return '昵称不能为空'
  }

  const email = String(form.email || '').trim()
  if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    return '邮箱格式不正确'
  }

  const phone = String(form.phone || '').trim()
  if (phone && !/^1[3-9]\d{9}$/.test(phone)) {
    return '手机号格式不正确'
  }

  const avatar = String(form.avatar || '').trim()
  if (avatar && !/^https?:\/\//i.test(avatar)) {
    return '头像链接必须以 http:// 或 https:// 开头'
  }

  if (![0, 1, 2].includes(Number(form.gender))) {
    return '性别值无效'
  }

  return ''
}

async function submitProfile() {
  if (!canEditProfile.value) {
    ElMessage.warning(detailError.value ? '用户详情尚未成功加载，已禁止继续编辑资料' : '当前账号无用户资料编辑权限')
    return
  }

  const message = validateProfileForm(profileDialog.form)
  if (message) {
    ElMessage.warning(message)
    return
  }

  submittingProfile.value = true
  try {
    await updateUserProfile(userInfo.value.id, {
      nickname: profileDialog.form.nickname.trim(),
      email: profileDialog.form.email.trim(),
      phone: profileDialog.form.phone.trim(),
      gender: Number(profileDialog.form.gender),
      avatar: profileDialog.form.avatar.trim()
    }, { showErrorMessage: false })
    ElMessage.success('用户资料已更新')
    profileDialog.visible = false
    await loadUserDetail()
  } catch (error) {
    ElMessage.error(error.message || '保存用户资料失败')
  } finally {
    submittingProfile.value = false
  }
}

function openPointsDialog() {
  if (detailError.value) {
    ElMessage.warning('用户详情尚未成功加载，已禁止继续调积分')
    return
  }

  if (!canAdjustPoints.value) {
    ElMessage.warning('当前账号无积分调整权限')
    return
  }

  pointsDialog.form.type = 'add'
  pointsDialog.form.amount = 10
  pointsDialog.form.reason = ''
  pointsDialog.visible = true
}

async function submitAdjustPoints() {
  if (!canAdjustPoints.value) {
    ElMessage.warning(detailError.value ? '用户详情尚未成功加载，已禁止继续调积分' : '当前账号无积分调整权限')
    return
  }

  if (!pointsDialog.form.reason.trim()) {
    ElMessage.warning('请输入积分调整原因')
    return
  }

  submitting.value = true
  try {
    await adjustPoints({
      user_id: userInfo.value.id,
      type: pointsDialog.form.type,
      amount: pointsDialog.form.amount,
      reason: pointsDialog.form.reason.trim()
    })
    ElMessage.success('积分调整成功')
    pointsDialog.visible = false
    await loadUserDetail()
  } catch (error) {
    ElMessage.error(error.message || '积分调整失败')
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  loadUserDetail()
})
</script>

<style lang="scss" scoped>
.detail-body {
  min-height: 320px;
}

.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.action-group {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

.simple-header {
  justify-content: flex-start;
}

.user-profile {
  text-align: center;
  padding: 20px 0;

  h3 {
    margin: 15px 0 5px;
  }
}

.text-gray {
  color: #909399;
}

.stat-item {
  text-align: center;
  padding: 20px;
  background: #f5f7fa;
  border-radius: 4px;

  .stat-value {
    font-size: 28px;
    font-weight: 600;
    color: #409eff;
  }

  .stat-label {
    margin-top: 8px;
    color: #606266;
  }
}
</style>
