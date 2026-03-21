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
      <template #header>
        <div class="card-header">
          <span class="card-title">反馈列表</span>
          <div class="card-actions">
            <el-select v-model="batchAction" placeholder="批量操作" style="width: 150px; margin-right: 10px;" @change="handleBatchActionChange">
              <el-option label="标记为已处理" value="resolved" />
              <el-option label="标记为处理中" value="processing" />
              <el-option label="标记为已关闭" value="closed" />
              <el-option label="批量回复" value="reply" />
            </el-select>
            <el-button type="primary" :disabled="selectedIds.length === 0" @click="executeBatchAction">执行</el-button>
          </div>
        </div>
      </template>
      <el-table v-loading="loading" :data="feedbackList" stripe @selection-change="handleSelectionChange">
        <el-table-column type="selection" width="55" />
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
        <el-table-column label="操作" width="280" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" @click="handleView(row)">查看</el-button>
            <el-button v-if="row.status !== 'resolved'" link type="success" @click="handleReply(row)">回复</el-button>
            <el-button link type="warning" @click="handleAssign(row)">分配</el-button>
            <el-button link type="info" @click="handleViewLog(row)">记录</el-button>
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

    <!-- 批量回复弹窗 -->
    <el-dialog v-model="batchReplyDialog.visible" title="批量回复反馈" width="500px">
      <el-form :model="batchReplyDialog.form" label-width="80px">
        <el-form-item label="回复模板">
          <el-select v-model="batchReplyDialog.form.template" style="width: 100%;" @change="applyReplyTemplate">
            <el-option label="自定义" value="" />
            <el-option label="感谢反馈 - 标准回复" value="thank" />
            <el-option label="问题已收到 - 等待处理" value="received" />
            <el-option label="功能建议 - 已记录" value="feature" />
          </el-select>
        </el-form-item>
        <el-form-item label="回复内容">
          <el-input
            v-model="batchReplyDialog.form.reply"
            type="textarea"
            rows="6"
            placeholder="请输入回复内容..."
          />
        </el-form-item>
        <el-form-item label="处理状态">
          <el-select v-model="batchReplyDialog.form.status" style="width: 100%;">
            <el-option label="处理中" value="processing" />
            <el-option label="已解决" value="resolved" />
            <el-option label="已关闭" value="closed" />
          </el-select>
        </el-form-item>
        <el-alert title="批量回复将应用到选中的 {{ selectedIds.length }} 条反馈" type="info" :closable="false" style="margin-bottom: 10px;" />
      </el-form>
      <template #footer>
        <el-button @click="batchReplyDialog.visible = false">取消</el-button>
        <el-button type="primary" @click="submitBatchReply">批量回复</el-button>
      </template>
    </el-dialog>

    <!-- 分配弹窗 -->
    <el-dialog v-model="assignDialog.visible" title="分配反馈" width="500px">
      <el-form :model="assignDialog.form" label-width="80px">
        <el-form-item label="分配给">
          <el-select v-model="assignDialog.form.assigned_to" style="width: 100%;" placeholder="选择管理员">
            <el-option
              v-for="admin in adminList"
              :key="admin.id"
              :label="admin.username"
              :value="admin.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="备注">
          <el-input
            v-model="assignDialog.form.note"
            type="textarea"
            rows="3"
            placeholder="分配备注（可选）"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="assignDialog.visible = false">取消</el-button>
        <el-button type="primary" @click="submitAssign">确定分配</el-button>
      </template>
    </el-dialog>

    <!-- 处理记录弹窗 -->
    <el-dialog v-model="logDialog.visible" title="处理记录" width="700px">
      <el-timeline>
        <el-timeline-item
          v-for="log in logList"
          :key="log.id"
          :timestamp="log.created_at"
          placement="top"
        >
          <el-card>
            <h4>{{ log.action_text }} - {{ log.admin?.username || '未知' }}</h4>
            <p>{{ log.content }}</p>
            <div v-if="log.old_value || log.new_value" style="margin-top: 8px; font-size: 12px; color: #909399;">
              <span v-if="log.old_value">变更前: {{ log.old_value }}</span>
              <span v-if="log.old_value && log.new_value" style="margin: 0 8px;">→</span>
              <span v-if="log.new_value">变更后: {{ log.new_value }}</span>
            </div>
          </el-card>
        </el-timeline-item>
      </el-timeline>
      <el-empty v-if="logList.length === 0" description="暂无处理记录" />
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getFeedbackList, getFeedbackDetail, replyFeedback, deleteFeedback } from '@/api/feedback'
import { reportAdminUiError } from '@/utils/dev-error'
import { useSavedFiltersStore } from '@/stores/savedFilters'

const savedFiltersStore = useSavedFiltersStore()
const PAGE_KEY = 'feedback_list'

const loading = ref(false)
const feedbackList = ref([])
const total = ref(0)
const selectedIds = ref([])
const batchAction = ref('')

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

