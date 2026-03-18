<template>
  <div class="app-container">
    <el-card shadow="never" class="search-form">
      <el-form :model="queryForm" inline>
        <el-form-item label="名称">
          <el-input v-model="queryForm.keyword" placeholder="搜索名称/描述/作用" clearable @keyup.enter="handleSearch" />
        </el-form-item>
        <el-form-item label="类型">
          <el-select v-model="queryForm.type" placeholder="选择类型" clearable style="width: 140px;">
            <el-option v-for="item in typeOptions" :key="item.value" :label="item.label" :value="item.value" />
          </el-select>
        </el-form-item>
        <el-form-item label="分类">
          <el-select v-model="queryForm.category" placeholder="选择分类" clearable style="width: 140px;">
            <el-option v-for="item in categoryOptions" :key="item.value" :label="item.label" :value="item.value" />
          </el-select>
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="queryForm.status" placeholder="选择状态" clearable style="width: 110px;">
            <el-option v-for="item in statusOptions" :key="item.value" :label="item.label" :value="item.value" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">搜索</el-button>
          <el-button type="success" :disabled="readonlyMode" @click="handleAdd">新增神煞</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <el-card shadow="never">
      <div v-if="pageError" class="page-state">
        <el-result icon="warning" :title="pageError.title" :sub-title="pageError.description">
          <template #extra>
            <el-button type="primary" :loading="loading || optionLoading" @click="loadPage">重新加载</el-button>
          </template>
        </el-result>
      </div>

      <template v-else>
        <el-table :data="shenshaList" v-loading="loading" stripe>
          <el-table-column prop="id" label="ID" width="80" />
          <el-table-column prop="name" label="名称" width="120" />
          <el-table-column prop="type" label="类型" width="100">
            <template #default="{ row }">
              <el-tag :type="getTypeTag(row.type)">{{ typeLabelMap[row.type] || row.type }}</el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="category" label="分类" width="100">
            <template #default="{ row }">
              <span>{{ categoryLabelMap[row.category] || row.category }}</span>
            </template>
          </el-table-column>
          <el-table-column prop="description" label="描述" min-width="150" show-overflow-tooltip />
          <el-table-column prop="effect" label="作用" min-width="150" show-overflow-tooltip />
          <el-table-column prop="status" label="状态" width="100">
            <template #default="{ row }">
              <el-switch
                v-model="row.status"
                :active-value="1"
                :inactive-value="0"
                :disabled="readonlyMode"
                @change="handleStatusChange(row)"
              />
            </template>
          </el-table-column>
          <el-table-column prop="sort" label="排序" width="80" />
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
            v-model:page-size="queryForm.pageSize"
            :total="total"
            :page-sizes="[10, 20, 50, 100]"
            layout="total, sizes, prev, pager, next, jumper"
            @size-change="handleSizeChange"
            @current-change="handleCurrentChange"
          />
        </div>
      </template>
    </el-card>

    <el-dialog v-model="dialog.visible" :title="dialog.form.id ? '编辑神煞' : '新增神煞'" width="600px" destroy-on-close>
      <el-form ref="formRef" :model="dialog.form" label-width="100px" :rules="rules" :disabled="readonlyMode">
        <el-form-item label="名称" prop="name">
          <el-input v-model="dialog.form.name" placeholder="请输入神煞名称" />
        </el-form-item>
        <el-row>
          <el-col :span="12">
            <el-form-item label="类型" prop="type">
              <el-select v-model="dialog.form.type" placeholder="请选择类型" style="width: 100%;">
                <el-option v-for="item in typeOptions" :key="item.value" :label="item.label" :value="item.value" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="分类" prop="category">
              <el-select v-model="dialog.form.category" placeholder="请选择分类" style="width: 100%;">
                <el-option v-for="item in categoryOptions" :key="item.value" :label="item.label" :value="item.value" />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
        <el-form-item label="描述" prop="description">
          <el-input v-model="dialog.form.description" type="textarea" :rows="3" placeholder="请输入神煞描述" />
        </el-form-item>
        <el-form-item label="作用" prop="effect">
          <el-input v-model="dialog.form.effect" type="textarea" :rows="3" placeholder="请输入神煞作用" />
        </el-form-item>
        <el-row>
          <el-col :span="12">
            <el-form-item label="排序" prop="sort">
              <el-input-number v-model="dialog.form.sort" :min="0" :max="999" style="width: 100%;" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="状态" prop="status">
              <el-radio-group v-model="dialog.form.status">
                <el-radio :label="1">启用</el-radio>
                <el-radio :label="0">禁用</el-radio>
              </el-radio-group>
            </el-form-item>
          </el-col>
        </el-row>
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
import { getShenshaList, getShenshaOptions, saveShensha, deleteShensha } from '@/api/content'
import { ElMessage, ElMessageBox } from 'element-plus'
import { createReadonlyErrorState } from '@/utils/page-error'

