<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'

const loading = ref(false)
const shenshaList = ref([])

onMounted(() => {
  fetchShenshaList()
})

async function fetchShenshaList() {
  loading.value = true
  try {
    // 这里调用API获取神煞管理数据
    // const res = await getShenshaList()
    // shenshaList.value = res.data
  } catch (error) {
    ElMessage.error('获取神煞数据失败')
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
          <span>神煞管理</span>
          <el-button type="primary">新增神煞</el-button>
        </div>
      </template>

      <el-empty description="暂无神煞数据" />
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
