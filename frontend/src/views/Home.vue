<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { useRouter } from 'vue-router'

import { useHomeStats } from '../composables/useHomeStats'
import { useUserPoints } from '../composables/useUserPoints'

import HeroSection from '../components/home/HeroSection.vue'
import FeaturesSection from '../components/home/FeaturesSection.vue'
import TestimonialsSection from '../components/home/TestimonialsSection.vue'
import AboutSection from '../components/home/AboutSection.vue'
import YearlyBanner from '../components/home/YearlyBanner.vue'

// Types
interface NavSection {
  id: string;
  label: string;
}

// Composables
const { stats, statsLoading, loadStats } = useHomeStats()
const { 
  isLoggedIn, 
  formattedUserPoints, 
  userPoints,
  baziOfferState, 
  loadUserPoints,
  refreshHomeAccountState 
} = useUserPoints()

// Router
const router = useRouter();

// UI State
const activeSection = ref('home');
const showScrollTop = ref(false);
const isMobileMenuOpen = ref(false);

// Navigation
const navSections: readonly NavSection[] = [
  { id: 'home', label: '首页' },
  { id: 'features', label: '功能特色' },
  { id: 'testimonials', label: '用户评价' },
  { id: 'about', label: '关于我们' },
];

// 使用Intersection Observer优化section检测
let observer: IntersectionObserver | null = null

const observeSections = () => {
  if (observer) {
    observer.disconnect()
  }
  
  const options = {
    root: null,
    rootMargin: '-50% 0px -50% 0px',
    threshold: 0
  }
  
  observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const id = entry.target.id
        if (navSections.some(s => s.id === id)) {
          activeSection.value = id
        }
      }
    })
  }, options)
  
  navSections.forEach(section => {
    const el = document.getElementById(section.id)
    if (el) observer?.observe(el)
  })
}

const scrollToSection = (sectionId: string) => {
  const element = document.getElementById(sectionId)
  if (element) {
    element.scrollIntoView({ behavior: 'smooth' })
    activeSection.value = sectionId
  }
}

const toggleMobileNav = () => {
  isMobileMenuOpen.value = !isMobileMenuOpen.value
}

// Routes
const registerIntentRoute = computed(() => ({
  path: '/login',
  query: { intent: 'register' }
}))

const navigateToLogin = () => {
  router.push('/login')
}

const navigateToBazi = () => {
  router.push('/bazi')
}

// Scroll handling
let ticking = false
const handleScroll = () => {
  if (!ticking) {
    window.requestAnimationFrame(() => {
      showScrollTop.value = window.scrollY > 400
      ticking = false
    })
    ticking = true
  }
}