const DEFAULT_TYPE_LABELS = {
  daji: '大吉',
  ji: '吉',
  ping: '平',
  xiong: '凶',
  daxiong: '大凶'
}

const DEFAULT_CATEGORY_LABELS = {
  guiren: '贵人',
  xueye: '学业',
  ganqing: '感情',
  jiankang: '健康',
  caiyun: '财运',
  qita: '其他'
}

const loading = ref(false)
const optionLoading = ref(false)
const submitLoading = ref(false)
const shenshaList = ref([])
const total = ref(0)
const formRef = ref(null)
const pageError = ref(null)
const typeOptions = ref(buildDefaultOptions(DEFAULT_TYPE_LABELS))
const categoryOptions = ref(buildDefaultOptions(DEFAULT_CATEGORY_LABELS))
const statusOptions = ref([
  { label: '启用', value: 1 },
  { label: '禁用', value: 0 }
])

const queryForm = reactive({
  keyword: '',
  type: '',
  category: '',
  status: '',
  page: 1,
  pageSize: 20
})

const dialog = reactive({
  visible: false,
  form: createDefaultForm()
})

const rules = {
  name: [{ required: true, message: '请输入名称', trigger: 'blur' }],
  type: [{ required: true, message: '请选择类型', trigger: 'change' }],
  category: [{ required: true, message: '请选择分类', trigger: 'change' }]
}

const readonlyMode = computed(() => Boolean(pageError.value))
const typeLabelMap = computed(() => buildLabelMap(typeOptions.value, DEFAULT_TYPE_LABELS))
const categoryLabelMap = computed(() => buildLabelMap(categoryOptions.value, DEFAULT_CATEGORY_LABELS))

function createDefaultForm() {
  return {
    id: null,
    name: '',
    type: typeOptions.value[0]?.value || 'ping',
    category: categoryOptions.value[0]?.value || 'qita',
    description: '',
    effect: '',
    sort: 10,
    status: 1
  }
}

function buildDefaultOptions(source) {
  return Object.entries(source).map(([value, label]) => ({ value, label }))
}

function buildLabelMap(options, fallback) {
  return options.reduce((map, item) => {
    map[item.value] = item.label
    return map
  }, { ...fallback })
}

function normalizeOptionList(list, fallback) {
  if (!Array.isArray(list) || list.length === 0) {
    return buildDefaultOptions(fallback)
  }

  return list.map(item => {
    if (typeof item === 'string') {
      return { value: item, label: fallback[item] || item }
    }
    return {
      value: item?.value,
      label: item?.label || fallback[item?.value] || item?.value
    }
  }).filter(item => item.value !== undefined && item.value !== null && item.value !== '')
}

function ensureWritable(message) {
  if (!readonlyMode.value) {
    return true
  }
  ElMessage.warning(message)
  return false
}

async function loadOptions() {
  optionLoading.value = true
  try {
    const res = await getShenshaOptions({ showErrorMessage: false })
    typeOptions.value = normalizeOptionList(res?.data?.types, DEFAULT_TYPE_LABELS)
    categoryOptions.value = normalizeOptionList(res?.data?.categories, DEFAULT_CATEGORY_LABELS)
    statusOptions.value = Array.isArray(res?.data?.statuses) && res.data.statuses.length > 0
      ? res.data.statuses.map(item => ({ label: item.label, value: Number(item.value) }))
      : [
          { label: '启用', value: 1 },
          { label: '禁用', value: 0 }
        ]
  } finally {
    optionLoading.value = false
  }
}

