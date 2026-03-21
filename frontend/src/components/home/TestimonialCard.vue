<script setup>
defineProps({
  service: String,
  persona: String,
  note: String,
  content: String,
  outcome: String
})
</script>

<template>
  <article class="testimonial-card card-hover">
    <div class="testimonial-topline">
      <span class="service-tag">{{ service }}</span>
    </div>
    <div class="testimonial-header">
      <div class="testimonial-scene">
        <span class="testimonial-scene-label">适用场景</span>
        <h4>{{ persona }}</h4>
      </div>
      <span class="testimonial-note">{{ note }}</span>
    </div>
    <p class="testimonial-content">{{ content }}</p>
    <div class="testimonial-footer">
      <span class="testimonial-outcome">{{ outcome }}</span>
      <span class="testimonial-service-copy">适合先从 {{ service }} 入手</span>
    </div>
  </article>
</template>

<style scoped>
.testimonial-card {
  height: 100%;
  padding: 28px;
  display: flex;
  flex-direction: column;
  gap: 16px;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(255, 249, 236, 0.98));
  border: 1px solid rgba(227, 184, 104, 0.28);
  border-radius: var(--radius-card);
  box-shadow: 0 12px 30px rgba(145, 103, 34, 0.1);
  transition: all 0.3s ease;
}

.testimonial-card:hover {
  transform: translateY(-5px);
  border-color: rgba(210, 154, 64, 0.42);
  box-shadow: 0 16px 34px rgba(145, 103, 34, 0.16);
}

.testimonial-topline {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.service-tag {
  min-height: 30px;
  padding: 6px 12px;
  border-radius: 999px;
  border: 1px solid rgba(210, 154, 64, 0.32);
  background: rgba(245, 196, 103, 0.18);
  color: #916018;
  font-size: var(--font-tiny);
  font-weight: var(--weight-semibold);
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.testimonial-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 14px;
}

.testimonial-scene {
  display: grid;
  gap: 6px;
}

.testimonial-scene-label {
  color: var(--text-tertiary);
  font-size: var(--font-tiny);
  letter-spacing: 0.04em;
}

.testimonial-scene h4 {
  margin: 0;
  color: var(--text-primary);
  font-size: var(--font-body);
  font-weight: var(--weight-semibold);
}

.testimonial-content {
  margin: 0;
  color: #5d5143;
  line-height: var(--line-height-base);
  font-size: var(--font-body);
  flex: 1;
}

.testimonial-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.testimonial-outcome {
  color: var(--text-primary);
  font-size: var(--font-caption);
  font-weight: var(--weight-medium);
}

.testimonial-note {
  min-height: 28px;
  padding: 4px 10px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.92);
  border: 1px solid rgba(227, 184, 104, 0.3);
  color: #7a6e5b;
  font-size: var(--font-tiny);
  white-space: nowrap;
}

.testimonial-service-copy {
  color: #a36d1e;
  font-size: var(--font-tiny);
}
</style>
