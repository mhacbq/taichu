<template>
  <Teleport to="body">
    <div v-if="show" class="confetti-container">
      <div
        v-for="(particle, index) in particles"
        :key="index"
        class="confetti"
        :style="getParticleStyle(particle)"
      >
        {{ particle.emoji || '' }}
      </div>
    </div>
  </Teleport>
</template>

<script>
import { ref, computed } from 'vue'

export default {
  name: 'ConfettiEffect',
  props: {
    count: {
      type: Number,
      default: 50
    },
    duration: {
      type: Number,
      default: 3000
    },
    emojis: {
      type: Array,
      default: () => ['🎉', '✨', '🎊', '💫', '⭐', '🌟']
    },
    colors: {
      type: Array,
      default: () => ['#ff6b6b', '#4ecdc4', '#45b7d1', '#f9ca24', '#f0932b', '#eb4d4b', '#6c5ce7', '#00b894']
    }
  },
  setup(props) {
    const show = ref(false)
    
    const particles = computed(() => {
      return Array.from({ length: props.count }, (_, i) => ({
        id: i,
        x: Math.random() * 100,
        delay: Math.random() * 0.5,
        duration: 2 + Math.random() * 2,
        size: 10 + Math.random() * 20,
        rotation: Math.random() * 360,
        emoji: props.emojis[Math.floor(Math.random() * props.emojis.length)],
        color: props.colors[Math.floor(Math.random() * props.colors.length)],
        type: Math.random() > 0.5 ? 'emoji' : 'shape'
      }))
    })
    
    const getParticleStyle = (particle) => {
      const shapes = ['circle', 'square', 'triangle']
      const shape = shapes[Math.floor(Math.random() * shapes.length)]
      
      return {
        left: `${particle.x}%`,
        animationDelay: `${particle.delay}s`,
        animationDuration: `${particle.duration}s`,
        fontSize: `${particle.size}px`,
        '--rotation': `${particle.rotation}deg`,
        '--color': particle.color,
        '--shape': shape
      }
    }
    
    const explode = () => {
      show.value = true
      setTimeout(() => {
        show.value = false
      }, props.duration)
    }
    
    return {
      show,
      particles,
      getParticleStyle,
      explode
    }
  }
}
</script>

<style scoped>
.confetti-container {
  position: fixed;
  inset: 0;
  pointer-events: none;
  z-index: 9999;
  overflow: hidden;
}

.confetti {
  position: absolute;
  top: -20px;
  animation: confetti-fall linear forwards;
  will-change: transform;
}

@keyframes confetti-fall {
  0% {
    transform: translateY(0) rotate(0deg) scale(1);
    opacity: 1;
  }
  25% {
    transform: translateY(25vh) rotate(calc(var(--rotation) * 0.5)) scale(1.1);
  }
  50% {
    transform: translateY(50vh) rotate(var(--rotation)) scale(1);
    opacity: 0.9;
  }
  75% {
    transform: translateY(75vh) rotate(calc(var(--rotation) * 1.5)) scale(0.9);
    opacity: 0.6;
  }
  100% {
    transform: translateY(100vh) rotate(calc(var(--rotation) * 2)) scale(0.5);
    opacity: 0;
  }
}
</style>
