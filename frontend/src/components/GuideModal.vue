<template>
  <el-dialog
    v-model="visible"
    :title="currentStep === 1 ? '欢迎来到太初命理 ✨' : '新手指引'"
    width="520px"
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
        <!-- 第1步：暖心欢迎 -->
        <template v-if="currentStep === 1">
          <div class="step-illustration welcome-anim">🌸</div>
          <h3>在迷茫中寻找方向</h3>
          <p class="warm-text">生活有时会让人感到困惑和迷茫，这很正常。</p>
          <p class="warm-text">太初命理不是预测命运的"神谕"，而是帮你从另一个角度认识自己、理解当下的工具。</p>
          <div class="comfort-cards">
            <div class="comfort-item">
              <span class="comfort-icon">💝</span>
              <span>你并不孤单</span>
            </div>
            <div class="comfort-item">
              <span class="comfort-icon">🌱</span>
              <span>迷茫是成长的开始</span>
            </div>
            <div class="comfort-item">
              <span class="comfort-icon">✨</span>
              <span>答案在你心中</span>
            </div>
          </div>
          <p class="sub-text">让我们一起探索，找到属于你的那份指引。</p>
        </template>

        <!-- 第2步：功能介绍 -->
        <template v-if="currentStep === 2">
          <div class="step-illustration">☯</div>
          <h3>我们能为你做什么</h3>
          <div class="feature-list">
            <div class="feature-item">
              <div class="feature-icon-bg">📅</div>
              <div class="feature-info">
                <h4>八字排盘</h4>
                <p>了解自己的性格特点、优势与挑战，找到适合的发展方向</p>
                <span class="feature-tag">首次免费</span>
              </div>
            </div>
            <div class="feature-item">
              <div class="feature-icon-bg">🎴</div>
              <div class="feature-info">
                <h4>塔罗占卜</h4>
                <p>针对具体困惑获得指引，工作、感情、人际关系都能找到答案</p>
                <span class="feature-tag">含问题模板</span>
              </div>
            </div>
            <div class="feature-item">
              <div class="feature-icon-bg">🌟</div>
              <div class="feature-info">
                <h4>每日运势</h4>
                <p>基于你的八字生成个性化建议，趋吉避凶，把握每一天</p>
                <span class="feature-tag free">完全免费</span>
              </div>
            </div>
          </div>
        </template>

        <!-- 第3步：积分说明 -->
        <template v-if="currentStep === 3">
          <div class="step-illustration">💎</div>
          <h3>关于积分</h3>
          <p class="points-intro">为了提供更优质的服务，部分功能需要消耗积分：</p>
          <div class="points-table">
            <div class="points-row">
              <span class="service-name">八字排盘</span>
              <span class="points-cost">
                <span class="original">10</span>
                <span class="free-tag">首次免费</span>
              </span>
            </div>
            <div class="points-row">
              <span class="service-name">塔罗占卜</span>
              <span class="points-cost">5 积分</span>
            </div>
            <div class="points-row">
              <span class="service-name">每日运势</span>
              <span class="points-cost free">免费</span>
            </div>
          </div>
          <div class="earn-points">
            <h4>💡 如何获取积分？</h4>
            <ul>
              <li>注册即送 <strong>100积分</strong> 新手礼包</li>
              <li>每日签到领积分</li>
              <li>邀请好友双方都得积分</li>
            </ul>
          </div>
        </template>

        <!-- 第4步：开始体验 -->
        <template v-if="currentStep === 4">
          <div class="step-illustration">🚀</div>
          <h3>准备好了吗？</h3>
          <p class="warm-text">记住：命理分析仅供参考，真正的改变来自于你的行动。</p>
          <div class="start-tips">
            <div class="tip-item">
              <span class="tip-icon">🎯</span>
              <p><strong>建议一：</strong>首次使用先从八字排盘开始，了解自己的基本命格</p>
            </div>
            <div class="tip-item">
              <span class="tip-icon">💭</span>
              <p><strong>建议二：</strong>有具体困惑时，使用塔罗占卜获得针对性指引</p>
            </div>
            <div class="tip-item">
              <span class="tip-icon">🌅</span>
              <p><strong>建议三：</strong>每天早上看看今日运势，为一天做好准备</p>
            </div>
          </div>
          <div class="encourage-box">
            <p>🌟 "每一个迷茫的时刻，都是重新认识自己的机会"</p>
          </div>
        </template>
      </div>
    </div>

    <template #footer>
      <div class="dialog-footer">
        <el-button v-if="currentStep > 1" @click="prevStep" class="footer-btn">
          上一步
        </el-button>
        <el-button 
          v-if="currentStep < totalSteps" 
          type="primary" 
          @click="nextStep"
          class="footer-btn primary"
        >
          {{ currentStep === 1 ? '告诉我更多' : '下一步' }}
        </el-button>
        <el-button 
          v-else 
          type="primary" 
          @click="finish"
          class="footer-btn primary"
        >
          开始探索 ✨
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
const totalSteps = 4

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
    }, 800)
  }
})
</script>

