<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'

const loading = ref(false)
const almanacList = ref([])

onMounted(() => {
  fetchAlmanacList()
})

async function fetchAlmanacList() {
  loading.value = true
  try {
    // 这里调用API获取黄历数据
    // const res = await getAlmanacList()
    // almanacList.value = res.data
  } catch (error) {
    ElMessage.error('获取黄历数据失败')
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
          <span>黄历管理</span>
          <el-button type="primary">新增黄历</el-button>
        </div>
      </template>

      <el-empty description="暂无黄历数据" />
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
