<template>
  <div class="empty-state" :class="[`empty-state--${size}`, { 'empty-state--inline': inline }]">
    <div class="empty-state__image">
      <!-- 根据类型显示不同插图 -->
      <slot name="image">
        <svg v-if="type === 'search'" viewBox="0 0 200 200" fill="none">
          <circle cx="90" cy="90" r="60" stroke="#d9d9d9" stroke-width="4"/>
          <path d="M135 135l40 40" stroke="#d9d9d9" stroke-width="4" stroke-linecap="round"/>
          <circle cx="90" cy="90" r="40" stroke="#f0f0f0" stroke-width="4" stroke-dasharray="8 8"/>
        </svg>
        <svg v-else-if="type === 'error'" viewBox="0 0 200 200" fill="none">
          <circle cx="100" cy="100" r="60" stroke="#ffccc7" stroke-width="4"/>
          <path d="M75 75l50 50M125 75l-50 50" stroke="#ff4d4f" stroke-width="6" stroke-linecap="round"/>
        </svg>
        <svg v-else-if="type === 'network'" viewBox="0 0 200 200" fill="none">
          <rect x="40" y="60" width="120" height="80" rx="8" stroke="#d9d9d9" stroke-width="4"/>
          <path d="M70 100h60M85 85l-15 15 15 15M115 85l15 15-15 15" stroke="#d9d9d9" stroke-width="4" stroke-linecap="round"/>
          <circle cx="160" cy="50" r="15" stroke="#ff4d4f" stroke-width="3"/>
          <path d="M152 50h16M160 42v16" stroke="#ff4d4f" stroke-width="3"/>
        </svg>
        <svg v-else-if="type === 'no-data'" viewBox="0 0 200 200" fill="none">
          <rect x="50" y="40" width="100" height="120" rx="8" stroke="#d9d9d9" stroke-width="4"/>
          <path d="M70 80h60M70 100h60M70 120h40" stroke="#f0f0f0" stroke-width="4" stroke-linecap="round"/>
          <circle cx="140" cy="160" r="25" fill="#fafafa"/>
          <path d="M130 160h20" stroke="#d9d9d9" stroke-width="4" stroke-linecap="round"/>
        </svg>
        <svg v-else viewBox="0 0 200 200" fill="none">
          <circle cx="100" cy="100" r="60" stroke="#f0f0f0" stroke-width="4"/>
          <circle cx="100" cy="80" r="20" fill="#d9d9d9"/>
          <path d="M70 130c0-16.569 13.431-30 30-30s30 13.431 30 30" stroke="#d9d9d9" stroke-width="4" stroke-linecap="round"/>
        </svg>
      </slot>
    </div>
    
    <div class="empty-state__content">
      <h3 v-if="title" class="empty-state__title">{{ title }}</h3>
      <p v-if="description" class="empty-state__description">{{ description }}</p>
      <slot name="extra">
        <div v-if="actionText" class="empty-state__action">
          <button class="btn btn-primary" @click="$emit('action')">
            {{ actionText }}
          </button>
        </div>
      </slot>
    </div>
  </div>
</template>

<script>
export default {
  name: 'EmptyState',
  props: {
    type: {
      type: String,
      default: 'default',
      validator: (val) => ['default', 'search', 'error', 'network', 'no-data'].includes(val)
    },
    title: {
      type: String,
      default: ''
    },
    description: {
      type: String,
      default: ''
    },
    actionText: {
      type: String,
      default: ''
    },
    size: {
      type: String,
      default: 'default',
      validator: (val) => ['small', 'default', 'large'].includes(val)
    },
    inline: {
      type: Boolean,
      default: false
    }
  },
  emits: ['action']
}
</script>

<style scoped>
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px;
  text-align: center;
}

.empty-state--inline {
  flex-direction: row;
  gap: 24px;
  padding: 20px;
  text-align: left;
}

.empty-state--inline .empty-state__image {
  width: 80px;
  height: 80px;
}

.empty-state--inline .empty-state__content {
  flex: 1;
}

.empty-state__image {
  width: 160px;
  height: 160px;
  margin-bottom: 24px;
}

.empty-state__image svg {
  width: 100%;
  height: 100%;
}

.empty-state--small .empty-state__image {
  width: 80px;
  height: 80px;
  margin-bottom: 16px;
}

.empty-state--large .empty-state__image {
  width: 240px;
  height: 240px;
  margin-bottom: 32px;
}

.empty-state__title {
  font-size: 18px;
  font-weight: 600;
  color: #1a1a1a;
  margin: 0 0 8px;
}

.empty-state--small .empty-state__title {
  font-size: 16px;
}

.empty-state--large .empty-state__title {
  font-size: 24px;
}

.empty-state__description {
  font-size: 14px;
  color: #666;
  margin: 0 0 16px;
  line-height: 1.6;
  max-width: 400px;
}

.empty-state--small .empty-state__description {
  font-size: 13px;
}

.empty-state__action {
  margin-top: 8px;
}

.btn {
  padding: 10px 24px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
}

.btn-primary {
  background: linear-gradient(135deg, #8B5CF6 0%, #7C3AED 100%);
  color: white;
}

.btn-primary:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
}

.btn-primary:active {
  transform: translateY(0);
}
</style>
