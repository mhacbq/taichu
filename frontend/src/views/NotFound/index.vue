<template>
  <div class="not-found">
    <div class="container">
      <div class="error-content">
        <div class="error-code">404</div>
        <div class="error-icon">
          <el-icon><MagicStick /></el-icon>
        </div>

        <h1>页面未找到</h1>
        <p>您寻找的页面似乎迷失在浩瀚的网络中了...</p>
        <div class="error-actions">
          <router-link to="/" class="btn-primary">
            返回首页
          </router-link>
          <router-link to="/bazi" class="btn-secondary">
            去算一卦
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { MagicStick } from '@element-plus/icons-vue'
</script>

<style scoped>
@import './style.css';
</style>
