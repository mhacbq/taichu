<template>
  <div class="ai-interpretation">
    <!-- 头部 -->
    <div class="ai-header">
      <div class="ai-avatar">
        <div class="avatar-ring"></div>
        <el-icon :size="28" color="#667eea"><Magic /></el-icon>
      </div>
      <div class="ai-info">
        <h3 class="ai-name">AI命理大师</h3>
        <p class="ai-desc">基于千年命理智慧 + 现代AI技术</p>
      </div>
      <div class="ai-badge">
        <el-icon><StarFilled /></el-icon>
        <span>专业版</span>
      </div>
    </div>
    
    <!-- 解读内容 -->
    <div class="interpretation-content">
      <div v-if="loading" class="thinking-section">
        <div class="thinking-dots">
          <span></span>
          <span></span>
          <span></span>
        </div>
        <p class="thinking-text">{{ thinkingText }}</p>
        <div class="thinking-progress">
          <div class="progress-bar" :style="{ width: `${progress}%` }"></div>
        </div>
      </div>
      
      <template v-else>
        <div class="interpretation-section" v-for="(section, index) in sections" :key="index">
          <div class="section-header">
            <div class="section-icon" :style="{ background: section.color }">
              <el-icon :size="18" color="white"><component :is="section.icon" /></el-icon>
            </div>
            <h4 class="section-title">{{ section.title }}</h4>
          </div>
          <div class="section-content">
            <p v-for="(para, pIndex) in section.content" :key="pIndex">
              {{ para }}
            </p>
          </div>
          <div class="section-tags" v-if="section.tags">
            <span 
              v-for="(tag, tIndex) in section.tags" 
              :key="tIndex"
              class="tag"
              :class="tag.type"
            >
              {{ tag.text }}
            </span>
          </div>
        </div>
        
        <!-- 建议卡片 -->
        <div class="advice-card">
          <div class="advice-icon">
            <el-icon :size="24" color="#10b981"><CircleCheck /></el-icon>
          </div>
          <div class="advice-content">
            <h4>大师建议</h4>
            <p>{{ advice }}</p>
          </div>
        </div>
      </template>
    </div>
    
    <!-- 底部操作 -->
    <div class="ai-actions">
      <button class="action-btn" @click="regenerate">
        <el-icon><Refresh /></el-icon>
        <span>重新解读</span>
      </button>
      <button class="action-btn primary" @click="shareResult">
        <el-icon><Share /></el-icon>
        <span>分享解读</span>
      </button>
      <button class="action-btn" @click="saveResult">
        <el-icon><Star /></el-icon>
        <span>收藏</span>
      </button>
    </div>
    
    <!-- 升级提示 -->
    <div v-if="!isVip" class="upgrade-banner">
      <div class="upgrade-content">
        <el-icon :size="20" color="#f59e0b"><Lock /></el-icon>
        <span>开通VIP解锁完整AI解读</span>
      </div>
      <el-button type="primary" size="small" @click="upgrade">
        立即开通
      </el-button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import {
  MagicStick,
  StarFilled,
  Star,
  Share,
  Refresh,
  CircleCheck,
  Lock,
  Sunny,
  Moon,
  Money,
  UserFilled,
  Briefcase,
  HeartFilled
} from '@element-plus/icons-vue'
import { useAnalytics } from '@/utils/analytics'

const props = defineProps({
  type: { type: String, default: 'bazi' }, // bazi, tarot, fortune
  data: { type: Object, default: () => ({}) },
  isVip: { type: Boolean, default: false }
})

const emit = defineEmits(['regenerate', 'share', 'save', 'upgrade'])
const analytics = useAnalytics()

const loading = ref(true)
const progress = ref(0)
const thinkingText = ref('正在分析命理数据...')

const thinkingTexts = [
  '正在分析命理数据...',
  '结合五行生克关系...',
  '参考千年古籍记载...',
  '生成专属解读方案...',
  '即将为您呈现...'
]

// 解读段落
const sections = ref([])
const advice = ref('')

// 模拟加载过程
const simulateLoading = () => {
  loading.value = true
  progress.value = 0
  let step = 0
  
  const timer = setInterval(() => {
    progress.value += Math.random() * 15 + 5
    
    if (progress.value >= (step + 1) * 20 && step < thinkingTexts.length - 1) {
      step++
      thinkingText.value = thinkingTexts[step]
    }
    
    if (progress.value >= 100) {
      clearInterval(timer)
      loading.value = false
      generateContent()
      
      analytics.track('ai_interpretation_complete', {
        type: props.type,
        duration: Date.now()
      })
    }
  }, 300)
}

