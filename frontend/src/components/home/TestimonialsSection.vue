<script setup>
import TestimonialCard from './TestimonialCard.vue'

const testimonials = [
  {
    service: '八字命理',
    persona: '职场发展受阻',
    note: '职场困惑期',
    content: '最近工作一直不太顺利，不知道该如何突破。通过八字分析，发现自己今年确实有事业压力，但也指出了下半年会有转机的时间点。现在心里有了方向，也知道该往哪个方向努力了。',
    outcome: '事业有了清晰的方向'
  },
  {
    service: '塔罗占卜',
    persona: '情感迷茫',
    note: '感情困扰',
    content: '和男友的关系到了瓶颈期，不知道是该继续还是放手。塔罗占卜给我很多启示，让我看清了自己内心的真实想法。现在已经做出了选择，感觉整个人都轻松了很多。',
    outcome: '找到了内心答案'
  },
  {
    service: '每日运势',
    persona: '需要提醒',
    note: '日常规划',
    content: '每天早上都会看一下今天的运势，提醒自己注意哪些方面。有一次提前知道当天会有财运，结果真的收到了一笔意外收入。虽然不是每次都准，但至少能让我更从容地面对生活。',
    outcome: '生活更加从容'
  }
]
</script>

<template>
  <section class="testimonials-section">
    <div class="testimonials-container">
      <div class="section-header">
        <h2 class="section-title">用户评价</h2>
        <p class="section-subtitle">听听用户们是如何通过我们的服务找到答案的</p>
      </div>

      <div class="testimonials-grid">
        <TestimonialCard
          v-for="(testimonial, index) in testimonials"
          :key="index"
          :service="testimonial.service"
          :persona="testimonial.persona"
          :note="testimonial.note"
          :content="testimonial.content"
          :outcome="testimonial.outcome"
        />
      </div>
    </div>
  </section>
</template>

<style scoped>
.testimonials-section {
  padding: 60px 24px;
  background: #ffffff;
}

.testimonials-container {
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
  line-height: 1.6;
  max-width: 600px;
  margin: 0 auto;
}

.testimonials-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 24px;
}

@media (max-width: 768px) {
  .testimonials-section {
    padding: 40px 16px;
  }
  
  .section-header {
    margin-bottom: 32px;
  }
  
  .testimonials-grid {
    grid-template-columns: 1fr;
  }
}
</style>
