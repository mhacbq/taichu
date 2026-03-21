<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import {
  getFeedbackList,
  replyFeedback,
  deleteFeedback
} from '../../api/admin'

const router = useRouter()
const loading = ref(false)
const feedbackList = ref([])
const total = ref(0)
const currentPage = ref(1)
const pageSize = ref(20)
const searchForm = ref({
  keyword: '',
  status: ''
})

const loadFeedback = async () => {
  loading.value = true
  try {
    const params = {
      page: currentPage.value,
      page_size: pageSize.value,
      ...searchForm.value
    }
    const response = await getFeedbackList(params)
    
    if (response.code === 200) {
      feedbackList.value = response.data.list || []
      total.value = response.data.total || 0
    } else {
      ElMessage.error(response.message || '加载失败')
    }
  } catch (error) {
    console.error('加载反馈失败:', error)
    ElMessage.error('加载失败')
  } finally {
    loading.value = false
  }
}

const handleSearch = () => {
  currentPage.value = 1
  loadFeedback()
}

const handleReset = () => {
  searchForm.value = { keyword: '', status: '' }
  currentPage.value = 1
  loadFeedback()
}

const handleView = (row) => {
  router.push(`/maodou/feedback/${row.id}`)
}

const handleReply = async (row) => {
  try {
    const { value } = await ElMessageBox.prompt('请输入回复内容', '回复反馈', {
      inputType: 'textarea',
      inputPlaceholder: '请输入回复内容'
    })
    
    if (!value) {
      ElMessage.warning('请输入回复内容')
      return
    }
    
    const response = await replyFeedback(row.id, { content: value })
    if (response.code === 200) {
      ElMessage.success('回复成功')
      loadFeedback()
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

const handleDelete = async (row) => {
  try {
    await ElMessageBox.confirm('确定要删除这条反馈吗？', '确认删除', {
      type: 'warning'
    })
    
    const response = await deleteFeedback(row.id)
    if (response.code === 200) {
      ElMessage.success('删除成功')
      loadFeedback()
    } else {
      ElMessage.error(response.message || '删除失败')
    }
  } catch (error) {
    if (error !== 'cancel') {
      console.error('删除失败:', error)
      ElMessage.error('删除失败')
    }
  }
}

const handlePageChange = (page) => {
  currentPage.value = page
  loadFeedback()
}

const getStatusLabel = (status) => {
  const statusMap = {
    'pending': '待处理',
    'replied': '已回复',
    'resolved': '已解决'
  }
  return statusMap[status] || status
}

const getStatusType = (status) => {
  const typeMap = {
    'pending': 'warning',
    'replied': 'primary',
    'resolved': 'success'
  }
  return typeMap[status] || 'info'
}

onMounted(() => {
  loadFeedback()
})
</script>

<template>
  <div class="admin-feedback-list">
    <div class="page-header">
      <h2>反馈管理</h2>
    </div>

    <!-- 搜索表单 -->
    <div class="search-form">
      <el-form :inline="true" :model="searchForm">
        <el-form-item label="关键词">
          <el-input v-model="searchForm.keyword" placeholder="用户/内容" clearable />
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="searchForm.status" placeholder="全部" clearable>
            <el-option label="待处理" value="pending" />
            <el-option label="已回复" value="replied" />
            <el-option label="已解决" value="resolved" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">搜索</el-button>
          <el-button @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>
    </div>

    <!-- 反馈列表 -->
    <div class="table-container">
      <el-table v-loading="loading" :data="feedbackList" stripe>
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="user_id" label="用户ID" width="100" />
        <el-table-column prop="username" label="用户名" min-width="120" />
        <el-table-column prop="content" label="反馈内容" min-width="250" show-overflow-tooltip />
        <el-table-column label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)">
              {{ getStatusLabel(row.status) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="提交时间" width="180" />
        <el-table-column label="操作" width="200" fixed="right">
          <template #default="{ row }">
            <el-button size="small" @click="handleView(row)">查看</el-button>
            <el-button size="small" type="primary" @click="handleReply(row)">回复</el-button>
            <el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>

      <el-pagination
        v-model:current-page="currentPage"
        v-model:page-size="pageSize"
        :total="total"
        :page-sizes="[10, 20, 50, 100]"
        layout="total, sizes, prev, pager, next, jumper"
        @current-change="handlePageChange"
      />
    </div>
  </div>
</template>

<style scoped>
.admin-feedback-list {
  padding: 24px;
}

.page-header {
  margin-bottom: 20px;
}

.search-form {
  background: white;
  padding: 20px;
  border-radius: 8px;
  margin-bottom: 20px;
}

.table-container {
  background: white;
  padding: 20px;
  border-radius: 8px;
}

.el-pagination {
  margin-top: 20px;
  justify-content: flex-end;
}
</style>
