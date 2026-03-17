<template>
  <div class="empty-state" :class="[`empty-state--${size}`, { 'empty-state--inline': inline }]">
    <div class="empty-state__image">
      <!-- 根据类型显示不同插图 -->
      <slot name="image">
        <svg v-if="type === 'search'" viewBox="0 0 200 200" fill="none" class="empty-svg">
          <defs>
            <radialGradient id="search-glow" cx="50%" cy="50%" r="50%">
              <stop offset="0%" stop-color="var(--primary-light)" stop-opacity="0.2" />
              <stop offset="100%" stop-color="var(--primary-light)" stop-opacity="0" />
            </radialGradient>
          </defs>
          <circle cx="100" cy="100" r="80" fill="url(#search-glow)"/>
          <circle cx="90" cy="90" r="50" stroke="var(--primary-light)" stroke-width="2" opacity="0.4"/>
          <path d="M130 130l40 40" stroke="var(--primary-color)" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/>
          <circle cx="90" cy="90" r="35" stroke="var(--primary-color)" stroke-width="1.5" stroke-dasharray="6 6"/>
        </svg>
        <svg v-else-if="type === 'error'" viewBox="0 0 200 200" fill="none" class="empty-svg">
          <circle cx="100" cy="100" r="70" stroke="var(--danger-light)" stroke-width="2" opacity="0.2"/>
          <path d="M70 70l60 60M130 70l-60 60" stroke="var(--danger-color)" stroke-width="8" stroke-linecap="round" opacity="0.8"/>
          <circle cx="100" cy="100" r="85" stroke="var(--danger-color)" stroke-width="1" stroke-dasharray="4 8" opacity="0.3"/>
        </svg>
        <svg v-else-if="type === 'network'" viewBox="0 0 200 200" fill="none" class="empty-svg">
          <rect x="30" y="50" width="140" height="100" rx="12" stroke="var(--primary-light)" stroke-width="2" opacity="0.2"/>
          <path d="M60 100h80M75 80l-20 20 20 20M125 80l20 20-20 20" stroke="var(--primary-color)" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
          <circle cx="100" cy="100" r="40" stroke="var(--primary-light)" stroke-width="1" stroke-dasharray="10 5" opacity="0.3"/>
        </svg>
        <svg v-else-if="type === 'no-data'" viewBox="0 0 200 200" fill="none" class="empty-svg">
          <rect x="55" y="35" width="90" height="110" rx="6" stroke="var(--primary-light)" stroke-width="2" opacity="0.3"/>
          <path d="M75 65h50M75 85h50M75 105h30" stroke="var(--primary-color)" stroke-width="4" stroke-linecap="round" opacity="0.6"/>
          <path d="M145 145c-5-5-13-5-18 0l-2 2-2-2c-5-5-13-5-18 0-5 5-5 13 0 18l20 20 20-20c5-5 5-13 0-18z" fill="var(--primary-color)" opacity="0.4">
            <animate attributeName="opacity" values="0.2;0.5;0.2" dur="3s" repeatCount="indefinite" />
          </path>
          <circle cx="140" cy="150" r="35" fill="var(--bg-tertiary)" stroke="var(--primary-light)" stroke-width="1" opacity="0.2"/>
        </svg>
        <svg v-else viewBox="0 0 200 200" fill="none" class="empty-svg">
          <circle cx="100" cy="100" r="75" stroke="var(--primary-light)" stroke-width="1" stroke-dasharray="8 4" opacity="0.2"/>
          <path d="M100 30v140M30 100h140" stroke="var(--primary-color)" stroke-width="0.5" opacity="0.2"/>
          <circle cx="100" cy="100" r="30" fill="var(--primary-gradient)" opacity="0.8"/>
          <circle cx="100" cy="100" r="45" stroke="var(--primary-color)" stroke-width="1" opacity="0.3"/>
        </svg>
      </slot>
    </div>
    
    <div class="empty-state__content">
      <h3 v-if="title" class="empty-state__title">{{ title }}</h3>
      <p v-if="description" class="empty-state__description">{{ description }}</p>
      <slot name="extra">
        <div v-if="actionText" class="empty-state__action">
          <button class="btn btn-primary-custom" @click="$emit('action')">
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
  background: transparent;
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
  opacity: 0.8;
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
  color: var(--text-primary);
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
  color: var(--text-secondary);
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

.btn-primary-custom {
  padding: 10px 30px;
  border-radius: var(--radius-btn);
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
  border: none;
  background: var(--primary-gradient);
  color: var(--text-primary);
}

.btn-primary-custom:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(184, 134, 11, 0.3);
}

.btn-primary-custom:active {
  transform: translateY(0);
}
</style>
