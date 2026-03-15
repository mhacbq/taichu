<template>
  <!-- 页面底部固定声明 -->
  <footer v-if="type === 'footer'" class="legal-footer-fixed">
    <div class="disclaimer-content">
      <p class="disclaimer-title">📢 免责声明</p>
      <p>本网站所有内容仅供娱乐参考，不构成任何专业建议或决策依据</p>
      <p>测试结果基于传统文化和概率算法生成，不具有科学依据，请理性对待</p>
      <div class="legal-links">
        <router-link to="/terms">用户协议</router-link>
        <span class="divider">|</span>
        <router-link to="/privacy">隐私政策</router-link>
        <span class="divider">|</span>
        <span>© 2024 太初文化</span>
      </div>
    </div>
  </footer>

  <!-- 测试结果页声明 -->
  <div v-else-if="type === 'result'" class="result-disclaimer">
    <div class="disclaimer-header" @click="expanded = !expanded">
      <span class="disclaimer-icon">📋</span>
      <span class="disclaimer-title">测试说明</span>
      <span class="expand-icon" :class="{ expanded }">{{ expanded ? '▼' : '▶' }}</span>
    </div>
    <div v-show="expanded" class="disclaimer-body">
      <ul>
        <li>本测试结果基于传统文化娱乐算法生成</li>
        <li>结果仅供娱乐参考，不代表任何预测或承诺</li>
        <li>请理性看待，切勿过度解读</li>
        <li>人生道路由自己把握，测试结果不构成决策依据</li>
      </ul>
      <p class="disclaimer-notice">我们尊重科学，倡导积极健康的生活态度 💪</p>
    </div>
  </div>

  <!-- 弹窗声明 -->
  <div v-else-if="type === 'modal'" class="modal-disclaimer-overlay" @click.self="close">
    <div class="modal-disclaimer">
      <div class="modal-header">
        <h3>⚠️ 重要提示</h3>
        <button class="close-btn" @click="close">×</button>
      </div>
      <div class="modal-body">
        <div class="warning-icon">📢</div>
        <p class="warning-text">在使用本服务前，请您了解：</p>
        <ul class="warning-list">
          <li>本服务仅供<strong>娱乐参考</strong>，不具有预测功能</li>
          <li>测试结果基于算法随机生成，不代表真实情况</li>
          <li>请勿将结果作为人生重大决策的依据</li>
          <li>我们倡导科学精神，反对封建迷信</li>
        </ul>
        <div class="agreement">
          <label class="checkbox-label">
            <input type="checkbox" v-model="agreed">
            <span>我已阅读并理解上述提示</span>
          </label>
        </div>
      </div>
      <div class="modal-footer">
        <button 
          class="btn btn-primary" 
          :disabled="!agreed"
          @click="confirm"
        >
          我知道了
        </button>
      </div>
    </div>
  </div>

  <!-- 简约内联声明 -->
  <div v-else class="inline-disclaimer">
    <span class="inline-icon">ℹ️</span>
    <span class="inline-text">{{ text || '本内容仅供娱乐参考' }}</span>
  </div>
</template>

<script>
import { ref } from 'vue'

export default {
  name: 'LegalDisclaimer',
  props: {
    type: {
      type: String,
      default: 'inline',
      validator: (val) => ['footer', 'result', 'modal', 'inline'].includes(val)
    },
    text: {
      type: String,
      default: ''
    }
  },
  emits: ['confirm', 'close'],
  setup(props, { emit }) {
    const expanded = ref(false)
    const agreed = ref(false)

    const close = () => {
      emit('close')
    }

    const confirm = () => {
      if (agreed.value) {
        emit('confirm')
      }
    }

    return {
      expanded,
      agreed,
      close,
      confirm
    }
  }
}
</script>

<style scoped>
/* 页面底部固定声明 */
.legal-footer-fixed {
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
  color: rgba(255, 255, 255, 0.7);
  padding: 24px 20px;
  text-align: center;
  font-size: 12px;
  line-height: 1.8;
}

.disclaimer-content {
  max-width: 800px;
  margin: 0 auto;
}

