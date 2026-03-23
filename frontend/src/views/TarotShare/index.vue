<template>
  <div class="tarot-share-page">
    <div class="container">
      <div class="page-header">
        <BackButton text="返回塔罗" fallback="/tarot" />
        <div>
          <h1 class="section-title">塔罗分享结果</h1>
          <p class="page-subtitle">公开查看这一次塔罗占卜的结果与解读</p>
        </div>
      </div>

      <div v-if="loading" class="loading-state card card-hover">
        <el-skeleton :rows="8" animated />
      </div>

      <div v-else-if="errorMessage" class="error-state card card-hover">
        <el-icon class="state-icon"><WarningFilled /></el-icon>
        <h3>分享内容暂时不可用</h3>
        <p>{{ errorMessage }}</p>
        <div class="state-actions">
          <el-button type="primary" @click="loadShareRecord">重新加载</el-button>
          <router-link to="/tarot">
            <el-button>去塔罗占卜</el-button>
          </router-link>
        </div>
      </div>

      <div v-else-if="shareRecord" class="share-result card card-hover">
        <div class="share-meta">
          <el-tag type="warning" effect="dark">{{ shareRecord.spread_name }}</el-tag>
          <span>浏览 {{ shareRecord.view_count || 0 }} 次</span>
          <span>{{ formatShareTime(shareRecord.created_at) }}</span>
        </div>

        <div class="question-box">
          <span class="question-label">占问</span>
          <p class="question-text">{{ shareRecord.question || '未填写问题' }}</p>
        </div>

        <div class="cards-section">
          <h3>牌阵展示</h3>
          <div class="cards-display">
            <div
              v-for="(card, index) in shareRecord.cards"
              :key="`${card.name}-${index}`"
              class="card-stack"
            >
              <TarotCard
                :name="card.name"
                :emoji="card.emoji"
                :reversed="card.reversed"
                :element="card.element"
                :color="card.color"
                :index="index"
              />
              <span v-if="shareRecord.cards.length > 1" class="card-position">
                {{ getPositionLabel(shareRecord.spread_type, index) }}
              </span>
            </div>
          </div>
        </div>

        <div class="interpretation-section">
          <h3>塔罗解读</h3>
          <div class="interpretation-content">{{ shareRecord.interpretation }}</div>
        </div>

        <div class="hot-questions-section">
          <h3>大家都在问</h3>
          <div class="hot-questions-list">
            <router-link
              to="/tarot"
              class="hot-question-item card-hover"
              v-for="(q, index) in hotQuestions"
              :key="index"
            >
              <span class="hot-question-rank" :class="`rank-${index + 1}`">{{ index + 1 }}</span>
              <span class="hot-question-text">{{ q }}</span>
              <el-icon><ArrowRight /></el-icon>
            </router-link>
          </div>
        </div>

        <div class="share-actions-fixed">
          <div class="share-actions-content">
            <p class="share-actions-hint">想知道你的专属答案吗？</p>
            <router-link to="/tarot" class="share-actions-btn">
              我也来占一卦 <el-icon><ArrowRight /></el-icon>
            </router-link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { WarningFilled, ArrowRight } from '@element-plus/icons-vue'
import BackButton from '../../components/BackButton.vue'
import TarotCard from '../../components/TarotCard.vue'
import { useTarotShare } from './useTarotShare'

const {
  loading,
  errorMessage,
  shareRecord,
  hotQuestions,
  getPositionLabel,
  formatShareTime,
  loadShareRecord,
} = useTarotShare()
</script>

<style scoped>
@import './style.css';
</style>
