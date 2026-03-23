<template>
  <div class="login-page">
    <div class="login-container">
      <div class="login-box">
        <div class="login-header">
          <div class="logo">
            <el-icon class="logo-icon"><Star /></el-icon>
            <span>太初命理</span>
          </div>
          <h2>{{ loginTitle }}</h2>
          <p>{{ loginSubtitle }}</p>
          <p v-if="isRegisterIntent" class="intent-tip">当前入口会在验证成功后直接发放新手积分，无需再额外找注册按钮。</p>
        </div>

        <!-- 注册福利提示 -->
        <div class="login-benefits" v-if="!isLoggedIn">
          <div class="benefit-item">
            <span class="benefit-icon">🎁</span>
            <div>
              <strong>注册即送积分</strong>
              <span>可直接体验八字排盘</span>
            </div>
          </div>
          <div class="benefit-item">
            <span class="benefit-icon">📅</span>
            <div>
              <strong>每日签到</strong>
              <span>连续签到积分翻倍</span>
            </div>
          </div>
        </div>

        <!-- 登录表单 -->
        <div class="login-methods">
          <div class="phone-login-form">
            <el-input
              v-model="phoneForm.phone"
              placeholder="请输入手机号"
              size="large"
              class="login-input"
              maxlength="11"
            >
              <template #prefix>
                <el-icon><Phone /></el-icon>
              </template>
            </el-input>
            <div class="code-input-row">
              <el-input
                v-model="phoneForm.code"
                placeholder="验证码"
                size="large"
                class="login-input code-input"
                maxlength="6"
              >
                <template #prefix>
                  <el-icon><Lock /></el-icon>
                </template>
              </el-input>
              <el-button
                size="large"
                :disabled="countdown > 0 || !isValidPhone"
                @click="sendCode"
                class="send-code-btn"
              >
                {{ countdown > 0 ? `${countdown}s` : '获取验证码' }}
              </el-button>
            </div>
            <el-button
              type="primary"
              size="large"
              class="login-submit-btn"
              :loading="loading"
              :disabled="!isValidPhone || !isValidCode"
              @click="handlePhoneLogin"
            >
              {{ submitButtonText }}
            </el-button>
          </div>
        </div>

        <div class="login-tips">
          <p><el-icon><Star /></el-icon> {{ primaryTipText }}</p>
          <p><el-icon><Lock /></el-icon> 我们严格保护您的隐私信息</p>
          <p class="agreement-tip">
            登录即表示同意
            <el-link type="primary" :underline="false" @click="showAgreement">用户协议</el-link>
            和
            <el-link type="primary" :underline="false" @click="showPrivacy">隐私政策</el-link>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Phone, Lock, Star } from '@element-plus/icons-vue'
import { useLogin } from './useLogin'

const {
  phoneForm,
  loading,
  countdown,
  isRegisterIntent,
  loginTitle,
  loginSubtitle,
  submitButtonText,
  primaryTipText,
  isValidPhone,
  isValidCode,
  isLoggedIn,
  sendCode,
  handlePhoneLogin,
  showAgreement,
  showPrivacy,
} = useLogin()
</script>

<style scoped>
@import './style.css';
</style>
