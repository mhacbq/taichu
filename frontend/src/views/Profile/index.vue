<template>
  <div class="profile-page">
    <div class="container">
      <div class="profile-hero">
        <div class="profile-hero-left">
          <!-- 返回按钮 -->
          <div class="profile-hero-header">
            <BackButton />
          </div>
          
          <!-- 用户信息区域 -->
          <div class="profile-hero-user">
            <div class="profile-hero-avatar">
              <img v-if="userInfo.avatar" :src="userInfo.avatar" alt="用户头像" class="profile-hero-avatar-img">
              <span v-else class="profile-hero-avatar-placeholder">{{ userInfo.nickname?.[0] || '用' }}</span>
            </div>
            <div class="profile-hero-details">
              <h1 class="profile-hero-name">{{ userInfo.nickname || '欢迎回来' }}</h1>
              <div class="profile-hero-meta">
                <span class="profile-hero-id">ID: {{ userInfo.id || '--' }}</span>
                <span class="profile-hero-divider">|</span>
                <span class="profile-hero-points">
                  <el-icon><Coin /></el-icon>
                  {{ pointsBalance }} 积分
                </span>
              </div>
              <div class="profile-hero-level">
                <span class="level-badge">{{ pointsLevelName }}</span>
                <span class="level-progress-mini">{{ pointsBalance }} / {{ currentPointsLevel.max === Infinity ? '∞' : currentPointsLevel.max }}</span>
              </div>
            </div>
          </div>
          
          <!-- 快捷统计 -->
          <div class="profile-hero-stats">
            <div class="hero-stat-item">
              <span class="hero-stat-value">{{ baziCount }}</span>
              <span class="hero-stat-label">排盘</span>
            </div>
            <div class="hero-stat-divider"></div>
            <div class="hero-stat-item">
              <span class="hero-stat-value">{{ tarotCount }}</span>
              <span class="hero-stat-label">占卜</span>
            </div>
            <div class="hero-stat-divider"></div>
            <div class="hero-stat-item">
              <span class="hero-stat-value">{{ liuyaoCount }}</span>
              <span class="hero-stat-label">六爻</span>
            </div>
            <div class="hero-stat-divider"></div>
            <div class="hero-stat-item">
              <span class="hero-stat-value">{{ hehunCount }}</span>
              <span class="hero-stat-label">合婚</span>
            </div>
          </div>
        </div>

        <!-- 签到卡片 - 右侧固定宽度 -->
        <div class="profile-hero-right">
          <CheckinCard />
        </div>
      </div>

      <div class="profile-layout">
        <!-- 左侧边栏 -->
        <aside class="profile-sidebar">
          <!-- 用户信息卡片 -->
          <div class="sidebar-card user-card card-hover">
            <div class="user-stats">
              <div class="stat-item">
                <span class="stat-value">{{ pointsBalance }}</span>
                <span class="stat-label">积分</span>
              </div>
              <div class="stat-item">
                <span class="stat-value">{{ baziCount }}</span>
                <span class="stat-label">排盘</span>
              </div>
              <div class="stat-item">
                <span class="stat-value">{{ tarotCount }}</span>
                <span class="stat-label">占卜</span>
              </div>
              <div class="stat-item">
                <span class="stat-value">{{ liuyaoCount }}</span>
                <span class="stat-label">六爻</span>
              </div>
              <div class="stat-item">
                <span class="stat-value">{{ hehunCount }}</span>
                <span class="stat-label">合婚</span>
              </div>
            </div>
            
            <!-- 积分等级 -->
            <div class="level-section">
              <div class="level-header">
                <span class="level-title">积分等级</span>
                <span class="level-name">{{ pointsLevelName }}</span>
              </div>
              <el-progress 
                :percentage="pointsPercentage" 
                :format="pointsProgressFormat"
                :color="pointsProgressColor"
                :stroke-width="10"
                class="level-progress"
              />
              <div class="level-footer">
                <span>还需 {{ pointsToNextLevel }} 积分升级</span>
                <router-link to="/recharge" class="recharge-btn">充值</router-link>
              </div>
            </div>

            <!-- 生日设置 -->
            <div class="birthday-section">
              <h4><el-icon><Calendar /></el-icon> 出生日期</h4>
              <p class="birthday-desc">设置生日后可使用每日运势功能</p>
              <el-date-picker
                v-model="userBirthDate"
                type="date"
                placeholder="选择出生日期"
                format="YYYY年MM月DD日"
                value-format="YYYY-MM-DD"
                size="small"
                @change="saveBirthDate"
                :disabled-date="(time) => time.getTime() > Date.now()"
              />
            </div>
          </div>

          <!-- 积分获取攻略 -->
          <div class="sidebar-card guide-card card-hover">
            <h4><el-icon><Coin /></el-icon> 积分获取攻略</h4>
            <div class="guide-list">
              <div class="guide-item" v-for="method in pointsMethods" :key="method.id">
                <div class="guide-icon">
                  <el-icon v-if="method.icon === 'calendar'"><Calendar /></el-icon>
                  <el-icon v-else-if="method.icon === 'present'"><Present /></el-icon>
                  <el-icon v-else-if="method.icon === 'user'"><UserFilled /></el-icon>
                  <el-icon v-else-if="method.icon === 'share'"><Share /></el-icon>
                  <el-icon v-else-if="method.icon === 'chat'"><ChatDotRound /></el-icon>
                </div>
                <div class="guide-content">
                  <span class="guide-name">{{ method.name }}</span>
                  <span class="guide-reward">+{{ method.points }}</span>
                </div>
                <el-button 
                  v-if="method.action" 
                  type="primary" 
                  size="small"
                  text
                  @click="handleMethodAction(method)"
                >
                  {{ method.actionText }}
                </el-button>
              </div>
            </div>
          </div>

          <!-- 邀请好友 -->
          <div class="sidebar-card invite-card card-hover">
            <h4><el-icon><Present /></el-icon> 邀请好友</h4>
            <p class="invite-text">每邀请一位好友，双方各得 <strong>20积分</strong></p>
            <div class="invite-actions">
              <el-button type="primary" @click="copyInviteCode" class="invite-btn-main">
                <el-icon><DocumentCopy /></el-icon> 复制邀请码
              </el-button>
              <el-button @click="copyInviteLink" size="small">
                <el-icon><Link /></el-icon> 分享链接
              </el-button>
            </div>
            <div class="invite-stats">
              <div class="invite-stat">
                <span class="stat-num">{{ inviteCount }}</span>
                <span class="stat-txt">已邀请</span>
              </div>
              <div class="invite-stat">
                <span class="stat-num">{{ invitePoints }}</span>
                <span class="stat-txt">获积分</span>
              </div>
            </div>
          </div>
        </aside>

        <!-- 右侧主区域 -->
        <main class="profile-main">
          <!-- 历史记录 -->
          <div class="main-card history-card card-hover">
            <el-tabs v-model="activeHistoryTab" class="custom-tabs">
              <el-tab-pane label="八字排盘" name="bazi">
                <AsyncState :status="baziStatus" loadingText="正在加载排盘记录..." @retry="loadBaziHistory">
                  <div class="history-list" v-if="baziHistory.length > 0">
                    <div v-for="record in baziHistory" :key="record.id" class="history-list-item" @click="viewDetail(record)">
                      <div class="item-left">
                        <span class="item-title">{{ formatDate(record.birthDate) }} · {{ record.gender === 'male' ? '男' : '女' }}</span>
                        <span class="item-time">{{ formatTime(record.createdAt) }}</span>
                      </div>
                      <div class="item-right">
                        <span class="item-bazi">{{ record.yearGan }}{{ record.yearZhi }} {{ record.dayGan }}{{ record.dayZhi }}</span>
                        <el-icon class="item-arrow"><ArrowRight /></el-icon>
                      </div>
                    </div>
                  </div>
                  <el-empty v-else description="暂无排盘记录" />
                  <div class="pagination-wrapper" v-if="baziTotal > baziPageSize">
                    <el-pagination
                      v-model:current-page="baziCurrentPage"
                      v-model:page-size="baziPageSize"
                      :total="baziTotal"
                      layout="prev, pager, next"
                      @current-change="loadBaziHistory"
                    />
                  </div>
                </AsyncState>
              </el-tab-pane>

              <el-tab-pane label="塔罗占卜" name="tarot">
                <AsyncState :status="tarotStatus" loadingText="正在加载占卜记录..." @retry="loadTarotHistory">
                  <div class="history-list" v-if="tarotHistory.length > 0">
                    <div v-for="(record, index) in tarotHistory" :key="index" class="history-list-item" @click="viewTarotDetail(record)">
                      <div class="item-left">
                        <span class="item-title">{{ record.spreadName }}</span>
                        <span class="item-time">{{ formatTime(record.date) }}</span>
                      </div>
                      <div class="item-right">
                        <span class="item-tarot">{{ record.cards[0]?.name }}{{ record.cards[0]?.reversed ? '(逆)' : '' }}</span>
                        <el-icon class="item-arrow"><ArrowRight /></el-icon>
                      </div>
                    </div>
                  </div>
                  <el-empty v-else description="暂无占卜记录" />
                </AsyncState>
              </el-tab-pane>

              <el-tab-pane label="六简占卜" name="liuyao">
                <AsyncState :status="liuyaoStatus" loadingText="正在加载六简记录..." @retry="loadLiuyaoHistory">
                  <div class="history-list" v-if="liuyaoHistory.length > 0">
                    <div v-for="record in liuyaoHistory" :key="record.id" class="history-list-item" @click="viewLiuyaoDetail(record)">
                      <div class="item-left">
                        <span class="item-title">{{ record.question || '六简占卜' }}</span>
                        <span class="item-time">{{ formatTime(record.created_at) }}</span>
                      </div>
                      <div class="item-right">
                        <span class="item-method">{{ record.method_name || '铜錢起卦' }}</span>
                        <el-icon class="item-arrow"><ArrowRight /></el-icon>
                      </div>
                    </div>
                  </div>
                  <el-empty v-else description="暂无六简记录" />
                </AsyncState>
              </el-tab-pane>

              <el-tab-pane label="八字合婚" name="hehun">
                <AsyncState :status="hehunStatus" loadingText="正在加载合婚记录..." @retry="loadHehunHistory">
                  <div class="history-list" v-if="hehunHistory.length > 0">
                    <div v-for="record in hehunHistory" :key="record.id" class="history-list-item" @click="viewHehunDetail(record)">
                      <div class="item-left">
                        <span class="item-title">{{ record.male_name || '男方' }} × {{ record.female_name || '女方' }}</span>
                        <span class="item-time">{{ formatTime(record.created_at) }}</span>
                      </div>
                      <div class="item-right" v-if="record.total_score">
                        <span class="item-score">{{ record.total_score }}分</span>
                        <el-icon class="item-arrow"><ArrowRight /></el-icon>
                      </div>
                      <div class="item-right" v-else>
                        <el-icon class="item-arrow"><ArrowRight /></el-icon>
                      </div>
                    </div>
                  </div>
                  <el-empty v-else description="暂无合婚记录" />
                </AsyncState>
              </el-tab-pane>
            </el-tabs>
          </div>

          <!-- 积分明细 -->
          <div class="main-card points-card card-hover">
            <div class="card-header">
              <h3>积分明细</h3>
            </div>
            <div class="points-list" v-if="pointsHistory.length > 0">
              <div v-for="record in pointsHistory" :key="record.id" class="points-row">
                <div class="points-left">
                  <span class="points-action">{{ record.action }}</span>
                  <span class="points-time">{{ formatTime(record.createdAt) }}</span>
                </div>
                <span class="points-amount" :class="{ positive: record.points > 0, negative: record.points < 0 }">
                  {{ record.points > 0 ? '+' : '' }}{{ record.points }}
                </span>
              </div>
            </div>
            <el-empty v-else description="暂无积分记录" />
          </div>

          <!-- 反馈建议 -->
          <div id="feedback-card" class="main-card feedback-card card-hover">
            <h3>反馈建议</h3>
            <div v-if="feedbackEnabled" class="feedback-form">
              <el-input
                v-model="feedbackContent"
                type="textarea"
                :rows="4"
                placeholder="请输入您的意见或建议..."
                class="feedback-input"
              />
              <el-input
                v-model="feedbackContact"
                placeholder="手机号或邮箱（选填）"
                class="feedback-contact"
              />
              <el-button type="primary" @click="submitFeedbackForm" :loading="feedbackLoading">
                提交反馈
              </el-button>
            </div>
            <el-empty v-else description="反馈功能暂时关闭" />
          </div>

          <!-- 帮助与设置 -->
          <div class="main-card help-card card-hover">
            <h3><el-icon><List /></el-icon> 帮助与设置</h3>
            <div class="help-list">
              <div class="help-link" @click="restartTourGuide">
                <el-icon><DocumentCopy /></el-icon>
                <span>重新查看引导</span>
                <el-icon><ArrowRight /></el-icon>
              </div>
            </div>
          </div>
        </main>
      </div>
    </div>
  </div>
