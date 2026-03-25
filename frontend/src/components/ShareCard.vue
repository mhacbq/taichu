<template>
  <div class="share-card-wrapper">
    <div @click="showShareDialog" class="share-trigger">
      <slot name="trigger">
        <el-button type="primary" plain class="share-btn">
          <el-icon><Share /></el-icon> 分享结果
        </el-button>
      </slot>
    </div>

    <el-dialog
      v-model="dialogVisible"
      title="分享给好友"
      width="420px"
      class="share-dialog"
      append-to-body
    >
      <div class="share-content" ref="shareCardRef">
        <div class="share-card-inner">
          <!-- 顶部金色装饰条 -->
          <div class="share-top-bar"></div>

          <div class="share-header">
            <div class="share-logo">
              <span class="logo-icon">☯</span>
              <span class="logo-text">太初命理</span>
            </div>
            <div class="share-title">{{ title }}</div>
          </div>

          <div class="share-body">
            <div class="share-summary">
              <p class="summary-text">"{{ summary }}"</p>
            </div>

            <!-- 五行分布可视化（仅八字排盘时展示） -->
            <div v-if="wuxingItems && wuxingItems.length" class="share-wuxing">
              <div class="share-wuxing__title">五行分布</div>
              <div class="share-wuxing__bars">
                <div
                  v-for="item in wuxingItems"
                  :key="item.name"
                  class="share-wx-item"
                  :class="{ 'wx-favorite': item.isFavorite, 'wx-missing': item.isMissing }"
                >
                  <div class="share-wx-label">
                    <span class="wx-name">{{ item.name }}</span>
                    <span v-if="item.badge" class="wx-badge" :class="'wx-badge--' + item.badge.type">
                      {{ item.badge.text }}
                    </span>
                  </div>
                  <div class="share-wx-bar-wrap">
                    <div
                      class="share-wx-bar-fill"
                      :class="'fill-' + item.name"
                      :style="{ width: `${item.width}%` }"
                    ></div>
                  </div>
                  <span class="share-wx-pct">{{ item.shareText }}</span>
                </div>
              </div>
              <div v-if="strengthStatus" class="share-strength">
                日主 <strong>{{ strengthStatus }}</strong>
              </div>
            </div>

            <div class="share-tags" v-if="tags && tags.length">
              <span v-for="tag in tags" :key="tag" class="share-tag">{{ tag }}</span>
            </div>
          </div>

          <div class="share-footer">
            <div class="qr-code-placeholder">
              <el-icon><FullScreen /></el-icon>
              <span>扫码测算</span>
            </div>
            <div class="share-desc">
              <p class="share-url">taichu.chat</p>
              <p>长按保存 · 分享给好友</p>
            </div>
          </div>
        </div>
      </div>

      <template #footer>
        <div class="dialog-footer">
          <el-button @click="dialogVisible = false">取消</el-button>
          <el-button type="primary" @click="generateImage" :loading="generating">
            保存为图片
          </el-button>
          <el-button type="success" @click="copyShareLink">
            复制链接
          </el-button>
        </div>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { ElMessage } from 'element-plus'
import { Share, FullScreen } from '@element-plus/icons-vue'
import html2canvas from 'html2canvas'

const props = defineProps({
  title: {
    type: String,
    required: true
  },
  summary: {
    type: String,
    required: true
  },
  tags: {
    type: Array,
    default: () => []
  },
  sharePath: {
    type: String,
    required: true
  },
  // 五行分布数据（来自 wuxingDistributionItems computed）
  wuxingItems: {
    type: Array,
    default: () => []
  }
})

const dialogVisible = ref(false)
const shareCardRef = ref(null)
const generating = ref(false)

// 从五行数据中提取日主强弱状态
const strengthStatus = computed(() => {
  return props.wuxingItems?.[0]?.strengthStatus || ''
})

const showShareDialog = () => {
  dialogVisible.value = true
}

const generateImage = async () => {
  if (!shareCardRef.value) return

  generating.value = true
  try {
    const canvas = await html2canvas(shareCardRef.value, {
      scale: 2,
      useCORS: true,
      backgroundColor: '#ffffff',
      logging: false
    })

    const link = document.createElement('a')
    link.download = `太初命理-${props.title}.png`
    link.href = canvas.toDataURL('image/png')
    link.click()

    ElMessage.success('图片已保存，快去分享吧！')
  } catch (error) {
    ElMessage.error('生成图片失败，请稍后重试')
  } finally {
    generating.value = false
  }
}

const copyShareLink = () => {
  const url = `${window.location.origin}${props.sharePath}`
  navigator.clipboard
    .writeText(`我在太初命理测算了【${props.title}】，结果很准！快来看看吧：${url}`)
    .then(() => {
      ElMessage.success('链接已复制，快去分享给好友吧！')
    })
    .catch(() => {
      ElMessage.error('复制失败，请手动复制')
    })
}
</script>

