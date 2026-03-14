<template>
  <el-dialog
    v-model="visible"
    title="欢迎使用太初命理"
    width="500px"
    :show-close="false"
    :close-on-click-modal="false"
    class="guide-dialog"
  >
    <div class="guide-content">
      <!-- 步骤指示器 -->
      <div class="step-indicator">
        <div 
          v-for="step in totalSteps" 
          :key="step"
          class="step-dot"
          :class="{ active: currentStep >= step }"
        />
      </div>

      <!-- 步骤内容 -->
      <div class="step-content">
        <template v-if="currentStep === 1">
          <div class="step-illustration">☯</div>
          <h3>探索命运的奥秘</h3>
          <p>太初命理是一款结合传统命理学与AI技术的智能分析平台，为您提供专业的八字排盘、塔罗占卜、每日运势等服务。</p>
          <div class="feature-preview">
            <div class="preview-item">
              <span class="icon">📅</span>
              <span>八字排盘</span>
            </div>
            <div class="preview-item">
              <span class="icon">🎴</span>
              <span>塔罗占卜</span>
            </div>
            <div class="preview-item">
              <span class="icon">🌟</span>
              <span>每日运势</span>
            </div>
          </div>
        </template>

        <template v-if="currentStep === 2">
          <div class="step-illustration">💎</div>
          <h3>积分系统说明</h3>
          <p>使用服务需要消耗积分：</p>
          <ul class="points-list">
            <li>八字排盘：10 积分/次</li>
            <li>塔罗占卜：5 积分/次</li>
            <li>每日运势：免费查看</li>
          </ul>
          <div class="bonus-info">
            <span class="bonus-tag">🎁 新用户福利</span>
            <p>注册即送 100 积分新手礼包！</p>
          </div>
        </template>

        <template v-if="currentStep === 3">
          <div class="step-illustration">🔐</div>
          <h3>开始体验</h3>
          <p>登录后即可体验全部功能。您的个人信息将被严格保护，仅用于生成个性化的命理分析。</p>
          <div class="start-actions">
            <p class="tip">💡 建议首次使用先进行八字排盘，获取最准确的个人运势分析</p>
          </div>
        </template>
      </div>
    </div>

    <template #footer>
      <div class="dialog-footer">
        <el-button v-if="currentStep > 1" @click="prevStep">上一步</el-button>
        <el-button v-if="currentStep < totalSteps" type="primary" @click="nextStep">
          下一步
        </el-button>
        <el-button v-else type="primary" @click="finish">
          立即开始
        </el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const visible = ref(false)
const currentStep = ref(1)
const totalSteps = 3

const nextStep = () => {
  if (currentStep.value < totalSteps) {
    currentStep.value++
  }
}

const prevStep = () => {
  if (currentStep.value > 1) {
    currentStep.value--
  }
}

const finish = () => {
  localStorage.setItem('guideShown', 'true')
  visible.value = false
  // 引导用户去登录
  const token = localStorage.getItem('token')
  if (!token) {
    router.push('/login')
  }
}

onMounted(() => {
  // 检查是否已显示过引导
  const guideShown = localStorage.getItem('guideShown')
  const token = localStorage.getItem('token')
  
  // 首次访问或未登录用户显示引导
  if (!guideShown && !token) {
    setTimeout(() => {
      visible.value = true
    }, 1000)
  }
})
</script>

<style scoped>
.guide-dialog :deep(.el-dialog__header) {
  text-align: center;
  padding-bottom: 10px;
}

.guide-dialog :deep(.el-dialog__title) {
  font-size: 20px;
  font-weight: bold;
  color: #fff;
}

.guide-dialog :deep(.el-dialog__body) {
  padding: 20px 30px;
}

.guide-dialog :deep(.el-dialog__footer) {
  text-align: center;
  padding: 20px 30px 30px;
}

.step-indicator {
  display: flex;
  justify-content: center;
  gap: 10px;
  margin-bottom: 30px;
}

.step-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.2);
  transition: all 0.3s ease;
}

.step-dot.active {
  background: #e94560;
  width: 30px;
  border-radius: 5px;
}

.step-content {
  text-align: center;
}

.step-illustration {
  font-size: 60px;
  margin-bottom: 20px;
}

.step-content h3 {
  font-size: 22px;
  color: #fff;
  margin-bottom: 15px;
}

.step-content p {
  color: rgba(255, 255, 255, 0.7);
  line-height: 1.8;
  margin-bottom: 20px;
}

.feature-preview {
  display: flex;
  justify-content: center;
  gap: 30px;
  margin-top: 25px;
}

.preview-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  color: rgba(255, 255, 255, 0.8);
}

.preview-item .icon {
  font-size: 28px;
}

.points-list {
  list-style: none;
  text-align: left;
  display: inline-block;
  margin: 20px 0;
}

.points-list li {
  color: rgba(255, 255, 255, 0.8);
  padding: 10px 0;
  padding-left: 25px;
  position: relative;
}

.points-list li::before {
  content: '💎';
  position: absolute;
  left: 0;
}

.bonus-info {
  background: linear-gradient(135deg, rgba(233, 69, 96, 0.2), rgba(255, 107, 107, 0.2));
  border: 1px solid rgba(233, 69, 96, 0.3);
  border-radius: 10px;
  padding: 15px;
  margin-top: 20px;
}

.bonus-tag {
  background: linear-gradient(135deg, #e94560, #ff6b6b);
  padding: 5px 12px;
  border-radius: 15px;
  font-size: 12px;
  color: #fff;
}

.bonus-info p {
  margin: 10px 0 0;
  color: #fff;
}

.start-actions {
  margin-top: 20px;
}

.tip {
  background: rgba(103, 194, 58, 0.1);
  border: 1px solid rgba(103, 194, 58, 0.3);
  padding: 12px;
  border-radius: 8px;
  font-size: 14px;
}

.dialog-footer {
  display: flex;
  justify-content: center;
  gap: 15px;
}
</style>
