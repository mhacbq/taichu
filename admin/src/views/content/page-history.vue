<template>
  <div class="app-container">
    <el-page-header @back="router.back()">
      <template #content>
        <span>页面版本历史</span>
      </template>
    </el-page-header>

    <el-card shadow="never" style="margin-top: 20px">
      <el-table :data="historyList" v-loading="loading" stripe>
        <el-table-column prop="version" label="版本号" width="100" />
        <el-table-column prop="operator" label="操作人" width="120" />
        <el-table-column prop="remark" label="变更说明" min-width="200" />
        <el-table-column prop="created_at" label="变更时间" width="180" />
        <el-table-column label="操作" width="120" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" @click="handlePreview(row)">预览</el-button>
            <el-button link type="primary" @click="handleRestore(row)">回滚</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'

const router = useRouter()
const route = useRoute()
const loading = ref(false)
const historyList = ref([])

onMounted(() => {
  loadHistory()
})

async function loadHistory() {
  loading.value = true
  // TODO: 调用真实API
  setTimeout(() => {
    historyList.value = []
    loading.value = false
  }, 500)
}

function handlePreview(row) {
  ElMessage.info('预览版本: ' + row.version)
}

async function handleRestore(row) {
  try {
    await ElMessageBox.confirm(`确定要将页面回滚到版本 ${row.version} 吗？`, '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    })
    ElMessage.success('回滚成功')
    loadHistory()
  } catch {
    // 用户取消
  }
}
</script>

<style scoped>
.app-container {
  padding: 20px;
}
</style>
