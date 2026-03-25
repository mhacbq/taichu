<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { getVipPackages } from '@/api/payment'

const loading = ref(false)
const packagesList = ref([])

onMounted(() => {
  fetchPackagesList()
})

async function fetchPackagesList() {
  loading.value = true
  try {
    const res = await getVipPackages()
    if (res.code === 200) {
      packagesList.value = res.data?.list || []
    }
  } catch (error) {
    ElMessage.error('获取VIP套餐失败')
  } finally {
    loading.value = false
  }
}

function getStatusText(status) {
  return status === 1 ? '上架' : '下架'
}

function getStatusTagType(status) {
  return status === 1 ? 'success' : 'info'
}
</script>

<template>
  <div class="app-container">
    <el-card v-loading="loading">
      <template #header>
        <div class="card-header">
          <span>VIP套餐管理</span>
          <el-button type="primary">新增套餐</el-button>
        </div>
      </template>

      <el-table :data="packagesList" stripe>
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="name" label="套餐名称" width="150" />
        <el-table-column prop="price" label="售价" width="100">
          <template #default="{ row }">
            <span style="color: #E6A23C">¥{{ row.price }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="original_price" label="原价" width="100">
          <template #default="{ row }">
            <span style="text-decoration: line-through; color: #999">¥{{ row.original_price }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="points" label="赠送积分" width="120" />
        <el-table-column prop="description" label="套餐描述" show-overflow-tooltip />
        <el-table-column prop="status" label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="getStatusTagType(row.status)" size="small">
              {{ getStatusText(row.status) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="创建时间" width="180" />
        <el-table-column label="操作" width="200" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" size="small">编辑</el-button>
            <el-button link type="warning" size="small">{{ row.status === 1 ? '下架' : '上架' }}</el-button>
            <el-button link type="danger" size="small">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
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
