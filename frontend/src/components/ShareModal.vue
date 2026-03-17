<template>
  <el-dialog
    v-model="visible"
    title="分享结果"
    width="500px"
    class="share-modal"
    :close-on-click-modal="false"
  >
    <div class="share-content">
      <!-- 奖励提示 -->
      <div class="reward-banner" v-if="showReward">
        <span class="reward-icon">🎁</span>
        <div class="reward-text">
          <p class="reward-title">分享即可获得 <strong>+5 积分</strong></p>
          <p class="reward-sub">每天首次分享都有奖励哦</p>
        </div>
      </div>
      
      <!-- 预览卡片 -->
      <div class="preview-card" ref="previewCard">
        <div class="preview-header">
          <span class="logo-icon">☯</span>
          <span class="logo-text">太初命理</span>
        </div>
        <div class="preview-body">
          <slot name="preview">
            <!-- 默认预览内容 -->
            <div class="default-preview">
              <p class="preview-title">{{ title }}</p>
              <p class="preview-desc">{{ description }}</p>
            </div>
          </slot>
        </div>
        <div class="preview-footer">
          <p>扫码获取你的专属运势分析</p>
          <div class="qr-placeholder">
            <span>☯</span>
          </div>
        </div>
      </div>
      
      <!-- 分享选项 -->
      <div class="share-options">
        <p class="options-title">选择分享方式</p>
        <div class="options-grid">
          <button 
            v-for="option in shareOptions" 
            :key="option.type"
            class="share-btn"
            :class="option.type"
            @click="handleShare(option.type)"
            :disabled="sharing"
          >
            <span class="btn-icon">{{ option.icon }}</span>
            <span class="btn-text">{{ option.name }}</span>
          </button>
        </div>
      </div>
      
      <!-- 复制链接 -->
      <div class="copy-section">
        <div class="copy-input-wrapper">
          <input 
            type="text" 
            :value="shareLink" 
            readonly 
            class="copy-input"
            ref="linkInput"
          />
          <button 
            class="copy-btn" 
            @click="copyLink"
            :class="{ copied: copied }"
          >
            <span v-if="!copied">📋 复制链接</span>
            <span v-else>✓ 已复制</span>
          </button>
        </div>
      </div>
      
      <!-- 分享文案 -->
      <div class="share-text-section">
        <p class="section-label">分享文案（可编辑）</p>
        <textarea 
          v-model="editableText" 
          class="share-textarea"
          rows="3"
        ></textarea>
        <button class="copy-text-btn" @click="copyText">
          📋 复制文案
        </button>
      </div>
    </div>
  </el-dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { ElMessage } from 'element-plus'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  title: {
    type: String,
    default: '我的命理分析结果'
  },
  description: {
    type: String,
    default: ''
  },
  shareLink: {
    type: String,
    default: ''
  },
  shareText: {
    type: String,
    default: ''
  },
  showReward: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['update:modelValue', 'share', 'close'])

const visible = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val)
})

const editableText = ref(props.shareText)
const copied = ref(false)
const sharing = ref(false)
const linkInput = ref(null)

watch(() => props.shareText, (newVal) => {
  editableText.value = newVal
})

const shareOptions = [
  { type: 'wechat', name: '微信好友', icon: '💬' },
  { type: 'moment', name: '朋友圈', icon: '📱' },
  { type: 'qq', name: 'QQ', icon: '🐧' },
  { type: 'weibo', name: '微博', icon: '📢' }
]

const handleShare = async (type) => {
  sharing.value = true
  
  try {
    // 调用原生分享API
    if (navigator.share) {
      await navigator.share({
        title: props.title,
        text: editableText.value,
        url: props.shareLink
      })
      ElMessage.success('分享成功！')
      emit('share', { type, success: true })
    } else {
      // 如果不支持，复制文案
      await navigator.clipboard.writeText(editableText.value + '\n' + props.shareLink)
      ElMessage.success('分享内容已复制，请粘贴到' + shareOptions.find(o => o.type === type)?.name)
    }
  } catch (error) {
    // 用户取消分享
    console.log('分享取消')
  } finally {
    sharing.value = false
  }
}

const copyLink = async () => {
  try {
    await navigator.clipboard.writeText(props.shareLink)
    copied.value = true
    ElMessage.success('链接已复制')
    
    setTimeout(() => {
      copied.value = false
    }, 2000)
  } catch (error) {
    // 降级方案
    linkInput.value.select()
    document.execCommand('copy')
    ElMessage.success('链接已复制')
  }
}

