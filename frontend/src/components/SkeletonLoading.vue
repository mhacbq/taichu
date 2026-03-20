<template>
  <div class="skeleton-loading" :class="{ 'skeleton-loading--dark': isDark }">
    <!-- 基础骨架屏 -->
    <div v-if="type === 'basic'" class="skeleton-basic">
      <div class="skeleton-avatar"></div>
      <div class="skeleton-lines">
        <div class="skeleton-line skeleton-line--long"></div>
        <div class="skeleton-line skeleton-line--medium"></div>
        <div class="skeleton-line skeleton-line--short"></div>
      </div>
    </div>

    <!-- 卡片骨架屏 -->
    <div v-else-if="type === 'card'" class="skeleton-card">
      <div class="skeleton-card__image"></div>
      <div class="skeleton-card__content">
        <div class="skeleton-line skeleton-line--long"></div>
        <div class="skeleton-line skeleton-line--medium"></div>
        <div class="skeleton-line skeleton-line--short"></div>
      </div>
    </div>

    <!-- 列表骨架屏 -->
    <div v-else-if="type === 'list'" class="skeleton-list">
      <div v-for="i in count" :key="i" class="skeleton-list__item">
        <div class="skeleton-avatar"></div>
        <div class="skeleton-lines">
          <div class="skeleton-line skeleton-line--long"></div>
          <div class="skeleton-line skeleton-line--medium"></div>
        </div>
      </div>
    </div>

    <!-- 文章骨架屏 -->
    <div v-else-if="type === 'article'" class="skeleton-article">
      <div class="skeleton-article__header">
        <div class="skeleton-line skeleton-line--extra-long"></div>
        <div class="skeleton-line skeleton-line--long"></div>
      </div>
      <div class="skeleton-article__body">
        <div v-for="i in 4" :key="i" class="skeleton-line skeleton-line--full"></div>
        <div class="skeleton-line skeleton-line--medium"></div>
      </div>
      <div class="skeleton-article__footer">
        <div class="skeleton-avatar skeleton-avatar--small"></div>
        <div class="skeleton-line skeleton-line--short"></div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  type?: 'basic' | 'card' | 'list' | 'article'
  count?: number
  isDark?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  type: 'basic',
  count: 3,
  isDark: false,
})
</script>

<style scoped>
.skeleton-loading {
  --skeleton-base: #f0f0f0;
  --skeleton-highlight: #e0e0e0;
  --skeleton-animation: skeleton-loading 1.5s ease-in-out infinite;
}

.skeleton-loading--dark {
  --skeleton-base: #2a2a2a;
  --skeleton-highlight: #3a3a3a;
}

@keyframes skeleton-loading {
  0% {
    background-position: -200% 0;
  }
  100% {
    background-position: 200% 0;
  }
}

.skeleton-avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: linear-gradient(
    90deg,
    var(--skeleton-base) 25%,
    var(--skeleton-highlight) 50%,
    var(--skeleton-base) 75%
  );
  background-size: 200% 100%;
  animation: var(--skeleton-animation);
}

.skeleton-avatar--small {
  width: 32px;
  height: 32px;
}

.skeleton-lines {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.skeleton-line {
  height: 16px;
  background: linear-gradient(
    90deg,
    var(--skeleton-base) 25%,
    var(--skeleton-highlight) 50%,
    var(--skeleton-base) 75%
  );
  background-size: 200% 100%;
  animation: var(--skeleton-animation);
  border-radius: 4px;
}

.skeleton-line--short {
  width: 60%;
}

.skeleton-line--medium {
  width: 80%;
}

.skeleton-line--long {
  width: 100%;
}

.skeleton-line--extra-long {
  width: 90%;
}

.skeleton-line--full {
  width: 100%;
  height: 14px;
}

.skeleton-basic {
  display: flex;
  gap: 16px;
  padding: 20px;
}

.skeleton-card {
  border: 1px solid var(--skeleton-base);
  border-radius: 8px;
  overflow: hidden;
}

.skeleton-card__image {
  height: 200px;
  background: linear-gradient(
    90deg,
    var(--skeleton-base) 25%,
    var(--skeleton-highlight) 50%,
    var(--skeleton-base) 75%
  );
  background-size: 200% 100%;
  animation: var(--skeleton-animation);
}

.skeleton-card__content {
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.skeleton-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.skeleton-list__item {
  display: flex;
  gap: 16px;
  padding: 16px;
}

.skeleton-article {
  padding: 24px;
}

.skeleton-article__header {
  margin-bottom: 24px;
}

.skeleton-article__body {
  margin-bottom: 24px;
}

.skeleton-article__footer {
  display: flex;
  align-items: center;
  gap: 12px;
}
</style>
