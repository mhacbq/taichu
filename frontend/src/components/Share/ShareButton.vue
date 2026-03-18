<template>
  <div class="share-component">
    <!-- 分享按钮 -->
    <button 
      class="share-btn"
      :class="[type, { 'is-plain': plain }]"
      @click="openShare"
    >
      <el-icon :size="iconSize"><Share /></el-icon>
      <span v-if="showText">{{ text }}</span>
    </button>
    
    <!-- 分享弹窗 -->
    <el-dialog
      v-model="visible"
      title="分享到"
      width="400px"
      center
      class="share-dialog"
    >
      <div class="share-options">
        <div 
          v-for="option in shareOptions" 
          :key="option.key"
          class="share-option"
          @click="handleShare(option)"
        >
          <div class="share-icon" :style="{ background: option.color }">
            <el-icon :size="24"><component :is="option.icon" /></el-icon>
          </div>
          <span class="share-label">{{ option.label }}</span>
        </div>
      </div>
      
      <!-- 链接分享 -->
      <div class="share-link-section">
        <div class="link-input-wrapper">
          <el-input
            v-model="shareUrl"
            readonly
            class="link-input"
          >
            <template #append>
              <el-button @click="copyLink">
                <el-icon><CopyDocument /></el-icon>
                复制
              </el-button>
            </template>
          </el-input>
        </div>
      </div>
      
      <!-- 分享海报 -->
      <div v-if="showPoster" class="share-poster-section">
        <div class="poster-preview" ref="posterRef">
          <div class="poster-bg">
            <div class="poster-header">
              <h3>{{ posterTitle }}</h3>
              <p>{{ posterDesc }}</p>
            </div>
            <div class="poster-content">
              <slot name="poster-content">
                <div class="default-content">
                  <el-icon :size="60" color="#667eea"><Star /></el-icon>
                  <p>发现更多精彩内容</p>
                </div>
              </slot>
            </div>
            <div class="poster-footer">
              <div class="qrcode-wrapper">
                <canvas ref="qrcodeCanvas" width="80" height="80"></canvas>
              </div>
              <div class="poster-tips">
                <p>扫码查看</p>
                <p class="app-name">太初命理</p>
              </div>
            </div>
          </div>
        </div>
        <el-button type="primary" class="download-btn" @click="downloadPoster">
          <el-icon><Download /></el-icon>
          保存海报
        </el-button>
      </div>
    </el-dialog>
    
    <!-- 分享成功提示 -->
    <Transition name="fade">
      <div v-if="showSuccess" class="share-success-toast">
        <el-icon :size="24" color="#10b981"><CircleCheck /></el-icon>
        <span>{{ successMessage }}</span>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, nextTick } from 'vue'
import { ElMessage } from 'element-plus'
import {
  Share,
  CopyDocument,
  Star,
  Download,
  CircleCheck,
  ChatDotRound,
  Postcard,
  Link
} from '@element-plus/icons-vue'
import QRCode from 'qrcode'
import html2canvas from 'html2canvas'
import { useAnalytics } from '@/utils/analytics'

const props = defineProps({
  type: { type: String, default: 'default' },
  plain: { type: Boolean, default: false },
  text: { type: String, default: '分享' },
  showText: { type: Boolean, default: true },
  iconSize: { type: Number, default: 16 },
  shareUrl: { type: String, default: '' },
  shareTitle: { type: String, default: '太初命理 - 探索命运的奥秘' },
  shareDesc: { type: String, default: '我发现了一个超准的命理分析平台，快来试试吧！' },
  shareImage: { type: String, default: '' },
  showPoster: { type: Boolean, default: true },
  posterTitle: { type: String, default: '我的运势分析' },
  posterDesc: { type: String, default: '今日运势指数: 85分' },
  rewardPoints: { type: Number, default: 0 }
})

const emit = defineEmits(['share', 'success'])
const analytics = useAnalytics()

const visible = ref(false)
const posterRef = ref(null)
const qrcodeCanvas = ref(null)
const showSuccess = ref(false)
const successMessage = ref('分享成功')

// 分享选项
const shareOptions = [
  { key: 'wechat', label: '微信', icon: ChatDotRound, color: '#07c160' },
  { key: 'moments', label: '朋友圈', icon: Postcard, color: '#07c160' },
  { key: 'copy', label: '复制链接', icon: Link, color: '#667eea' }
]


// 实际分享链接
const actualShareUrl = computed(() => {
  return props.shareUrl || window.location.href
})

const getShareOrigin = () => {
  try {
    return new URL(actualShareUrl.value, window.location.origin).origin
  } catch {
    return window.location.origin
  }
}

const sanitizeClientErrorMessage = (error, fallback = 'unknown') => {
  const message = typeof error?.message === 'string' ? error.message.trim() : ''
  if (!message) {
    return fallback
  }

  return message.length > 120 ? `${message.slice(0, 120)}...` : message
}

const trackShareClientError = (action, error) => {
  analytics.track('share_component_error', {
    action,
    error_type: error?.name || typeof error,
    message: sanitizeClientErrorMessage(error),
    url_origin: getShareOrigin(),
    has_share_url: Boolean(actualShareUrl.value)
  })
}

// 打开分享弹窗
const openShare = () => {

  visible.value = true
  
  // 生成二维码
  nextTick(() => {
    generateQRCode()
  })
  
  // 埋点
  analytics.trackButtonClick('open_share', { url: actualShareUrl.value })
}

