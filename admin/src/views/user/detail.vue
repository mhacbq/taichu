<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { getUserDetail, adjustUserPoints } from '@/api/user'
import { createReadonlyErrorState } from '@/utils/page-error'

const route = useRoute()
const router = useRouter()

const loading = ref(false)
const adjusting = ref(false)
const pageError = ref(null)
const detail = ref(createEmptyDetail())
const pointsDialogVisible = ref(false)
const pointsForm = ref(createDefaultPointsForm())

const userId = computed(() => String(route.params.id || ''))
const readonlyMode = computed(() => Boolean(pageError.value))
const canAdjustPoints = computed(() => {
  if (readonlyMode.value || loading.value) {
    return false
  }

  return Boolean(detail.value.actions?.can_adjust_points)
})
const totalAdjustRecords = computed(() => {
  const summaryCount = Number(detail.value.stats?.points_summary?.total_adjust_records)
  if (Number.isFinite(summaryCount)) {
    return summaryCount
  }

  const legacyCount = Number(detail.value.stats?.total_adjust_records)
  if (Number.isFinite(legacyCount)) {
    return legacyCount
  }

  return recentPointsRecords.value.length
})
const recentPointsRecords = computed(() => {
  const records = Array.isArray(detail.value.points_records)
    ? detail.value.points_records
    : (Array.isArray(detail.value.stats?.points_records) ? detail.value.stats.points_records : [])

  return records.slice(0, 5)
})

function createEmptyDetail() {
  return {
    user: {},
    stats: {},
    actions: {},
    points_records: []
  }
}

function createDefaultPointsForm() {
  return {
    points: null,
    reason: ''
  }
}

function normalizeDetailPayload(payload = {}) {
  const stats = payload.stats && typeof payload.stats === 'object' ? payload.stats : {}
  const actions = payload.actions && typeof payload.actions === 'object'
    ? payload.actions
    : {
        can_adjust_points: Boolean(payload.can_adjust_points),
        can_edit_profile: Boolean(payload.can_edit_profile)
      }
  const pointsRecords = Array.isArray(payload.points_records)
    ? payload.points_records
    : (Array.isArray(stats.points_records) ? stats.points_records : [])

  return {
    ...createEmptyDetail(),
    ...payload,
    user: payload.user && typeof payload.user === 'object' ? payload.user : payload,
    stats,
    actions,
    points_records: pointsRecords
  }
}

function getAmountText(amount) {
  const numericAmount = Number(amount || 0)
  if (Number.isFinite(numericAmount) && numericAmount > 0) {
    return `+${numericAmount}`
  }

  return `${Number.isFinite(numericAmount) ? numericAmount : 0}`
}

function getAmountColor(amount) {
  return Number(amount || 0) >= 0 ? '#67C23A' : '#F56C6C'
}

function getBalanceAfterText(row) {
  const candidates = [row?.balance_after, row?.current_points, row?.balance]
  for (const value of candidates) {
    const numericValue = Number(value)
    if (Number.isFinite(numericValue)) {
      return numericValue
    }
  }

  return '-'
}

async function fetchUserDetail() {
  if (!userId.value) {
    pageError.value = {
      title: '用户详情加载失败',
      description: '当前缺少用户ID，无法读取真实详情。请返回列表后重新进入。'
    }
    detail.value = createEmptyDetail()
    return
  }

  loading.value = true
  try {
    const res = await getUserDetail(userId.value, { showErrorMessage: false })
    detail.value = normalizeDetailPayload(res.data || {})
    pageError.value = null
  } catch (error) {
    detail.value = createEmptyDetail()
    pageError.value = createReadonlyErrorState(error, '用户详情', 'user_view / points_adjust')
  } finally {
    loading.value = false
  }
}

function openPointsDialog() {
  if (readonlyMode.value) {
    ElMessage.warning('用户详情尚未成功加载，当前为只读保护状态')
    return
  }

  if (!canAdjustPoints.value) {
    ElMessage.warning('当前账号暂无调整积分权限，或详情尚未准备完成')
    return
  }

  pointsDialogVisible.value = true
  pointsForm.value = createDefaultPointsForm()
}

async function handleAdjustPoints() {
  const normalizedPoints = Number(pointsForm.value.points)
  if (!Number.isFinite(normalizedPoints)) {
    ElMessage.warning('请输入有效的积分调整数量')
    return
  }

  if (normalizedPoints === 0) {
    ElMessage.warning('积分调整数量不能为0')
    return
  }

  adjusting.value = true
  try {
    const reason = String(pointsForm.value.reason || '').trim() || '管理员调整'
    const res = await adjustUserPoints(userId.value, {
      points: Math.trunc(normalizedPoints),
      reason
    }, { showErrorMessage: false })

    pointsDialogVisible.value = false
    pointsForm.value = createDefaultPointsForm()
    await fetchUserDetail()
    ElMessage.success(res.message || '积分调整成功')
  } catch (error) {
    ElMessage.error(error.message || '积分调整失败')
  } finally {
    adjusting.value = false
  }
}