<style scoped>
.guide-dialog :deep(.el-dialog__header) {
  text-align: center;
  padding-bottom: 10px;
}

.guide-dialog :deep(.el-dialog__title) {
  font-size: 22px;
  font-weight: bold;
  color: #fff;
  background: linear-gradient(135deg, #fff 0%, #e94560 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
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
  background: linear-gradient(135deg, #e94560, #ff6b6b);
  width: 30px;
  border-radius: 5px;
}

.step-content {
  text-align: center;
}

.step-illustration {
  font-size: 60px;
  margin-bottom: 20px;
  animation: float 3s ease-in-out infinite;
}

@keyframes float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-10px); }
}

.welcome-anim {
  animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.1); }
}

.step-content h3 {
  font-size: 24px;
  color: #fff;
  margin-bottom: 20px;
  font-weight: 600;
}

.warm-text {
  color: rgba(255, 255, 255, 0.85) !important;
  line-height: 1.8;
  margin-bottom: 15px;
  font-size: 15px;
}

.sub-text {
  color: rgba(184, 134, 11, 0.9) !important;
  margin-top: 20px;
  font-size: 14px;
}

/* 暖心卡片 */
.comfort-cards {
  display: flex;
  justify-content: center;
  gap: 15px;
  margin: 25px 0;
  flex-wrap: wrap;
}

.comfort-item {
  background: linear-gradient(135deg, rgba(184, 134, 11, 0.15), rgba(218, 165, 32, 0.1));
  border: 1px solid rgba(184, 134, 11, 0.2);
  border-radius: 12px;
  padding: 15px 20px;
  display: flex;
  align-items: center;
  gap: 8px;
  color: rgba(255, 255, 255, 0.9);
  font-size: 14px;
  transition: all 0.3s ease;
}

.comfort-item:hover {
  transform: translateY(-3px);
  border-color: rgba(184, 134, 11, 0.4);
}

.comfort-icon {
  font-size: 20px;
}

/* 功能列表 */
.feature-list {
  display: flex;
  flex-direction: column;
  gap: 15px;
  margin-top: 20px;
}

.feature-item {
  display: flex;
  align-items: flex-start;
  gap: 15px;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 12px;
  padding: 18px;
  text-align: left;
  border: 1px solid rgba(255, 255, 255, 0.1);
  transition: all 0.3s ease;
}

.feature-item:hover {
  background: rgba(255, 255, 255, 0.08);
  border-color: rgba(184, 134, 11, 0.3);
}

.feature-icon-bg {
  width: 45px;
  height: 45px;
  background: linear-gradient(135deg, rgba(184, 134, 11, 0.3), rgba(218, 165, 32, 0.3));
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  flex-shrink: 0;
}

