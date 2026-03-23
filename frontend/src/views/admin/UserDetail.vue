<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { ElMessage } from 'element-plus'
import {
  getUserDetail,
  adjustUserPoints
} from '../../api/admin'

const router = useRouter()
const route = useRoute()
const loading = ref(false)
const user = ref(null)
const pointsForm = ref({
  points: '',
  type: '',
  reason: ''
})

const loadUserDetail = async () => {
  loading.value = true
  try {
    const response = await getUserDetail(route.params.id)
    if (response.code === 200) {
      user.value = response.data
    } else {
      ElMessage.error(response.message || '加载失败')
    }
  } catch (error) {
    console.error('加载用户详情失败:', error)
    ElMessage.error('加载失败')
  } finally {
    loading.value = false
  }
}

const handleAdjustPoints = async () => {
  if (!pointsForm.value.points) {
    ElMessage.warning('请输入调整积分')
    return
  }

  if (!pointsForm.value.type) {
    ElMessage.warning('请选择调整类型')
    return
  }

  try {
    const data = {
      target_type: 'specific',
      targets: [user.value.id],
      type: pointsForm.value.type === 'increase' ? 'add' : 'sub',
      amount: parseInt(pointsForm.value.points),
      reason: pointsForm.value.reason || '管理员手动调整'
    }
    const response = await adjustUserPoints(data)
    
    if (response.code === 200) {
      ElMessage.success('积分调整成功')
      pointsForm.value = { points: '', type: '', reason: '' }
      loadUserDetail()
    } else {
      ElMessage.error(response.message || '操作失败')
    }
  } catch (error) {
    console.error('积分调整失败:', error)
    ElMessage.error('操作失败')
  }
}

const goBack = () => {
  router.back()
}

onMounted(() => {
  loadUserDetail()
})
</script>

<template>
  <div class="admin-user-detail" v-loading="loading">
    <div class="page-header">
      <el-button @click="goBack">返回</el-button>
      <h2>用户详情</h2>
    </div>

    <div v-if="user" class="detail-container">
      <!-- 基本信息 -->
      <el-card class="info-card">
        <template #header>
          <span>基本信息</span>
        </template>
        <div class="info-grid">
          <div class="info-item">
            <label>用户ID:</label>
            <span>{{ user.id }}</span>
          </div>
          <div class="info-item">
            <label>用户名:</label>
            <span>{{ user.username }}</span>
          </div>
          <div class="info-item">
            <label>昵称:</label>
            <span>{{ user.nickname || '-' }}</span>
          </div>
          <div class="info-item">
            <label>手机号:</label>
            <span>{{ user.phone || '-' }}</span>
          </div>
          <div class="info-item">
            <label>当前积分:</label>
            <span class="points-value">{{ user.points }}</span>
          </div>
          <div class="info-item">
            <label>VIP到期时间:</label>
            <span>{{ user.vip_expire_time || '-' }}</span>
          </div>
          <div class="info-item">
            <label>状态:</label>
            <el-tag :type="user.status === 'active' ? 'success' : 'danger'">
              {{ user.status === 'active' ? '正常' : '禁用' }}
            </el-tag>
          </div>
          <div class="info-item">
            <label>注册时间:</label>
            <span>{{ user.created_at }}</span>
          </div>
        </div>
      </el-card>

      <!-- 积分调整 -->
      <el-card class="info-card">
        <template #header>
          <span>积分调整</span>
        </template>
        <el-form :model="pointsForm" label-width="100px">
          <el-form-item label="调整类型">
            <el-radio-group v-model="pointsForm.type">
              <el-radio label="increase">增加</el-radio>
              <el-radio label="decrease">扣减</el-radio>
            </el-radio-group>
          </el-form-item>
          <el-form-item label="积分数值">
            <el-input-number
              v-model="pointsForm.points"
              :min="1"
              :max="10000"
              controls-position="right"
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
          <el-form-item>
            <el-button type="primary" @click="handleAdjustPoints">确认调整</el-button>
          </el-form-item>
        </el-form>
      </el-card>
    </div>
  </div>
</template>

<style scoped>
.admin-user-detail {
  padding: 24px;
}

.page-header {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 20px;
}

.detail-container {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
  gap: 20px;
}

.info-card {
  margin-bottom: 20px;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
}

.info-item {
  display: flex;
  align-items: center;
}

.info-item label {
  width: 100px;
  color: #666;
  font-weight: 500;
}

.info-item span {
  flex: 1;
  color: #333;
}

.points-value {
  font-size: 24px;
  font-weight: bold;
  color: #f57c00;
}
</style>
