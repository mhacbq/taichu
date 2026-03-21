<script setup>
defineProps({
  badge: String,
  title: String,
  description: String
})

const emit = defineEmits(['click'])
</script>

<template>
  <div class="yearly-banner card-hover" @click="emit('click')">
    <div class="yearly-banner-content">
      <div class="yearly-banner-badge">{{ badge }}</div>
      <h3 class="yearly-banner-title">{{ title }}</h3>
      <p class="yearly-banner-desc">{{ description }}</p>
      <div class="yearly-banner-action">
        <span class="yearly-banner-btn">立即测算 <el-icon><ArrowRight /></el-icon></span>
      </div>
    </div>
    <div class="yearly-banner-bg">
      <el-icon class="yearly-banner-icon"><Calendar /></el-icon>
    </div>
  </div>
</template>

<style scoped>
.yearly-banner {
  position: relative;
  overflow: hidden;
  background: linear-gradient(135deg, #df3b3b 0%, #e75b5b 100%);
  border-radius: var(--radius-xl);
  padding: 36px 40px;
  margin-bottom: 32px;
  cursor: pointer;
  box-shadow: 0 16px 32px rgba(223, 59, 59, 0.2);
  border: 1px solid rgba(255, 255, 255, 0.2);
  transition: all 0.35s cubic-bezier(0.25, 0.46, 0.45, 0.94);
  display: flex;
  align-items: center;
}

.yearly-banner:hover {
  transform: translateY(-6px);
  box-shadow: 0 24px 48px rgba(223, 59, 59, 0.3);
}

.yearly-banner-content {
  position: relative;
  z-index: 2;
  max-width: 600px;
  color: #fff;
}

.yearly-banner-badge {
  display: inline-block;
  padding: 4px 12px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 999px;
  font-size: 13px;
  font-weight: 600;
  margin-bottom: 16px;
  backdrop-filter: blur(4px);
  border: 1px solid rgba(255, 255, 255, 0.3);
}

.yearly-banner-title {
  font-size: 32px;
  font-weight: 800;
  margin-bottom: 12px;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.yearly-banner-desc {
  font-size: 15px;
  line-height: 1.6;
  opacity: 0.9;
  margin-bottom: 24px;
}

.yearly-banner-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 24px;
  background: #fff;
  color: #df3b3b;
  border-radius: 999px;
  font-weight: 600;
  font-size: 15px;
  transition: all 0.3s ease;
}

.yearly-banner:hover .yearly-banner-btn {
  gap: 12px;
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
}

.yearly-banner-bg {
  position: absolute;
  right: -20px;
  top: -40px;
  z-index: 1;
  opacity: 0.15;
  transform: rotate(15deg);
}

.yearly-banner-icon {
  font-size: 280px;
  color: #fff;
}

@media (max-width: 768px) {
  .yearly-banner {
    padding: 28px 24px;
  }
  
  .yearly-banner-title {
    font-size: 24px;
  }
  
  .yearly-banner-icon {
    font-size: 200px;
    right: -40px;
  }
}
</style>
