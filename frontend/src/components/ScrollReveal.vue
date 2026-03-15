<template>
  <div
    ref="elementRef"
    class="scroll-reveal"
    :class="{ 'is-visible': isVisible, [`reveal-${animation}`]: true }"
    :style="customStyles"
  >
    <slot />
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'

const props = defineProps({
  animation: {
    type: String,
    default: 'fade-up', // fade-up, fade-down, fade-left, fade-right, zoom, flip
  },
  delay: {
    type: Number,
    default: 0,
  },
  duration: {
    type: Number,
    default: 600,
  },
  threshold: {
    type: Number,
    default: 0.2,
  },
  once: {
    type: Boolean,
    default: true,
  },
})

const elementRef = ref(null)
const isVisible = ref(false)
let observer = null

const customStyles = computed(() => ({
  transitionDelay: `${props.delay}ms`,
  transitionDuration: `${props.duration}ms`,
}))

onMounted(() => {
  observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          isVisible.value = true
          if (props.once && observer) {
            observer.unobserve(entry.target)
          }
        } else if (!props.once) {
          isVisible.value = false
        }
      }),
    },
    {
      threshold: props.threshold,
      rootMargin: '0px 0px -50px 0px',
    }
  )

  if (elementRef.value) {
    observer.observe(elementRef.value)
  }
})

onUnmounted(() => {
  if (observer) {
    observer.disconnect()
  }
})
</script>

<style scoped>
.scroll-reveal {
  opacity: 0;
  transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}

.scroll-reveal.is-visible {
  opacity: 1;
  transform: translate(0) scale(1) rotate(0) !important;
}

/* Fade Up */
.reveal-fade-up {
  transform: translateY(40px);
}

/* Fade Down */
.reveal-fade-down {
  transform: translateY(-40px);
}

/* Fade Left */
.reveal-fade-left {
  transform: translateX(-40px);
}

/* Fade Right */
.reveal-fade-right {
  transform: translateX(40px);
}

/* Zoom */
.reveal-zoom {
  transform: scale(0.8);
}

/* Flip */
.reveal-flip {
  transform: perspective(400px) rotateX(20deg);
}

/* Stagger children */
:deep(.stagger-children > *) {
  opacity: 0;
  transform: translateY(20px);
  transition: all 0.5s ease;
}

.scroll-reveal.is-visible :deep(.stagger-children > *) {
  opacity: 1;
  transform: translateY(0);
}

.scroll-reveal.is-visible :deep(.stagger-children > *:nth-child(1)) { transition-delay: 0ms; }
.scroll-reveal.is-visible :deep(.stagger-children > *:nth-child(2)) { transition-delay: 100ms; }
.scroll-reveal.is-visible :deep(.stagger-children > *:nth-child(3)) { transition-delay: 200ms; }
.scroll-reveal.is-visible :deep(.stagger-children > *:nth-child(4)) { transition-delay: 300ms; }
.scroll-reveal.is-visible :deep(.stagger-children > *:nth-child(5)) { transition-delay: 400ms; }
.scroll-reveal.is-visible :deep(.stagger-children > *:nth-child(6)) { transition-delay: 500ms; }
</style>
