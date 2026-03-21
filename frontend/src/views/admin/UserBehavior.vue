<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { ElMessage } from 'element-plus'
import { getUserBehavior } from '../../api/admin'

const router = useRouter()
const route = useRoute()
const loading = ref(false)
const behaviors = ref([])

const loadBehavior = async () => {
  loading.value = true
  try {
    const response = await getUserBehavior(route.params.id)
    if (response.code === 200) {
      behaviors.value = response.data.list || []
    } else {
      ElMessage.error(response.message || '加载失败')
    }
  } catch (error) {
    console.error('加载行为日志失败:', error)
    ElMessage.error('加载失败')
  } finally {
    loading.value = false
  }
}

const goBack = () => {
  router.back()
}

onMounted(() => {
  loadBehavior()
})
</script>

<template>
  <div class="admin-user-behavior" v-loading="loading">
    <div class="page-header">
      <el-button @click="goBack">返回</el-button>
      <h2>用户行为日志</h2>
    </div>

    <div class="table-container">
      <el-table :data="behaviors" stripe>
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="action" label="行为类型" min-width="150" />
        <el-table-column prop="description" label="描述" min-width="200" />
        <el-table-column prop="ip" label="IP地址" width="150" />
        <el-table-column prop="user_agent" label="设备信息" min-width="200" show-overflow-tooltip />
        <el-table-column prop="created_at" label="时间" width="180" />
      </el-table>
    </div>
  </div>
</template>

<style scoped>
.admin-user-behavior {
  padding: 24px;
}

.page-header {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 20px;
}

.table-container {
  background: white;
  padding: 20px;
  border-radius: 8px;
}
</style>
