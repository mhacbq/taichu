<template>
  <div class="app-container">
    <el-card shadow="never" class="search-form">
      <el-form :model="queryForm" inline>
        <el-form-item label="任务ID">
          <el-input v-model="queryForm.taskId" placeholder="任务ID" clearable />
        </el-form-item>
        <el-form-item label="执行结果">
          <el-select v-model="queryForm.status" placeholder="全部" clearable>
            <el-option label="成功" value="success" />
            <el-option label="失败" value="fail" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">搜索</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <el-card shadow="never">
      <el-table :data="logList" v-loading="loading" stripe>
        <el-table-column prop="task_id" label="任务ID" width="100" />
        <el-table-column prop="task_name" label="任务名称" width="150" />
        <el-table-column prop="status" label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="row.status === 'success' ? 'success' : 'danger'">{{ row.status === 'success' ? '成功' : '失败' }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="duration" label="耗时(ms)" width="100" />
        <el-table-column prop="message" label="执行消息" min-width="200" show-overflow-tooltip />
        <el-table-column prop="created_at" label="执行时间" width="180" />
      </el-table>
    </el-card>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'

const loading = ref(false)
const logList = ref([])
const queryForm = reactive({
  taskId: '',
  status: ''
})

function handleSearch() {
  loadLogs()
}

function loadLogs() {
  loading.value = true
  setTimeout(() => {
    logList.value = []
    loading.value = false
  }, 500)
}
</script>

<style scoped>
.app-container { padding: 20px; }
.search-form { margin-bottom: 20px; }
</style>
