<template>
  <el-dialog
    v-model="visible"
    :title="currentStep === 1 ? '欢迎来到太初命理 ✨' : '新手指引'"
    width="520px"
    :show-close="true"
    :close-on-click-modal="canSkip"
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
        <!-- 跳过按钮（仅在第1-3步显示） -->
        <el-button 
          v-if="currentStep < totalSteps" 
          @click="handleSkip"
          class="footer-btn skip"
          text
        >
          跳过引导
        </el-button>
        
        <div class="nav-buttons">
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
      </div>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessageBox } from 'element-plus'

const router = useRouter()
const visible = ref(false)
const currentStep = ref(1)
const totalSteps = 4

// 是否允许跳过（至少看过第一步）
const canSkip = computed(() => currentStep.value > 1)

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

const handleSkip = () => {
  ElMessageBox.confirm(
    '跳过后您可以在"帮助中心"随时查看新手指引',
    '确定跳过新手指引吗？',
    {
      confirmButtonText: '确定跳过',
      cancelButtonText: '继续引导',
      type: 'info',
    }
  ).then(() => {
    // 记录用户跳过了引导
    localStorage.setItem('guideSkipped', 'true')
    localStorage.setItem('guideShown', 'true')
    visible.value = false
    
    // 引导去登录
    const token = localStorage.getItem('token')
    if (!token) {
      router.push('/login')
    }
  }).catch(() => {
    // 用户选择继续，不做任何事
  })
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
/* 继承原GuideModal的样式，添加以下新样式 */

.dialog-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;
}

.footer-btn.skip {
  color: rgba(255, 255, 255, 0.5);
  font-size: 14px;
}

.footer-btn.skip:hover {
  color: rgba(255, 255, 255, 0.8);
}

.nav-buttons {
  display: flex;
  gap: 15px;
  margin-left: auto;
}

@media (max-width: 480px) {
  .dialog-footer {
    flex-direction: column;
    gap: 15px;
  }
  
  .nav-buttons {
    margin-left: 0;
    width: 100%;
  }
  
  .footer-btn {
    flex: 1;
  }
}
</style>
