<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'

const loading = ref(false)
const seoConfigs = ref([])

const loadSeoConfigs = async () => {
  loading.value = true
  // TODO: 实现加载SEO配置列表逻辑
  loading.value = false
}

onMounted(() => {
  loadSeoConfigs()
})
</script>

<template>
  <div class="admin-seo-manage" v-loading="loading">
    <div class="page-header">
      <h2>SEO管理</h2>
    </div>
    <div class="placeholder">
      <el-empty description="功能开发中" />
    </div>
  </div>
</template>

<style scoped>
.admin-seo-manage {
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
