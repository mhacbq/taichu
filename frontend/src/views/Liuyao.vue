<template>
  <div class="liuyao-page">
    <div class="container">
      <!-- 页面标题 -->
      <div class="page-header">
        <h1 class="page-title">
          <el-icon class="title-icon"><YinYang /></el-icon>
          六爻占卜
        </h1>
        <p class="page-subtitle">传统周易六爻，为您解答心中疑惑</p>
      </div>

      <!-- 占卜结果 -->
      <div v-if="result" class="result-section">
        <div class="result-card">
          <div class="result-header">
            <h2>占卜结果</h2>
            <span v-if="result.is_first" class="first-free-badge">首次免费</span>
          </div>

          <!-- 问题 -->
          <div class="question-box">
            <span class="label">占问：</span>
            <span class="question-text">{{ result.question }}</span>
          </div>

          <!-- 卦象展示 -->
          <div class="gua-display">
            <div class="gua-info">
              <h3 class="gua-name">{{ result.gua.name }}</h3>
              <p class="gua-code">卦象代码：{{ result.gua.code }}</p>
            </div>

            <!-- 六爻图形 -->
            <div class="yao-container">
              <div v-for="(yao, index) in result.yao_result" :key="index" class="yao-line"
                :class="{ 'moving': yao == 0 || yao == 3, 'yang': yao >= 2, 'yin': yao <= 1 }">
                <span class="yao-mark">{{ getYaoMark(yao) }}</span>
                <span class="yao-bar"></span>
                <span class="yao-name">{{ result.yao_names[index] }}</span>
              </div>
            </div>
          </div>

          <!-- 卦辞 -->
          <div class="gua-ci-section">
            <h4>卦辞</h4>
            <p class="gua-ci">{{ result.gua.gua_ci }}</p>
          </div>

          <!-- 解读 -->
          <div class="interpretation-section">
            <h4>卦象解读</h4>
            <pre class="interpretation-text">{{ result.interpretation }}</pre>
          </div>

          <!-- AI分析 -->
          <div v-if="result.ai_analysis" class="ai-section">
            <h4>
              <el-icon><Magic /></el-icon>
              AI深度分析
            </h4>
            <div class="ai-content">{{ result.ai_analysis.content }}</div>
          </div>

          <!-- 消耗信息 -->
          <div class="points-info">
            <span v-if="result.points_cost > 0">消耗 {{ result.points_cost }} 积分</span>
            <span v-else>本次免费</span>
            <span>剩余 {{ result.remaining_points }} 积分</span>
          </div>

          <!-- 操作按钮 -->
          <div class="action-buttons">
            <button class="btn-secondary" @click="resetForm">
              <el-icon><RefreshRight /></el-icon> 再次占卜
            </button>
            <button class="btn-primary" @click="saveResult">
              <el-icon><Download /></el-icon> 保存结果
            </button>
          </div>
        </div>
      </div>

      <!-- 占卜表单 -->
      <div v-else class="form-section">
        <div class="form-card">
          <h2>心诚则灵</h2>
          <p class="form-tip">请静心思考您要询问的问题，问题越具体，占卜结果越准确</p>

          <div class="form-group">
            <label>您的问题 <span class="required">*</span></label>
            <textarea v-model="form.question" rows="4" placeholder="例如：
