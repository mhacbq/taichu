<template>
  <div class="fab-container" :class="{ 'fab-container--expanded': expanded }">
    <!-- 子按钮 -->
    <TransitionGroup name="fab-item">
      <button
        v-for="(item, index) in items"
        v-show="expanded"
        :key="item.key"
        class="fab-item"
        :style="getItemStyle(index)"
        @click="handleItemClick(item)"
      >
        <span class="fab-item__label">{{ item.label }}</span>
        <div class="fab-item__icon" :style="{ background: item.color }">
          <component :is="item.icon" v-if="item.icon" />
          <svg v-else viewBox="0 0 24 24" fill="none">
            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
          </svg>
        </div>
      </button>
    </TransitionGroup>
    
    <!-- 主按钮 -->
    <button
      class="fab-main"
      :class="{ 'fab-main--active': expanded }"
      :style="{ background: mainColor }"
      @click="toggle"
    >
      <svg class="fab-main__icon" viewBox="0 0 24 24" fill="none">
        <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
      </svg>
    </button>
    
    <!-- 遮罩 -->
    <Transition name="fade">
      <div v-if="expanded" class="fab-overlay" @click="close"></div>
    </Transition>
  </div>
</template>

<script>
import { ref, computed } from 'vue'

export default {
  name: 'FloatingActionButton',
  props: {
    items: {
      type: Array,
      default: () => []
    },
    mainColor: {
      type: String,
      default: '#8B5CF6'
    },
    direction: {
      type: String,
      default: 'up',
      validator: (val) => ['up', 'down', 'left', 'right'].includes(val)
    },
    offset: {
      type: Number,
      default: 70
    }
  },
  emits: ['click'],
  setup(props, { emit }) {
    const expanded = ref(false)
    
    const toggle = () => {
      expanded.value = !expanded.value
    }
    
    const close = () => {
      expanded.value = false
    }
    
    const handleItemClick = (item) => {
      emit('click', item)
      close()
      
      if (item.onClick) {
        item.onClick()
      }
    }
    
    const getItemStyle = (index) => {
      const distance = props.offset * (index + 1)
      const angle = props.direction === 'up' ? -90 :
                    props.direction === 'down' ? 90 :
                    props.direction === 'left' ? 180 : 0
      
      const radian = (angle * Math.PI) / 180
      const x = Math.cos(radian) * distance
      const y = Math.sin(radian) * distance
      
      return {
        transform: `translate(${x}px, ${y}px)`,
        transitionDelay: `${index * 0.05}s`
      }
    }
    
    return {
      expanded,
      toggle,
      close,
      handleItemClick,
      getItemStyle
    }
  }
}
</script>

<style scoped>
.fab-container {
  position: fixed;
  bottom: 100px;
  right: 20px;
  z-index: 100;
}

.fab-main {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  border: none;
  color: white;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
  transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
  position: relative;
  z-index: 2;
}

.fab-main:hover {
  transform: scale(1.1);
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
}

.fab-main--active {
  transform: rotate(45deg);
}

.fab-main__icon {
  width: 24px;
  height: 24px;
  transition: transform 0.3s;
}

.fab-item {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 0;
  border: none;
  background: none;
  cursor: pointer;
  z-index: 1;
}

.fab-item__label {
  font-size: 14px;
  color: #333;
  background: white;
  padding: 6px 12px;
  border-radius: 6px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  white-space: nowrap;
  opacity: 0;
  transform: translateX(10px);
  transition: all 0.3s ease;
}

.fab-item:hover .fab-item__label {
  opacity: 1;
  transform: translateX(0);
}

.fab-item__icon {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
  transition: all 0.2s;
}

.fab-item__icon:hover {
  transform: scale(1.1);
}

.fab-item__icon svg {
  width: 20px;
  height: 20px;
}

.fab-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.3);
  z-index: 0;
}

/* 动画 */
.fab-item-enter-active,
.fab-item-leave-active {
  transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.fab-item-enter-from,
.fab-item-leave-to {
  opacity: 0;
  transform: translate(-50%, -50%) scale(0.5) !important;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* 移动端适配 */
@media (max-width: 768px) {
  .fab-container {
    bottom: 80px;
    right: 16px;
  }
  
  .fab-main {
    width: 48px;
    height: 48px;
  }
  
  .fab-main__icon {
    width: 20px;
    height: 20px;
  }
  
  .fab-item__label {
    display: none;
  }
}
</style>
