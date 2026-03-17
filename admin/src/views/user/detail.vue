<template>
  <div class="app-container">
    <el-page-header @back="$router.back()" title="用户详情" />
    
    <el-row :gutter="20" style="margin-top: 20px;">
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
            <el-descriptions-item label="用户名">{{ userInfo.username }}</el-descriptions-item>
            <el-descriptions-item label="手机号">{{ userInfo.phone }}</el-descriptions-item>
            <el-descriptions-item label="邮箱">{{ userInfo.email || '-' }}</el-descriptions-item>
            <el-descriptions-item label="注册时间">{{ userInfo.created_at }}</el-descriptions-item>
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
              <el-button type="primary" @click="openPointsDialog">手动调积分</el-button>
            </div>
          </template>

          <el-row :gutter="20">
            <el-col :span="8">
              <div class="stat-item">
                <div class="stat-value">{{ userInfo.points || 0 }}</div>
                <div class="stat-label">当前积分</div>
              </div>
            </el-col>
            <el-col :span="8">
              <div class="stat-item">
                <div class="stat-value">{{ userInfo.bazi_count || 0 }}</div>
                <div class="stat-label">八字排盘次数</div>
              </div>
            </el-col>
            <el-col :span="8">
              <div class="stat-item">
                <div class="stat-value">{{ userInfo.tarot_count || 0 }}</div>
                <div class="stat-label">塔罗占卜次数</div>
              </div>
            </el-col>
          </el-row>
        </el-card>
        
        <el-card style="margin-top: 20px;">
          <template #header>
            <span>最近活动</span>
          </template>
          <el-timeline>
            <el-timeline-item
              v-for="(activity, index) in activities"
              :key="index"
              :timestamp="activity.time"
            >
              {{ activity.content }}
            </el-timeline-item>
          </el-timeline>
        </el-card>
      </el-col>
    </el-row>

    <el-dialog v-model="pointsDialog.visible" title="手动调整积分" width="420px">
      <el-form :model="pointsDialog.form" label-width="90px">
        <el-form-item label="当前积分">
          <el-tag type="warning" effect="plain">{{ userInfo.points || 0 }}</el-tag>
        </el-form-item>
        <el-form-item label="调整类型">
          <el-radio-group v-model="pointsDialog.form.type">
            <el-radio label="add">增加</el-radio>
            <el-radio label="subtract">扣减</el-radio>
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
        <el-button type="primary" :loading="submitting" @click="submitAdjustPoints">确认调整</el-button>
      </template>
    </el-dialog>
  </div>
</template>


<script setup>
import { reactive, ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { ElMessage } from 'element-plus'
import { getUserDetail } from '@/api/user'
import { adjustPoints } from '@/api/points'

const route = useRoute()
const userInfo = ref({})
const submitting = ref(false)
const activities = ref([
  { content: '登录系统', time: '2026-03-15 10:30:00' },
  { content: '进行八字排盘', time: '2026-03-15 09:45:00' },
  { content: '查看每日运势', time: '2026-03-14 08:20:00' }
])
const pointsDialog = reactive({
  visible: false,
  form: {
    type: 'add',
    amount: 10,
    reason: ''
  }
})

async function loadUserDetail() {
  const userId = route.params.id
  const { data } = await getUserDetail(userId)
  userInfo.value = data
}

function openPointsDialog() {
  pointsDialog.form.type = 'add'
  pointsDialog.form.amount = 10
  pointsDialog.form.reason = ''
  pointsDialog.visible = true
}

async function submitAdjustPoints() {
  if (!pointsDialog.form.reason.trim()) {
    ElMessage.warning('请输入积分调整原因')
    return
  }

  submitting.value = true
  try {
    await adjustPoints({
      user_id: userInfo.value.id,
      type: pointsDialog.form.type,
      amount: pointsDialog.form.type === 'add' ? pointsDialog.form.amount : -pointsDialog.form.amount,
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

onMounted(async () => {
  try {
    await loadUserDetail()
  } catch (error) {
    console.error(error)
  }
})
</script>


<style lang="scss" scoped>
.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
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
