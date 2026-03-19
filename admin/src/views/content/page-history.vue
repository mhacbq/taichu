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
import { getVersions, restoreVersion, previewVersion } from '@/api/contentEditor'

const router = useRouter()
const route = useRoute()
const loading = ref(false)
const historyList = ref([])

onMounted(() => {
  loadHistory()
})

async function loadHistory() {
  const pageId = route.params.id
  if (!pageId) {
    ElMessage.warning('缺少页面ID参数')
    return
  }
  loading.value = true
  try {
    const res = await getVersions(pageId)
    historyList.value = res.data?.list || res.data || []
  } catch (e) {
    ElMessage.error('加载版本历史失败：' + (e.message || '未知错误'))
  } finally {
    loading.value = false
  }
}

async function handlePreview(row) {
  try {
    const res = await previewVersion(row.id)
    const previewUrl = res.data?.url || res.data?.preview_url
    if (previewUrl) {
      window.open(previewUrl, '_blank')
    } else {
      ElMessage.info('暂无预览地址')
    }
  } catch (e) {
    ElMessage.error('预览失败：' + (e.message || '未知错误'))
  }
}

async function handleRestore(row) {
  try {
    await ElMessageBox.confirm(`确定要将页面回滚到版本 ${row.version} 吗？此操作不可撤销。`, '确认回滚', {
      confirmButtonText: '确定回滚',
      cancelButtonText: '取消',
      type: 'warning'
    })
    await restoreVersion(row.id)
    ElMessage.success('回滚成功')
    loadHistory()
  } catch (e) {
    if (e !== 'cancel') {
      ElMessage.error('回滚失败：' + (e.message || '未知错误'))
    }
  }
}
</script>

<style scoped>
.app-container {
  padding: 20px;
}
</style>
