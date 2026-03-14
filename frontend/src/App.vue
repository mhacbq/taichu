<template>
  <div class="app">
    <nav class="navbar">
      <div class="container nav-container">
        <router-link to="/" class="logo">
          <span class="logo-icon">☯</span>
          <span>太初命理</span>
        </router-link>
        <div class="nav-links">
          <router-link to="/" class="nav-link">首页</router-link>
          <router-link to="/bazi" class="nav-link">八字排盘</router-link>
          <router-link to="/tarot" class="nav-link">塔罗占卜</router-link>
          <router-link to="/daily" class="nav-link">每日运势</router-link>
          <router-link to="/profile" class="nav-link">个人中心</router-link>
        </div>
      </div>
    </nav>
    <main class="main-content">
      <router-view />
    </main>
    <footer class="footer">
      <div class="container">
        <p>&copy; 2025 太初命理 - AI智能命理分析平台</p>
      </div>
    </footer>
  </div>
</template>

<style scoped>
.app {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.navbar {
  background: rgba(0, 0, 0, 0.3);
  backdrop-filter: blur(10px);
  padding: 15px 0;
  position: sticky;
  top: 0;
  z-index: 100;
}

.nav-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.logo {
  font-size: 24px;
  font-weight: bold;
  color: #fff;
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 8px;
}

.logo-icon {
  font-size: 28px;
  color: #e94560;
}

.nav-links {
  display: flex;
  gap: 30px;
}

.nav-link {
  color: rgba(255, 255, 255, 0.8);
  text-decoration: none;
  font-size: 16px;
  transition: all 0.3s ease;
  position: relative;
}

.nav-link:hover,
.nav-link.router-link-active {
  color: #fff;
}

.nav-link.router-link-active::after {
  content: '';
  position: absolute;
  bottom: -5px;
  left: 0;
  right: 0;
  height: 2px;
  background: linear-gradient(90deg, #e94560, #ff6b6b);
  border-radius: 1px;
}

.main-content {
  flex: 1;
}

.footer {
  background: rgba(0, 0, 0, 0.3);
  padding: 20px 0;
  text-align: center;
  color: rgba(255, 255, 255, 0.6);
}

@media (max-width: 768px) {
  .nav-links {
    display: none;
  }
}
</style>
