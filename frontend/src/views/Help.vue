<template>
  <div class="help-page">
    <div class="container">
      <div class="page-header">
        <BackButton />
        <h1 class="section-title">帮助中心</h1>
      </div>

      <!-- 搜索区域 -->
      <div class="search-section card">
        <h2><el-icon><Search /></el-icon> 有问题？我们来帮您</h2>
        <el-input
          v-model="searchQuery"
          placeholder="搜索问题关键词..."
          size="large"
          clearable
          class="search-input"
        >
          <template #prefix><el-icon><Search /></el-icon></template>
        </el-input>
        <div class="hot-tags">
          <span class="tag-label">热门搜索：</span>
          <el-tag 
            v-for="tag in hotTags" 
            :key="tag"
            class="hot-tag"
            @click="searchQuery = tag"
          >
            {{ tag }}
          </el-tag>
        </div>
      </div>

      <!-- 常见问题分类 -->
      <div class="faq-section">
        <el-collapse v-model="activeNames" class="faq-collapse">
          <el-collapse-item 
            v-for="(category, idx) in filteredCategories" 
            :key="idx"
            :name="idx"
          >
            <template #title>
              <span class="category-title">
                <el-icon v-if="category.icon"><component :is="category.icon" /></el-icon>
                {{ category.title }}
              </span>
            </template>
            <div class="faq-list">
              <div 
                v-for="(item, itemIdx) in category.items" 
                :key="itemIdx"
                class="faq-item"
              >
                <div class="faq-question" @click="toggleQuestion(item)">
                  <span class="q-icon">Q</span>
                  <span class="question-text">{{ item.question }}</span>
                  <span class="expand-icon" :class="{ expanded: item.expanded }">▼</span>
                </div>
                <div class="faq-answer" v-show="item.expanded">
                  <span class="a-icon">A</span>
                  <p>{{ item.answer }}</p>
                </div>
              </div>
            </div>
          </el-collapse-item>
        </el-collapse>
      </div>

      <!-- 联系客服 -->
      <div class="contact-section card">
        <h3><el-icon><Phone /></el-icon> 还有其他问题？</h3>
        <p>如果以上问题没有解答您的疑问，欢迎联系我们</p>
        <div class="contact-methods">
          <div class="contact-item">
            <span class="contact-icon"><el-icon><ChatDotRound /></el-icon></span>
            <span class="contact-label">在线客服</span>
            <span class="contact-value">工作日 9:00-18:00</span>
          </div>
          <div class="contact-item">
            <span class="contact-icon"><el-icon><Message /></el-icon></span>
            <span class="contact-label">邮箱</span>
            <span class="contact-value">support@taichu.com</span>
          </div>
          <div class="contact-item">
            <span class="contact-icon"><el-icon><ChatDotRound /></el-icon></span>
            <span class="contact-label">微信公众号</span>
            <span class="contact-value">太初命理</span>
          </div>
        </div>
        <el-button type="primary" size="large" @click="goToFeedback">
          提交反馈
        </el-button>
      </div>

      <!-- 快速入口 -->
      <div class="quick-links">
        <h3>快速入口</h3>
        <div class="links-grid">
          <router-link to="/bazi" class="quick-link">
            <span class="link-icon"><el-icon><YinYang /></el-icon></span>
            <span>八字排盘</span>
          </router-link>
          <router-link to="/tarot" class="quick-link">
            <span class="link-icon"><el-icon><Magic /></el-icon></span>
            <span>塔罗占卜</span>
          </router-link>
          <router-link to="/daily" class="quick-link">
            <span class="link-icon"><el-icon><StarFilled /></el-icon></span>
            <span>每日运势</span>
          </router-link>
          <router-link to="/profile" class="quick-link">
            <span class="link-icon"><el-icon><UserFilled /></el-icon></span>
            <span>个人中心</span>
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import BackButton from '../components/BackButton.vue'
import { Search, Phone, ChatDotRound, Message, YinYang, Magic, StarFilled, UserFilled, CollectionTag, Coin, Lock } from '@element-plus/icons-vue'

const router = useRouter()
const searchQuery = ref('')
const activeNames = ref([0, 1, 2])

const hotTags = ['积分', '八字', '登录', '塔罗', '充值']