• 我最近的考试能通过吗？
• 这份工作适合我吗？
• 我和TA的感情发展如何？
• 这个项目能成功吗？" maxlength="100"></textarea>
            <span class="char-count">{{ form.question.length }}/100</span>
          </div>

          <div class="options-section">
            <label class="option-item">
              <input type="checkbox" v-model="form.useAi" />
              <span>使用AI深度分析（更准确、更详细）</span>
            </label>
          </div>

          <!-- 定价信息 -->
          <div class="pricing-info" v-if="pricing">
            <div v-if="pricing.is_first_free" class="pricing-free">
              <span><el-icon><Present /></el-icon> 首次占卜免费</span>
            </div>
            <div v-else-if="pricing.is_vip_free" class="pricing-vip">
              <span><el-icon><Trophy /></el-icon> VIP免费</span>
            </div>
            <div v-else class="pricing-normal">
              <span>本次消耗 {{ pricing.cost }} 积分</span>
            </div>
          </div>

          <button class="btn-submit" @click="submitDivination" :disabled="isLoading || !form.question.trim()">
            <div v-if="isLoading" class="loading-taiji mini"></div>
            <span v-else>
              <el-icon class="btn-icon"><YinYang /></el-icon>
              开始占卜
            </span>
          </button>

          <div class="history-link" v-if="history.length > 0">
            <a @click="showHistory = true">查看历史记录 ({{ history.length }}条)</a>
          </div>
        </div>
      </div>

      <!-- 历史记录弹窗 -->
      <div v-if="showHistory" class="modal-overlay" @click.self="showHistory = false">
        <div class="modal-content">
          <div class="modal-header">
            <h3>历史记录</h3>
            <button class="close-btn" @click="showHistory = false">
              <el-icon><Close /></el-icon>
            </button>
          </div>
          <div class="history-list">
            <div v-for="item in history" :key="item.id" class="history-item" @click="loadHistoryDetail(item)">
              <div class="history-main">
                <p class="history-question">{{ item.question }}</p>
                <p class="history-gua">{{ item.gua_name }} · {{ formatDate(item.created_at) }}</p>
              </div>
              <button class="delete-btn" @click.stop="deleteRecord(item.id)"><el-icon><Delete /></el-icon></button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getLiuyaoPricing, liuyaoDivination, getLiuyaoHistory, deleteLiuyaoRecord } from '../api'
import { YinYang, RefreshRight, Download, Delete, Magic, Present, Trophy } from '@element-plus/icons-vue'

// 表单数据
const form = reactive({
  question: '',
  useAi: true,
})

// 状态
const isLoading = ref(false)
const result = ref(null)
const pricing = ref(null)
const history = ref([])
const showHistory = ref(false)

// 爻标记
const getYaoMark = (yao) => {
  if (yao === 0) return '×' // 老阴
  if (yao === 3) return '○' // 老阳
  return '' // 少阴少阳
}

// 获取定价
const loadPricing = async () => {
  try {
    const response = await getLiuyaoPricing()
    if (response.code === 200) {
      pricing.value = response.data
    }
  } catch (error) {
    console.error('获取定价失败:', error)
    ElMessage.error('获取定价信息失败')
  }
}

// 加载历史记录
const loadHistory = async () => {
  try {
    const response = await getLiuyaoHistory({ page: 1, page_size: 50 })
    if (response.code === 200) {
      history.value = response.data.list || []
    }
  } catch (error) {
    console.error('获取历史记录失败:', error)
  }
}

// 提交占卜
const submitDivination = async () => {
  if (!form.question.trim()) {
    ElMessage.warning('请输入占卜问题')
    return
  }

  if (form.question.length < 2) {
    ElMessage.warning('问题太短了，请详细描述您的问题')
    return
  }

  isLoading.value = true
  try {
    const response = await liuyaoDivination({
      question: form.question.trim(),
      useAi: form.useAi,
    })

    if (response.code === 200) {
      result.value = response.data
      loadHistory() // 刷新历史
    } else {
      ElMessage.error(response.message)
    }
  } catch (error) {
    ElMessage.error('占卜失败，请重试')
  } finally {
    isLoading.value = false
  }
}

// 重置表单
const resetForm = () => {
  result.value = null
  form.question = ''
  loadPricing() // 重新获取定价（可能是首次了）
}

// 保存结果
const saveResult = () => {
  ElMessage.success('结果已自动保存到历史记录')
}

// 加载历史记录详情
const loadHistoryDetail = (item) => {
  result.value = {
    id: item.id,
    question: item.question,
    yao_result: item.yao_result,
    yao_names: (item.yao_result || []).map(yao => {
      const names = ['老阴', '少阴', '少阳', '老阳']
      return names[yao]
    }),
    gua: {
      name: item.gua_name,
      code: item.gua_code,
      gua_ci: item.gua_ci,
    },
    interpretation: item.interpretation,
    ai_analysis: item.ai_analysis,
    points_cost: item.consumed_points,
    remaining_points: 0, // 历史记录不显示剩余积分
  }
  showHistory.value = false
}

// 删除记录
const deleteRecord = async (id) => {
  try {
    await ElMessageBox.confirm('确定要删除这条记录吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
    })

    const response = await deleteLiuyaoRecord({ id })
    if (response.code === 200) {
      ElMessage.success('删除成功')
      loadHistory()
    } else {
      ElMessage.error(response.message)
    }
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('删除失败')
    }
  }
}

