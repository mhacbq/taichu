<template>
  <div class="not-found">
    <div class="container">
      <div class="error-content">
        <div class="error-code">404</div>
        <div class="error-icon">🔮</div>
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

<style scoped>
.not-found {
  min-height: 60vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
}

.error-content {
  text-align: center;
  max-width: 500px;
}

.error-code {
  font-size: 120px;
  font-weight: bold;
  background: linear-gradient(135deg, #e94560, #ffd700);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  line-height: 1;
  margin-bottom: 20px;
}

.error-icon {
  font-size: 60px;
  margin-bottom: 20px;
}

.error-content h1 {
  font-size: 32px;
  color: #fff;
  margin-bottom: 15px;
}

.error-content p {
  color: rgba(255, 255, 255, 0.6);
  font-size: 16px;
  margin-bottom: 30px;
}

.error-actions {
  display: flex;
  gap: 20px;
  justify-content: center;
}

@media (max-width: 480px) {
  .error-code {
    font-size: 80px;
  }
  
  .error-actions {
    flex-direction: column;
    align-items: center;
  }
}
</style>
