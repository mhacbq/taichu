<template>
  <div class="app-container">
    <el-card shadow="never" class="search-form">
      <el-form :model="queryForm" inline>
        <el-form-item label="接口路径">
          <el-input v-model="queryForm.path" placeholder="/api/..." clearable />
        </el-form-item>
        <el-form-item label="状态码">
          <el-input v-model="queryForm.status" placeholder="200" clearable />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">搜索</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <el-card shadow="never">
      <el-table :data="logList" v-loading="loading" stripe>
        <el-table-column prop="method" label="方法" width="80" />
        <el-table-column prop="path" label="路径" min-width="180" />
        <el-table-column prop="user_id" label="用户ID" width="100" />
        <el-table-column prop="ip" label="IP" width="140" />
        <el-table-column prop="status" label="状态" width="80" />
        <el-table-column prop="duration" label="耗时(ms)" width="100" />
        <el-table-column prop="created_at" label="时间" width="180" />
        <el-table-column label="操作" width="100" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" @click="handleDetail(row)">详情</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'

const loading = ref(false)
const logList = ref([])
const queryForm = reactive({
  path: '',
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

function handleDetail(row) {}
</script>

<style scoped>
.app-container { padding: 20px; }
.search-form { margin-bottom: 20px; }
</style>
