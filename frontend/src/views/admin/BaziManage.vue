<script setup>
import { ref } from 'vue'

const loading = ref(false)
</script>

<template>
  <div class="admin-page-placeholder">
    <el-card>
      <div class="placeholder-content">
        <el-icon class="placeholder-icon" size="80">
          <Tools />
        </el-icon>
        <h2>八字管理</h2>
        <p>该功能模块正在开发中，敬请期待...</p>
        <el-button type="primary" disabled>功能开发中</el-button>
      </div>
    </el-card>
  </div>
</template>

<style scoped>
.admin-page-placeholder {
  padding: 24px;
}

.placeholder-content {
  text-align: center;
  padding: 60px 20px;
}

.placeholder-icon {
  color: #d4af37;
  margin-bottom: 20px;
}

.placeholder-content h2 {
  font-size: 24px;
  color: #333;
  margin-bottom: 12px;
}

.placeholder-content p {
  font-size: 16px;
  color: #666;
  margin-bottom: 24px;
}
</style>
