<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'

const loading = ref(false)
const almanacs = ref([])

const loadAlmanacs = async () => {
  loading.value = true
  // TODO: 实现加载黄历列表逻辑
  loading.value = false
}

onMounted(() => {
  loadAlmanacs()
})
</script>

<template>
  <div class="admin-almanac-manage" v-loading="loading">
    <div class="page-header">
      <h2>黄历管理</h2>
    </div>
    <div class="placeholder">
      <el-empty description="功能开发中" />
    </div>
  </div>
</template>

<style scoped>
.admin-almanac-manage {
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