// 生成二维码
const generateQRCode = async () => {
  if (!qrcodeCanvas.value) return
  
  try {
    await QRCode.toCanvas(qrcodeCanvas.value, actualShareUrl.value, {
      width: 80,
      margin: 2,
      color: {
        dark: '#333',
        light: '#fff'
      }
    })
  } catch (error) {
    trackShareClientError('generate_qrcode', error)
  }
}


// 处理分享
const handleShare = (option) => {
  switch (option.key) {
    case 'wechat':
      shareToWechat()
      break
    case 'moments':
      shareToMoments()
      break
    case 'copy':
      copyLink()
      break
  }
  
  emit('share', option.key)
}

// 分享到微信
const shareToWechat = () => {
  // 实际项目中这里调用微信SDK
  ElMessage.info('请使用微信扫一扫功能分享')
  
  analytics.track('share_wechat', {
    url: actualShareUrl.value,
    title: props.shareTitle
  })
}

// 分享到朋友圈
const shareToMoments = () => {
  ElMessage.info('请使用微信扫一扫功能分享到朋友圈')
  
  analytics.track('share_moments', {
    url: actualShareUrl.value,
    title: props.shareTitle
  })
}

// 复制链接
const copyLink = async () => {
  try {
    await navigator.clipboard.writeText(actualShareUrl.value)
    showSuccessToast('链接已复制')
    
    analytics.track('share_copy_link', {
      url: actualShareUrl.value
    })
  } catch (error) {
    trackShareClientError('copy_link', error)
    ElMessage.error('复制失败，请稍后重试')
  }
}


// 下载海报
const downloadPoster = async () => {
  if (!posterRef.value) return
  
  try {
    const canvas = await html2canvas(posterRef.value, {
      scale: 2,
      backgroundColor: null,
      useCORS: true
    })
    
    const link = document.createElement('a')
    link.download = `太初命理分享_${Date.now()}.png`
    link.href = canvas.toDataURL()
    link.click()
    
    showSuccessToast('海报已保存')
    
    analytics.track('share_download_poster', {
      url: actualShareUrl.value
    })
    
    // 发放奖励积分
    if (props.rewardPoints > 0) {
      claimReward()
    }
    
    emit('success', { type: 'poster' })
  } catch (error) {
    trackShareClientError('download_poster', error)
    ElMessage.error('保存失败，请稍后重试')
  }
}


// 领取奖励
const claimReward = async () => {
  // 实际项目中调用API
  showSuccessToast(`获得 ${props.rewardPoints} 积分奖励`)
}

// 显示成功提示
const showSuccessToast = (message) => {
  successMessage.value = message
  showSuccess.value = true
  setTimeout(() => {
    showSuccess.value = false
  }, 2000)
}
</script>

<style scoped>
.share-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  border-radius: 20px;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.3s;
  border: none;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.share-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.share-btn.is-plain {
  background: transparent;
  border: 1px solid #667eea;
  color: #667eea;
}

.share-options {
  display: flex;
  justify-content: space-around;
  padding: 20px 0;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.share-option {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  transition: transform 0.3s;
}

.share-option:hover {
  transform: translateY(-4px);
}

.share-icon {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  transition: transform 0.3s;
}

.share-option:hover .share-icon {
  transform: scale(1.1);
}

.share-label {
  font-size: 13px;
  color: #666;
}

.share-link-section {
  padding: 20px 0;
}

.link-input :deep(.el-input__wrapper) {
  border-radius: 8px 0 0 8px;
}

.link-input :deep(.el-input-group__append) {
  border-radius: 0 8px 8px 0;
  background: #667eea;
  color: white;
  border-color: #667eea;
}

.share-poster-section {
  padding-top: 20px;
  border-top: 1px solid rgba(0, 0, 0, 0.05);
}

.poster-preview {
  width: 280px;
  margin: 0 auto 16px;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
}

.poster-bg {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 24px;
  color: white;
}

.poster-header {
  text-align: center;
  margin-bottom: 20px;
}

.poster-header h3 {
  font-size: 20px;
  margin-bottom: 8px;
}

.poster-header p {
  font-size: 14px;
  opacity: 0.9;
}

.poster-content {
  background: rgba(255, 255, 255, 0.15);
  border-radius: 12px;
  padding: 30px;
  text-align: center;
  margin-bottom: 20px;
  backdrop-filter: blur(10px);
}

.default-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
}

.default-content p {
  font-size: 14px;
  opacity: 0.9;
}

.poster-footer {
  display: flex;
  align-items: center;
  gap: 16px;
  background: rgba(255, 255, 255, 0.95);
  border-radius: 12px;
  padding: 12px;
}

.qrcode-wrapper canvas {
  display: block;
  border-radius: 4px;
}

.poster-tips {
  flex: 1;
}

.poster-tips p {
  color: #666;
  font-size: 13px;
}

.poster-tips .app-name {
  color: #333;
  font-size: 16px;
  font-weight: 600;
  margin-top: 4px;
}

.download-btn {
  width: 100%;
  border-radius: 12px;
}

.share-success-toast {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  background: white;
  padding: 20px 32px;
  border-radius: 16px;
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
  display: flex;
  align-items: center;
  gap: 12px;
  z-index: 9999;
}

.fade-enter-active,
.fade-leave-active {
  transition: all 0.3s;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translate(-50%, -50%) scale(0.9);
}
</style>