// 生成解读内容
const generateContent = () => {
  if (props.type === 'bazi') {
    sections.value = [
      {
        title: '性格特质',
        icon: 'UserFilled',
        color: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
        content: [
          '您的八字显示为「正官格」，为人正直守信，处事严谨认真。日主坐印，内心细腻敏感，具有较强的学习能力和思考深度。',
          '五行木旺，性格开朗乐观，富有创造力和想象力。但需注意有时过于理想化，建议脚踏实地，循序渐进。'
        ],
        tags: [
          { text: '正直守信', type: 'positive' },
          { text: '创造力强', type: 'positive' },
          { text: '需防急躁', type: 'warning' }
        ]
      },
      {
        title: '事业财运',
        icon: 'Briefcase',
        color: 'linear-gradient(135deg, #11998e 0%, #38ef7d 100%)',
        content: [
          '财星透干，财运亨通，特别适合从事与创意、文化、教育相关的行业。30岁后财运逐渐上升，40岁达到巅峰。',
          '2024年甲辰年，事业上会有新的机遇，建议把握时机，但需谨防小人暗算，重大决策需三思而后行。'
        ],
        tags: [
          { text: '财运亨通', type: 'positive' },
          { text: '文化行业有利', type: 'info' }
        ]
      },
      {
        title: '感情婚姻',
        icon: 'HeartFilled',
        color: 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
        content: [
          '桃花位在东南，今年有望遇到心仪对象。已婚者夫妻感情和睦，但需注意沟通交流，避免因小事产生误会。',
          '建议多参加社交活动，扩大交友圈，良缘就在不经意间出现。'
        ],
        tags: [
          { text: '桃花运旺', type: 'positive' },
          { text: '东南方有利', type: 'info' }
        ]
      }
    ]
    advice.value = '今年宜稳中求进，把握事业机遇的同时，也要注重身心健康。建议每月初一、十五行善积德，可增强运势。'
  } else if (props.type === 'tarot') {
    sections.value = [
      {
        title: '牌面解析',
        icon: 'Magic',
        color: 'linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%)',
        content: [
          '您抽到的「太阳」正位，象征光明、成功与喜悦。这是一张极具正能量的牌，预示着您即将迎来人生的高光时刻。',
          '牌面中的孩童骑在白马上，象征纯真与自信，提醒您在追求目标时保持初心，不被外界干扰。'
        ],
        tags: [
          { text: '大吉', type: 'positive' },
          { text: '光明前景', type: 'positive' }
        ]
      },
      {
        title: '现状分析',
        icon: 'Sunny',
        color: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
        content: [
          '目前您正处于能量饱满的状态，过去的努力即将开花结果。您的心态积极乐观，这种状态会持续吸引更多好运。',
          '需要注意的是，成功时不要骄傲自满，保持谦逊才能持续获得支持。'
        ]
      },
      {
        title: '未来指引',
        icon: 'Moon',
        color: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
        content: [
          '未来三个月是行动的最佳时机，您的计划会得到顺利推进。财运方面会有意外之喜，可能是投资回报或额外收入。',
          '感情方面，单身者有望遇到阳光开朗的对象；有伴侣者关系将更加甜蜜。'
        ]
      }
    ]
    advice.value = '太阳牌提醒您：保持乐观心态，相信自己。现在播下的种子将在不久的将来收获丰硕果实。'
  } else {
    // 通用运势
    sections.value = [
      {
        title: '今日总运',
        icon: 'StarFilled',
        color: 'linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%)',
        content: [
          '今日运势指数85分，整体呈上升趋势。贵人星高照，适合与人合作洽谈，容易获得他人支持和认可。',
          '今日吉时在上午9点至11点，适合处理重要事务。幸运颜色为蓝色和白色。'
        ],
        tags: [
          { text: '贵人相助', type: 'positive' },
          { text: '合作顺利', type: 'positive' }
        ]
      },
      {
        title: '财运分析',
        icon: 'Money',
        color: 'linear-gradient(135deg, #10b981 0%, #34d399 100%)',
        content: [
          '财运平稳，正财收入稳定，偏财运一般，不宜进行高风险投资。今日适合理财规划，可考虑稳健型理财产品。',
          '支出方面需节制，避免冲动消费。'
        ]
      }
    ]
    advice.value = '把握今日的贵人运，主动出击，会有意想不到的收获。'
  }
}

// 重新生成
const regenerate = () => {
  simulateLoading()
  emit('regenerate')
  
  analytics.trackButtonClick('ai_regenerate', { type: props.type })
}