<style scoped>
.share-btn {
  border-radius: 20px;
}

.share-content {
  padding: 16px;
  background: #f5f7fa;
  border-radius: 12px;
}

.share-card-inner {
  background: #fff;
  border-radius: 16px;
  padding: 0 0 20px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.05);
  position: relative;
  overflow: hidden;
}

.share-top-bar {
  height: 6px;
  background: linear-gradient(90deg, #D4AF37, #F3E5AB, #D4AF37);
  border-radius: 16px 16px 0 0;
  margin-bottom: 20px;
}

.share-header {
  text-align: center;
  margin-bottom: 16px;
  padding: 0 20px;
}

.share-logo {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  margin-bottom: 10px;
  color: #D4AF37;
}

.logo-icon {
  font-size: 22px;
}

.logo-text {
  font-size: 15px;
  font-weight: bold;
  letter-spacing: 2px;
}

.share-title {
  font-size: 20px;
  font-weight: bold;
  color: #333;
}

.share-body {
  margin-bottom: 16px;
  padding: 0 20px;
}

.share-summary {
  background: rgba(212, 175, 55, 0.05);
  padding: 12px 14px;
  border-radius: 8px;
  border-left: 4px solid #D4AF37;
  margin-bottom: 14px;
}

.summary-text {
  margin: 0;
  font-size: 14px;
  color: #666;
  line-height: 1.6;
  font-style: italic;
}

/* 五行分布 */
.share-wuxing {
  background: rgba(212, 175, 55, 0.04);
  border: 1px solid rgba(212, 175, 55, 0.15);
  border-radius: 10px;
  padding: 12px 14px;
  margin-bottom: 14px;
}

.share-wuxing__title {
  font-size: 12px;
  color: #b8860b;
  font-weight: 600;
  margin-bottom: 10px;
  letter-spacing: 1px;
}

.share-wuxing__bars {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.share-wx-item {
  display: flex;
  align-items: center;
  gap: 8px;
}

.share-wx-label {
  width: 52px;
  display: flex;
  align-items: center;
  gap: 4px;
  flex-shrink: 0;
}

.wx-name {
  font-size: 13px;
  font-weight: 600;
  color: #444;
}

.wx-badge {
  font-size: 10px;
  padding: 1px 4px;
  border-radius: 3px;
  font-weight: 600;
}

.wx-badge--favorite {
  background: rgba(212, 175, 55, 0.2);
  color: #b8860b;
}

.wx-badge--missing {
  background: rgba(41, 128, 185, 0.12);
  color: #1a6fa8;
}

.wx-badge--dominant {
  background: rgba(192, 57, 43, 0.1);
  color: #c0392b;
}

.wx-badge--weak {
  background: rgba(127, 140, 141, 0.1);
  color: #7f8c8d;
}

.share-wx-bar-wrap {
  flex: 1;
  height: 8px;
  background: #f0f0f0;
  border-radius: 4px;
  overflow: hidden;
}

.share-wx-bar-fill {
  height: 100%;
  border-radius: 4px;
  transition: width 0.6s ease;
}

/* 五行颜色 */
.fill-金 { background: linear-gradient(90deg, #c8a84b, #f0d060); }
.fill-木 { background: linear-gradient(90deg, #27ae60, #52c41a); }
.fill-水 { background: linear-gradient(90deg, #2980b9, #40a9ff); }
.fill-火 { background: linear-gradient(90deg, #c0392b, #ff6b6b); }
.fill-土 { background: linear-gradient(90deg, #8b6914, #d4a017); }

.share-wx-item.wx-favorite .share-wx-bar-fill {
  box-shadow: 0 0 6px rgba(212, 175, 55, 0.6);
}

.share-wx-pct {
  font-size: 11px;
  color: #999;
  width: 52px;
  text-align: right;
  flex-shrink: 0;
}

.share-strength {
  margin-top: 8px;
  font-size: 12px;
  color: #888;
  text-align: right;
}

.share-strength strong {
  color: #b8860b;
}

.share-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  justify-content: center;
}

.share-tag {
  padding: 3px 10px;
  background: #f0f2f5;
  color: #666;
  border-radius: 12px;
  font-size: 12px;
}

.share-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 20px 0;
  border-top: 1px dashed #eee;
  margin: 0 0;
}

.qr-code-placeholder {
  width: 64px;
  height: 64px;
  background: #f5f7fa;
  border-radius: 8px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #999;
  font-size: 11px;
  gap: 3px;
}

.qr-code-placeholder .el-icon {
  font-size: 20px;
}

.share-desc {
  text-align: right;
}

.share-url {
  font-size: 13px;
  color: #D4AF37;
  font-weight: 600;
  margin: 0 0 2px;
}

.share-desc p {
  margin: 0;
  font-size: 12px;
  color: #999;
  line-height: 1.5;
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}
</style>
