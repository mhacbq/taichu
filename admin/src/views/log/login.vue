<template>
  <div class="app-container">
    <el-card shadow="never" class="search-form">
      <el-form :model="queryForm" inline>
        <el-form-item label="用户名">
          <el-input v-model="queryForm.username" placeholder="请输入用户名" clearable />
        </el-form-item>
        <el-form-item label="登录结果">
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
        <el-table-column prop="username" label="登录账号" width="120" />
        <el-table-column prop="ip" label="登录IP" width="140" />
        <el-table-column prop="location" label="登录地点" width="150" />
        <el-table-column prop="browser" label="浏览器" width="120" />
        <el-table-column prop="os" label="操作系统" width="120" />
        <el-table-column prop="status" label="结果" width="100">
          <template #default="{ row }">
            <el-tag :type="row.status === 'success' ? 'success' : 'danger'">{{ row.status === 'success' ? '成功' : '失败' }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="message" label="描述" min-width="150" />
        <el-table-column prop="created_at" label="登录时间" width="180" />
      </el-table>
    </el-card>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'

const loading = ref(false)
const logList = ref([])
const queryForm = reactive({
  username: '',
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
