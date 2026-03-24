<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import request from '@/api/request'

const loading = ref(false)
const submitLoading = ref(false)
const shenshaList = ref<any[]>([])
const total = ref(0)
const dialogVisible = ref(false)
const dialogTitle = ref('')

const queryForm = ref({
  page: 1,
  page_size: 20,
  keyword: ''
})

const formRef = ref()
const formData = ref({
  id: 0,
  name: '',
  type: '',
  description: '',
  effect: '',
  status: 1
})

const rules = {
  name: [{ required: true, message: '请输入神煞名称', trigger: 'blur' }],
  type: [{ required: true, message: '请选择神煞类型', trigger: 'change' }]
}

const typeOptions = [
  { label: '吉神', value: 'lucky' },
  { label: '凶煞', value: 'unlucky' },
  { label: '中性', value: 'neutral' }
]

const typeMap: Record<string, { label: string; type: string }> = {
  lucky: { label: '吉神', type: 'success' },
  unlucky: { label: '凶煞', type: 'danger' },
  neutral: { label: '中性', type: 'info' }
}

onMounted(() => {
  fetchList()
})

async function fetchList() {
  loading.value = true
  try {
    const res = await request({
      url: '/system/shensha',
      method: 'get',
      params: queryForm.value
    })
    shenshaList.value = res.data?.list || res.data || []
    total.value = res.data?.total || shenshaList.value.length
  } catch (error) {
    ElMessage.error('获取神煞数据失败')
  } finally {
    loading.value = false
  }
}

function handleSearch() {
  queryForm.value.page = 1
  fetchList()
}

function handleReset() {
  queryForm.value = { page: 1, page_size: 20, keyword: '' }
  fetchList()
}

function handleAdd() {
  dialogTitle.value = '新增神煞'
  formData.value = { id: 0, name: '', type: '', description: '', effect: '', status: 1 }
  dialogVisible.value = true
}

function handleEdit(row: any) {
  dialogTitle.value = '编辑神煞'
  formData.value = { ...row }
  dialogVisible.value = true
}

async function handleDelete(row: any) {
  try {
    await ElMessageBox.confirm(`确定要删除神煞「${row.name}」吗？`, '提示', {
      type: 'warning',
      confirmButtonText: '确定',
      cancelButtonText: '取消'
    })
    await request({ url: `/system/shensha/${row.id}`, method: 'delete' })
    ElMessage.success('删除成功')
    fetchList()
  } catch (error: any) {
    if (error !== 'cancel') ElMessage.error('删除失败')
  }
}

async function handleToggleStatus(row: any) {
  try {
    await request({
      url: '/shensha/toggle-status',
      method: 'post',
      data: { id: row.id, status: row.status === 1 ? 0 : 1 }
    })
    ElMessage.success('状态更新成功')
    fetchList()
  } catch {
    ElMessage.error('状态更新失败')
  }
}

async function handleSubmit() {
  if (!formRef.value) return
  await formRef.value.validate()
  submitLoading.value = true
  try {
    if (formData.value.id) {
      await request({ url: `/system/shensha/${formData.value.id}`, method: 'put', data: formData.value })
    } else {
      await request({ url: '/system/shensha', method: 'post', data: formData.value })
    }
    ElMessage.success(formData.value.id ? '更新成功' : '新增成功')
    dialogVisible.value = false
    fetchList()
  } catch {
    ElMessage.error('保存失败')
  } finally {
    submitLoading.value = false
  }
}

function handlePageChange(page: number) {
  queryForm.value.page = page
  fetchList()
}
</script>

<template>
  <div class="app-container">
    <!-- 搜索栏 -->
    <el-card shadow="never" class="search-card">
      <el-form :model="queryForm" inline>
        <el-form-item label="关键词">
          <el-input v-model="queryForm.keyword" placeholder="神煞名称" clearable @keyup.enter="handleSearch" />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">搜索</el-button>
          <el-button @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- 列表 -->
    <el-card shadow="never">
      <template #header>
        <div class="card-header">
          <span>神煞管理</span>
          <el-button type="primary" @click="handleAdd">新增神煞</el-button>
        </div>
      </template>

      <el-table :data="shenshaList" v-loading="loading" stripe border>
        <el-table-column prop="id" label="ID" width="70" />
        <el-table-column prop="name" label="神煞名称" width="140" />
        <el-table-column prop="type" label="类型" width="100">
          <template #default="{ row }">
            <el-tag :type="typeMap[row.type]?.type || 'info'" size="small">
              {{ typeMap[row.type]?.label || row.type }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="description" label="描述" min-width="180" show-overflow-tooltip />
        <el-table-column prop="effect" label="影响" min-width="180" show-overflow-tooltip />
        <el-table-column prop="status" label="状态" width="90">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'info'" size="small">
              {{ row.status === 1 ? '启用' : '禁用' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="200" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" size="small" @click="handleEdit(row)">编辑</el-button>
            <el-button link :type="row.status === 1 ? 'warning' : 'success'" size="small" @click="handleToggleStatus(row)">
              {{ row.status === 1 ? '禁用' : '启用' }}
            </el-button>
            <el-button link type="danger" size="small" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination-wrap">
        <el-pagination
          v-model:current-page="queryForm.page"
          v-model:page-size="queryForm.page_size"
          :total="total"
          :page-sizes="[20, 50, 100]"
          layout="total, sizes, prev, pager, next, jumper"
          @current-change="handlePageChange"
          @size-change="fetchList"
        />
      </div>
    </el-card>

    <!-- 新增/编辑弹窗 -->
    <el-dialog v-model="dialogVisible" :title="dialogTitle" width="560px" destroy-on-close>
      <el-form ref="formRef" :model="formData" :rules="rules" label-width="100px">
        <el-form-item label="神煞名称" prop="name">
          <el-input v-model="formData.name" placeholder="请输入神煞名称" />
        </el-form-item>
        <el-form-item label="神煞类型" prop="type">
          <el-select v-model="formData.type" placeholder="请选择类型">
            <el-option v-for="opt in typeOptions" :key="opt.value" :label="opt.label" :value="opt.value" />
          </el-select>
        </el-form-item>
        <el-form-item label="描述">
          <el-input v-model="formData.description" type="textarea" :rows="3" placeholder="请输入神煞描述" />
        </el-form-item>
        <el-form-item label="影响说明">
          <el-input v-model="formData.effect" type="textarea" :rows="3" placeholder="请输入对运势的影响说明" />
        </el-form-item>
        <el-form-item label="状态">
          <el-switch v-model="formData.status" :active-value="1" :inactive-value="0" active-text="启用" inactive-text="禁用" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="submitLoading" @click="handleSubmit">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<style scoped>
.search-card {
  margin-bottom: 16px;
}
.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.pagination-wrap {
  margin-top: 16px;
  display: flex;
  justify-content: flex-end;
}
</style>
