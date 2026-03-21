<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { useUserStore } from '@/stores/user'
import { getFeedbackList, getFeedbackDetail, deleteFeedback } from '@/api/feedback'

const router = useRouter()
const userStore = useUserStore()

const loading = ref(false)
const pageLoading = ref(false)
const feedbackList = ref([])
const pagination = ref({
  current: 1,
  pageSize: 20,
  total: 0
})

const detailDialogVisible = ref(false)
const currentFeedback = ref(null)

onMounted(() => {
  fetchFeedbackList()
})

async function fetchFeedbackList() {
  loading.value = true
  try {
    const res = await getFeedbackList({
      page: pagination.value.current,
      page_size: pagination.value.pageSize
    })
    if (res.code === 200) {
      feedbackList.value = res.data.list || []
      pagination.value.total = res.data.total || 0
    } else {
      ElMessage.error(res.message || '获取反馈列表失败')
    }
  } catch (error) {
    ElMessage.error('获取反馈列表失败')
  } finally {
    loading.value = false
  }
}

async function handleView(row) {
  pageLoading.value = true
  try {
    const res = await getFeedbackDetail(row.id)
    if (res.code === 200) {
      currentFeedback.value = res.data
      detailDialogVisible.value = true
    } else {
      ElMessage.error(res.message || '获取反馈详情失败')
    }
  } catch (error) {
    ElMessage.error('获取反馈详情失败')
  } finally {
    pageLoading.value = false
  }
}

async function handleDelete(row) {
  try {
    await ElMessageBox.confirm('确定删除这条反馈吗？', '提示', {
      type: 'warning'
    })
    const res = await deleteFeedback(row.id)
    if (res.code === 200) {
      ElMessage.success('删除成功')
      fetchFeedbackList()
    } else {
      ElMessage.error(res.message || '删除失败')
    }
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('删除失败')
    }
  }
}

function handlePageChange(page) {
  pagination.value.current = page
  fetchFeedbackList()
}

function handlePageSizeChange(size) {
  pagination.value.pageSize = size
  pagination.value.current = 1
  fetchFeedbackList()
}

function formatDate(dateStr) {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleString('zh-CN')
}

function getStatusType(status) {
  const types = {
    pending: 'warning',
    processing: 'primary',
    resolved: 'success',
    closed: 'info'
  }
  return types[status] || 'info'
}

function getStatusText(status) {
  const texts = {
    pending: '待处理',
    processing: '处理中',
    resolved: '已解决',
    closed: '已关闭'
  }
  return texts[status] || status
}
</script>

<template>
  <div class="feedback-list">
    <el-card v-loading="loading">
      <template #header>
        <div class="card-header">
          <h3>用户反馈列表</h3>
        </div>
      </template>

      <el-table :data="feedbackList" border stripe>
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="type" label="类型" width="100">
          <template #default="{ row }">
            {{ row.type || '-' }}
          </template>
        </el-table-column>
        <el-table-column prop="title" label="标题" min-width="200" show-overflow-tooltip />
        <el-table-column prop="content" label="内容" min-width="300" show-overflow-tooltip />
        <el-table-column prop="status" label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)" size="small">
              {{ getStatusText(row.status) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="提交时间" width="180">
          <template #default="{ row }">
            {{ formatDate(row.created_at) }}
          </template>
        </el-table-column>
        <el-table-column label="操作" width="150" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" @click="handleView(row)">查看</el-button>
            <el-button link type="danger" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>

      <el-pagination
        v-model:current-page="pagination.current"
        v-model:page-size="pagination.pageSize"
        :total="pagination.total"
        :page-sizes="[10, 20, 50, 100]"
        layout="total, sizes, prev, pager, next, jumper"
        @current-change="handlePageChange"
        @size-change="handlePageSizeChange"
        style="margin-top: 20px; justify-content: center"
      />
    </el-card>

    <!-- 详情弹窗 -->
    <el-dialog v-model="detailDialogVisible" title="反馈详情" width="600px">
      <div v-if="currentFeedback" v-loading="pageLoading">
        <el-descriptions :column="1" border>
          <el-descriptions-item label="ID">{{ currentFeedback.id }}</el-descriptions-item>
          <el-descriptions-item label="类型">{{ currentFeedback.type || '-' }}</el-descriptions-item>
          <el-descriptions-item label="标题">{{ currentFeedback.title || '-' }}</el-descriptions-item>
          <el-descriptions-item label="内容">{{ currentFeedback.content || '-' }}</el-descriptions-item>
          <el-descriptions-item label="联系方式">{{ currentFeedback.contact || '-' }}</el-descriptions-item>
          <el-descriptions-item label="状态">
            <el-tag :type="getStatusType(currentFeedback.status)" size="small">
              {{ getStatusText(currentFeedback.status) }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="提交时间">{{ formatDate(currentFeedback.created_at) }}</el-descriptions-item>
          <el-descriptions-item label="更新时间">{{ formatDate(currentFeedback.updated_at) }}</el-descriptions-item>
        </el-descriptions>
      </div>
    </el-dialog>
  </div>
</template>

<style scoped>
.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.card-header h3 {
  margin: 0;
}
</style>