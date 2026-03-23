<template>
  <div class="help-page">
    <div class="container">
      <div class="page-header">
        <BackButton />
        <h1 class="section-title">{{ helpPageTitle }}</h1>
      </div>

      <!-- 搜索区域 -->
      <div class="search-section card">
        <h2><el-icon><Search /></el-icon> {{ helpSearchTitle }}</h2>
        <el-input
          v-model="searchQuery"
          :placeholder="helpSearchPlaceholder"
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
        <h3><el-icon><Phone /></el-icon> {{ helpContactTitle }}</h3>
        <p>{{ helpContactDesc }}</p>
        <div class="contact-methods">
          <div v-for="item in contactMethods" :key="item.label" class="contact-item">
            <span class="contact-icon"><el-icon><component :is="contactIcons[item.icon]" /></el-icon></span>
            <span class="contact-label">{{ item.label }}</span>
            <span class="contact-value">{{ item.value }}</span>
          </div>
        </div>
        <el-button type="primary" size="large" @click="goToFeedback">
          {{ helpFeedbackButtonText }}
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
import { ArrowDown, Search, Phone, ChatDotRound, Message, MagicStick, StarFilled, UserFilled, Coin } from '@element-plus/icons-vue'
import BackButton from '../../components/BackButton.vue'
import AsyncState from '../../components/AsyncState.vue'
import { useHelp } from './useHelp'

// 联系方式图标映射（模板中需要用组件引用）
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
</script>

<style scoped>
@import './style.css';
</style>
