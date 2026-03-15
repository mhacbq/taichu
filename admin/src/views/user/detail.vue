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
            <span>使用统计</span>
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
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { getUserDetail } from '@/api/user'

const route = useRoute()
const userInfo = ref({})
const activities = ref([
  { content: '登录系统', time: '2026-03-15 10:30:00' },
  { content: '进行八字排盘', time: '2026-03-15 09:45:00' },
  { content: '查看每日运势', time: '2026-03-14 08:20:00' }
])

onMounted(async () => {
  const userId = route.params.id
  try {
    const { data } = await getUserDetail(userId)
    userInfo.value = data
  } catch (error) {
    console.error(error)
  }
})
</script>

<style lang="scss" scoped>
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
