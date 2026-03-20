<template>
  <section class="result-next-steps card card-hover">
    <div class="result-next-steps__header">
      <span class="result-next-steps__eyebrow">{{ eyebrow }}</span>
      <h3 v-if="title" class="result-next-steps__title">{{ title }}</h3>
      <p v-if="description" class="result-next-steps__description">{{ description }}</p>
    </div>

    <div v-if="highlights.length" class="result-next-steps__highlights" aria-label="当前结果状态">
      <span
        v-for="item in highlights"
        :key="item.key || item.label"
        class="result-next-steps__highlight"
        :class="item.tone ? `result-next-steps__highlight--${item.tone}` : ''"
      >
        {{ item.label }}
      </span>
    </div>

    <div v-if="actions.length || $slots.actions" class="result-next-steps__actions">
      <template v-for="action in actions" :key="action.key || action.label">
        <router-link
          v-if="action.to && !action.disabled && !action.loading"
          :to="action.to"
          class="result-next-steps__action-link"
        >
          <el-button
            :type="action.type || 'default'"
            :plain="Boolean(action.plain)"
            :link="Boolean(action.link)"
            :size="action.size || 'default'"
            class="result-next-steps__action-btn"
          >
            {{ action.label }}
          </el-button>
        </router-link>
        <el-button
          v-else
          :type="action.type || 'default'"
          :plain="Boolean(action.plain)"
          :link="Boolean(action.link)"
          :size="action.size || 'default'"
          :disabled="Boolean(action.disabled)"
          :loading="Boolean(action.loading)"
          class="result-next-steps__action-btn"
          @click="handleAction(action)"
        >
          {{ action.label }}
        </el-button>
      </template>
      <slot name="actions"></slot>
    </div>

    <div v-if="recommendations.length" class="result-next-steps__recommendations">
      <div class="result-next-steps__recommendations-head">
        <span>顺手继续</span>
        <small>把这次结果自然接到下一步</small>
      </div>
      <div class="result-next-steps__recommendation-grid">
        <router-link
          v-for="item in recommendations"
          :key="item.key || item.to || item.title"
          :to="item.to"
          class="result-next-steps__recommendation-card"
        >
          <span v-if="item.badge" class="result-next-steps__recommendation-badge">{{ item.badge }}</span>
          <strong>{{ item.title }}</strong>
          <p>{{ item.description }}</p>
        </router-link>
      </div>
    </div>
  </section>
</template>

<script setup>
defineProps({
  eyebrow: {
    type: String,
    default: '下一步动作',
  },
  title: {
    type: String,
    default: '',
  },
  description: {
    type: String,
    default: '',
  },
  highlights: {
    type: Array,
    default: () => [],
  },
  actions: {
    type: Array,
    default: () => [],
  },
  recommendations: {
    type: Array,
    default: () => [],
  },
})

const handleAction = (action = {}) => {
  if (typeof action.onClick === 'function') {
    action.onClick()
  }
}
</script>

<style scoped>
.result-next-steps {
  margin-top: 28px;
  padding: 24px;
  border: 1px solid rgba(212, 175, 55, 0.16);
  background:
    radial-gradient(circle at top right, rgba(212, 175, 55, 0.1), transparent 36%),
    linear-gradient(145deg, rgba(22, 28, 45, 0.96), rgba(13, 18, 32, 0.98));
  box-shadow: 0 20px 45px rgba(5, 8, 18, 0.28);
}

.result-next-steps__header {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.result-next-steps__eyebrow {
  display: inline-flex;
  width: fit-content;
  padding: 4px 10px;
  border-radius: 999px;
  background: rgba(212, 175, 55, 0.14);
  color: #f3d27a;
  font-size: 12px;
  font-weight: 600;
  letter-spacing: 0.08em;
}

.result-next-steps__title {
  margin: 0;
  color: #f8f6ef;
  font-size: 22px;
  font-weight: 600;
}

.result-next-steps__description {
  margin: 0;
  color: rgba(245, 240, 226, 0.78);
  line-height: 1.7;
}

.result-next-steps__highlights {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 16px;
}

.result-next-steps__highlight {
  display: inline-flex;
  align-items: center;
  min-height: 34px;
  padding: 0 12px;
  border-radius: 999px;
  border: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(255, 255, 255, 0.05);
  color: rgba(248, 246, 239, 0.88);
  font-size: 13px;
}

.result-next-steps__highlight--success {
  border-color: rgba(103, 194, 58, 0.28);
  background: rgba(103, 194, 58, 0.12);
  color: #d8f2c8;
}

.result-next-steps__highlight--warning {
  border-color: rgba(230, 162, 60, 0.28);
  background: rgba(230, 162, 60, 0.12);
  color: #f6ddb1;
}

.result-next-steps__highlight--danger {
  border-color: rgba(245, 108, 108, 0.24);
  background: rgba(245, 108, 108, 0.12);
  color: #ffd3d3;
}

.result-next-steps__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 18px;
}

.result-next-steps__action-link {
  display: inline-block;
}

.result-next-steps__action-btn {
  min-height: 36px;
  font-size: 13px;
  padding: 0 16px;
}

.result-next-steps__recommendations {
  margin-top: 22px;
  padding-top: 18px;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.result-next-steps__recommendations-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  margin-bottom: 14px;
  color: rgba(248, 246, 239, 0.9);
}

.result-next-steps__recommendations-head small {
  color: rgba(245, 240, 226, 0.6);
}

.result-next-steps__recommendation-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 12px;
}

.result-next-steps__recommendation-card {
  display: flex;
  flex-direction: column;
  gap: 8px;
  min-height: 128px;
  padding: 16px;
  border-radius: 18px;
  border: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(255, 255, 255, 0.04);
  color: inherit;
  text-decoration: none;
  transition: transform 0.2s ease, border-color 0.2s ease, background 0.2s ease;
}

.result-next-steps__recommendation-card:hover {
  transform: translateY(-2px);
  border-color: rgba(212, 175, 55, 0.26);
  background: rgba(212, 175, 55, 0.08);
}

.result-next-steps__recommendation-card strong {
  color: #fff7e8;
  font-size: 16px;
}

.result-next-steps__recommendation-card p {
  margin: 0;
  color: rgba(245, 240, 226, 0.72);
  line-height: 1.6;
}

.result-next-steps__recommendation-badge {
  display: inline-flex;
  width: fit-content;
  padding: 3px 10px;
  border-radius: 999px;
  background: rgba(79, 209, 197, 0.14);
  color: #bdf7f0;
  font-size: 12px;
}

@media (max-width: 767px) {
  .result-next-steps {
    padding: 20px 16px;
  }

  .result-next-steps__recommendations-head {
    align-items: flex-start;
    flex-direction: column;
  }
}
</style>
