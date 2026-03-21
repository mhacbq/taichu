<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'

const loading = ref(false)
const questionTemplates = ref([])

const loadQuestionTemplates = async () => {
  loading.value = true
  // TODO: 实现加载问题模板列表逻辑
  loading.value = false
}

onMounted(() => {
  loadQuestionTemplates()
})
</script>

<template>
  <div class="admin-question-templates" v-loading="loading">
    <div class="page-header">
      <h2>问题模板管理</h2>
    </div>
    <div class="placeholder">
      <el-empty description="功能开发中" />
    </div>
  </div>
</template>

<style scoped>
.admin-question-templates {
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