async function loadData() {
  loading.value = true
  try {
    const res = await getShenshaList(queryForm, { showErrorMessage: false })
    shenshaList.value = Array.isArray(res?.data?.list) ? res.data.list : []
    total.value = Number(res?.data?.total || 0)
  } finally {
    loading.value = false
  }
}

async function loadPage() {
  try {
    await Promise.all([loadOptions(), loadData()])
    pageError.value = null
  } catch (error) {
    shenshaList.value = []
    total.value = 0
    dialog.visible = false
    pageError.value = createReadonlyErrorState(error, '神煞管理', 'content_manage')
  }
}

const handleSearch = () => {
  queryForm.page = 1
  loadPage()
}

const handleSizeChange = (size) => {
  queryForm.pageSize = size
  queryForm.page = 1
  loadData().catch(handleReadonlyFailure)
}

const handleCurrentChange = (page) => {
  queryForm.page = page
  loadData().catch(handleReadonlyFailure)
}

function handleReadonlyFailure(error) {
  shenshaList.value = []
  total.value = 0
  dialog.visible = false
  pageError.value = createReadonlyErrorState(error, '神煞管理', 'content_manage')
}

const handleAdd = () => {
  if (!ensureWritable('神煞管理尚未成功加载，当前为只读保护状态')) {
    return
  }
  dialog.form = createDefaultForm()
  dialog.visible = true
}

const handleEdit = (row) => {
  if (!ensureWritable('神煞管理尚未成功加载，当前暂时无法编辑')) {
    return
  }
  dialog.form = { ...row }
  dialog.visible = true
}

const handleSubmit = async () => {
  if (!ensureWritable('神煞管理尚未成功加载，当前暂时无法保存')) {
    return
  }
  if (!formRef.value) return

  const valid = await formRef.value.validate().catch(() => false)
  if (!valid) {
    return
  }

  submitLoading.value = true
  try {
    await saveShensha(dialog.form, { showErrorMessage: false })
    ElMessage.success('保存成功')
    dialog.visible = false
    await loadPage()
  } catch (error) {
    ElMessage.error(error.message || '保存失败')
  } finally {
    submitLoading.value = false
  }
}

const handleDelete = async (row) => {
  if (!ensureWritable('神煞管理尚未成功加载，当前暂时无法删除')) {
    return
  }

  try {
    await ElMessageBox.confirm('确定要删除该神煞吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    })
    await deleteShensha(row.id, { showErrorMessage: false })
    ElMessage.success('删除成功')
    if (shenshaList.value.length === 1 && queryForm.page > 1) {
      queryForm.page -= 1
    }
    await loadPage()
  } catch (error) {
    if (error !== 'cancel' && error !== 'close') {
      ElMessage.error(error.message || '删除失败')
    }
  }
}

const handleStatusChange = async (row) => {
  if (!ensureWritable('神煞管理尚未成功加载，当前暂时无法修改状态')) {
    row.status = row.status === 1 ? 0 : 1
    return
  }

  const nextStatus = row.status
  const previousStatus = nextStatus === 1 ? 0 : 1

  try {
    await saveShensha({ id: row.id, status: nextStatus }, { showErrorMessage: false })
    ElMessage.success('状态更新成功')
  } catch (error) {
    row.status = previousStatus
    ElMessage.error(error.message || '状态更新失败')
  }
}

const getTypeTag = (type) => {
  switch (type) {
    case 'daji': return 'success'
    case 'ji': return ''
    case 'ping': return 'info'
    case 'xiong': return 'warning'
    case 'daxiong': return 'danger'
    default: return 'info'
  }
}

onMounted(() => {
  loadPage()
})
</script>

<style scoped>
.app-container { padding: 20px; }
.search-form { margin-bottom: 20px; }
.pagination-container { margin-top: 20px; display: flex; justify-content: flex-end; }
.page-state { padding: 12px 0; }
</style>
