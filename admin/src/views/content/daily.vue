<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'

const loading = ref(false)
const dailyList = ref([])

onMounted(() => {
  fetchDailyList()
})

async function fetchDailyList() {
  loading.value = true
  try {
    // 这里调用API获取每日运势数据
    // const res = await getDailyList()
    // dailyList.value = res.data
  } catch (error) {
    ElMessage.error('获取每日运势失败')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="app-container">
    <el-card v-loading="loading">
      <template #header>
        <div class="card-header">
          <span>每日运势</span>
          <el-button type="primary">新增运势</el-button>
        </div>
      </template>

      <el-empty description="暂无每日运势数据" />
    </el-card>
  </div>
</template>

<style scoped>
.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
</style>
