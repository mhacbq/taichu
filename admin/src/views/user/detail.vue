<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getUserDetail, adjustUserPoints } from '@/api/user'

const route = useRoute()
const router = useRouter()

const loading = ref(false)
const detail = ref({
  user: {},
  stats: {}
})
const pointsDialogVisible = ref(false)
const pointsForm = ref({
  points: '',
  reason: ''
})

const userId = computed(() => route.params.id as string)

onMounted(() => {
  fetchUserDetail()
})

async function fetchUserDetail() {
  loading.value = true
  try {
    const res = await getUserDetail(userId.value)
    detail.value = res.data
  } catch (error) {
    ElMessage.error('获取用户详情失败')
  } finally {
    loading.value = false
  }
}

function openPointsDialog() {
  pointsDialogVisible.value = true
  pointsForm.value = { points: '', reason: '' }
}

async function handleAdjustPoints() {
  if (!pointsForm.value.points) {
    ElMessage.warning('请输入积分调整数量')
    return
  }

  try {
    await adjustUserPoints(userId.value, pointsForm.value)
    ElMessage.success('积分调整成功')
    pointsDialogVisible.value = false
    fetchUserDetail()
  } catch (error) {
    ElMessage.error('积分调整失败')
  }
}

function goBack() {
  router.back()
}
</script>

<template>
  <div class="app-container">
    <el-card v-loading="loading">
      <template #header>
        <div class="card-header">
          <el-button link @click="goBack">← 返回</el-button>
          <span>用户详情</span>
          <div class="actions">
            <el-button type="primary" @click="openPointsDialog">调整积分</el-button>
          </div>
        </div>
      </template>

      <el-descriptions :column="2" border>
        <el-descriptions-item label="用户ID">{{ detail.user?.id || '-' }}</el-descriptions-item>
        <el-descriptions-item label="用户名">{{ detail.user?.username || '-' }}</el-descriptions-item>
        <el-descriptions-item label="昵称">{{ detail.user?.nickname || '-' }}</el-descriptions-item>
        <el-descriptions-item label="手机号">{{ detail.user?.phone || '-' }}</el-descriptions-item>
        <el-descriptions-item label="邮箱">{{ detail.user?.email || '-' }}</el-descriptions-item>
        <el-descriptions-item label="积分余额">{{ detail.user?.points || 0 }}</el-descriptions-item>
        <el-descriptions-item label="注册时间">{{ detail.user?.created_at || '-' }}</el-descriptions-item>
        <el-descriptions-item label="最后登录">{{ detail.user?.last_login_at || '-' }}</el-descriptions-item>
      </el-descriptions>

      <el-divider content-position="left">使用统计</el-divider>
      <el-row :gutter="20">
        <el-col :span="6">
          <el-statistic title="八字测算" :value="detail.stats?.bazi_count || 0" />
        </el-col>
        <el-col :span="6">
          <el-statistic title="塔罗测算" :value="detail.stats?.tarot_count || 0" />
        </el-col>
        <el-col :span="6">
          <el-statistic title="六爻测算" :value="detail.stats?.liuyao_count || 0" />
        </el-col>
        <el-col :span="6">
          <el-statistic title="积分记录" :value="detail.stats?.total_adjust_records || 0" />
        </el-col>
      </el-row>
    </el-card>

    <el-dialog v-model="pointsDialogVisible" title="调整积分" width="500px">
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
        <el-button @click="pointsDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleAdjustPoints">确认调整</el-button>
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

.actions {
  display: flex;
  gap: 10px;
}
</style>