// 格式化日期
const formatDate = (dateStr) => {
  const date = new Date(dateStr)
  return date.toLocaleDateString('zh-CN')
}

// 初始化
onMounted(() => {
  loadPricing()
  loadHistory()
})
</script>

<style scoped>
.liuyao-page {
  padding: 40px 20px;
  min-height: 100vh;
}

.container {
  max-width: 700px;
  margin: 0 auto;
}

.page-header {
  text-align: center;
  margin-bottom: 40px;
}

.page-title {
  font-size: 36px;
  color: var(--text-primary);
  margin-bottom: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
}

.title-icon {
  font-size: 42px;
}

.page-subtitle {
  color: var(--text-secondary);
  font-size: 16px;
}

/* 表单样式 */
.form-card {
  background: var(--bg-card);
  backdrop-filter: blur(10px);
  border-radius: 16px;
  padding: 40px;
  border: 1px solid var(--border-light);
  box-shadow: var(--shadow-lg);
}

.form-card h2 {
  color: var(--text-primary);
  text-align: center;
  margin-bottom: 8px;
}

.form-tip {
  color: var(--text-secondary);
  text-align: center;
  font-size: 14px;
  margin-bottom: 30px;
}

.form-group {
  margin-bottom: 24px;
}

.form-group label {
  display: block;
  color: var(--text-secondary);
  margin-bottom: 10px;
  font-size: 14px;
}

.form-group label .required {
  color: var(--primary-color);
}

.form-group textarea {
  width: 100%;
  padding: 16px;
  background: var(--bg-secondary);
  border: 1px solid var(--border-color);
  border-radius: 12px;
  color: var(--text-primary);
  font-size: 15px;
  line-height: 1.6;
  resize: vertical;
  transition: all 0.3s;
  font-family: inherit;
}

.form-group textarea:focus {
  outline: none;
  border-color: var(--primary-color);
}

.form-group textarea::placeholder {
  color: var(--text-muted);
}

.char-count {
  display: block;
  text-align: right;
  color: var(--text-secondary);
  font-size: 12px;
  margin-top: 6px;
}

.options-section {
  margin: 20px 0;
}

.option-item {
  display: flex;
  align-items: center;
  gap: 10px;
  color: var(--text-secondary);
  cursor: pointer;
}

.option-item input {
  width: 18px;
  height: 18px;
  accent-color: var(--primary-color);
}

.pricing-info {
  text-align: center;
  padding: 16px;
  background: var(--bg-secondary);
  border-radius: 16px;
  margin: 20px 0;
  border: 1px solid var(--border-light);
}

.pricing-free,
.pricing-vip {
  color: var(--success-color);
  font-size: 18px;
  font-weight: 600;
}

.pricing-normal {
  color: var(--text-primary);
  font-size: 16px;
}

.btn-submit {
  width: 100%;
  padding: 18px;
  background: var(--primary-gradient);
  color: #fff;
  border: none;
  border-radius: 16px;
  font-size: 18px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.btn-submit:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-submit:not(:disabled):hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 30px var(--primary-light-40);
}

.btn-icon {
  font-size: 24px;
}

