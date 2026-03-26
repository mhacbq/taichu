<template>
  <div class="app-container">
    <el-card shadow="never" class="search-form">
      <el-form :model="queryForm" inline>
        <el-form-item label="用户ID">
          <el-input v-model="queryForm.userId" placeholder="请输入用户ID" clearable />
        </el-form-item>
        <el-form-item label="行为类型">
          <el-select v-model="queryForm.type" placeholder="全部类型" clearable>
            <el-option label="登录" value="login" />
            <el-option label="浏览" value="view" />
            <el-option label="操作" value="action" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">搜索</el-button>
          <el-button @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <el-card shadow="never">
      <el-table :data="logList" v-loading="loading" stripe>
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="user_id" label="用户ID" width="100" />
        <el-table-column prop="display_name" label="用户名" width="120">
          <template #default="{ row }">
            {{ row.display_name || row.username || row.nickname || '-' }}
          </template>
        </el-table-column>
        <el-table-column prop="type" label="类型" width="100" />
        <el-table-column prop="content" label="详情" min-width="200" />
        <el-table-column prop="ip" label="IP地址" width="140" />
        <el-table-column prop="created_at" label="发生时间" width="180" />
      </el-table>
      
      <div class="pagination-container">
        <el-pagination
          v-model:current-page="queryForm.page"
          v-model:page-size="queryForm.pageSize"
          :total="total"
          layout="total, sizes, prev, pager, next, jumper"
          @size-change="loadLogs"
          @current-change="loadLogs"
        />
      </div>
    </el-card>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { ElMessage } from 'element-plus'
import { getUserBehavior } from '@/api/user'

const route = useRoute()
const loading = ref(false)
const total = ref(0)
const logList = ref([])

const queryForm = reactive({
  userId: route.query.userId || '',
  type: '',
  page: 1,
  pageSize: 20
})

onMounted(() => {
  loadLogs()
})

async function loadLogs() {
  loading.value = true
  try {
    const params = {
      id: queryForm.userId,
      type: queryForm.type,
      page: queryForm.page,
      limit: queryForm.pageSize
    }
    const res = await getUserBehavior(params)
    if (res.code !== 0) {
      ElMessage.error(res.message || '加载日志失败')
      return
    }
    logList.value = res.data?.list || []
    total.value = res.data?.total || 0
  } catch (error) {
    ElMessage.error('加载日志失败')
  } finally {
    loading.value = false
  }
}


function handleSearch() {
  queryForm.page = 1
  loadLogs()
}

function handleReset() {
  Object.assign(queryForm, {
    userId: '',
    type: '',
    page: 1,
    pageSize: 20
  })
  loadLogs()
}
</script>

<style scoped>
.app-container {
  padding: 20px;
}
.search-form {
  margin-bottom: 20px;
}
.pagination-container {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
}
</style>
