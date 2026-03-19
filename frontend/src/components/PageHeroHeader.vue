<template>
  <div class="page-hero">
    <BackButton :fallback="fallback" class="page-hero__back" />
    <div class="page-hero__panel">
      <span class="page-hero__halo page-hero__halo--primary" aria-hidden="true"></span>
      <span class="page-hero__halo page-hero__halo--secondary" aria-hidden="true"></span>
      <div class="page-hero__content">
        <span class="page-hero__eyebrow">太初命理 · 工具页</span>
        <h1 class="page-hero__title">
          <span v-if="icon" class="page-hero__icon-shell">
            <el-icon class="page-hero__icon" :size="iconSize">
              <component :is="icon" />
            </el-icon>
          </span>
          <span>{{ title }}</span>
        </h1>
        <p v-if="subtitle" class="page-hero__subtitle">{{ subtitle }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import BackButton from './BackButton.vue'

defineProps({
  title: {
    type: String,
    required: true,
  },
  subtitle: {
    type: String,
    default: '',
  },
  icon: {
    type: [Object, Function],
    default: null,
  },
  iconSize: {
    type: Number,
    default: 28,
  },
  fallback: {
    type: String,
    default: '/',
  },
})
</script>

<style scoped>
.page-hero {
  display: grid;
  grid-template-columns: auto minmax(0, 1fr);
  align-items: stretch;
  gap: 20px;
  margin-bottom: 36px;
}

.page-hero__back {
  align-self: flex-start;
}

.page-hero__panel {
  position: relative;
  overflow: hidden;
  min-height: 172px;
  padding: 28px 32px;
  border-radius: 28px;
  background:
    linear-gradient(145deg, rgba(255, 255, 255, 0.98), rgba(255, 249, 238, 0.96));
  border: 1px solid rgba(var(--primary-rgb), 0.14);
  box-shadow: 0 20px 44px rgba(15, 23, 42, 0.08), 0 10px 26px rgba(var(--primary-rgb), 0.08);
}

.page-hero__halo {
  position: absolute;
  border-radius: 999px;
  pointer-events: none;
  filter: blur(6px);
}

.page-hero__halo--primary {
  width: 220px;
  height: 220px;
  top: -72px;
  right: -40px;
  background: radial-gradient(circle, rgba(var(--primary-rgb), 0.2) 0%, rgba(var(--primary-rgb), 0) 72%);
}

.page-hero__halo--secondary {
  width: 180px;
  height: 180px;
  bottom: -76px;
  left: 12%;
  background: radial-gradient(circle, rgba(245, 196, 103, 0.18) 0%, rgba(245, 196, 103, 0) 74%);
}

.page-hero__content {
  position: relative;
  z-index: 1;
  max-width: 760px;
}

.page-hero__eyebrow {
  display: inline-flex;
  align-items: center;
  min-height: 30px;
  padding: 0 12px;
  margin-bottom: 14px;
  border-radius: 999px;
  background: rgba(var(--primary-rgb), 0.09);
  border: 1px solid rgba(var(--primary-rgb), 0.14);
  color: #8c641f;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.08em;
}

.page-hero__title {
  margin: 0;
  display: flex;
  align-items: center;
  gap: 16px;
  font-size: clamp(30px, 4vw, 40px);
  font-weight: 800;
  color: var(--text-primary);
  line-height: 1.12;
  letter-spacing: var(--tracking-tight);
}

.page-hero__icon-shell {
  width: 56px;
  height: 56px;
  border-radius: 18px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.16), rgba(245, 196, 103, 0.28));
  border: 1px solid rgba(var(--primary-rgb), 0.14);
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.58);
}

.page-hero__icon {
  color: var(--primary-color);
}

.page-hero__subtitle {
  margin: 16px 0 0;
  color: var(--text-secondary);
  font-size: 15px;
  line-height: 1.8;
  max-width: 680px;
}

@media (max-width: 768px) {
  .page-hero {
    grid-template-columns: 1fr;
    gap: 14px;
    margin-bottom: 28px;
  }

  .page-hero__panel {
    min-height: 0;
    padding: 22px 20px;
    border-radius: 22px;
  }

  .page-hero__title {
    align-items: flex-start;
    gap: 12px;
    font-size: clamp(26px, 8vw, 34px);
  }

  .page-hero__icon-shell {
    width: 48px;
    height: 48px;
    border-radius: 16px;
  }

  .page-hero__subtitle {
    margin-top: 14px;
    font-size: 14px;
  }
}
</style>
