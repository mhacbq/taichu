<template>
  <div class="help-page">
    <div class="container">
      <!-- 页面头部 - 神秘主题 -->
      <div class="page-header">
        <BackButton />
        <div class="title-section">
          <h1 class="mystic-title">{{ helpPageTitle }}</h1>
          <p class="subtitle">探索太初命理平台的奥秘与指引</p>
        </div>
      </div>

      <!-- 搜索区域 - 水晶球灵感 -->
      <div class="crystal-search-section">
        <div class="search-container">
          <h2 class="search-title">
            <span class="magic-icon">🔮</span>
            {{ helpSearchTitle }}
          </h2>
          <div class="search-input-wrapper">
            <el-input
              v-model="searchQuery"
              :placeholder="helpSearchPlaceholder"
              size="large"
              clearable
              class="crystal-input"
              @input="handleSearchInput"
            >
              <template #prefix>
                <span class="search-icon">✨</span>
              </template>
            </el-input>
            <div class="search-glow"></div>
          </div>
          
          <!-- 热门标签云 -->
          <div class="tag-cloud">
            <div class="cloud-label">热门探索：</div>
            <div class="tags-container">
              <span
                v-for="tag in hotTags"
                :key="tag"
                class="magic-tag"
                @click="searchQuery = tag; handleSearchInput()"
              >
                {{ tag }}
              </span>
            </div>
          </div>

          <!-- 搜索建议 - 悬浮效果 -->
          <div v-if="searchSuggestions.length > 0" class="suggestion-orbs">
            <div
              v-for="suggestion in searchSuggestions"
              :key="suggestion.id"
              class="suggestion-orb"
              @click="selectSuggestion(suggestion)"
            >
              <span class="orb-icon">💫</span>
              <span class="suggestion-text">{{ suggestion.question }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- FAQ区域 - 卷轴式设计 -->
      <div class="scroll-section">
        <div class="scroll-header">
          <h3 class="scroll-title">
            <span class="scroll-icon">📜</span>
            智慧卷轴
          </h3>
          <p class="scroll-desc">解开你心中的疑惑</p>
        </div>

        <AsyncState :status="faqStatus" :error="faqError" loadingText="正在开启智慧卷轴..." @retry="loadFaqs">
          <div class="knowledge-scrolls">
            <div
              v-for="(category, idx) in filteredCategories"
              :key="idx"
              class="knowledge-scroll"
              :class="{ active: activeNames.includes(idx) }"
            >
              <div class="scroll-header" @click="activeNames = activeNames.includes(idx) ? activeNames.filter(i => i !== idx) : [...activeNames, idx]">
                <div class="scroll-category">
                  <span class="category-icon">{{ getCategoryIcon(category.title) }}</span>
                  <span class="category-title">{{ category.title }}</span>
                </div>
                <span class="expand-arrow">▼</span>
              </div>
              
              <div class="scroll-content" v-show="activeNames.includes(idx)">
                <div
                  v-for="(item, itemIdx) in category.items"
                  :key="itemIdx"
                  class="wisdom-item"
                  :class="{ expanded: item.expanded }"
                >
                  <div class="question-crystal" @click="toggleQuestion(item)">
                    <div class="crystal-shape"></div>
                    <div class="question-content">
                      <span class="q-symbol">❓</span>
                      <span class="question-text">{{ item.question }}</span>
                    </div>
                    <span class="expand-crystal">💎</span>
                  </div>
                  <div class="answer-aura" v-show="item.expanded">
                    <div class="aura-effect"></div>
                    <div class="answer-content">
                      <span class="a-symbol">💡</span>
                      <p class="answer-text">{{ item.answer }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </AsyncState>
      </div>

      <!-- 联系区域 - 魔法阵设计 -->
      <div class="magic-circle-section">
        <div class="circle-container">
          <h3 class="circle-title">
            <span class="circle-icon">🌀</span>
            {{ helpContactTitle }}
          </h3>
          <p class="circle-desc">{{ helpContactDesc }}</p>
          
          <div class="contact-runes">
            <div
              v-for="item in contactMethods"
              :key="item.label"
              class="rune-stone"
            >
              <div class="rune-symbol">{{ getContactSymbol(item.label) }}</div>
              <div class="rune-info">
                <span class="rune-label">{{ item.label }}</span>
                <span class="rune-value">{{ item.value }}</span>
              </div>
            </div>
          </div>
          
          <el-button type="primary" size="large" class="magic-button" @click="goToFeedback">
            <span class="button-icon">🌟</span>
            {{ helpFeedbackButtonText }}
          </el-button>
        </div>
      </div>

      <!-- 快速入口 - 星图设计 -->
      <div class="star-map-section">
        <h3 class="star-title">
          <span class="star-icon">🌌</span>
          星图导航
        </h3>
        <div class="constellation-grid">
          <router-link to="/bazi" class="star-point">
            <div class="star-glow"></div>
            <span class="point-icon">📅</span>
            <span class="point-label">八字排盘</span>
          </router-link>
          <router-link to="/tarot" class="star-point">
            <div class="star-glow"></div>
            <span class="point-icon">🃏</span>
            <span class="point-label">塔罗占卜</span>
          </router-link>
          <router-link to="/daily" class="star-point">
            <div class="star-glow"></div>
            <span class="point-icon">📊</span>
            <span class="point-label">每日运势</span>
          </router-link>
          <router-link to="/profile" class="star-point">
            <div class="star-glow"></div>
            <span class="point-icon">👤</span>
            <span class="point-label">个人中心</span>
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ArrowDown, Search, Phone, ChatDotRound, Message, MagicStick, StarFilled, UserFilled, Coin } from '@element-plus/icons-vue'
import BackButton from '../../components/BackButton.vue'
import AsyncState from '../../components/AsyncState.vue'
import { useSEO } from '../../composables/useSEO'
import { useHelp } from './useHelp'

// 设置SEO配置
useSEO({
  title: '帮助中心_使用指南_常见问题 - 太初命理',
  description: '太初命理使用指南和常见问题解答，帮助您更好地使用八字排盘、塔罗占卜等功能。',
  keywords: ['帮助中心', '使用指南', '常见问题', '命理教程', '八字教程', '塔罗教程'],
  image: '/images/og-help.jpg'
})

// 联系方式图标映射
const contactIcons = { ChatDotRound, Message }

const {
  searchQuery,
  activeNames,
  faqStatus,
  faqError,
  searchSuggestions,
  helpPageTitle,
  helpSearchTitle,
  helpSearchPlaceholder,
  hotTags,
  helpContactTitle,
  helpContactDesc,
  helpFeedbackButtonText,
  contactMethods,
  filteredCategories,
  loadFaqs,
  handleSearchInput,
  selectSuggestion,
  toggleQuestion,
  goToFeedback,
} = useHelp()

// 分类图标映射
const getCategoryIcon = (title) => {
  const icons = {
    '新手指南': '📖',
    '八字分析': '🧮',
    '塔罗测试': '🔮',
    '账号相关': '🔐',
    '积分问题': '💰'
  }
  return icons[title] || '❓'
}

// 联系方式符号映射
const getContactSymbol = (label) => {
  const symbols = {
    '在线客服': '💬',
    '邮箱': '📧',
    '微信公众号': '📱'
  }
  return symbols[label] || '📞'
}
</script>

<style scoped>
@import './style.css';
</style>