// 分享结果
const shareResult = () => {
  emit('share')
  
  analytics.trackButtonClick('ai_share', { type: props.type })
}

// 收藏结果
const saveResult = () => {
  ElMessage.success('已收藏到个人中心')
  emit('save')
  
  analytics.trackButtonClick('ai_save', { type: props.type })
}

// 升级VIP
const upgrade = () => {
  emit('upgrade')
  
  analytics.trackButtonClick('ai_upgrade_vip')
}

onMounted(() => {
  simulateLoading()
  
  analytics.track('ai_interpretation_start', { type: props.type })
})
</script>

<style scoped>
.ai-interpretation {
  background: white;
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
}

.ai-header {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 20px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.ai-avatar {
  position: relative;
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
}

.avatar-ring {
  position: absolute;
  inset: -4px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 2s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.ai-info {
  flex: 1;
}

.ai-name {
  font-size: 18px;
  font-weight: 600;
  margin-bottom: 4px;
}

.ai-desc {
  font-size: 13px;
  opacity: 0.9;
}

.ai-badge {
  display: flex;
  align-items: center;
  gap: 4px;
  background: rgba(255, 255, 255, 0.2);
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 12px;
}

.interpretation-content {
  padding: 24px;
  min-height: 300px;
}

.thinking-section {
  text-align: center;
  padding: 60px 20px;
}

.thinking-dots {
  display: flex;
  justify-content: center;
  gap: 8px;
  margin-bottom: 20px;
}

.thinking-dots span {
  width: 12px;
  height: 12px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 50%;
  animation: bounce 1.4s ease-in-out infinite both;
}

.thinking-dots span:nth-child(1) { animation-delay: -0.32s; }
.thinking-dots span:nth-child(2) { animation-delay: -0.16s; }

@keyframes bounce {
  0%, 80%, 100% { transform: scale(0); }
  40% { transform: scale(1); }
}

.thinking-text {
  font-size: 15px;
  color: #666;
  margin-bottom: 24px;
}

.thinking-progress {
  width: 200px;
  height: 4px;
  background: rgba(0, 0, 0, 0.05);
  border-radius: 2px;
  margin: 0 auto;
  overflow: hidden;
}

.progress-bar {
  height: 100%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 2px;
  transition: width 0.3s ease;
}

.interpretation-section {
  margin-bottom: 24px;
  padding-bottom: 24px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.interpretation-section:last-of-type {
  border-bottom: none;
}

.section-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
}

.section-icon {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.section-title {
  font-size: 17px;
  font-weight: 600;
  color: #333;
}

.section-content {
  color: #555;
  line-height: 1.8;
  font-size: 14px;
}

.section-content p {
  margin-bottom: 10px;
}

.section-content p:last-child {
  margin-bottom: 0;
}

.section-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 12px;
}

.tag {
  padding: 4px 10px;
  border-radius: 12px;
  font-size: 12px;
}

.tag.positive {
  background: rgba(16, 185, 129, 0.1);
  color: #10b981;
}

.tag.warning {
  background: rgba(245, 158, 11, 0.1);
  color: #f59e0b;
}

.tag.info {
  background: rgba(102, 126, 234, 0.1);
  color: #667eea;
}

.advice-card {
  display: flex;
  gap: 16px;
  padding: 20px;
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.05) 0%, rgba(52, 211, 153, 0.05) 100%);
  border-radius: 16px;
  border: 1px solid rgba(16, 185, 129, 0.1);
}

.advice-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: rgba(16, 185, 129, 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.advice-content h4 {
  font-size: 15px;
  font-weight: 600;
  color: #333;
  margin-bottom: 6px;
}

.advice-content p {
  font-size: 14px;
  color: #555;
  line-height: 1.6;
}

.ai-actions {
  display: flex;
  gap: 12px;
  padding: 16px 24px;
  border-top: 1px solid rgba(0, 0, 0, 0.05);
}

.action-btn {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 12px;
  border-radius: 12px;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.3s;
  border: 1px solid rgba(0, 0, 0, 0.1);
  background: white;
  color: #666;
}

.action-btn:hover {
  background: #f5f5f5;
}

.action-btn.primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
}

.action-btn.primary:hover {
  opacity: 0.9;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.upgrade-banner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 24px;
  background: linear-gradient(135deg, rgba(245, 158, 11, 0.1) 0%, rgba(251, 191, 36, 0.1) 100%);
  border-top: 1px solid rgba(245, 158, 11, 0.1);
}

.upgrade-content {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  color: #b45309;
}
</style>