function goBack() {
  router.back()
}

onMounted(() => {
  fetchUserDetail()
})
</script>

<template>
  <div class="app-container">
    <el-card v-loading="loading">
      <template #header>
        <div class="card-header">
          <el-button link @click="goBack">← 返回</el-button>
          <span>用户详情</span>
          <div class="actions">
            <el-button type="primary" :disabled="!canAdjustPoints" @click="openPointsDialog">调整积分</el-button>
          </div>
        </div>
      </template>

      <div v-if="pageError" class="page-state">
        <el-result icon="warning" :title="pageError.title" :sub-title="pageError.description">
          <template #extra>
            <el-space wrap>
              <el-button type="primary" :loading="loading" @click="fetchUserDetail">重新加载</el-button>
              <el-button @click="goBack">返回列表</el-button>
            </el-space>
          </template>
        </el-result>
      </div>

      <template v-else>
        <el-alert
          v-if="!detail.actions?.can_adjust_points"
          title="当前账号暂无积分调整权限；页面已保留只读详情，避免误触发不可用操作。"
          type="info"
          show-icon
          class="mb-4"
        />

        <el-descriptions :column="2" border>
          <el-descriptions-item label="用户ID">{{ detail.user?.id || '-' }}</el-descriptions-item>
          <el-descriptions-item label="用户名">{{ detail.user?.username || '-' }}</el-descriptions-item>
          <el-descriptions-item label="昵称">{{ detail.user?.nickname || '-' }}</el-descriptions-item>
          <el-descriptions-item label="手机号">{{ detail.user?.phone || '-' }}</el-descriptions-item>
          <el-descriptions-item label="邮箱">{{ detail.user?.email || '-' }}</el-descriptions-item>
          <el-descriptions-item label="积分余额">{{ detail.user?.points ?? 0 }}</el-descriptions-item>
          <el-descriptions-item label="注册时间">{{ detail.user?.created_at || '-' }}</el-descriptions-item>
          <el-descriptions-item label="最后登录">{{ detail.user?.last_login_at || '-' }}</el-descriptions-item>
        </el-descriptions>

        <el-divider content-position="left">使用统计</el-divider>
        <el-row :gutter="20" class="stats-row">
          <el-col :lg="6" :md="12" :sm="12" :xs="24">
            <el-statistic title="八字测算" :value="detail.stats?.bazi_count || 0" />
          </el-col>
          <el-col :lg="6" :md="12" :sm="12" :xs="24">
            <el-statistic title="塔罗测算" :value="detail.stats?.tarot_count || 0" />
          </el-col>
          <el-col :lg="6" :md="12" :sm="12" :xs="24">
            <el-statistic title="六爻测算" :value="detail.stats?.liuyao_count || 0" />
          </el-col>
          <el-col :lg="6" :md="12" :sm="12" :xs="24">
            <el-statistic title="积分记录" :value="totalAdjustRecords" />
          </el-col>
        </el-row>

        <el-divider content-position="left">最近积分记录</el-divider>
        <el-table :data="recentPointsRecords" stripe empty-text="暂无积分变动记录，调整成功后会在这里回读显示。">
          <el-table-column prop="created_at" label="时间" width="180" />
          <el-table-column prop="amount" label="变动值" width="120">
            <template #default="{ row }">
              <span :style="{ color: getAmountColor(row.amount) }">
                {{ getAmountText(row.amount) }}
              </span>
            </template>
          </el-table-column>
          <el-table-column label="调整后余额" width="140">
            <template #default="{ row }">
              {{ getBalanceAfterText(row) }}
            </template>
          </el-table-column>
          <el-table-column prop="remark" label="原因" min-width="180" show-overflow-tooltip />
        </el-table>
      </template>
    </el-card>

    <el-dialog v-model="pointsDialogVisible" title="调整积分" width="500px" destroy-on-close>
      <el-form :model="pointsForm" label-width="100px">
        <el-form-item label="调整数量">
          <el-input-number
            v-model="pointsForm.points"
            :min="-10000"
            :max="10000"
            placeholder="正数为增加，负数为减少"
            style="width: 100%"
          />
        </el-form-item>
        <el-form-item label="调整原因">
          <el-input
            v-model="pointsForm.reason"
            type="textarea"
            :rows="3"
            placeholder="请输入调整原因"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button :disabled="adjusting" @click="pointsDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="adjusting" @click="handleAdjustPoints">确认调整</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<style scoped>
.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
}

.actions {
  display: flex;
  gap: 10px;
}

.page-state {
  padding: 12px 0;
}

.stats-row {
  row-gap: 16px;
}

.mb-4 {
  margin-bottom: 16px;
}
</style>
