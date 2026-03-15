<template>
  <div class="star-background">
    <canvas ref="canvasRef" class="star-canvas"></canvas>
    <div class="gradient-overlay"></div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

const canvasRef = ref(null)
let animationId = null
let ctx = null
let stars = []
let mouse = { x: null, y: null }

class Star {
  constructor(canvas) {
    this.canvas = canvas
    this.x = Math.random() * canvas.width
    this.y = Math.random() * canvas.height
    this.size = Math.random() * 2 + 0.5
    this.speedX = (Math.random() - 0.5) * 0.2
    this.speedY = (Math.random() - 0.5) * 0.2
    this.brightness = Math.random()
    this.twinkleSpeed = Math.random() * 0.02 + 0.005
  }

  update() {
    // 基础移动
    this.x += this.speedX
    this.y += this.speedY

    // 鼠标交互
    if (mouse.x !== null && mouse.y !== null) {
      const dx = mouse.x - this.x
      const dy = mouse.y - this.y
      const distance = Math.sqrt(dx * dx + dy * dy)
      
      if (distance < 150) {
        const force = (150 - distance) / 150
        this.x -= dx * force * 0.02
        this.y -= dy * force * 0.02
      }
    }

    // 边界处理
    if (this.x < 0) this.x = this.canvas.width
    if (this.x > this.canvas.width) this.x = 0
    if (this.y < 0) this.y = this.canvas.height
    if (this.y > this.canvas.height) this.y = 0

    // 闪烁效果
    this.brightness += this.twinkleSpeed
    if (this.brightness > 1 || this.brightness < 0.3) {
      this.twinkleSpeed = -this.twinkleSpeed
    }
  }

  draw(ctx) {
    ctx.beginPath()
    ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2)
    ctx.fillStyle = `rgba(255, 255, 255, ${this.brightness})`
    ctx.fill()

    // 发光效果
    if (this.size > 1.5) {
      ctx.beginPath()
      ctx.arc(this.x, this.y, this.size * 2, 0, Math.PI * 2)
      ctx.fillStyle = `rgba(255, 255, 255, ${this.brightness * 0.2})`
      ctx.fill()
    }
  }
}

const initCanvas = () => {
  const canvas = canvasRef.value
  if (!canvas) return

  ctx = canvas.getContext('2d')
  
  const resize = () => {
    canvas.width = window.innerWidth
    canvas.height = window.innerHeight
  }
  
  resize()
  window.addEventListener('resize', resize)

  // 创建星星
  const starCount = Math.min(150, Math.floor((canvas.width * canvas.height) / 10000))
  stars = []
  for (let i = 0; i < starCount; i++) {
    stars.push(new Star(canvas))
  }

  // 鼠标事件
  const handleMouseMove = (e) => {
    mouse.x = e.clientX
    mouse.y = e.clientY
  }

  const handleMouseLeave = () => {
    mouse.x = null
    mouse.y = null
  }

  window.addEventListener('mousemove', handleMouseMove)
  window.addEventListener('mouseleave', handleMouseLeave)

  // 动画循环
  const animate = () => {
    ctx.clearRect(0, 0, canvas.width, canvas.height)

    // 绘制连线
    stars.forEach((star, i) => {
      stars.slice(i + 1).forEach(other => {
        const dx = star.x - other.x
        const dy = star.y - other.y
        const distance = Math.sqrt(dx * dx + dy * dy)

        if (distance < 100) {
          ctx.beginPath()
          ctx.moveTo(star.x, star.y)
          ctx.lineTo(other.x, other.y)
          ctx.strokeStyle = `rgba(255, 255, 255, ${0.15 * (1 - distance / 100)})`
          ctx.lineWidth = 0.5
          ctx.stroke()
        }
      })
    })

    // 更新和绘制星星
    stars.forEach(star => {
      star.update()
      star.draw(ctx)
    })

    animationId = requestAnimationFrame(animate)
  }

  animate()

  return () => {
    window.removeEventListener('resize', resize)
    window.removeEventListener('mousemove', handleMouseMove)
    window.removeEventListener('mouseleave', handleMouseLeave)
  }
}

onMounted(() => {
  const cleanup = initCanvas()
  
  onUnmounted(() => {
    cleanup?.()
    if (animationId) {
      cancelAnimationFrame(animationId)
    }
  })
})
</script>

<style scoped>
.star-background {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: -1;
  background: linear-gradient(135deg, #0a0a1a 0%, #1a1a3e 50%, #16213e 100%);
}

.star-canvas {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
}

.gradient-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: 
    radial-gradient(ellipse at top, rgba(233, 69, 96, 0.08) 0%, transparent 50%),
    radial-gradient(ellipse at bottom right, rgba(107, 70, 193, 0.08) 0%, transparent 50%);
  pointer-events: none;
}
</style>