const categories = ref([
  {
    title: '新手指南',
    items: [
      {
        question: '太初命理是什么？',
        answer: '太初命理是一款结合传统命理学与人工智能技术的智能分析平台，为您提供专业的八字排盘、塔罗占卜、每日运势等服务。',
        expanded: false
      },
      {
        question: '如何注册账号？',
        answer: '点击页面右上角的"登录"按钮，选择微信登录或手机号登录即可完成注册。新用户注册即送100积分新手礼包！',
        expanded: false
      },
      {
        question: '积分有什么用？',
        answer: '积分可用于使用平台服务：八字排盘（10积分/次）、塔罗占卜（5积分/次）。积分可通过每日签到、邀请好友等方式获得。',
        expanded: false
      }
    ]
  },
  {
    title: '积分相关',
    items: [
      {
        question: '如何获得积分？',
        answer: '1. 每日签到：基础5积分，连续签到有额外奖励\n2. 新用户注册：送100积分\n3. 邀请好友：每邀请一位好友获得20积分\n4. 分享结果：分享排盘或占卜结果获得5积分',
        expanded: false
      },
      {
        question: '积分可以充值吗？',
        answer: '目前积分主要通过平台活动获得，暂不支持直接充值。我们会不定期推出积分赠送活动，请持续关注！',
        expanded: false
      },
      {
        question: '积分会过期吗？',
        answer: '积分永久有效，不会过期，请放心使用。',
        expanded: false
      }
    ]
  },
  {
    title: '八字排盘',
    items: [
      {
        question: '什么是真太阳时？',
        answer: '真太阳时是根据出生地点的经度计算出的真实太阳时间。中国幅员辽阔，东西跨度大，不同地区的北京时间与实际太阳时存在差异。八字排盘需要使用真太阳时才能准确计算。',
        expanded: false
      },
      {
        question: '八字排盘准确吗？',
        answer: '我们的八字排盘基于传统命理学算法，结合精确的节气计算和天文数据。但命理分析仅供参考，人生道路还需自己把握。',
        expanded: false
      },
      {
        question: '可以多次排盘吗？',
        answer: '可以，每次排盘消耗10积分。同一人的八字是固定的，重复排盘结果相同。建议将结果保存或分享，方便日后查看。',
        expanded: falsen      }
    ]
  },
  {
    title: '塔罗占卜',
    items: [
      {
        question: '塔罗占卜有什么用？',
        answer: '塔罗牌是一种趣味心理测试工具，可以帮助您：了解当前心境、探索内心可能、获得思考角度。塔罗测试仅供娱乐参考，帮助您从另一个角度认识自己。',
        expanded: false
      },
      {
        question: '不同牌阵有什么区别？',
        answer: '单张牌：简单直接，适合快速解答；三张牌：过去-现在-未来，了解时间线；凯尔特十字：深度分析，全面解读问题。',
        expanded: false
      },
      {
        question: '塔罗牌正逆位有什么区别？',
        answer: '正位通常表示能量的正常流动，逆位可能表示能量受阻、延迟或需要反向思考。逆位不一定是坏事，有时是提醒需要注意的方面。',
        expanded: false
      }
    ]
  },
  {
    title: '账号安全',
    icon: 'Lock',
    items: [
      {
        question: '如何修改个人信息？',
        answer: '进入"个人中心"页面，可以查看和修改您的个人信息，包括昵称、头像等。',
        expanded: false
      },
      {
        question: '忘记登录怎么办？',
        answer: '您可以重新使用微信或手机号登录，系统会自动识别您的账号信息。',
        expanded: false
      },
      {
        question: '如何退出登录？',
        answer: '点击页面右上角的用户头像，选择"退出登录"即可。',
        expanded: false
      }
    ]
  }
])

const filteredCategories = computed(() => {
  if (!searchQuery.value) return categories.value
  
  const query = searchQuery.value.toLowerCase()
  return categories.value.map(category => ({
    ...category,
    items: category.items.filter(item => 
      item.question.toLowerCase().includes(query) || 
      item.answer.toLowerCase().includes(query)
    )
  })).filter(category => category.items.length > 0)
})

const toggleQuestion = (item) => {
  item.expanded = !item.expanded
}

const goToFeedback = () => {
  router.push('/profile')
}
</script>

<style scoped>
.help-page {
  padding: 60px 0;
}

.page-header {
  display: flex;
  align-items: center;
  gap: 20px;
  margin-bottom: 30px;
}

.page-header .section-title {
  margin: 0;
}

/* 搜索区域 */
.search-section {
  max-width: 800px;
  margin: 0 auto 40px;
  text-align: center;
  padding: 40px;
}

.search-section h2 {
  color: #fff;
  margin-bottom: 25px;
  font-size: 24px;
}

