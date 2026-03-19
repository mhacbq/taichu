<template>
  <div class="feedback-page">
    <div class="page-header">
      <h2>用户反馈</h2>
      <el-badge :value="pendingCount" :max="99" :hidden="!pendingCount">
        <span class="pending-tip">待处理</span>
      </el-badge>
    </div>

    <!-- 筛选栏 -->
    <el-card class="filter-card" shadow="never">
      <el-form :model="filters" inline>
        <el-form-item label="关键词">
          <el-input v-model="filters.keyword" placeholder="标题/内容" clearable style="width:160px" @keyup.enter="handleSearch" />
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="filters.status" placeholder="全部" clearable style="width:110px">
            <el-option label="待处理" :value="0" />
            <el-option label="处理中" :value="1" />
            <el-option label="已解决" :value="2" />
            <el-option label="已关闭" :value="3" />
          </el-select>
        </el-form-item>
        <el-form-item label="类型">
          <el-select v-model="filters.category" placeholder="全部" clearable style="width:110px">
            <el-option label="功能建议" value="suggestion" />
            <el-option label="Bug反馈" value="bug" />
            <el-option label="投诉" value="complaint" />
            <el-option label="其他" value="other" />
          </el-select>
        </el-form-item>
        <el-form-item label="时间">
          <el-date-picker
            v-model="filters.dateRange"
            type="daterange"
            range-separator="至"
            start-placeholder="开始"
            end-placeholder="结束"
            value-format="YYYY-MM-DD"
            style="width:230px"
          />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" :loading="loading" @click="handleSearch">查询</el-button>
          <el-button @click="resetFilters">重置</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- 数据表格 -->
    <el-card shadow="never">
      <el-table :data="list" v-loading="loading" stripe @row-click="viewDetail">
        <el-table-column type="index" width="55" label="#" />
        <el-table-column prop="id" label="ID" width="70" />
        <el-table-column label="用户" width="130">
          <template #default="{ row }">
            <div>{{ row.nickname || row.username || '匿名' }}</div>
            <div class="text-small text-gray">{{ row.phone || '' }}</div>
          </template>
        </el-table-column>
        <el-table-column label="类型" width="90" align="center">
          <template #default="{ row }">
            <el-tag :type="getCategoryType(row.category)" size="small">{{ getCategoryLabel(row.category) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="title" label="标题" min-width="180" show-overflow-tooltip />
        <el-table-column label="内容" min-width="200" show-overflow-tooltip>
          <template #default="{ row }">
            <span class="text-gray">{{ row.content }}</span>
          </template>
        </el-table-column>
        <el-table-column label="状态" width="90" align="center">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)" size="small">{{ getStatusLabel(row.status) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="提交时间" width="155" />
        <el-table-column label="操作" width="140" align="center" fixed="right">
          <template #default="{ row }">
            <el-button text size="small" type="primary" @click.stop="viewDetail(row)">详情</el-button>
            <el-button
              text
              size="small"
              type="success"
              v-if="row.status === 0"
              @click.stop="handleReply(row)"
            >回复</el-button>
            <el-button
              text
              size="small"
              type="danger"
              @click.stop="handleDelete(row)"
            >删除</el-button>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination-wrap">
        <el-pagination
          v-model:current-page="pagination.page"
          v-model:page-size="pagination.pageSize"
          :page-sizes="[20, 50, 100]"
          :total="pagination.total"
          layout="total, sizes, prev, pager, next"
          @size-change="fetchList"
          @current-change="fetchList"
        />
      </div>
    </el-card>

    <!-- 详情/回复弹窗 -->
    <el-dialog v-model="detailVisible" :title="`反馈详情 #${currentItem?.id}`" width="620px">
      <template v-if="currentItem">
        <el-descriptions :column="2" border>
          <el-descriptions-item label="用户">{{ currentItem.nickname || currentItem.username }}</el-descriptions-item>
          <el-descriptions-item label="手机">{{ currentItem.phone || '—' }}</el-descriptions-item>
          <el-descriptions-item label="类型">{{ getCategoryLabel(currentItem.category) }}</el-descriptions-item>
          <el-descriptions-item label="状态">
            <el-tag :type="getStatusType(currentItem.status)" size="small">{{ getStatusLabel(currentItem.status) }}</el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="标题" :span="2">{{ currentItem.title }}</el-descriptions-item>
          <el-descriptions-item label="内容" :span="2">{{ currentItem.content }}</el-descriptions-item>
          <el-descriptions-item label="提交时间" :span="2">{{ currentItem.created_at }}</el-descriptions-item>
          <el-descriptions-item label="管理员回复" :span="2">{{ currentItem.reply || '暂无回复' }}</el-descriptions-item>
        </el-descriptions>

        <div class="reply-box" v-if="currentItem.status !== 2 && currentItem.status !== 3">
          <el-divider>回复</el-divider>
          <el-input
            v-model="replyContent"
            type="textarea"
            :rows="4"
            placeholder="输入回复内容..."
          />
          <div class="reply-actions">
            <el-select v-model="replyStatus" style="width:110px; margin-right:8px">
              <el-option label="处理中" :value="1" />
              <el-option label="已解决" :value="2" />
              <el-option label="已关闭" :value="3" />
            </el-select>
            <el-button type="primary" :loading="replying" @click="submitReply">提交回复</el-button>
          </div>
        </div>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getFeedbackList, getFeedbackDetail, replyFeedback, updateFeedbackStatus, deleteFeedback } from '@/api/admin'