const batchReplyDialog = reactive({
  visible: false,
  form: {
    reply: '',
    status: 'resolved',
    template: ''
  }
})

const assignDialog = reactive({
  visible: false,
  feedbackId: null,
  form: {
    assigned_to: null,
    note: ''
  }
})

const logDialog = reactive({
  visible: false,
  feedbackId: null
})

const adminList = ref([])
const logList = ref([])

const replyTemplates = {
  thank: '感谢您的反馈！我们会认真对待您的意见，并尽快处理。',
  received: '收到您的反馈，我们正在处理中，请耐心等待。如有进展会及时通知您。',
  feature: '感谢您的建议！已记录到我们的功能需求池中，将在后续版本评估实现。'
}

onMounted(() => {
  const saved = savedFiltersStore.getFilter(PAGE_KEY)
  if (saved) {
    Object.assign(queryForm, saved)
  }
  loadFeedbackList()
  loadAdminList()
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
  savedFiltersStore.saveFilter(PAGE_KEY, {
    type: queryForm.type,
    status: queryForm.status,
    pageSize: queryForm.pageSize
  })
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
    reportAdminUiError('feedback_list', 'reply_failed', error, {
      feedback_id: replyDialog.feedbackId,
      reply_status: replyDialog.form.status,
      reply_length: replyDialog.form.reply?.trim().length || 0
    })
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

function handleSelectionChange(selection) {
  selectedIds.value = selection.map(item => item.id)
}

function handleBatchActionChange(value) {
  if (value === 'reply') {
    batchReplyDialog.form = {
      reply: '',
      status: 'resolved',
      template: ''
    }
    batchReplyDialog.visible = true
  }
}

async function executeBatchAction() {
  if (selectedIds.value.length === 0) {
    ElMessage.warning('请先选择要操作的反馈')
    return
  }

  const action = batchAction.value
  if (!action) {
    ElMessage.warning('请选择批量操作类型')
    return
  }

  try {
    await ElMessageBox.confirm(
      `确定要对选中的 ${selectedIds.value.length} 条反馈执行批量操作吗？`,
      '确认操作',
      { type: 'warning' }
    )

    const statusMap = {
      resolved: '已解决',
      processing: '处理中',
      closed: '已关闭'
    }

    for (const id of selectedIds.value) {
      await replyFeedback(id, { reply: '', status: action })
    }

    ElMessage.success(`已将 ${selectedIds.value.length} 条反馈标记为${statusMap[action]}`)
    selectedIds.value = []
    batchAction.value = ''
    loadFeedbackList()
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('批量操作失败')
    }
  }
}

function applyReplyTemplate(templateKey) {
  if (templateKey && replyTemplates[templateKey]) {
    batchReplyDialog.form.reply = replyTemplates[templateKey]
  }
}

async function submitBatchReply() {
  if (!batchReplyDialog.form.reply.trim()) {
    ElMessage.warning('请输入回复内容')
    return
  }

  try {
    for (const id of selectedIds.value) {
      await replyFeedback(id, {
        reply: batchReplyDialog.form.reply,
        status: batchReplyDialog.form.status
      })
    }

    ElMessage.success(`已批量回复 ${selectedIds.value.length} 条反馈`)
    batchReplyDialog.visible = false
    selectedIds.value = []
    batchAction.value = ''
    loadFeedbackList()
  } catch (error) {
    ElMessage.error('批量回复失败')
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

async function loadAdminList() {
  try {
    const { data } = await fetch('/api/maodou/system/admin-list').then(res => res.json())
    adminList.value = data || []
  } catch (error) {
    console.error('加载管理员列表失败:', error)
  }
}

function handleAssign(row) {
  assignDialog.feedbackId = row.id
  assignDialog.form = {
    assigned_to: null,
    note: ''
  }
  assignDialog.visible = true
}

async function submitAssign() {
  if (!assignDialog.form.assigned_to) {
    ElMessage.warning('请选择要分配的管理员')
    return
  }

  try {
    await fetch('/api/maodou/feedback/assign', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        feedback_id: assignDialog.feedbackId,
        assigned_to: assignDialog.form.assigned_to,
        note: assignDialog.form.note
      })
    }).then(res => res.json())

    ElMessage.success('分配成功')
    assignDialog.visible = false
    loadFeedbackList()
  } catch (error) {
    ElMessage.error('分配失败')
  }
}

async function handleViewLog(row) {
  logDialog.feedbackId = row.id
  logDialog.visible = true
  
  try {
    const { data } = await fetch(`/api/maodou/feedback/logs?feedback_id=${row.id}`)
      .then(res => res.json())
    logList.value = data || []
  } catch (error) {
    console.error('加载处理记录失败:', error)
    logList.value = []
  }
}
</script>

<style lang="scss" scoped>
.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-title {
  font-size: 16px;
  font-weight: 500;
  color: #303133;
}

.card-actions {
  display: flex;
  align-items: center;
}

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