.disclaimer-title {
  font-size: 14px;
  font-weight: 600;
  color: #fbbf24;
  margin-bottom: 8px;
}

.legal-footer-fixed p {
  margin: 4px 0;
}

.legal-links {
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.legal-links a {
  color: rgba(255, 255, 255, 0.9);
  text-decoration: none;
  transition: color 0.2s;
}

.legal-links a:hover {
  color: #8B5CF6;
}

.divider {
  margin: 0 12px;
  opacity: 0.3;
}

/* 测试结果页声明 */
.result-disclaimer {
  background: #f6ffed;
  border: 1px solid #b7eb8f;
  border-radius: 12px;
  margin: 20px 0;
  overflow: hidden;
}

.disclaimer-header {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 16px;
  cursor: pointer;
  background: rgba(183, 235, 143, 0.2);
  transition: background 0.2s;
}

.disclaimer-header:hover {
  background: rgba(183, 235, 143, 0.3);
}

.disclaimer-icon {
  font-size: 16px;
}

.disclaimer-title {
  flex: 1;
  font-size: 14px;
  font-weight: 500;
  color: #389e0d;
}

.expand-icon {
  font-size: 12px;
  color: #389e0d;
  transition: transform 0.2s;
}

.expand-icon.expanded {
  transform: rotate(180deg);
}

.disclaimer-body {
  padding: 16px;
}

.disclaimer-body ul {
  margin: 0;
  padding-left: 20px;
}

.disclaimer-body li {
  font-size: 13px;
  color: #555;
  line-height: 1.8;
  margin-bottom: 6px;
}

.disclaimer-notice {
  margin-top: 12px;
  padding-top: 12px;
  border-top: 1px dashed #b7eb8f;
  font-size: 13px;
  color: #389e0d;
  font-weight: 500;
}

/* 弹窗声明 */
.modal-disclaimer-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 20px;
}

.modal-disclaimer {
  background: white;
  border-radius: 20px;
  max-width: 420px;
  width: 100%;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
  overflow: hidden;
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 24px;
  background: linear-gradient(135deg, #fff7e6 0%, #fff1d6 100%);
  border-bottom: 1px solid #ffd591;
}

.modal-header h3 {
  margin: 0;
  font-size: 18px;
  color: #d48806;
}

.close-btn {
  width: 32px;
  height: 32px;
  border: none;
  background: rgba(0, 0, 0, 0.05);
  border-radius: 50%;
  font-size: 20px;
  color: #666;
  cursor: pointer;
  transition: all 0.2s;
}

.close-btn:hover {
  background: rgba(0, 0, 0, 0.1);
}

.modal-body {
  padding: 24px;
}

.warning-icon {
  font-size: 48px;
  text-align: center;
  margin-bottom: 16px;
}

.warning-text {
  text-align: center;
  font-size: 15px;
  color: #333;
  font-weight: 500;
  margin-bottom: 16px;
}

.warning-list {
  margin: 0 0 20px;
  padding-left: 20px;
}

.warning-list li {
  font-size: 14px;
  color: #555;
  line-height: 1.8;
  margin-bottom: 8px;
}

.agreement {
  padding: 16px;
  background: #f5f5f5;
  border-radius: 8px;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
  font-size: 14px;
  color: #333;
}

.checkbox-label input[type="checkbox"] {
  width: 18px;
  height: 18px;
  cursor: pointer;
}

.modal-footer {
  padding: 16px 24px 24px;
}

.btn {
  width: 100%;
  padding: 14px 24px;
  border: none;
  border-radius: 10px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-primary {
  background: linear-gradient(135deg, #8B5CF6 0%, #7C3AED 100%);
  color: white;
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
}

.btn-primary:disabled {
  background: #d9d9d9;
  cursor: not-allowed;
}

/* 简约内联声明 */
.inline-disclaimer {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  background: #f5f5f5;
  border-radius: 6px;
  font-size: 12px;
  color: #888;
}

.inline-icon {
  font-size: 14px;
}

.inline-text {
  font-size: 12px;
}

/* 动画 */
.modal-disclaimer-overlay {
  animation: fadeIn 0.3s ease;
}

.modal-disclaimer {
  animation: slideUp 0.3s ease;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
