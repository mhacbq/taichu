<template>
  <el-dialog
    v-model="visible"
    title="开启每日运势推送 🌅"
    width="450px"
    class="push-modal"
    :close-on-click-modal="false"
  >
    <div class="push-content">
      <!-- 预览卡片 -->
      <div class="preview-section">
        <div class="phone-preview">
          <div class="phone-header">
            <span class="time">08:00</span>
          </div>
          <div class="notification-preview">
            <div class="app-icon">☯</div>
            <div class="notification-content">
              <p class="app-name">太初命理</p>
              <p class="notification-title">今日运势已送达 ✨</p>
              <p class="notification-text">今日综合运势85分，事业运佳，适合推进重要项目...</p>
            </div>
          </div>
        </div>
      </div>
      
      <!-- 功能说明 -->
      <div class="features-section">
        <h4>您将收到：</h4>
        <ul class="feature-list">
          <li class="feature-item">
            <span class="feature-icon">🌅</span>
            <div class="feature-text">
              <strong>每日早晨推送</strong>
              <span>每天早上8点，开启一天的好运</span>
            </div>
          </li>
          <li class="feature-item">
            <span class="feature-icon">🔮</span>
            <div class="feature-text">
              <strong>个性化运势</strong>
              <span>基于您的八字，专属定制</span>
            </div>
          </li>
          <li class="feature-item">
            <span class="feature-icon">💡</span>
            <div class="feature-text">
              <strong>贴心提醒</strong>
              <span>吉日提醒、注意事项，趋吉避凶</span>
            </div>
          </li>
        </ul>
      </div>
      
      <!-- 隐私说明 -->
      <div class="privacy-notice">
        <span class="privacy-icon">🔒</span>
        <p>我们尊重您的隐私，推送内容仅与您相关，不会打扰您的休息时间</p>
      </div>
    </div>
    
    <template #footer>
      <div class="dialog-footer">
        <el-button @click="handleLater" class="footer-btn secondary">
          稍后再说
        </el-button>
        <el-button 
          type="primary" 
          @click="handleEnable"
          class="footer-btn primary"
          :loading="enabling"
        >
          开启推送
        </el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, computed } from 'vue'
import { ElMessage } from 'element-plus'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:modelValue', 'enable', 'later'])

const visible = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val)
})

const enabling = ref(false)

const handleEnable = async () => {
  enabling.value = true
  
  try {
    // 请求推送权限
    if ('Notification' in window) {
      const permission = await Notification.requestPermission()
      
      if (permission === 'granted') {
        // 保存用户偏好
        localStorage.setItem('pushEnabled', 'true')
        localStorage.setItem('pushTime', '08:00')
        
        // 显示成功消息
        new Notification('太初命理', {
          body: '推送已开启！明天早上8点见 ✨',
          icon: '/favicon.ico'
        })
        
        ElMessage.success('推送已开启')
        emit('enable')
        visible.value = false
      } else {
        ElMessage.warning('需要您授权才能发送推送')
      }
    } else {
      ElMessage.warning('您的浏览器不支持推送功能')
    }
  } catch (error) {
    console.error('开启推送失败:', error)
    ElMessage.error('开启失败，请稍后重试')
  } finally {
    enabling.value = false
  }
}

const handleLater = () => {
  // 记录用户选择稍后再说的时间
  localStorage.setItem('pushPromptLater', Date.now().toString())
  emit('later')
  visible.value = false
}
</script>

<style scoped>
.push-modal :deep(.el-dialog__header) {
  text-align: center;
  padding-bottom: 10px;
}

.push-modal :deep(.el-dialog__title) {
  font-size: 20px;
  font-weight: bold;
  color: var(--text-primary);
}

.push-content {
  padding: 10px 0;
}

/* 预览区域 */
.preview-section {
  margin-bottom: 25px;
}

.phone-preview {
  background: var(--bg-primary);
  border-radius: 20px;
  padding: 15px;
  max-width: 280px;
  margin: 0 auto;
  border: 2px solid var(--border-color);
}

.phone-header {
  text-align: center;
  margin-bottom: 15px;
}

.time {
  font-size: 12px;
  color: var(--text-tertiary);
}

.notification-preview {
  background: var(--bg-card);
  border-radius: 12px;
  padding: 12px;
  display: flex;
  gap: 12px;
}

.app-icon {
  width: 40px;
  height: 40px;
  background: linear-gradient(135deg, #ffd700, #ffc107);
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  flex-shrink: 0;
}

.notification-content {
  flex: 1;
  min-width: 0;
}

.app-name {
  font-size: 12px;
  color: var(--text-secondary);
  margin-bottom: 2px;
}

.notification-title {
  font-size: 14px;
  font-weight: bold;
  color: var(--text-primary);
  margin-bottom: 4px;
}

.notification-text {
  font-size: 12px;
  color: var(--text-secondary);
  line-height: 1.4;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

/* 功能说明 */
.features-section {
  background: var(--bg-secondary);
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 20px;
}

.features-section h4 {
  color: var(--text-primary);
  font-size: 15px;
  margin-bottom: 15px;
}

.feature-list {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.feature-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
}

.feature-icon {
  font-size: 24px;
  flex-shrink: 0;
}

.feature-text {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.feature-text strong {
  color: var(--text-primary);
  font-size: 14px;
}

.feature-text span {
  color: var(--text-secondary);
  font-size: 13px;
}

/* 隐私说明 */
.privacy-notice {
  display: flex;
  align-items: center;
  gap: 10px;
  background: linear-gradient(135deg, rgba(103, 194, 58, 0.1), rgba(133, 206, 97, 0.05));
  border: 1px solid rgba(103, 194, 58, 0.2);
  border-radius: 10px;
  padding: 12px 15px;
}

.privacy-icon {
  font-size: 20px;
  flex-shrink: 0;
}

.privacy-notice p {
  color: rgba(255, 255, 255, 0.7);
  font-size: 13px;
  line-height: 1.5;
  margin: 0;
}

/* 底部按钮 */
.dialog-footer {
  display: flex;
  justify-content: center;
  gap: 15px;
}

.footer-btn {
  min-width: 120px;
  padding: 12px 24px;
  font-size: 15px;
}

.footer-btn.primary {
  background: linear-gradient(135deg, #ffd700, #ffc107);
  border: none;
}

.footer-btn.primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 20px rgba(255, 215, 0, 0.4);
}

.footer-btn.secondary {
  background: var(--bg-secondary);
  border: 1px solid var(--border-color);
  color: var(--text-secondary);
}

@media (max-width: 480px) {
  .dialog-footer {
    flex-direction: column;
  }
  
  .footer-btn {
    width: 100%;
  }
}
</style>