</template>


<script setup>
import CheckinCard from '../../components/CheckinCard.vue'
import BackButton from '../../components/BackButton.vue'
import AsyncState from '../../components/AsyncState.vue'
import { Coin, Present, UserFilled, ChatDotRound, DocumentCopy, Share, Link, List, Calendar, ArrowRight } from '@element-plus/icons-vue'

import { useProfile } from './useProfile'

const {
  // 状态
  userInfo, pointsBalance, baziCount, tarotCount, liuyaoCount, hehunCount,
  pointsHistory, baziHistory, tarotHistory, liuyaoHistory, hehunHistory,
  feedbackContent, feedbackContact, feedbackLoading, feedbackEnabled,
  activeHistoryTab, userBirthDate,
  profileStatus, profileError,
  baziStatus, tarotStatus, liuyaoStatus, hehunStatus,
  baziCurrentPage, baziPageSize, baziTotal,
  inviteCode, inviteCount, invitePoints, inviteLink,

  // 计算属性
  currentPointsLevel, pointsLevelName, pointsPercentage,
  pointsProgressColor, pointsToNextLevel, pointsMethods,

  // 常量
  pointsLevels,

  // 方法
  pointsProgressFormat, formatTime, formatDate,
  restartTourGuide,
  loadBaziHistory, loadTarotHistory, loadLiuyaoHistory, loadHehunHistory,
  submitFeedbackForm, saveBirthDate,
  viewDetail, viewTarotDetail, viewLiuyaoDetail, viewHehunDetail,
  handleMethodAction, copyInviteCode, copyInviteLink, shareToWechat,
} = useProfile()
</script>

<style scoped>
@import './style.css';
</style>
