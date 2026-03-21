<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { ElMessage } from 'element-plus'
import {
  getFeedbackDetail,
  replyFeedback
} from '../../api/admin'

const router = useRouter()
const route = useRoute()
const loading = ref(false)
const detail = ref(null)

const loadDetail = async () => {
  loading.value = true
  try {
    const response = await getFeedbackDetail(route.params.id)
    if (response.code === 200) {
      detail.value = response.data
    } else {
      ElMessage.error(response.message || '加载失败')
    }
  } catch (error) {
    console.error('加载反馈详情失败:', error)
    ElMessage.error('加载失败')
  } finally {
    loading.value = false
  }
}

const handleReply = async () => {
  try {
    const { value } = await ElMessageBox.prompt('请输入回复内容', '回复反馈', {
      inputType: 'textarea',
      inputPlaceholder: '请输入回复内容'
    })
    
    if (!value) {
      ElMessage.warning('请输入回复内容')
      return
    }
    
    const response = await replyFeedback(detail.value.id, { content: value })
    if (response.code === 200) {
      ElMessage.success('回复成功')
      loadDetail()
    } else {
      ElMessage.error(response.message || '回复失败')
    }
  } catch (error) {
    if (error !== 'cancel') {
      console.error('回复失败:', error)
      ElMessage.error('回复失败')
    }
  }
}

const goBack = () => {
  router.back()
}

onMounted(() => {
  loadDetail()
})
</script>

<template>
  <div class="admin-feedback-detail" v-loading="loading">
    <div class="page-header">
      <el-button @click="goBack">返回</el-button>
      <h2>反馈详情</h2>
    </div>

    <div v-if="detail" class="detail-container">
      <!-- 反馈信息 -->
      <el-card class="info-card">
        <template #header>
          <div class="card-header">
            <span>反馈信息</span>
            <el-tag :type="detail.status === 'pending' ? 'warning' : detail.status === 'replied' ? 'primary' : 'success'">
              {{ detail.status === 'pending' ? '待处理' : detail.status === 'replied' ? '已回复' : '已解决' }}
            </el-tag>
          </div>
        </template>
        <div class="info-grid">
          <div class="info-item">
            <label>反馈ID:</label>
            <span>{{ detail.id }}</span>
          </div>
          <div class="info-item">
            <label>用户ID:</label>
            <span>{{ detail.user_id }}</span>
          </div>
          <div class="info-item">
            <label>反馈类型:</label>
            <el-tag>{{ detail.type }}</el-tag>
          </div>
          <div class="info-item">
            <label>联系方式:</label>
            <span>{{ detail.contact || '-' }}</span>
          </div>
          <div class="info-item">
            <label>提交时间:</label>
            <span>{{ detail.created_at }}</span>
          </div>
          <div class="info-item">
            <label>状态:</label>
            <el-tag :type="detail.status_value === 'pending' ? 'warning' : detail.status_value === 'replied' ? 'primary' : 'success'">
              {{ detail.status_value === 'pending' ? '待处理' : detail.status_value === 'replied' ? '已回复' : '已解决' }}
            </el-tag>
          </div>
        </div>

        <div v-if="detail.title" class="content-section">
          <label>反馈标题:</label>
          <div class="content-text">{{ detail.title }}</div>
        </div>

        <div class="content-section">
          <label>反馈内容:</label>
          <div class="content-text">{{ detail.content }}</div>
        </div>

        <div v-if="detail.images && detail.images.length > 0" class="images-section">
          <label>附件图片:</label>
          <div class="images-grid">
            <el-image
              v-for="(img, index) in detail.images"
              :key="index"
              :src="img"
              :preview-src-list="detail.images"
              fit="cover"
              class="feedback-image"
            />
          </div>
        </div>

        <div class="actions">
          <el-button type="primary" @click="handleReply">回复</el-button>
        </div>
      </el-card>

      <!-- 回复历史 -->
      <el-card class="info-card" v-if="detail.reply || detail.replied_at">
        <template #header>
          <span>回复历史</span>
        </template>
        <div class="replies-list">
          <div class="reply-item">
            <div class="reply-header">
              <span class="reply-user">管理员回复</span>
              <span class="reply-time">{{ detail.replied_at || '-' }}</span>
            </div>
            <div class="reply-content">{{ detail.reply }}</div>
          </div>
        </div>
      </el-card>
    </div>
  </div>
</template>

<style scoped>
.admin-feedback-detail {
  padding: 24px;
}

.page-header {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 20px;
}

.detail-container {
  max-width: 1000px;
}

.info-card {
  margin-bottom: 20px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
  margin-bottom: 20px;
}

.info-item {
  display: flex;
  align-items: center;
}

.info-item label {
  width: 100px;
  color: #666;
  font-weight: 500;
}

.info-item span {
  flex: 1;
  color: #333;
}

.content-section,
.images-section {
  margin-bottom: 20px;
}

.content-section label,
.images-section label {
  display: block;
  margin-bottom: 10px;
  color: #666;
  font-weight: 500;
}

.content-text {
  padding: 16px;
  background: #f5f7fa;
  border-radius: 4px;
  line-height: 1.6;
  color: #333;
}

.images-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  gap: 12px;
}

.feedback-image {
  width: 100%;
  height: 150px;
  border-radius: 4px;
}

.actions {
  margin-top: 20px;
  text-align: right;
}

.replies-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.reply-item {
  padding: 16px;
  background: #f5f7fa;
  border-radius: 8px;
}

.reply-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 10px;
}

.reply-user {
  font-weight: 600;
  color: #333;
}

.reply-time {
  color: #999;
  font-size: 14px;
}

.reply-content {
  color: #666;
  line-height: 1.6;
}
</style>
