<script setup>
import { ref } from 'vue'

const about = ref({
  title: '关于我们',
  description: '每个人在人生的不同阶段都会遇到困惑：事业发展该选什么方向？感情问题如何抉择？当下的迷茫是不是只有自己一个人在经历？我们帮你把这些困惑拆开来看，找到根源，说清能帮你解决什么问题。',
  values: [
    {
      icon: 'Check',
      title: '看清问题本质',
      description: '不给你模糊的玄学，而是清晰的分析：你的困惑从何而来，根源是什么'
    },
    {
      icon: 'Shield',
      title: '提供行动建议',
      description: '分析完之后，给你可执行的建议：当下该做什么，下一步怎么走'
    },
    {
      icon: 'Star',
      title: '尊重你的选择',
      description: '我们只提供参考和视角，最终的决定权永远在你手里'
    }
  ]
})
</script>

<template>
  <section class="about-section">
    <div class="about-container">
      <div class="section-header">
        <h2 class="section-title">{{ about.title }}</h2>
        <p class="section-subtitle">{{ about.description }}</p>
      </div>

      <div class="about-values">
        <article 
          v-for="(value, index) in about.values"
          :key="index"
          class="about-value card-hover"
        >
          <el-icon class="about-value-icon" :size="40">
            <component :is="value.icon" />
          </el-icon>
          <h3>{{ value.title }}</h3>
          <p>{{ value.description }}</p>
        </article>
      </div>
    </div>
  </section>
</template>

<style scoped>
.about-section {
  padding: 60px 24px;
  background: linear-gradient(180deg, #fffbf5 0%, #ffffff 100%);
}

.about-container {
  max-width: 1200px;
  margin: 0 auto;
}

.section-header {
  text-align: center;
  margin-bottom: 48px;
}

.section-title {
  font-size: clamp(28px, 4vw, 36px);
  font-weight: var(--weight-bold);
  color: #5e4318;
  margin-bottom: 12px;
  letter-spacing: 0.04em;
}

.section-subtitle {
  font-size: clamp(14px, 2vw, 16px);
  color: #6b6254;
  line-height: 1.7;
  max-width: 800px;
  margin: 0 auto;
}

.about-values {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 28px;
}

.about-value {
  padding: 36px 28px;
  background: rgba(255, 255, 255, 0.96);
  border: 1px solid rgba(227, 184, 104, 0.28);
  border-radius: var(--radius-xl);
  text-align: center;
  transition: all 0.3s ease;
  box-shadow: 0 12px 32px rgba(145, 103, 34, 0.08);
}

.about-value:hover {
  transform: translateY(-5px);
  border-color: rgba(210, 154, 64, 0.42);
  box-shadow: 0 16px 40px rgba(145, 103, 34, 0.14);
}

.about-value-icon {
  color: #D4AF37;
  margin-bottom: 20px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.about-value h3 {
  font-size: var(--font-h4);
  font-weight: var(--weight-bold);
  color: #5e4318;
  margin-bottom: 12px;
}

.about-value p {
  font-size: var(--font-body);
  color: #6b6254;
  line-height: 1.7;
  margin: 0;
}

@media (max-width: 768px) {
  .about-section {
    padding: 40px 16px;
  }
  
  .section-header {
    margin-bottom: 32px;
  }
  
  .about-values {
    grid-template-columns: 1fr;
  }
  
  .about-value {
    padding: 28px 20px;
  }
}
</style>