const copyText = async () => {
  try {
    await navigator.clipboard.writeText(editableText.value)
    ElMessage.success('文案已复制')
  } catch (error) {
    ElMessage.error('复制失败')
  }
}
</script>

<style scoped>
.share-modal :deep(.el-dialog__header) {
  text-align: center;
}

.share-modal :deep(.el-dialog__title) {
  font-size: 20px;
  font-weight: bold;
  color: #fff;
}

.share-content {
  padding: 10px;
}

/* 奖励提示 */
.reward-banner {
  background: linear-gradient(135deg, rgba(255, 215, 0, 0.2), rgba(255, 193, 7, 0.1));
  border: 1px solid rgba(255, 215, 0, 0.3);
  border-radius: 12px;
  padding: 15px 20px;
  display: flex;
  align-items: center;
  gap: 15px;
  margin-bottom: 20px;
}

.reward-icon {
  font-size: 36px;
}

.reward-title {
  color: #fff;
  font-size: 16px;
  margin-bottom: 4px;
}

.reward-title strong {
  color: #ffd700;
  font-size: 18px;
}

.reward-sub {
  color: rgba(255, 255, 255, 0.6);
  font-size: 13px;
}

/* 预览卡片 */
.preview-card {
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
  border-radius: 16px;
  padding: 25px;
  margin-bottom: 25px;
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.preview-header {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 20px;
  padding-bottom: 15px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.logo-icon {
  font-size: 28px;
  color: #ffd700;
}

.logo-text {
  font-size: 18px;
  font-weight: bold;
  color: #fff;
}

.preview-body {
  margin-bottom: 20px;
}

.default-preview {
  text-align: center;
}

.preview-title {
  font-size: 20px;
  font-weight: bold;
  color: #fff;
  margin-bottom: 10px;
}

.preview-desc {
  font-size: 14px;
  color: rgba(255, 255, 255, 0.7);
  line-height: 1.6;
}

.preview-footer {
  text-align: center;
  padding-top: 15px;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.preview-footer p {
  font-size: 12px;
  color: rgba(255, 255, 255, 0.5);
  margin-bottom: 10px;
}

.qr-placeholder {
  width: 80px;
  height: 80px;
  background: #fff;
  border-radius: 8px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 40px;
}

/* 分享选项 */
.share-options {
  margin-bottom: 25px;
}

.options-title {
  font-size: 14px;
  color: rgba(255, 255, 255, 0.6);
  margin-bottom: 15px;
  text-align: center;
}

.options-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 15px;
}

.share-btn {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 15px 10px;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.share-btn:hover {
  background: rgba(255, 215, 0, 0.1);
  border-color: rgba(255, 215, 0, 0.3);
  transform: translateY(-3px);
}

.share-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-icon {
  font-size: 28px;
}

.btn-text {
  font-size: 13px;
  color: rgba(255, 255, 255, 0.8);
}

/* 复制链接 */
.copy-section {
  margin-bottom: 20px;
}

.copy-input-wrapper {
  display: flex;
  gap: 10px;
}

.copy-input {
  flex: 1;
  padding: 12px 15px;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  color: rgba(255, 255, 255, 0.8);
  font-size: 14px;
}

.copy-btn {
  padding: 12px 20px;
  background: linear-gradient(135deg, #ffd700, #ffc107);
  border: none;
  border-radius: 8px;
  color: #fff;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.3s ease;
  white-space: nowrap;
}

.copy-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(255, 215, 0, 0.3);
}

.copy-btn.copied {
  background: linear-gradient(135deg, #67c23a, #85ce61);
}

/* 分享文案 */
.share-text-section {
  background: rgba(255, 255, 255, 0.03);
  border-radius: 12px;
  padding: 15px;
}

.section-label {
  font-size: 13px;
  color: rgba(255, 255, 255, 0.6);
  margin-bottom: 10px;
}

.share-textarea {
  width: 100%;
  padding: 12px;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  color: rgba(255, 255, 255, 0.9);
  font-size: 14px;
  line-height: 1.6;
  resize: vertical;
  margin-bottom: 10px;
}

.share-textarea:focus {
  outline: none;
  border-color: rgba(255, 215, 0, 0.5);
}

.copy-text-btn {
  padding: 8px 16px;
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 6px;
  color: rgba(255, 255, 255, 0.8);
  font-size: 13px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.copy-text-btn:hover {
  background: rgba(255, 215, 0, 0.2);
  border-color: rgba(255, 215, 0, 0.3);
}

@media (max-width: 480px) {
  .options-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .copy-input-wrapper {
    flex-direction: column;
  }
}
</style>
