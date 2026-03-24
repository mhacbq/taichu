<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import request from '@/api/request'

const loading = ref(false)
const rulesList = ref([])

onMounted(() => {
  fetchRulesList()
})

async function fetchRulesList() {
  loading.value = true
  try {
    const res = await request.get('/points/rules')
    rulesList.value = res.data.list || []
  } catch (error) {
    ElMessage.error('获取积分规则失败')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="app-container">
    <el-card v-loading="loading">
      <template #header>
        <div class="card-header">
          <span>积分规则</span>
          <el-button type="primary">新增规则</el-button>
        </div>
      </template>

      <el-table :data="rulesList" stripe>
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="rule_name" label="规则名称" width="150" />
        <el-table-column prop="points" label="积分" width="120">
          <template #default="{ row }">
            <span :style="{ color: row.points > 0 ? '#67C23A' : '#F56C6C' }">
              {{ row.points > 0 ? '+' : '' }}{{ row.points }}
            </span>
          </template>
        </el-table-column>
        <el-table-column prop="description" label="规则描述" show-overflow-tooltip />
        <el-table-column prop="status" label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'info'" size="small">
              {{ row.status === 1 ? '启用' : '禁用' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="150" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" size="small">编辑</el-button>
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