.feature-info h4 {
  color: #fff;
  font-size: 16px;
  margin-bottom: 5px;
}

.feature-info p {
  color: rgba(255, 255, 255, 0.6);
  font-size: 13px;
  line-height: 1.6;
  margin-bottom: 8px;
}

.feature-tag {
  display: inline-block;
  background: rgba(184, 134, 11, 0.2);
  color: #b8860b;
  padding: 2px 10px;
  border-radius: 10px;
  font-size: 11px;
}

.feature-tag.free {
  background: rgba(103, 194, 58, 0.2);
  color: #67c23a;
}

/* 积分表格 */
.points-intro {
  color: rgba(255, 255, 255, 0.7);
  margin-bottom: 20px;
}

.points-table {
  background: rgba(255, 255, 255, 0.05);
  border-radius: 12px;
  padding: 15px;
  margin-bottom: 20px;
}

.points-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.points-row:last-child {
  border-bottom: none;
}

.service-name {
  color: rgba(255, 255, 255, 0.9);
  font-size: 15px;
}

.points-cost {
  color: #ffd700;
  font-weight: 500;
}

.points-cost .original {
  text-decoration: line-through;
  color: rgba(255, 255, 255, 0.4);
  margin-right: 8px;
}

.free-tag {
  background: linear-gradient(135deg, #67c23a, #85ce61);
  color: #fff;
  padding: 2px 10px;
  border-radius: 10px;
  font-size: 11px;
}

.points-cost.free {
  color: #67c23a;
}

.earn-points {
  background: linear-gradient(135deg, rgba(103, 194, 58, 0.1), rgba(133, 206, 97, 0.1));
  border: 1px solid rgba(103, 194, 58, 0.2);
  border-radius: 12px;
  padding: 18px;
  text-align: left;
}

.earn-points h4 {
  color: #67c23a;
  margin-bottom: 12px;
  font-size: 14px;
}

.earn-points ul {
  list-style: none;
}

.earn-points li {
  color: rgba(255, 255, 255, 0.8);
  padding: 6px 0;
  padding-left: 20px;
  position: relative;
  font-size: 14px;
}

.earn-points li::before {
  content: '✓';
  position: absolute;
  left: 0;
  color: #67c23a;
  font-weight: bold;
}

/* 开始提示 */
.start-tips {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin: 20px 0;
}

.tip-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 10px;
  padding: 15px;
  text-align: left;
}

.tip-icon {
  font-size: 24px;
  flex-shrink: 0;
}

.tip-item p {
  color: rgba(255, 255, 255, 0.8);
  font-size: 14px;
  line-height: 1.6;
  margin: 0;
}

.tip-item strong {
  color: #b8860b;
}

.encourage-box {
  background: linear-gradient(135deg, rgba(184, 134, 11, 0.15), rgba(218, 165, 32, 0.15));
  border: 1px solid rgba(184, 134, 11, 0.3);
  border-radius: 12px;
  padding: 18px;
  margin-top: 20px;
}

.encourage-box p {
  color: rgba(255, 255, 255, 0.9);
  font-size: 14px;
  margin: 0;
  font-style: italic;
}

/* 底部按钮 */
.dialog-footer {
  display: flex;
  justify-content: center;
  gap: 15px;
}

.footer-btn {
  min-width: 120px;
  padding: 12px 24px;
  font-size: 15px;
}

.footer-btn.primary {
  background: linear-gradient(135deg, #b8860b, #daa520);
  border: none;
}

.footer-btn.primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 20px rgba(184, 134, 11, 0.4);
}

@media (max-width: 480px) {
  .comfort-cards {
    flex-direction: column;
  }
  
  .comfort-item {
    justify-content: center;
  }
  
  .feature-item {
    flex-direction: column;
    text-align: center;
  }
  
  .feature-icon-bg {
    margin: 0 auto;
  }
}
</style>