.loading {
  width: 24px;
  height: 24px;
  border: 2px solid var(--border-color);
  border-top-color: var(--primary-color);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.history-link {
  text-align: center;
  margin-top: 20px;
}

.history-link a {
  color: var(--text-secondary);
  cursor: pointer;
  text-decoration: underline;
  font-size: 14px;
}

.history-link a:hover {
  color: var(--primary-color);
}

/* 结果卡片 */
.result-section {
  margin-bottom: 40px;
}

.result-card {
  background: var(--bg-card);
  backdrop-filter: blur(10px);
  border-radius: 16px;
  padding: 32px;
  border: 1px solid var(--border-light);
  box-shadow: var(--shadow-lg);
}

.result-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.result-header h2 {
  color: var(--text-primary);
  margin: 0;
}

.first-free-badge {
  background: var(--success-gradient);
  color: #fff;
  padding: 6px 14px;
  border-radius: 20px;
  font-size: 12px;
}

.question-box {
  padding: 16px 20px;
  background: var(--bg-secondary);
  border-radius: 16px;
  margin-bottom: 24px;
  border: 1px solid var(--border-light);
}

.question-box .label {
  color: var(--primary-color);
  font-weight: 600;
}

.question-box .question-text {
  color: var(--text-primary);
  font-size: 16px;
}

/* 卦象展示 */
.gua-display {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 40px;
  background: linear-gradient(135deg, var(--bg-secondary), var(--bg-tertiary));
  border-radius: 20px;
  margin-bottom: 30px;
  border: 1px solid rgba(184, 134, 11, 0.2);
  position: relative;
  overflow: hidden;
  box-shadow: inset 0 0 30px rgba(0, 0, 0, 0.3);
}

.gua-display::before {
  content: '';
  position: absolute;
  top: 10px;
  left: 10px;
  right: 10px;
  bottom: 10px;
  border: 1px solid rgba(184, 134, 11, 0.1);
  border-radius: 15px;
  pointer-events: none;
}

/* 装饰角 */
.gua-display::after {
  content: '☯';
  position: absolute;
  bottom: -20px;
  right: -20px;
  font-size: 100px;
  color: rgba(184, 134, 11, 0.03);
  transform: rotate(-15deg);
}

.gua-info {
  text-align: center;
  z-index: 1;
}

.gua-name {
  color: var(--primary-color);
  font-size: 36px;
  margin-bottom: 12px;
  font-weight: 800;
  text-shadow: 0 4px 8px rgba(0, 0, 0, 0.5);
  letter-spacing: 4px;
  background: var(--primary-gradient);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.gua-code {
  color: var(--text-tertiary);
  font-size: 13px;
  letter-spacing: 3px;
  text-transform: uppercase;
  background: rgba(255, 255, 255, 0.05);
  padding: 4px 12px;
  border-radius: 20px;
  display: inline-block;
}

/* 六爻图形 */
.yao-container {
  display: flex;
  flex-direction: column-reverse; /* 从下往上排 */
  gap: 16px;
  z-index: 1;
  background: rgba(0, 0, 0, 0.3);
  padding: 24px 30px;
  border-radius: 16px;
  border: 1px solid rgba(184, 134, 11, 0.15);
  box-shadow: inset 0 0 20px rgba(0, 0, 0, 0.5);
}

.yao-line {
  display: flex;
  align-items: center;
  gap: 20px;
  padding: 6px 16px;
  border-radius: 10px;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  border: 1px solid transparent;
}

.yao-line:hover {
  background: rgba(184, 134, 11, 0.15);
  transform: translateX(-5px);
  border-color: rgba(184, 134, 11, 0.2);
}

.yao-line.moving {
  position: relative;
  background: rgba(184, 134, 11, 0.05);
}

.yao-line.moving::before {
  content: '';
  position: absolute;
  left: 0;
  top: 4px;
  bottom: 4px;
  width: 4px;
  background: var(--primary-gradient);
  border-radius: 4px;
  animation: pulse-border 1s ease-in-out infinite;
  box-shadow: 0 0 10px var(--primary-light);
}

@keyframes pulse-border {
  0%, 100% { opacity: 0.4; height: 40%; }
  50% { opacity: 1; height: 80%; }
}

.yao-mark {
  width: 36px;
  text-align: center;
  font-size: 24px;
  color: var(--primary-light);
  font-weight: 900;
  filter: drop-shadow(0 0 5px rgba(212, 175, 55, 0.5));
}

.yao-bar {
  width: 120px;
  height: 12px;
  border-radius: 6px;
  position: relative;
  box-shadow: 0 3px 6px rgba(0, 0, 0, 0.4);
}

.yao-line.yang .yao-bar {
  background: linear-gradient(90deg, #8B6914 0%, #D4AF37 20%, #F4E4C1 50%, #D4AF37 80%, #8B6914 100%);
  border: 1px solid rgba(184, 134, 11, 0.4);
}

.yao-line.yin .yao-bar::before,
.yao-line.yin .yao-bar::after {
  content: '';
  position: absolute;
  width: 44%;
  height: 100%;
  background: linear-gradient(90deg, #3D2F0C 0%, #8B6914 20%, #B8860B 50%, #8B6914 80%, #3D2F0C 100%);
  border-radius: 6px;
  border: 1px solid rgba(184, 134, 11, 0.3);
}

.yao-line.yin .yao-bar::after {
  right: 0;
}

.yao-name {
  color: var(--text-secondary);
  font-size: 14px;
  min-width: 60px;
  font-weight: 500;
}


/* 卦辞 */
.gua-ci-section {
  padding: 20px;
  background: var(--bg-secondary);
  border-radius: 16px;
  margin-bottom: 24px;
  border: 1px solid var(--border-light);
}

.gua-ci-section h4 {
  color: var(--primary-color);
  margin-bottom: 10px;
  font-size: 16px;
}

.gua-ci {
  color: var(--text-primary);
  line-height: 1.8;
  font-size: 15px;
  margin: 0;
}

/* 解读 */
.interpretation-section {
  margin-bottom: 24px;
}

.interpretation-section h4 {
  color: var(--text-primary);
  margin-bottom: 12px;
  font-size: 16px;
}

.interpretation-text {
  color: var(--text-secondary);
  line-height: 1.8;
  font-size: 14px;
  white-space: pre-wrap;
  margin: 0;
  font-family: inherit;
}

/* AI分析 */
.ai-section {
  margin-bottom: 24px;
  padding: 20px;
  background: var(--bg-secondary);
  border-radius: 16px;
  border-left: 4px solid var(--success-color);
}

.ai-section h4 {
  color: var(--success-color);
  margin-bottom: 12px;
  font-size: 16px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.ai-content {
  color: var(--text-secondary);
  line-height: 1.8;
  font-size: 14px;
  white-space: pre-wrap;
}

/* 积分信息 */
.points-info {
  display: flex;
  justify-content: space-between;
  padding: 16px 20px;
  background: var(--bg-secondary);
  border-radius: 16px;
  color: var(--text-secondary);
  font-size: 14px;
  margin-bottom: 24px;
  border: 1px solid var(--border-light);
}

/* 操作按钮 */
.action-buttons {
  display: flex;
  gap: 16px;
}

.btn-primary,
.btn-secondary {
  flex: 1;
  padding: 14px 24px;
  border-radius: 25px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: all 0.3s;
  border: none;
  min-height: 44px;
}

.btn-primary {
  background: var(--primary-gradient);
  color: var(--text-primary);
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px var(--primary-light-40);
}

.btn-secondary {
  background: var(--bg-secondary);
  color: var(--text-primary);
  border: 1px solid var(--border-light);
}

.btn-secondary:hover {
  background: var(--bg-hover);
}

/* 弹窗 */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}

.modal-content {
  background: var(--bg-card);
  border-radius: 16px;
  width: 100%;
  max-width: 500px;
  max-height: 80vh;
  overflow: hidden;
  border: 1px solid var(--border-light);
  box-shadow: var(--shadow-xl);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid var(--border-light);
}

.modal-header h3 {
  color: var(--text-primary);
  margin: 0;
}

.close-btn {
  background: none;
  border: none;
  color: var(--text-secondary);
  font-size: 20px;
  cursor: pointer;
  width: 44px;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s;
}

.close-btn:hover {
  color: var(--text-primary);
  background: var(--bg-hover);
  border-radius: 50%;
}

.history-list {
  max-height: 60vh;
  overflow-y: auto;
  padding: 10px;
}

.history-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 14px 16px;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.3s;
}

.history-item:hover {
  background: var(--bg-secondary);
}

.history-main {
  flex: 1;
  min-width: 0;
}

.history-question {
  color: var(--text-primary);
  margin: 0 0 6px 0;
  font-size: 14px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.history-gua {
  color: var(--text-secondary);
  margin: 0;
  font-size: 12px;
}

.delete-btn {
  background: none;
  border: none;
  color: var(--text-secondary);
  cursor: pointer;
  padding: 8px;
  font-size: 16px;
  opacity: 0;
  transition: all 0.3s;
}

.history-item:hover .delete-btn {
  opacity: 1;
}

.delete-btn:hover {
  color: var(--error-color);
}

/* 响应式 */
@media (max-width: 768px) {
  .page-title {
    font-size: 28px;
  }

  .form-card,
  .result-card {
    padding: 24px;
  }

  .gua-display {
    flex-direction: column;
    gap: 20px;
  }

  .action-buttons {
    flex-direction: column;
  }
}
</style>
