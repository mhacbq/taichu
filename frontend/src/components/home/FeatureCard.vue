<script setup lang="ts">
type CostType = 'free' | 'normal' | 'none'
type AccessType = 'normal' | 'free' | 'none'
type Size = 'primary' | 'secondary'

interface Props {
  type: string
  symbol: string
  title: string
  description: string
  cost: string
  costType?: CostType
  access: string
  accessType?: AccessType
  link: string
  size?: Size
  coming?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  costType: 'normal',
  accessType: 'normal',
  size: 'primary',
  coming: false
});
</script>

<template>
  <div 
    class="feature-card"
    :class="[
      size === 'secondary' ? 'feature-card--secondary' : '',
      coming ? 'feature-card--coming' : ''
    ]"
    :data-type="type"
  >
    <div v-if="coming" class="coming-badge">即将推出</div>
    
    <div class="feature-icon-wrap" :class="size === 'secondary' ? 'feature-icon-wrap--sm' : ''">
      <span class="feature-symbol">{{ symbol }}</span>
    </div>
    
    <h3>{{ title }}</h3>
    <p>{{ description }}</p>
    
    <div v-if="!coming" class="feature-meta">
      <span v-if="cost" class="feature-cost" :class="costType === 'free' ? 'feature-cost--free' : ''">
        {{ cost }}
      </span>
      <span v-if="access" class="feature-access" :class="accessType === 'free' ? 'feature-access--free' : ''">
        {{ access }}
      </span>
    </div>
    
    <router-link v-if="!coming" :to="link" class="feature-link">
      立即体验 <el-icon><ArrowRight /></el-icon>
    </router-link>
    
    <el-button 
      v-else 
      type="primary" 
      plain 
      size="small" 
      class="feature-link feature-link--sm"
      @click="$emit('reserve', type)"
    >
      感兴趣？点击预约
    </el-button>
  </div>
</template>

<style scoped>
.feature-card {
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(255, 249, 236, 0.95));
  border-radius: var(--radius-xl);
  padding: 32px 24px;
  text-align: center;
  border: 1px solid rgba(227, 184, 104, 0.32);
  box-shadow: 0 12px 32px rgba(145, 103, 34, 0.1);
  transition: all 0.35s cubic-bezier(0.25, 0.46, 0.45, 0.94);
  position: relative;
  overflow: hidden;
  height: 100%;
}

.feature-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 15%;
  right: 15%;
  height: 2px;
  background: linear-gradient(90deg, transparent, rgba(212, 175, 55, 0.4), transparent);
}

.feature-card::after {
  content: '';
  position: absolute;
  inset: 3px;
  border-radius: var(--radius-xl);
  border: 1px dashed rgba(212, 175, 55, 0.25);
  pointer-events: none;
}

.feature-card:hover {
  transform: translateY(-6px);
  border-color: rgba(212, 175, 55, 0.5);
  box-shadow: 
    0 20px 48px rgba(145, 103, 34, 0.16), 
    0 0 0 1px rgba(212, 175, 55, 0.1),
    inset 0 0 40px rgba(212, 175, 55, 0.02);
}

.feature-card--secondary {
  background: rgba(255, 253, 246, 0.9);
  border-color: rgba(227, 184, 104, 0.2);
  padding: 24px 20px;
}

.feature-card--secondary:hover {
  border-color: rgba(212, 175, 55, 0.35);
}

.feature-card--coming {
  background: rgba(248, 248, 248, 0.7);
  border-color: rgba(200, 200, 200, 0.3);
  padding: 24px 20px;
  opacity: 0.75;
}

.feature-card--coming:hover {
  opacity: 0.9;
  border-color: rgba(212, 175, 55, 0.25);
}

.coming-badge {
  display: inline-block;
  font-size: 11px;
  font-weight: 600;
  color: #fff;
  background: linear-gradient(135deg, #B8860B, #D4AF37);
  border-radius: 20px;
  padding: 2px 10px;
  margin-bottom: 8px;
  letter-spacing: 0.5px;
}

.feature-icon-wrap {
  width: 64px;
  height: 64px;
  margin: 0 auto 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 16px;
  background: rgba(184, 134, 11, 0.1);
  border: 1px solid rgba(184, 134, 11, 0.2);
}

.feature-icon-wrap--sm {
  width: 48px;
  height: 48px;
  margin-bottom: 14px;
  border-radius: 12px;
}

.feature-symbol {
  font-size: 28px;
  color: #D4AF37;
  line-height: 1;
  filter: drop-shadow(0 0 6px rgba(212, 175, 55, 0.4));
}

.feature-icon-wrap--sm .feature-symbol {
  font-size: 22px;
}

[data-type="daily"] .feature-icon-wrap { 
  background: rgba(76, 175, 130, 0.1); 
  border-color: rgba(76, 175, 130, 0.2); 
}
[data-type="daily"] .feature-symbol { 
  color: #4CAF82; 
  filter: drop-shadow(0 0 6px rgba(76, 175, 130, 0.4)); 
}
[data-type="tarot"] .feature-icon-wrap { 
  background: rgba(155, 127, 212, 0.1); 
  border-color: rgba(155, 127, 212, 0.2); 
}
[data-type="tarot"] .feature-symbol { 
  color: #A090E0; 
  filter: drop-shadow(0 0 6px rgba(155, 127, 212, 0.4)); 
}

.feature-card h3 {
  font-size: var(--font-h4);
  font-weight: var(--weight-bold);
  margin-bottom: 10px;
  color: #3d3428;
  letter-spacing: 0.04em;
}

.feature-card--secondary h3 {
  font-size: var(--font-body);
  color: #5f5446;
}

.feature-card p {
  color: #6b6254;
  font-size: var(--font-small);
  line-height: 1.7;
  margin-bottom: 16px;
}

.feature-meta {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  margin-bottom: 18px;
  flex-wrap: wrap;
}

.feature-cost {
  display: inline-flex;
  align-items: center;
  padding: 3px 10px;
  border-radius: 999px;
  background: rgba(184, 134, 11, 0.1);
  border: 1px solid rgba(184, 134, 11, 0.18);
  color: #9b6a20;
  font-size: 12px;
  font-weight: 600;
}

.feature-cost--free {
  background: rgba(76, 175, 130, 0.12);
  border-color: rgba(76, 175, 130, 0.2);
  color: rgba(76, 175, 130, 0.9);
}

.feature-access {
  font-size: 12px;
  color: #8a7a68;
}

.feature-access--free {
  color: #2d8a5e;
}

.feature-link {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  min-height: 42px;
  padding: 0 16px;
  border-radius: 12px;
  border: 1px solid rgba(210, 154, 64, 0.3);
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(255, 247, 229, 0.96));
  box-shadow: 0 8px 18px rgba(149, 111, 45, 0.12), inset 0 1px 0 rgba(255, 255, 255, 0.85);
  color: #9b6a20;
  text-decoration: none;
  font-weight: var(--weight-semibold);
  transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease, color 0.25s ease;
}

.feature-link:hover {
  transform: translateY(-2px);
  border-color: rgba(210, 154, 64, 0.42);
  box-shadow: 0 12px 24px rgba(149, 111, 45, 0.18), inset 0 1px 0 rgba(255, 255, 255, 0.95);
  color: #7f5415;
}

.feature-link--sm {
  font-size: 13px;
  color: rgba(212, 175, 55, 0.7);
}
</style>
