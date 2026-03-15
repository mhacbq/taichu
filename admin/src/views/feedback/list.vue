<template>
  <div class="app-container">
    <el-card class="search-form" shadow="never">
      <el-form :model="queryForm" inline>
        <el-form-item label="反馈类型">
          <el-select v-model="queryForm.type" placeholder="全部类型" clearable>
            <el-option label="问题反馈" value="bug" />
            <el-option label="功能建议" value="feature" />
            <el-option label="投诉举报" value="complaint" />
            <el-option label="其他" value="other" />
          </el-select>
        </el-form-item>
        <el-form-item label="处理状态">
          <el-select v-model="queryForm.status" placeholder="全部状态" clearable>
            <el-option label="待处理" value="pending" />
            <el-option label="处理中" value="processing" />
            <el-option label="已解决" value="resolved" />
            <el-option label="已关闭" value="closed" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">搜索</el-button>
          <el-button @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <el-card shadow="never">
      <el-table v-loading="loading" :data="feedbackList" stripe>
        <el-table-column type="index" label="#" width="50" />
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="user_id" label="用户" width="100" />
        <el-table-column prop="type" label="类型" width="100">
          <template #default="{ row }">
            <el-tag :type="getTypeType(row.type)">{{ getTypeText(row.type) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="content" label="反馈内容" min-width="250" show-overflow-tooltip />
        <el-table-column prop="contact" label="联系方式" width="120" />
        <el-table-column prop="created_at" label="提交时间" width="160" />
        <el-table-column prop="status" label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)">{{ getStatusText(row.status) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="180" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" @click="handleView(row)">查看</el-button>
            <el-button v-if="row.status !== 'resolved'" link type="success" @click="handleReply(row)">回复</el-button>
            <el-button link type="danger" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination-container">
        <el-pagination
          v-model:current-page="queryForm.page"
          v-model:page-size="queryForm.pageSize"
          :total="total"
          layout="total, sizes, prev, pager, next"
          @size-change="handleSizeChange"
          @current-change="handleCurrentChange"
        />
      </div>
    </el-card>

    <!-- 查看详情弹窗 -->
    <el-dialog v-model="detailDialog.visible" title="反馈详情" width="600px">
      <el-descriptions :column="1" border>
        <el-descriptions-item label="反馈ID">{{ detailDialog.data.id }}</el-descriptions-item>
        <el-descriptions-item label="用户ID">{{ detailDialog.data.user_id }}</el-descriptions-item>
        <el-descriptions-item label="反馈类型">
          <el-tag :type="getTypeType(detailDialog.data.type)">{{ getTypeText(detailDialog.data.type) }}</el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="联系方式">{{ detailDialog.data.contact || '-' }}</el-descriptions-item>
        <el-descriptions-item label="提交时间">{{ detailDialog.data.created_at }}</el-descriptions-item>
        <el-descriptions-item label="当前状态">
          <el-tag :type="getStatusType(detailDialog.data.status)">{{ getStatusText(detailDialog.data.status) }}</el-tag>
        </el-descriptions-item>
      </el-descriptions>
      <div style="margin-top: 20px;">
        <h4>反馈内容</h4>
        <div class="feedback-content">{{ detailDialog.data.content }}</div>
      </div>
      <div v-if="detailDialog.data.images?.length" style="margin-top: 20px;">
        <h4>附件图片</h4>
        <el-image
          v-for="(img, index) in detailDialog.data.images"
          :key="index"
          :src="img"
          style="width: 100px; height: 100px; margin-right: 10px;"
          :preview-src-list="detailDialog.data.images"
        />
      </div>
      <div v-if="detailDialog.data.reply" style="margin-top: 20px;">
        <h4>回复内容</h4>
        <div class="reply-content">{{ detailDialog.data.reply }}</div>
      </div>
    </el-dialog>

    <!-- 回复弹窗 -->
    <el-dialog v-model="replyDialog.visible" title="回复反馈" width="500px">
      <el-form :model="replyDialog.form" label-width="80px">
        <el-form-item label="回复内容">
          <el-input
            v-model="replyDialog.form.reply"
            type="textarea"
            rows="6"
            placeholder="请输入回复内容..."
          />
        </el-form-item>
        <el-form-item label="处理状态">
          <el-select v-model="replyDialog.form.status" style="width: 100%;">
            <el-option label="处理中" value="processing" />
            <el-option label="已解决" value="resolved" />
            <el-option label="已关闭" value="closed" />
          </el-select>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="replyDialog.visible = false">取消</el-button>
        <el-button type="primary" @click="submitReply">提交回复</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getFeedbackList, getFeedbackDetail, replyFeedback, deleteFeedback } from '@/api/feedback'

