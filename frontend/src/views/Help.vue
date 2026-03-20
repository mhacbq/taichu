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
          @input="handleSearchInput"
        >
          <template #prefix><el-icon><Search /></el-icon></template>
        </el-input>
        <div class="hot-tags">
          <span class="tag-label">热门搜索：</span>
          <el-tag 
            v-for="tag in hotTags" 
            :key="tag"
            class="hot-tag"
            @click="searchQuery = tag; handleSearchInput()"
          >
            {{ tag }}
          </el-tag>
        </div>
        <!-- 搜索建议 -->
        <div v-if="searchSuggestions.length > 0" class="search-suggestions">
          <div 
            v-for="suggestion in searchSuggestions" 
            :key="suggestion.id"
            class="suggestion-item"
            @click="selectSuggestion(suggestion)"
          >
            <el-icon><Search /></el-icon>
            <span class="suggestion-text">{{ suggestion.question }}</span>
          </div>
        </div>
      </div>

      <!-- 常见问题分类 -->
      <div class="faq-section">
        <AsyncState :status="faqStatus" :error="faqError" loadingText="正在加载帮助内容..." @retry="loadFaqs">
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
                    <el-icon class="expand-icon" :class="{ expanded: item.expanded }"><ArrowDown /></el-icon>
                  </div>
                  <div class="faq-answer" v-show="item.expanded">
                    <span class="a-icon">A</span>
                    <p>{{ item.answer }}</p>
                  </div>
                </div>
              </div>
            </el-collapse-item>
          </el-collapse>
        </AsyncState>
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
            <span class="link-icon"><el-icon><Coin /></el-icon></span>
            <span>八字排盘</span>
          </router-link>
          <router-link to="/tarot" class="quick-link">
            <span class="link-icon"><el-icon><MagicStick /></el-icon></span>
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
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import BackButton from '../components/BackButton.vue'
import AsyncState from '../components/AsyncState.vue'
import { ArrowDown, Search, Phone, ChatDotRound, Message, MagicStick, StarFilled, UserFilled, Coin, Lock } from '@element-plus/icons-vue'
import { getFaqs } from '../api/siteContent'

const router = useRouter()
const searchQuery = ref('')
const activeNames = ref([0, 1, 2])
const faqData = ref([])
const searchSuggestions = ref([])
const faqStatus = ref('loading')
const faqError = ref(null)

// 热门搜索标签
const hotTags = ['积分', '八字', '登录', '塔罗', '充值']

// 分类映射
const categoryMap = {
  'general': '新手指南',
  'bazi': '八字分析', 
  'tarot': '塔罗测试',
  'account': '账号相关',
  'points': '积分问题'
}

// 分类图标映射
const categoryIcons = {
  'general': 'StarFilled',
  'bazi': 'Coin',
  'tarot': 'MagicStick',
  'account': 'UserFilled',
  'points': 'Lock'
}

// 加载FAQ数据
const loadFaqs = async () => {
  try {
    faqStatus.value = 'loading'
    faqError.value = null
    const response = await getFaqs()
    if (response.code === 200) {
      faqData.value = response.data.map(item => ({
        ...item,
        expanded: false
      }))
      faqStatus.value = faqData.value.length > 0 ? 'success' : 'empty'
    } else {
      faqStatus.value = 'error'
      faqError.value = response.message || '加载失败'
    }
  } catch (error) {
    console.error('加载FAQ数据失败:', error)
    faqStatus.value = 'error'
    faqError.value = error.message || '网络错误，请稍后重试'
    ElMessage.error('加载帮助内容失败，请稍后重试')
  }
}

// 处理搜索输入
const handleSearchInput = () => {
  if (searchQuery.value.length > 1) {
    // 显示搜索建议
    const suggestions = faqData.value.filter(item => 
      item.question.toLowerCase().includes(searchQuery.value.toLowerCase())
    ).slice(0, 5)
    searchSuggestions.value = suggestions
  } else {
    searchSuggestions.value = []
  }
}

// 选择搜索建议
const selectSuggestion = (suggestion) => {
  searchQuery.value = suggestion.question
  searchSuggestions.value = []
  // 自动展开对应问题
  const item = faqData.value.find(item => item.id === suggestion.id)
  if (item) {
    item.expanded = true
  }
}

// 按分类分组FAQ数据
const groupedFaqs = computed(() => {
  const groups = {}
  faqData.value.forEach(item => {
    if (!groups[item.category]) {
      groups[item.category] = []
    }
    groups[item.category].push(item)
  })
  
  // 按浏览量排序（热门问题优先）
  Object.keys(groups).forEach(category => {
    groups[category].sort((a, b) => (b.view_count || 0) - (a.view_count || 0))
  })
  
  return groups
})