const loading = ref(false)
const replying = ref(false)
const list = ref([])
const pendingCount = ref(0)
const detailVisible = ref(false)
const currentItem = ref(null)
const replyContent = ref('')
const replyStatus = ref(2)

const filters = reactive({
  keyword: '',
  status: '',
  category: '',
  dateRange: []
})

const pagination = reactive({
  page: 1,
  pageSize: 20,
  total: 0
})

const statusMap = {
  0: { label: '待处理', type: 'danger' },
  1: { label: '处理中', type: 'warning' },
  2: { label: '已解决', type: 'success' },
  3: { label: '已关闭', type: 'info' }
}

const categoryMap = {
  suggestion: { label: '功能建议', type: 'primary' },
  bug: { label: 'Bug', type: 'danger' },
  complaint: { label: '投诉', type: 'warning' },
  other: { label: '其他', type: 'info' }
}

function getStatusLabel(s) { return statusMap[s]?.label ?? '未知' }
function getStatusType(s) { return statusMap[s]?.type ?? 'info' }
function getCategoryLabel(c) { return categoryMap[c]?.label ?? c ?? '其他' }
function getCategoryType(c) { return categoryMap[c]?.type ?? 'info' }

async function fetchList() {
  loading.value = true
  try {
    const params = {
      page: pagination.page,
      page_size: pagination.pageSize,
      keyword: filters.keyword || undefined,
      status: filters.status !== '' ? filters.status : undefined,
      category: filters.category || undefined,
      start_date: filters.dateRange?.[0] || undefined,
      end_date: filters.dateRange?.[1] || undefined
    }
    const res = await getFeedbackList(params)
    const data = res?.data ?? res
    list.value = data?.list ?? data?.data ?? []
    pagination.total = data?.total ?? 0
    pendingCount.value = data?.pending_count ?? 0
  } catch {
    ElMessage.error('获取反馈列表失败')
  } finally {
    loading.value = false
  }
}

function handleSearch() {
  pagination.page = 1
  fetchList()
}

function resetFilters() {
  Object.assign(filters, { keyword: '', status: '', category: '', dateRange: [] })
  handleSearch()
}

async function viewDetail(row) {
  try {
    const res = await getFeedbackDetail(row.id)
    currentItem.value = res?.data ?? res
    replyContent.value = ''
    replyStatus.value = 2
    detailVisible.value = true
  } catch {
    ElMessage.error('获取详情失败')
  }
}

async function handleReply(row) {
  await viewDetail(row)
}

async function submitReply() {
  if (!replyContent.value.trim()) {
    ElMessage.warning('请输入回复内容')
    return
  }
  replying.value = true
  try {
    await replyFeedback(currentItem.value.id, replyContent.value)
    await updateFeedbackStatus(currentItem.value.id, replyStatus.value)
    ElMessage.success('回复成功')
    detailVisible.value = false
    fetchList()
  } catch {
    ElMessage.error('回复失败')
  } finally {
    replying.value = false
  }
}

async function handleDelete(row) {
  await ElMessageBox.confirm(`确认删除反馈 #${row.id}？`, '确认', { type: 'warning' })
  try {
    await deleteFeedback(row.id)
    ElMessage.success('删除成功')
    fetchList()
  } catch {
    ElMessage.error('删除失败')
  }
}

onMounted(fetchList)
</script>

<style scoped>
.feedback-page { padding: 20px; }
.page-header { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
.page-header h2 { margin: 0; font-size: 20px; }
.pending-tip { font-size: 13px; color: #f56c6c; }
.filter-card { margin-bottom: 16px; }
.filter-card :deep(.el-card__body) { padding: 16px 16px 0; }
.pagination-wrap { display: flex; justify-content: flex-end; margin-top: 16px; }
.reply-box { margin-top: 16px; }
.reply-actions { display: flex; justify-content: flex-end; margin-top: 12px; }
.text-small { font-size: 12px; }
.text-gray { color: #909399; }
:deep(.el-table__row) { cursor: pointer; }
</style>