const loading = ref(false)
const feedbackList = ref([])
const total = ref(0)

const queryForm = reactive({
  type: '',
  status: '',
  page: 1,
  pageSize: 20
})

const detailDialog = reactive({
  visible: false,
  data: {}
})

const replyDialog = reactive({
  visible: false,
  feedbackId: null,
  form: {
    reply: '',
    status: 'resolved'
  }
})

onMounted(() => {
  loadFeedbackList()
})

async function loadFeedbackList() {
  loading.value = true
  try {
    const { data } = await getFeedbackList(queryForm)
    feedbackList.value = data.list
    total.value = data.total
  } finally {
    loading.value = false
  }
}

function handleSearch() {
  queryForm.page = 1
  loadFeedbackList()
}

function handleReset() {
  Object.assign(queryForm, {
    type: '',
    status: '',
    page: 1,
    pageSize: 20
  })
  loadFeedbackList()
}

function handleSizeChange(val) {
  queryForm.pageSize = val
  loadFeedbackList()
}

function handleCurrentChange(val) {
  queryForm.page = val
  loadFeedbackList()
}

async function handleView(row) {
  try {
    const { data } = await getFeedbackDetail(row.id)
    detailDialog.data = data
    detailDialog.visible = true
  } catch (error) {
    console.error(error)
  }
}

function handleReply(row) {
  replyDialog.feedbackId = row.id
  replyDialog.form = {
    reply: '',
    status: 'resolved'
  }
  replyDialog.visible = true
}

async function submitReply() {
  if (!replyDialog.form.reply.trim()) {
    ElMessage.warning('请输入回复内容')
    return
  }

  try {
    await replyFeedback(replyDialog.feedbackId, replyDialog.form)
    ElMessage.success('回复成功')
    replyDialog.visible = false
    loadFeedbackList()
  } catch (error) {
    console.error(error)
  }
}

async function handleDelete(row) {
  try {
    await ElMessageBox.confirm('确定删除该反馈吗？', '提示', { type: 'warning' })
    await deleteFeedback(row.id)
    ElMessage.success('删除成功')
    loadFeedbackList()
  } catch {
    // 取消删除
  }
}

function getTypeType(type) {
  const map = { bug: 'danger', feature: 'success', complaint: 'warning', other: 'info' }
  return map[type] || ''
}
function getTypeText(type) {
  const map = { bug: '问题反馈', feature: '功能建议', complaint: '投诉举报', other: '其他' }
  return map[type] || type
}
function getStatusType(status) {
  const map = { pending: 'warning', processing: 'primary', resolved: 'success', closed: 'info' }
  return map[status] || ''
}
function getStatusText(status) {
  const map = { pending: '待处理', processing: '处理中', resolved: '已解决', closed: '已关闭' }
  return map[status] || status
}
</script>

<style lang="scss" scoped>
.feedback-content {
  background: #f5f7fa;
  padding: 15px;
  border-radius: 4px;
  line-height: 1.8;
  white-space: pre-wrap;
}

.reply-content {
  background: #f0f9ff;
  padding: 15px;
  border-radius: 4px;
  line-height: 1.8;
  white-space: pre-wrap;
  border-left: 3px solid #409eff;
}
</style>
