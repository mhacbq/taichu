<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'

const loading = ref(false)
const shenshas = ref([])

const loadShenshas = async () => {
  loading.value = true
  // TODO: 实现加载神煞列表逻辑
  loading.value = false
}

onMounted(() => {
  loadShenshas()
})
</script>

<template>
  <div class="admin-shensha-manage" v-loading="loading">
    <div class="page-header">
      <h2>神煞管理</h2>
    </div>
    <div class="placeholder">
      <el-empty description="功能开发中" />
    </div>
  </div>
</template>

<style scoped>
.admin-shensha-manage {
  padding: 24px;
}

.page-header {
  margin-bottom: 20px;
}

.placeholder {
  background: white;
  padding: 40px;
  border-radius: 8px;
  text-align: center;
}
</style>
