<template>
  <div class="error-page">
    <div class="error-content">
      <div class="error-code">404</div>
      <div class="error-desc">页面不存在</div>
      <div class="error-tip">抱歉，您访问的页面不存在或已被移除</div>
      <el-button type="primary" @click="$router.push('/')">返回首页</el-button>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.error-page {
  height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f5f7fa;
}

.error-content {
  text-align: center;
}

.error-code {
  font-size: 120px;
  font-weight: 700;
  color: #409eff;
  line-height: 1;
  margin-bottom: 20px;
}

.error-desc {
  font-size: 24px;
  color: #303133;
  margin-bottom: 10px;
}

.error-tip {
  font-size: 14px;
  color: #909399;
  margin-bottom: 30px;
}
</style>
