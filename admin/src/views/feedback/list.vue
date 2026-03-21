<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { useUserStore } from '@/stores/user'

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

onMounted(() => {
  fetchFeedbackList()
})

async function fetchFeedbackList() {
  loading.value = true
  try {
    // 这里调用API获取反馈列表
    // const res = await getFeedbackList({ ...pagination.value })
    // feedbackList.value = res.data.list
    // pagination.value.total = res.data.total
  } catch (error) {
    ElMessage.error('获取反馈列表失败')
  } finally {
    loading.value = false
  }
}

async function handleView(row) {
  pageLoading.value = true
  try {
    // 获取反馈详情
    // const res = await getFeedbackDetail(row.id)
    // 显示详情弹窗
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
    // 调用删除API
    // await deleteFeedback(row.id)
    ElMessage.success('删除成功')
    fetchFeedbackList()
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
</script>

<template>
  <div class="app-container">
    <el-card v-loading="pageLoading">
      <template #header>
        <div class="card-header">
          <span>反馈列表</span>
          <el-button type="primary" @click="fetchFeedbackList">刷新</el-button>
        </div>
      </template>

      <el-table v-loading="loading" :data="feedbackList" stripe>
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="user_name" label="用户名" width="120" />
        <el-table-column prop="content" label="反馈内容" show-overflow-tooltip />
        <el-table-column prop="type" label="类型" width="100">
          <template #default="{ row }">
            <el-tag size="small">{{ row.type }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="status" label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="row.status === 'resolved' ? 'success' : 'warning'" size="small">
              {{ row.status === 'resolved' ? '已处理' : '待处理' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="提交时间" width="180" />
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
        @size-change="fetchFeedbackList"
        style="margin-top: 20px; justify-content: center"
      />
    </el-card>
  </div>
</template>

<style scoped>
.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
</style>
