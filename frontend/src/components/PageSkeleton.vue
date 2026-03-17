<template>
  <div class="page-skeleton">
    <!-- 导航栏骨架 -->
    <div class="skeleton-navbar">
      <div class="skeleton-logo"></div>
      <div class="skeleton-nav-links">
        <div v-for="i in 5" :key="i" class="skeleton-nav-item"></div>
      </div>
    </div>
    
    <!-- 内容区域骨架 -->
    <div class="skeleton-content">
      <!-- Hero区域 -->
      <div class="skeleton-hero">
        <div class="skeleton-title"></div>
        <div class="skeleton-subtitle"></div>
        <div class="skeleton-subtitle short"></div>
        <div class="skeleton-buttons">
          <div v-for="i in 2" :key="i" class="skeleton-btn"></div>
        </div>
      </div>
      
      <!-- 功能卡片区域 -->
      <div class="skeleton-features">
        <div v-for="i in 3" :key="i" class="skeleton-feature-card">
          <div class="skeleton-icon"></div>
          <div class="skeleton-card-title"></div>
          <div class="skeleton-card-text"></div>
          <div class="skeleton-card-text short"></div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.page-skeleton {
  min-height: 100vh;
  background: var(--bg-primary);
}

.skeleton-navbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px 40px;
  background: var(--bg-secondary);
  border-bottom: 1px solid var(--border-color);
}

.skeleton-logo {
  width: 150px;
  height: 36px;
  background: linear-gradient(90deg, rgba(255,255,255,0.1) 25%, rgba(255,255,255,0.2) 50%, rgba(255,255,255,0.1) 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  border-radius: 8px;
}

.skeleton-nav-links {
  display: flex;
  gap: 30px;
}

.skeleton-nav-item {
  width: 80px;
  height: 20px;
  background: linear-gradient(90deg, rgba(255,255,255,0.1) 25%, rgba(255,255,255,0.2) 50%, rgba(255,255,255,0.1) 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  border-radius: 4px;
}

.skeleton-content {
  padding: 60px 40px;
  max-width: 1200px;
  margin: 0 auto;
}

.skeleton-hero {
  text-align: center;
  margin-bottom: 80px;
}

.skeleton-title {
  width: 500px;
  height: 60px;
  margin: 0 auto 20px;
  background: linear-gradient(90deg, rgba(255,255,255,0.1) 25%, rgba(255,255,255,0.2) 50%, rgba(255,255,255,0.1) 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  border-radius: 8px;
}

.skeleton-subtitle {
  width: 400px;
  height: 24px;
  margin: 0 auto 10px;
  background: linear-gradient(90deg, rgba(255,255,255,0.05) 25%, rgba(255,255,255,0.1) 50%, rgba(255,255,255,0.05) 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  border-radius: 4px;
}

.skeleton-subtitle.short {
  width: 300px;
}

.skeleton-buttons {
  display: flex;
  justify-content: center;
  gap: 20px;
  margin-top: 40px;
}

.skeleton-btn {
  width: 140px;
  height: 48px;
  background: linear-gradient(90deg, rgba(255,215,0,0.3) 25%, rgba(255,215,0,0.5) 50%, rgba(255,215,0,0.3) 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  border-radius: 24px;
}

.skeleton-features {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 30px;
}

.skeleton-feature-card {
  background: rgba(255, 255, 255, 0.05);
  border-radius: 20px;
  padding: 40px 30px;
  text-align: center;
}

.skeleton-icon {
  width: 60px;
  height: 60px;
  margin: 0 auto 20px;
  background: linear-gradient(90deg, rgba(255,255,255,0.1) 25%, rgba(255,255,255,0.2) 50%, rgba(255,255,255,0.1) 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  border-radius: 50%;
}

.skeleton-card-title {
  width: 120px;
  height: 28px;
  margin: 0 auto 15px;
  background: linear-gradient(90deg, rgba(255,255,255,0.1) 25%, rgba(255,255,255,0.2) 50%, rgba(255,255,255,0.1) 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  border-radius: 4px;
}

.skeleton-card-text {
  width: 100%;
  height: 16px;
  margin-bottom: 8px;
  background: linear-gradient(90deg, rgba(255,255,255,0.05) 25%, rgba(255,255,255,0.1) 50%, rgba(255,255,255,0.05) 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  border-radius: 4px;
}

.skeleton-card-text.short {
  width: 70%;
  margin: 0 auto;
}

@keyframes shimmer {
  0% {
    background-position: -200% 0;
  }
  100% {
    background-position: 200% 0;
  }
}

@media (max-width: 768px) {
  .skeleton-navbar {
    padding: 15px 20px;
  }
  
  .skeleton-nav-links {
    display: none;
  }
  
  .skeleton-title {
    width: 80%;
    height: 40px;
  }
  
  .skeleton-subtitle {
    width: 90%;
  }
  
  .skeleton-subtitle.short {
    width: 70%;
  }
  
  .skeleton-features {
    grid-template-columns: 1fr;
  }
}
</style>
