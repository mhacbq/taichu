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
      width="400px"
      class="share-dialog"
      append-to-body
    >
      <div class="share-content" ref="shareCardRef">
        <div class="share-card-inner">
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
              <p>长按保存图片</p>
              <p>或分享给好友</p>
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
import { ref } from 'vue'
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
  }
})

const dialogVisible = ref(false)
const shareCardRef = ref(null)
const generating = ref(false)

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
      backgroundColor: '#ffffff'
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
  navigator.clipboard.writeText(`我在太初命理测算了【${props.title}】，结果很准！快来看看吧：${url}`).then(() => {
    ElMessage.success('链接已复制，快去分享给好友吧！')
  }).catch(() => {
    ElMessage.error('复制失败，请手动复制')
  })
}
</script>

<style scoped>
.share-btn {
  border-radius: 20px;
}

.share-content {
  padding: 20px;
  background: #f5f7fa;
  border-radius: 12px;
}

.share-card-inner {
  background: #fff;
  border-radius: 16px;
  padding: 24px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.05);
  position: relative;
  overflow: hidden;
}

.share-card-inner::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 6px;
  background: linear-gradient(90deg, #D4AF37, #F3E5AB);
}

.share-header {
  text-align: center;
  margin-bottom: 24px;
}

.share-logo {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  margin-bottom: 12px;
  color: #D4AF37;
}

.logo-icon {
  font-size: 24px;
}

.logo-text {
  font-size: 16px;
  font-weight: bold;
  letter-spacing: 2px;
}

.share-title {
  font-size: 22px;
  font-weight: bold;
  color: #333;
}

.share-body {
  margin-bottom: 30px;
}

.share-summary {
  background: rgba(212, 175, 55, 0.05);
  padding: 16px;
  border-radius: 8px;
  border-left: 4px solid #D4AF37;
  margin-bottom: 16px;
}

.summary-text {
  margin: 0;
  font-size: 15px;
  color: #666;
  line-height: 1.6;
  font-style: italic;
}

.share-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  justify-content: center;
}

.share-tag {
  padding: 4px 12px;
  background: #f0f2f5;
  color: #666;
  border-radius: 12px;
  font-size: 12px;
}

.share-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-top: 20px;
  border-top: 1px dashed #eee;
}

.qr-code-placeholder {
  width: 80px;
  height: 80px;
  background: #f5f7fa;
  border-radius: 8px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #999;
  font-size: 12px;
  gap: 4px;
}

.qr-code-placeholder .el-icon {
  font-size: 24px;
}

.share-desc {
  text-align: right;
}

.share-desc p {
  margin: 0;
  font-size: 13px;
  color: #999;
  line-height: 1.5;
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}
</style>