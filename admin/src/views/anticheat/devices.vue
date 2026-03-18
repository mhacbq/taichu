<template>
  <div class="app-container">
    <el-card shadow="never" class="search-form">
      <el-form :model="queryForm" inline>
        <el-form-item label="设备指纹">
          <el-input v-model="queryForm.fingerprint" placeholder="请输入设备指纹" clearable />
        </el-form-item>
        <el-form-item label="关联用户">
          <el-input v-model="queryForm.userId" placeholder="用户ID" clearable />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">搜索</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <el-card shadow="never">
      <el-table :data="deviceList" v-loading="loading" stripe>
        <el-table-column prop="fingerprint" label="设备指纹" min-width="180" />
        <el-table-column prop="user_ids" label="关联用户ID" width="150" />
        <el-table-column prop="os" label="操作系统" width="120" />
        <el-table-column prop="browser" label="浏览器" width="120" />
        <el-table-column prop="ip" label="最后IP" width="140" />
        <el-table-column prop="risk_score" label="风险评分" width="100">
          <template #default="{ row }">
            <el-tag :type="row.risk_score > 60 ? 'danger' : 'success'">{{ row.risk_score }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="updated_at" label="最后活跃" width="180" />
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
const deviceList = ref([])
const queryForm = reactive({
  fingerprint: '',
  userId: ''
})

function handleSearch() {
  loadDevices()
}

function loadDevices() {
  loading.value = true
  setTimeout(() => {
    deviceList.value = []
    loading.value = false
  }, 500)
}

function handleDetail(row) {}
</script>

<style scoped>
.app-container { padding: 20px; }
.search-form { margin-bottom: 20px; }
</style>