const scrollToTop = () => {
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

// Lifecycle
let scrollHandler: (() => void) | null = null
let storageHandler: ((e: StorageEvent) => void) | null = null

onMounted(() => {
  loadStats()
  loadUserPoints()
  observeSections()
  
  // 节流处理scroll事件
  scrollHandler = handleScroll
  window.addEventListener('scroll', scrollHandler, { passive: true })
  
  // 监听登录状态变化
  storageHandler = (e: StorageEvent) => {
    if (e.key === 'token') {
      refreshHomeAccountState()
    }
  }
  window.addEventListener('storage', storageHandler)
})

onUnmounted(() => {
  if (observer) {
    observer.disconnect()
  }
  if (scrollHandler) {
    window.removeEventListener('scroll', scrollHandler)
  }
  if (storageHandler) {
    window.removeEventListener('storage', storageHandler)
  }
})
</script>

<template>
  <div class="home-page">
    <!-- 导航栏 -->
    <nav class="nav-bar" :class="{ 'scrolled': showScrollTop }">
      <div class="nav-container">
        <div class="nav-logo" @click="scrollToSection('home')">
          <h2>太初命理</h2>
        </div>
        
        <!-- 桌面端导航 -->
        <div class="nav-links desktop-only">
          <a 
            v-for="section in navSections" 
            :key="section.id"
            :href="`#${section.id}`"
            :class="{ active: activeSection === section.id }"
            @click.prevent="scrollToSection(section.id)"
          >
            {{ section.label }}
          </a>
        </div>

        <!-- 移动端菜单按钮 -->
        <div class="nav-actions desktop-only">
          <button v-if="!isLoggedIn" class="nav-button secondary" @click="navigateToLogin">
            登录
          </button>
          <button class="nav-button primary" @click="navigateToBazi">
            开始分析
          </button>
        </div>

        <!-- 移动端汉堡菜单 -->
        <button class="mobile-menu-btn mobile-only" @click="toggleMobileNav">
          <span :class="{ active: isMobileMenuOpen }"></span>
          <span :class="{ active: isMobileMenuOpen }"></span>
          <span :class="{ active: isMobileMenuOpen }"></span>
        </button>
      </div>

      <!-- 移动端导航菜单 -->
      <div class="mobile-nav-menu mobile-only" :class="{ open: isMobileMenuOpen }">
        <a 
          v-for="section in navSections" 
          :key="section.id"
          :href="`#${section.id}`"
          :class="{ active: activeSection === section.id }"
          @click="scrollToSection(section.id); isMobileMenuOpen = false"
        >
          {{ section.label }}
        </a>
        <div class="mobile-nav-actions">
          <button v-if="!isLoggedIn" class="nav-button secondary" @click="navigateToLogin; isMobileMenuOpen = false">
            登录
          </button>
          <button class="nav-button primary" @click="navigateToBazi; isMobileMenuOpen = false">
            开始分析
          </button>
        </div>
      </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="home-section">
      <HeroSection
        :is-logged-in="isLoggedIn"
        :user-points="userPoints"
        :formatted-user-points="formattedUserPoints"
        :bazi-offer-state="baziOfferState"
        :register-intent-route="registerIntentRoute"
      />
    </section>

    <!-- 年度横幅 -->
    <YearlyBanner />

    <!-- 统计数据 -->
    <section v-if="!statsLoading && stats.length > 0" class="stats-section">
      <div class="stats-grid">
        <div v-for="(stat, index) in stats" :key="index" class="stat-card">
          <div class="stat-number">{{ stat.number }}</div>
          <div class="stat-label">{{ stat.label }}</div>
          <div v-if="stat.caption" class="stat-caption">{{ stat.caption }}</div>
        </div>
      </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features-section">
      <FeaturesSection />
    </section>

    <!-- Testimonials Section -->
    <section id="testimonials" class="testimonials-section">
      <TestimonialsSection />
    </section>

    <!-- About Section -->
    <section id="about" class="about-section">
      <AboutSection />
    </section>

    <!-- 返回顶部按钮 -->
    <button 
      v-show="showScrollTop" 
      class="scroll-top-btn"
      @click="scrollToTop"
      aria-label="返回顶部"
    >
      ↑
    </button>
  </div>
</template>

<style scoped>
.home-page {
  min-height: 100vh;
  background: #FFFFFF;
}

/* Navigation */
.nav-bar {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  border-bottom: 1px solid rgba(212, 175, 55, 0.15);
  z-index: 1000;
  transition: all 0.3s ease;
}

.nav-bar.scrolled {
  background: rgba(255, 255, 255, 0.98);
  box-shadow: 0 4px 20px rgba(212, 175, 55, 0.1);
}

.nav-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 16px 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.nav-logo {
  cursor: pointer;
  transition: opacity 0.3s;
}

.nav-logo:hover {
  opacity: 0.8;
}

.nav-logo h2 {
  font-size: 24px;
  font-weight: 700;
  color: #5e4318;
  margin: 0;
  background: linear-gradient(135deg, #5e4318 0%, #8b6914 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.nav-links {
  display: flex;
  gap: 32px;
}

.nav-links a {
  text-decoration: none;
  color: #6b6254;
  font-size: 15px;
  font-weight: 500;
  transition: all 0.3s;
  position: relative;
  padding: 8px 0;
}

.nav-links a::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 50%;
  width: 0;
  height: 2px;
  background: linear-gradient(90deg, #8b6914, #d4af37);
  transition: all 0.3s;
  transform: translateX(-50%);
}

.nav-links a:hover,
.nav-links a.active {
  color: #8b6914;
}

.nav-links a:hover::after,
.nav-links a.active::after {
  width: 100%;
}

.nav-actions {
  display: flex;
  gap: 12px;
}

.nav-button {
  padding: 10px 24px;
  border-radius: 8px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
  border: none;
}

.nav-button.secondary {
  background: transparent;
  color: #8b6914;
  border: 1px solid rgba(212, 175, 55, 0.4);
}

.nav-button.secondary:hover {
  background: rgba(212, 175, 55, 0.1);
  border-color: #d4af37;
}

.nav-button.primary {
  background: linear-gradient(135deg, #e3b254 0%, #f6d484 52%, #ffe5aa 100%);
  color: #5f4317;
  border: 1px solid rgba(203, 149, 55, 0.48);
  box-shadow: 
    0 4px 12px rgba(212, 175, 55, 0.2),
    inset 0 1px 0 rgba(255, 255, 255, 0.65);
}

.nav-button.primary:hover {
  transform: translateY(-2px);
  box-shadow: 
    0 6px 16px rgba(212, 175, 55, 0.3),
    inset 0 1px 0 rgba(255, 255, 255, 0.65);
}

.mobile-menu-btn {
  display: none;
  flex-direction: column;
  gap: 5px;
  background: none;
  border: none;
  cursor: pointer;
  padding: 8px;
}

.mobile-menu-btn span {
  width: 24px;
  height: 2px;
  background: #5e4318;
  transition: all 0.3s;
}

.mobile-menu-btn span.active:nth-child(1) {
  transform: rotate(45deg) translate(5px, 5px);
}

.mobile-menu-btn span.active:nth-child(2) {
  opacity: 0;
}

.mobile-menu-btn span.active:nth-child(3) {
  transform: rotate(-45deg) translate(6px, -6px);
}

.mobile-nav-menu {
  display: none;
  position: fixed;
  top: 60px;
  left: 0;
  right: 0;
  background: rgba(255, 255, 255, 0.98);
  backdrop-filter: blur(10px);
  padding: 24px;
  flex-direction: column;
  gap: 16px;
  border-bottom: 1px solid rgba(212, 175, 55, 0.15);
  transform: translateY(-100%);
  opacity: 0;
  transition: all 0.3s;
}

.mobile-nav-menu.open {
  transform: translateY(0);
  opacity: 1;
}

.mobile-nav-menu a {
  text-decoration: none;
  color: #6b6254;
  font-size: 16px;
  font-weight: 500;
  padding: 12px 16px;
  border-radius: 8px;
  transition: all 0.3s;
}

.mobile-nav-menu a:hover,
.mobile-nav-menu a.active {
  background: rgba(212, 175, 55, 0.1);
  color: #8b6914;
}

.mobile-nav-actions {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-top: 8px;
}

.mobile-nav-actions .nav-button {
  width: 100%;
}

/* Stats Section */
.stats-section {
  max-width: 1200px;
  margin: 0 auto;
  padding: 48px 24px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 24px;
}

.stat-card {
  text-align: center;
  padding: 32px 24px;
  background: linear-gradient(135deg, rgba(255, 247, 229, 0.6) 0%, rgba(255, 255, 255, 0.8) 100%);
  border-radius: 16px;
  border: 1px solid rgba(212, 175, 55, 0.2);
  transition: all 0.3s;
}

.stat-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 32px rgba(212, 175, 55, 0.15);
}

.stat-number {
  font-size: 36px;
  font-weight: 700;
  color: #8b6914;
  margin-bottom: 8px;
  background: linear-gradient(135deg, #5e4318 0%, #8b6914 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.stat-label {
  font-size: 16px;
  font-weight: 600;
  color: #5e4318;
  margin-bottom: 4px;
}

.stat-caption {
  font-size: 14px;
  color: #9a8b6f;
}

/* Section Spacing */
.home-section,
.features-section,
.testimonials-section,
.about-section {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
}

.features-section,
.testimonials-section,
.about-section {
  padding: 80px 24px;
  min-height: auto;
}

/* Scroll to Top Button */
.scroll-top-btn {
  position: fixed;
  bottom: 32px;
  right: 32px;
  width: 48px;
  height: 48px;
  background: linear-gradient(135deg, #e3b254 0%, #f6d484 100%);
  color: #5f4317;
  border: none;
  border-radius: 50%;
  font-size: 20px;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 4px 16px rgba(212, 175, 55, 0.3);
  transition: all 0.3s;
  z-index: 999;
}

.scroll-top-btn:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(212, 175, 55, 0.4);
}

/* Responsive */
@media (max-width: 768px) {
  .nav-links,
  .nav-actions {
    display: none;
  }

  .mobile-menu-btn {
    display: flex;
  }

  .mobile-nav-menu {
    display: flex;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .features-section,
  .testimonials-section,
  .about-section {
    padding: 48px 16px;
  }

  .scroll-top-btn {
    bottom: 24px;
    right: 24px;
    width: 40px;
    height: 40px;
    font-size: 16px;
  }
}

@media (max-width: 1024px) {
  .desktop-only {
    display: none;
  }

  .mobile-only {
    display: flex;
  }
}
</style>
