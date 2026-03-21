<template>
  <div class="feedback-manage">
    <div class="page-header">
      <h2>用户反馈</h2>
    </div>

    <el-card>
      <el-form :inline="true" :model="searchForm" class="search-form">
        <el-form-item label="用户名">
          <el-input v-model="searchForm.username" placeholder="输入用户名" clearable />
        </el-form-item>
        <el-form-item label="反馈类型">
          <el-select v-model="searchForm.type" placeholder="选择类型" clearable>
            <el-option label="功能建议" value="suggestion" />
            <el-option label="问题反馈" value="bug" />
            <el-option label="其他" value="other" />
          </el-select>
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="searchForm.status" placeholder="选择状态" clearable>
            <el-option label="待处理" value="pending" />
            <el-option label="处理中" value="processing" />
            <el-option label="已完成" value="completed" />
          </el-select>
        </el-form-item>
        <el-form-item label="时间范围">
          <el-date-picker
            v-model="dateRange"
            type="daterange"
            range-separator="至"
            start-placeholder="开始日期"
            end-placeholder="结束日期"
            value-format="YYYY-MM-DD"
          />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">搜索</el-button>
          <el-button @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>

      <el-table :data="tableData" v-loading="loading" style="width: 100%">
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="username" label="用户名" width="120" />
        <el-table-column prop="type" label="类型" width="100">
          <template #default="{ row }">
            <el-tag :type="getTypeTag(row.type)">{{ getTypeName(row.type) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="content" label="反馈内容" min-width="300" show-overflow-tooltip />
        <el-table-column prop="status" label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="getStatusTag(row.status)">{{ getStatusName(row.status) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="提交时间" width="160" />
        <el-table-column label="操作" width="180" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link size="small" @click="handleView(row)">详情</el-button>
            <el-button
              v-if="row.status !== 'completed'"
              type="success"
              link
              size="small"
              @click="handleReply(row)"
            >
              回复
            </el-button>
            <el-button type="danger" link size="small" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>

      <el-pagination
        v-model:current-page="pagination.page"
        v-model:page-size="pagination.size"
        :total="pagination.total"
        :page-sizes="[10, 20, 50, 100]"
        layout="total, sizes, prev, pager, next, jumper"
        @current-change="fetchData"
        @size-change="fetchData"
        style="margin-top: 20px; justify-content: flex-end;"
      />
    </el-card>

    <!-- 详情对话框 -->
    <el-dialog v-model="detailDialogVisible" title="反馈详情" width="700px">
      <el-descriptions :column="1" border>
        <el-descriptions-item label="ID">{{ currentRow.id }}</el-descriptions-item>
        <el-descriptions-item label="用户名">{{ currentRow.username }}</el-descriptions-item>
        <el-descriptions-item label="反馈类型">{{ getTypeName(currentRow.type) }}</el-descriptions-item>
        <el-descriptions-item label="反馈内容">{{ currentRow.content }}</el-descriptions-item>
        <el-descriptions-item label="联系方式">{{ currentRow.contact || '未提供' }}</el-descriptions-item>
        <el-descriptions-item label="提交时间">{{ currentRow.created_at }}</el-descriptions-item>
        <el-descriptions-item label="处理状态">
          <el-tag :type="getStatusTag(currentRow.status)">{{ getStatusName(currentRow.status) }}</el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="回复内容" v-if="currentRow.reply">
          {{ currentRow.reply }}
        </el-descriptions-item>
        <el-descriptions-item label="回复时间" v-if="currentRow.replied_at">
          {{ currentRow.replied_at }}
        </el-descriptions-item>
      </el-descriptions>
    </el-dialog>

    <!-- 回复对话框 -->
    <el-dialog v-model="replyDialogVisible" title="回复反馈" width="600px">
      <el-form :model="replyForm" label-width="80px">
        <el-form-item label="反馈内容">
          <el-input v-model="replyForm.content" type="textarea" :rows="3" disabled />
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="replyForm.status">
            <el-option label="处理中" value="processing" />
            <el-option label="已完成" value="completed" />
          </el-select>
        </el-form-item>
        <el-form-item label="回复内容">
          <el-input v-model="replyForm.reply" type="textarea" :rows="5" placeholder="请输入回复内容" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="replyDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitReply">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'

const loading = ref(false)
const tableData = ref([])
const detailDialogVisible = ref(false)
const replyDialogVisible = ref(false)
const currentRow = ref({})
const dateRange = ref([])

const searchForm = reactive({
  username: '',
  type: '',
  status: ''
})

const replyForm = reactive({
  id: null,
  content: '',
  status: 'completed',
  reply: ''
})

const pagination = reactive({
  page: 1,
  size: 20,
  total: 0
})

const getTypeName = (type) => {
  const map = { suggestion: '功能建议', bug: '问题反馈', other: '其他' }
  return map[type] || type
}

const getTypeTag = (type) => {
  const map = { suggestion: 'success', bug: 'danger', other: 'info' }
  return map[type] || 'info'
}

const getStatusName = (status) => {
  const map = { pending: '待处理', processing: '处理中', completed: '已完成' }
  return map[status] || status
}

const getStatusTag = (status) => {
  const map = { pending: 'info', processing: 'warning', completed: 'success' }
  return map[status] || 'info'
}

const fetchData = async () => {
  loading.value = true
  try {
    const params = new URLSearchParams({
      page: pagination.page,
      size: pagination.size,
      ...searchForm
    })
    
    if (dateRange.value && dateRange.value.length === 2) {
      params.append('start_date', dateRange.value[0])
      params.append('end_date', dateRange.value[1])
    }
    
    const response = await fetch(`/api/admin/feedback?${params}`)
    const data = await response.json()
    
    if (data.code === 200) {
      tableData.value = data.data.list || []
      pagination.total = data.data.total || 0
    }
  } catch (error) {
    ElMessage.error('获取数据失败')
  } finally {
    loading.value = false
  }
}

const handleSearch = () => {
  pagination.page = 1
  fetchData()
}

const handleReset = () => {
  searchForm.username = ''
  searchForm.type = ''
  searchForm.status = ''
  dateRange.value = []
  handleSearch()
}

const handleView = (row) => {
  currentRow.value = row
  detailDialogVisible.value = true
}

const handleReply = (row) => {
  replyForm.id = row.id
  replyForm.content = row.content
  replyForm.status = 'completed'
  replyForm.reply = row.reply || ''
  replyDialogVisible.value = true
}

const submitReply = async () => {
  try {
    const response = await fetch('/api/admin/feedback/reply', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        id: replyForm.id,
        status: replyForm.status,
        reply: replyForm.reply
      })
    })
    
    const data = await response.json()
    
    if (data.code === 200) {
      ElMessage.success('回复成功')
      replyDialogVisible.value = false
      fetchData()
    } else {
      ElMessage.error(data.message || '回复失败')
    }
  } catch (error) {
    ElMessage.error('回复失败')
  }
}

const handleDelete = async (row) => {
  try {
    await ElMessageBox.confirm('确定删除这条反馈吗？', '警告', { type: 'warning' })
    
    const response = await fetch(`/api/admin/feedback/${row.id}`, {
      method: 'DELETE'
    })
    const data = await response.json()
    
    if (data.code === 200) {
      ElMessage.success('删除成功')
      fetchData()
    } else {
      ElMessage.error(data.message || '删除失败')
    }
  } catch (error) {
    if (error !== 'cancel') ElMessage.error('删除失败')
  }
}

onMounted(() => {
  fetchData()
})
</script>

<style scoped>
.page-header {
  margin-bottom: 20px;
}
.page-header h2 {
  margin: 0;
  font-size: 20px;
  color: #333;
}
.search-form {
  margin-bottom: 20px;
}
</style>
