<template>
  <component
    :is="tag"
    v-bind="$attrs"
    @click="handleClick"
  >
    <slot></slot>
  </component>
</template>

<script>
/**
 * 触感反馈组件
 * 在支持的设备上提供触觉反馈
 */
export default {
  name: 'HapticFeedback',
  props: {
    tag: {
      type: String,
      default: 'button'
    },
    type: {
      type: String,
      default: 'light',
      validator: (val) => ['light', 'medium', 'heavy', 'success', 'warning', 'error'].includes(val)
    },
    disabled: {
      type: Boolean,
      default: false
    }
  },
  emits: ['click'],
  setup(props, { emit }) {
    // 检查是否支持触感反馈
    const isHapticSupported = () => {
      return 'vibrate' in navigator || 
             (window.navigator && window.navigator.userAgent.match(/iPhone|iPad|iPod/i))
    }
    
    // 触发触感反馈
    const triggerHaptic = () => {
      if (props.disabled) return
      
      if (!isHapticSupported()) return
      
      // 使用 Vibration API (Android)
      if ('vibrate' in navigator) {
        const patterns = {
          light: 10,
          medium: 20,
          heavy: 30,
          success: [10, 50, 10],
          warning: [20, 30, 20],
          error: [30, 50, 30]
        }
        navigator.vibrate(patterns[props.type] || 10)
      }
      
      // iOS Haptic Feedback (通过 WebKit 消息)
      if (window.webkit && window.webkit.messageHandlers) {
        try {
          window.webkit.messageHandlers.hapticFeedback.postMessage(props.type)
        } catch (e) {
          // 忽略错误
        }
      }
    }
    
    const handleClick = (event) => {
      triggerHaptic()
      emit('click', event)
    }
    
    return {
      handleClick
    }
  }
}
</script>

<style scoped>
/* 无样式，仅作为行为包装器 */
</style>
