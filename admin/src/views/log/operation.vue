<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { ElMessage } from 'element-plus'

const loading = ref(false)
const operationLogs = ref([])
const pagination = ref({
  current: 1,
  pageSize: 20,
  total: 0
})

onMounted(() => {
  fetchOperationLogs()
})

async function fetchOperationLogs() {
  loading.value = true
  try {
    // 这里调用API获取操作日志
    // const res = await getOperationLogs({ ...pagination.value })
    // operationLogs.value = res.data.list
    // pagination.value.total = res.data.total
  } catch (error) {
    ElMessage.error('获取操作日志失败')
  } finally {
    loading.value = false
  }
}

function handlePageChange(page) {
  pagination.value.current = page
  fetchOperationLogs()
}
</script>

<template>
  <div class="app-container">
    <el-card v-loading="loading">
      <template #header>
        <div class="card-header">
          <span>操作日志</span>
          <el-button type="primary" @click="fetchOperationLogs">刷新</el-button>
        </div>
      </template>

      <el-table :data="operationLogs" stripe>
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="admin_name" label="操作人" width="120" />
        <el-table-column prop="action" label="操作" width="150" />
        <el-table-column prop="target_type" label="对象类型" width="120" />
        <el-table-column prop="detail" label="详情" show-overflow-tooltip />
        <el-table-column prop="ip" label="IP地址" width="140" />
        <el-table-column prop="created_at" label="操作时间" width="180" />
      </el-table>

      <el-pagination
        v-model:current-page="pagination.current"
        v-model:page-size="pagination.pageSize"
        :total="pagination.total"
        :page-sizes="[10, 20, 50, 100]"
        layout="total, sizes, prev, pager, next, jumper"
        @current-change="handlePageChange"
        @size-change="fetchOperationLogs"
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
