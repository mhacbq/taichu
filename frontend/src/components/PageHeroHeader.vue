<template>
  <div class="page-hero">
    <BackButton :fallback="fallback" />
    <div class="page-hero__content">
      <h1 class="page-hero__title">
        <el-icon v-if="icon" class="page-hero__icon" :size="iconSize">
          <component :is="icon" />
        </el-icon>
        <span>{{ title }}</span>
      </h1>
      <p v-if="subtitle" class="page-hero__subtitle">{{ subtitle }}</p>
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
    default: 34,
  },
  fallback: {
    type: String,
    default: '/',
  },
})
</script>

<style scoped>
.page-hero {
  display: flex;
  align-items: flex-start;
  gap: 20px;
  margin-bottom: 32px;
}

.page-hero__content {
  flex: 1;
}

.page-hero__title {
  margin: 0 0 10px;
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: clamp(28px, 4vw, 36px);
  font-weight: 700;
  color: var(--text-primary);
  line-height: 1.15;
}

.page-hero__icon {
  color: var(--primary-color);
  flex-shrink: 0;
}

.page-hero__subtitle {
  margin: 0;
  color: var(--text-secondary);
  font-size: 15px;
  line-height: 1.7;
  max-width: 680px;
}

@media (max-width: 768px) {
  .page-hero {
    flex-direction: column;
    align-items: flex-start;
    gap: 16px;
    margin-bottom: 28px;
  }

  .page-hero__title {
    gap: 10px;
  }
}
</style>