.search-input {
  max-width: 500px;
  margin: 0 auto 20px;
}

.search-input :deep(.el-input__wrapper) {
  background: rgba(255, 255, 255, 0.1);
  box-shadow: none;
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 25px;
  padding: 5px 15px;
}

.search-input :deep(.el-input__inner) {
  color: #fff;
  font-size: 16px;
}

.hot-tags {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-wrap: wrap;
  gap: 10px;
}

.tag-label {
  color: rgba(255, 255, 255, 0.6);
  font-size: 14px;
}

.hot-tag {
  cursor: pointer;
  background: rgba(184, 134, 11, 0.2);
  border-color: rgba(184, 134, 11, 0.3);
  color: var(--text-primary);
}

.hot-tag:hover {
  background: rgba(184, 134, 11, 0.4);
}

/* FAQ区域 */
.faq-section {
  max-width: 800px;
  margin: 0 auto 40px;
}

.faq-collapse {
  background: transparent;
  border: none;
}

.faq-collapse :deep(.el-collapse-item__header) {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 10px;
  padding: 20px;
  color: #fff;
  font-size: 18px;
  font-weight: bold;
  margin-bottom: 10px;
}

.faq-collapse :deep(.el-collapse-item__wrap) {
  background: transparent;
  border: none;
}

.faq-collapse :deep(.el-collapse-item__content) {
  padding: 0;
  color: rgba(255, 255, 255, 0.8);
}

.faq-list {
  padding: 10px 0;
}

.faq-item {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 10px;
  margin-bottom: 10px;
  overflow: hidden;
}

.faq-question {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 15px 20px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.faq-question:hover {
  background: rgba(255, 255, 255, 0.05);
}

.q-icon {
  width: 24px;
  height: 24px;
  background: linear-gradient(135deg, #B8860B, #D4AF37);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: bold;
  color: #fff;
  flex-shrink: 0;
}

.question-text {
  flex: 1;
  color: rgba(255, 255, 255, 0.9);
  font-size: 15px;
}

.expand-icon {
  color: rgba(255, 255, 255, 0.5);
  font-size: 12px;
  transition: transform 0.3s ease;
}

.expand-icon.expanded {
  transform: rotate(180deg);
}

.faq-answer {
  display: flex;
  gap: 12px;
  padding: 0 20px 20px 20px;
  border-top: 1px solid rgba(255, 255, 255, 0.05);
  margin-top: 0;
  padding-top: 15px;
}

.a-icon {
  width: 24px;
  height: 24px;
  background: rgba(103, 194, 58, 0.8);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: bold;
  color: #fff;
  flex-shrink: 0;
}

.faq-answer p {
  color: rgba(255, 255, 255, 0.7);
  line-height: 1.8;
  margin: 0;
  white-space: pre-line;
}

/* 联系区域 */
.contact-section {
  max-width: 800px;
  margin: 0 auto 40px;
  text-align: center;
  padding: 40px;
}

.contact-section h3 {
  color: #fff;
  font-size: 22px;
  margin-bottom: 10px;
}

.contact-section > p {
  color: rgba(255, 255, 255, 0.6);
  margin-bottom: 25px;
}

.contact-methods {
  display: flex;
  justify-content: center;
  gap: 40px;
  margin-bottom: 25px;
  flex-wrap: wrap;
}

.contact-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
}

.contact-icon {
  font-size: 32px;
}

.contact-label {
  color: rgba(255, 255, 255, 0.8);
  font-size: 14px;
}

.contact-value {
  color: rgba(255, 255, 255, 0.6);
  font-size: 13px;
}

/* 快速入口 */
.quick-links {
  max-width: 800px;
  margin: 0 auto;
  text-align: center;
}

.quick-links h3 {
  color: #fff;
  font-size: 20px;
  margin-bottom: 25px;
}

.links-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
}

.quick-link {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 15px;
  padding: 25px 15px;
  text-decoration: none;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  transition: all 0.3s ease;
}

.quick-link:hover {
  background: rgba(184, 134, 11, 0.1);
  border-color: rgba(184, 134, 11, 0.3);
  transform: translateY(-5px);
}

.link-icon {
  font-size: 32px;
}

.quick-link span:last-child {
  color: rgba(255, 255, 255, 0.9);
  font-size: 14px;
}

@media (max-width: 768px) {
  .contact-methods {
    flex-direction: column;
    gap: 20px;
  }
  
  .links-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .faq-question {
    padding: 12px 15px;
  }
  
  .question-text {
    font-size: 14px;
  }
}
</style>
