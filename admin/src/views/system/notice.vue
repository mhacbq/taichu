<template>
  <div class="app-container">
    <div class="table-operations">
      <el-button type="primary" @click="handleAdd">
        <el-icon><Plus /></el-icon>发布公告
      </el-button>
    </div>

    <el-card shadow="never">
      <el-table :data="noticeList" v-loading="loading" stripe>
        <el-table-column prop="title" label="公告标题" min-width="220" />
        <el-table-column prop="type" label="类型" width="100">
          <template #default="{ row }">
            <el-tag :type="row.type === 'important' ? 'danger' : 'info'">
              {{ row.type === 'important' ? '重要' : '普通' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="status" label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'info'">
              {{ row.status === 1 ? '已发布' : '草稿' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="发布时间" width="180" />
        <el-table-column label="操作" width="160" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" @click="handleEdit(row)">编辑</el-button>
            <el-button link type="danger" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <el-dialog v-model="dialog.visible" title="公告管理" width="600px" destroy-on-close>
      <el-form :model="dialog.form" label-width="80px">
        <el-form-item label="标题">
          <el-input v-model="dialog.form.title" maxlength="100" show-word-limit placeholder="请输入公告标题" />
        </el-form-item>
        <el-form-item label="类型">
          <el-select v-model="dialog.form.type" style="width: 100%;">
            <el-option label="普通" value="normal" />
            <el-option label="重要" value="important" />
          </el-select>
        </el-form-item>
        <el-form-item label="内容">
          <el-input
            v-model="dialog.form.content"
            type="textarea"
            rows="6"
            maxlength="2000"
            show-word-limit
            placeholder="请输入公告内容"
          />
        </el-form-item>
        <el-form-item label="发布状态">
          <el-radio-group v-model="dialog.form.status">
            <el-radio :label="1">立即发布</el-radio>
            <el-radio :label="0">保存草稿</el-radio>
          </el-radio-group>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialog.visible = false">取消</el-button>
        <el-button type="primary" :loading="submitting" @click="handleSubmit">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue'
import { Plus } from '@element-plus/icons-vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { deleteNotice, getNotices, saveNotice } from '@/api/system'

const loading = ref(false)
const submitting = ref(false)
const noticeList = ref([])
const dialog = reactive({
  visible: false,
  form: createDefaultForm()
})

function createDefaultForm() {
  return {
    id: undefined,
    title: '',
    type: 'normal',
    content: '',
    status: 1
  }
}

function normalizeNoticeRow(row = {}) {
  return {
    id: Number(row.id) || undefined,
    title: String(row.title || ''),
    type: row.type === 'important' ? 'important' : 'normal',
    content: String(row.content || ''),
    status: Number(row.status) === 1 ? 1 : 0,
    created_at: String(row.created_at || ''),
    updated_at: String(row.updated_at || '')
  }
}

async function loadNotices() {
  loading.value = true
  try {
    const res = await getNotices({ page: 1, pageSize: 100 })
    const list = Array.isArray(res?.data?.list) ? res.data.list : []
    noticeList.value = list.map(normalizeNoticeRow)
  } catch (error) {
    ElMessage.error(error.message || '加载公告失败，请稍后重试')
  } finally {
    loading.value = false
  }
}

function handleAdd() {
  dialog.form = createDefaultForm()
  dialog.visible = true
}

function handleEdit(row) {
  dialog.form = normalizeNoticeRow(row)
  dialog.visible = true
}

async function handleSubmit() {
  const title = dialog.form.title.trim()
  const content = dialog.form.content.trim()

  if (!title) {
    ElMessage.warning('请输入公告标题')
    return
  }

  if (!content) {
    ElMessage.warning('请输入公告内容')
    return
  }

  submitting.value = true
  try {
    await saveNotice({
      id: dialog.form.id,
      title,
      type: dialog.form.type,
      content,
      status: dialog.form.status
    })
    ElMessage.success(dialog.form.id ? '公告更新成功' : '公告发布成功')
    dialog.visible = false
    await loadNotices()
  } catch (error) {
    ElMessage.error(error.message || '保存公告失败，请稍后重试')
  } finally {
    submitting.value = false
  }
}

async function handleDelete(row) {
  try {
    await ElMessageBox.confirm(`确定删除公告「${row.title}」吗？`, '删除确认', {
      type: 'warning',
      confirmButtonText: '删除',
      cancelButtonText: '取消'
    })
    await deleteNotice(row.id)
    ElMessage.success('公告已删除')
    await loadNotices()
  } catch (error) {
    if (error !== 'cancel' && error !== 'close') {
      ElMessage.error(error.message || '删除公告失败，请稍后重试')
    }
  }
}

onMounted(() => {
  loadNotices()
})
</script>

<style scoped>
.app-container {
  padding: 20px;
}

.table-operations {
  margin-bottom: 20px;
}
</style>
