<script setup lang="ts">
import HeroStatusCard from './HeroStatusCard.vue'

interface Props {
  isLoggedIn: boolean;
  userPoints: number | null;
  formattedUserPoints: string;
  baziOfferState: string;
  registerIntentRoute: Record<string, any> | null;
  heroPointsDetail: string;
  heroPointsCardNote: string;
}

const props = withDefaults(defineProps<Props>(), {
  userPoints: null,
  registerIntentRoute: null,
  heroPointsDetail: '',
  heroPointsCardNote: ''
});
</script>

<template>
  <section class="hero-section">
    <div class="hero-container">
      <div class="hero-content">
        <h1 class="hero-title">
          太初命理<br>
          <span>先理解自己 再做决定</span>
        </h1>
        
        <p class="hero-subtitle">
          把人生阶段、当下困惑和下一步行动拆开来看，先告诉你能做什么，再决定要不要深入探索
        </p>

        <div class="hero-status">
          <HeroStatusCard
            :is-logged-in="isLoggedIn"
            :user-points="userPoints"
            :formatted-user-points="formattedUserPoints"
            :bazi-offer-state="baziOfferState"
            :register-intent-route="registerIntentRoute"
            :hero-points-detail="heroPointsDetail"
            :hero-points-card-note="heroPointsCardNote"
          />
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.hero-section {
  position: relative;
  background: radial-gradient(ellipse at top, rgba(255, 247, 229, 0.6) 0%, rgba(255, 255, 255, 0.95) 70%),
              linear-gradient(180deg, #FFFAF0 0%, #FFFFFF 100%);
  padding: 60px 24px 48px;
  min-height: 540px;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.hero-section::before {
  content: '';
  position: absolute;
  top: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 70%;
  height: 60%;
  max-width: 1200px;
  background: radial-gradient(ellipse at center top, rgba(212, 175, 55, 0.08) 0%, transparent 60%);
  pointer-events: none;
}

.hero-container {
  width: 100%;
  max-width: 1000px;
  margin: 0 auto;
}

.hero-content {
  text-align: center;
  margin-bottom: 20px;
  position: relative;
  z-index: 2;
}

.hero-title {
  font-size: clamp(42px, 7vw, 60px);
  font-weight: var(--weight-black);
  line-height: 1.1;
  letter-spacing: var(--tracking-tight);
  margin-bottom: 20px;
  color: #5e4318;
  text-shadow: 
    0 2px 4px rgba(255, 255, 255, 0.8),
    0 0 40px rgba(212, 175, 55, 0.1);
  background: linear-gradient(
    135deg,
    #5e4318 0%,
    #8b6914 50%,
    #5e4318 100%
  );
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.hero-title span {
  font-size: clamp(32px, 5vw, 48px);
  font-weight: var(--weight-medium);
  display: block;
  color: rgba(94, 67, 24, 0.75);
  -webkit-text-fill-color: rgba(94, 67, 24, 0.75);
}

.hero-subtitle {
  font-size: clamp(16px, 2vw, 19px);
  color: #6b6254;
  line-height: 1.7;
  max-width: 600px;
  margin: 0 auto 36px;
  font-weight: var(--weight-regular);
}

.hero-status {
  max-width: 720px;
  margin: 0 auto;
  position: relative;
  z-index: 2;
}

@media (max-width: 768px) {
  .hero-section {
    padding: 40px 16px 32px;
    min-height: auto;
  }
  
  .hero-title {
    margin-bottom: 16px;
  }
  
  .hero-subtitle {
    margin-bottom: 28px;
  }
}
</style>