// 处理后的分类数据
const categories = computed(() => {
  return Object.keys(groupedFaqs.value).map((category, index) => ({
    title: categoryMap[category] || category,
    icon: categoryIcons[category],
    items: groupedFaqs.value[category]
  }))
})

// 根据搜索条件过滤分类
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
  // 增加浏览量
  if (item.expanded && item.id) {
    // 这里可以调用API增加浏览量
  }
}

const goToFeedback = () => {
  router.push('/profile')
}

// 页面加载时获取数据
onMounted(() => {
  loadFaqs()
})
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
  position: relative;
}

.search-section h2 {
  color: var(--text-primary);
  margin-bottom: 25px;
  font-size: 24px;
}

.search-input {
  max-width: 500px;
  margin: 0 auto 20px;
}

.search-input :deep(.el-input__wrapper) {
  background: var(--bg-card);
  box-shadow: 0 0 0 1px var(--border-light) inset, 0 6px 16px rgba(15, 23, 42, 0.04);
  border: 1px solid transparent;
  border-radius: 25px;
  padding: 5px 15px;
}

.search-input :deep(.el-input__wrapper:hover) {
  box-shadow: 0 0 0 1px var(--primary-light-20) inset, 0 10px 22px rgba(var(--primary-rgb), 0.08);
}

.search-input :deep(.el-input__wrapper.is-focus) {
  box-shadow: 0 0 0 1px var(--primary-color) inset, var(--focus-ring);
}

.search-input :deep(.el-input__inner) {
  color: var(--text-primary);
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
  color: var(--white-60);
  font-size: 14px;
}

.hot-tag {
  cursor: pointer;
  background: var(--primary-light-20);
  border-color: var(--primary-light-30);
  color: var(--text-primary);
}

.hot-tag:hover {
  background: var(--primary-light-40);
}

/* 搜索建议 */
.search-suggestions {
  position: absolute;
  top: 100%;
  left: 50%;
  transform: translateX(-50%);
  width: 500px;
  max-width: 90%;
  background: var(--bg-card);
  border: 1px solid var(--border-light);
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  z-index: 1000;
  margin-top: 5px;
}

.suggestion-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 16px;
  cursor: pointer;
  border-bottom: 1px solid var(--border-light);
  transition: background-color 0.2s;
}

.suggestion-item:last-child {
  border-bottom: none;
}

.suggestion-item:hover {
  background: var(--bg-hover);
}

.suggestion-item .el-icon {
  color: var(--text-tertiary);
}

.suggestion-text {
  color: var(--text-primary);
  font-size: 14px;
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
  background: var(--bg-hover);
  border: 1px solid var(--border-color);
  border-radius: 10px;
  padding: 20px;
  color: var(--text-primary);
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
  color: var(--white-80);
}

.faq-list {
  padding: 10px 0;
}

.faq-item {
  background: var(--white-03);
  border: 1px solid var(--white-10);
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
  background: var(--white-05);
}

.q-icon {
  width: 24px;
  height: 24px;
  background: var(--primary-gradient);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: bold;
  color: var(--text-accent-contrast);
  flex-shrink: 0;
}

.question-text {
  flex: 1;
  color: var(--text-primary);
  font-size: 15px;
}

.expand-icon {
  color: var(--text-tertiary);
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
  border-top: 1px solid var(--border-light);
  margin-top: 0;
  padding-top: 15px;
  background: rgba(var(--primary-rgb), 0.02);
}

.a-icon {
  width: 24px;
  height: 24px;
  background: rgba(103, 194, 58, 0.16);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: bold;
  color: var(--success-color);
  flex-shrink: 0;
}

.faq-answer p {
  color: var(--text-secondary);
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
  color: var(--text-primary);
  font-size: 22px;
  margin-bottom: 10px;
}

.contact-section > p {
  color: var(--text-secondary);
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
  color: var(--text-primary);
  font-size: 14px;
}

.contact-value {
  color: var(--text-tertiary);
  font-size: 13px;
}

/* 快速入口 */
.quick-links {
  max-width: 800px;
  margin: 0 auto;
  text-align: center;
}

.quick-links h3 {
  color: var(--text-primary);
  font-size: 20px;
  margin-bottom: 25px;
}

.links-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
}

.quick-link {
  background: var(--bg-hover);
  border: 1px solid var(--border-color);
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
  color: var(--text-primary);
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