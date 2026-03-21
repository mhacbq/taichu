<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'

const loading = ref(false)
const tarotList = ref([])

onMounted(() => {
  fetchTarotList()
})

async function fetchTarotList() {
  loading.value = true
  try {
    // 这里调用API获取塔罗记录数据
    // const res = await getTarotList()
    // tarotList.value = res.data
  } catch (error) {
    ElMessage.error('获取塔罗记录失败')
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
          <span>塔罗记录</span>
          <el-button type="primary">刷新列表</el-button>
        </div>
      </template>

      <el-empty description="暂无塔罗记录" />
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
