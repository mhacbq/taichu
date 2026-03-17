<template>
  <div class="app-container">
    <el-card shadow="never" class="search-form">
      <el-form :model="queryForm" inline>
        <el-form-item label="日期">
          <el-date-picker
            v-model="queryForm.date"
            type="date"
            placeholder="选择日期"
            value-format="YYYY-MM-DD"
          />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">搜索</el-button>
          <el-button type="success" :disabled="readonlyMode" @click="handleAdd">新增数据</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <el-card shadow="never">
      <div v-if="pageError" class="page-state">
        <el-result icon="warning" :title="pageError.title" :sub-title="pageError.description">
          <template #extra>
            <el-button type="primary" :loading="loading" @click="loadData">重新加载</el-button>
          </template>
        </el-result>
      </div>

      <template v-else>
        <el-table :data="almanacList" v-loading="loading" stripe>
          <el-table-column prop="date" label="日期" width="120" />
          <el-table-column prop="suit" label="宜" min-width="150" show-overflow-tooltip />
          <el-table-column prop="avoid" label="忌" min-width="150" show-overflow-tooltip />
          <el-table-column prop="ganzhi" label="干支" width="120" />
          <el-table-column prop="wuxing" label="五行" width="100" />
          <el-table-column label="操作" width="150" fixed="right">
            <template #default="{ row }">
              <el-button link type="primary" :disabled="readonlyMode" @click="handleEdit(row)">编辑</el-button>
              <el-button link type="danger" :disabled="readonlyMode" @click="handleDelete(row)">删除</el-button>
            </template>
          </el-table-column>
        </el-table>

        <div class="pagination-container">
          <el-pagination
            v-model:current-page="queryForm.page"
            v-model:page-size="queryForm.limit"
            :total="total"
            :page-sizes="[10, 20, 50, 100]"
            layout="total, sizes, prev, pager, next, jumper"
            @size-change="handleSizeChange"
            @current-change="handleCurrentChange"
          />
        </div>
      </template>
    </el-card>

    <el-dialog v-model="dialog.visible" :title="dialog.form.id ? '编辑黄历数据' : '新增黄历数据'" width="600px" destroy-on-close>
      <el-form :model="dialog.form" label-width="80px" :disabled="readonlyMode">
        <el-form-item label="日期" prop="date" required>
          <el-date-picker v-model="dialog.form.date" type="date" value-format="YYYY-MM-DD" placeholder="请选择日期" />
        </el-form-item>
        <el-form-item label="宜" prop="suit">
          <el-input v-model="dialog.form.suit" placeholder="多个请用空格分隔" />
        </el-form-item>
        <el-form-item label="忌" prop="avoid">
          <el-input v-model="dialog.form.avoid" placeholder="多个请用空格分隔" />
        </el-form-item>
        <el-form-item label="干支" prop="ganzhi">
          <el-input v-model="dialog.form.ganzhi" placeholder="如：甲子" />
        </el-form-item>
        <el-form-item label="五行" prop="wuxing">
          <el-input v-model="dialog.form.wuxing" placeholder="如：金" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialog.visible = false">取消</el-button>
        <el-button type="primary" :loading="submitLoading" :disabled="readonlyMode" @click="handleSubmit">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { getAlmanacList, createAlmanac, updateAlmanac, deleteAlmanac } from '@/api/content'
import { ElMessage, ElMessageBox } from 'element-plus'
import { createReadonlyErrorState } from '@/utils/page-error'

const loading = ref(false)
const submitLoading = ref(false)
const almanacList = ref([])
const total = ref(0)
const pageError = ref(null)
const queryForm = reactive({
  date: '',
  page: 1,
  limit: 20
})

const dialog = reactive({
  visible: false,
  form: createDefaultForm()
})

const readonlyMode = computed(() => Boolean(pageError.value))

function createDefaultForm() {
  return {
    id: null,
    date: '',
    suit: '',
    avoid: '',
    ganzhi: '',
    wuxing: ''
  }
}

function ensureWritable(message) {
  if (!readonlyMode.value) {
    return true
  }
  ElMessage.warning(message)
  return false
}

const loadData = async () => {
  loading.value = true
  try {
    const res = await getAlmanacList(queryForm, { showErrorMessage: false })
    almanacList.value = Array.isArray(res?.data?.list) ? res.data.list : []
    total.value = Number(res?.data?.total || 0)
    pageError.value = null
  } catch (error) {
    almanacList.value = []
    total.value = 0
    dialog.visible = false
    pageError.value = createReadonlyErrorState(error, '黄历管理', 'almanac_view / almanac_edit')
  } finally {
    loading.value = false
  }
}

const handleSearch = () => {
  queryForm.page = 1
  loadData()
}

const handleSizeChange = (size) => {
  queryForm.limit = size
  queryForm.page = 1
  loadData()
}

const handleCurrentChange = (page) => {
  queryForm.page = page
  loadData()
}

const handleAdd = () => {
  if (!ensureWritable('黄历管理尚未成功加载，当前为只读保护状态')) {
    return
  }
  dialog.form = createDefaultForm()
  dialog.visible = true
}

const handleEdit = (row) => {
  if (!ensureWritable('黄历管理尚未成功加载，当前暂时无法编辑')) {
    return
  }
  dialog.form = { ...row }
  dialog.visible = true
}

const handleSubmit = async () => {
  if (!ensureWritable('黄历管理尚未成功加载，当前暂时无法保存')) {
    return
  }

  if (!dialog.form.date) {
    ElMessage.warning('请选择日期')
    return
  }

  submitLoading.value = true
  try {
    if (dialog.form.id) {
      await updateAlmanac(dialog.form.id, dialog.form, { showErrorMessage: false })
    } else {
      await createAlmanac(dialog.form, { showErrorMessage: false })
    }
    ElMessage.success('保存成功')
    dialog.visible = false
    await loadData()
  } catch (error) {
    ElMessage.error(error.message || '保存失败')
  } finally {
    submitLoading.value = false
  }
}

const handleDelete = async (row) => {
  if (!ensureWritable('黄历管理尚未成功加载，当前暂时无法删除')) {
    return
  }

  try {
    await ElMessageBox.confirm('确定要删除该条数据吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    })
    await deleteAlmanac(row.id, { showErrorMessage: false })
    ElMessage.success('删除成功')
    if (almanacList.value.length === 1 && queryForm.page > 1) {
      queryForm.page -= 1
    }
    await loadData()
  } catch (error) {
    if (error !== 'cancel' && error !== 'close') {
      ElMessage.error(error.message || '删除失败')
    }
  }
}

onMounted(() => {
  loadData()
})
</script>

<style scoped>
.app-container { padding: 20px; }
.search-form { margin-bottom: 20px; }
.pagination-container {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
}
.page-state {
  padding: 12px 0;
}
</style>
