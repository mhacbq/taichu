<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getVipPackages, saveVipPackage, deleteVipPackage, batchUpdateVipPackageStatus } from '@/api/payment'

const loading = ref(false)
const packagesList = ref([])

// 弹窗状态
const dialogVisible = ref(false)
const dialogTitle = ref('新增套餐')
const formLoading = ref(false)

const formRef = ref(null)
const form = reactive({
  id: null,
  name: '',
  price: '',
  original_price: '',
  points: 0,
  description: '',
  status: 1
})

const formRules = {
  name: [{ required: true, message: '请输入套餐名称', trigger: 'blur' }],
  price: [{ required: true, message: '请输入售价', trigger: 'blur' }]
}

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

// 新增套餐
function handleAdd() {
  dialogTitle.value = '新增套餐'
  Object.assign(form, {
    id: null,
    name: '',
    price: '',
    original_price: '',
    points: 0,
    description: '',
    status: 1
  })
  dialogVisible.value = true
}

// 编辑套餐
function handleEdit(row) {
  dialogTitle.value = '编辑套餐'
  Object.assign(form, {
    id: row.id,
    name: row.name,
    price: row.price,
    original_price: row.original_price,
    points: row.points || 0,
    description: row.description || '',
    status: row.status
  })
  dialogVisible.value = true
}

// 上下架
async function handleToggleStatus(row) {
  const newStatus = row.status === 1 ? 0 : 1
  const action = newStatus === 1 ? '上架' : '下架'
  try {
    await ElMessageBox.confirm(`确定要${action}套餐"${row.name}"吗？`, '提示', { type: 'warning' })
    const res = await batchUpdateVipPackageStatus({ ids: [row.id], status: newStatus })
    if (res.code === 200) {
      ElMessage.success(`${action}成功`)
      fetchPackagesList()
    }
  } catch (e) {
    if (e !== 'cancel') ElMessage.error(`${action}失败`)
  }
}

// 删除套餐
async function handleDelete(row) {
  try {
    await ElMessageBox.confirm(`确定要删除套餐"${row.name}"吗？此操作不可恢复！`, '警告', { type: 'warning' })
    const res = await deleteVipPackage(row.id)
    if (res.code === 200) {
      ElMessage.success('删除成功')
      fetchPackagesList()
    }
  } catch (e) {
    if (e !== 'cancel') ElMessage.error('删除失败')
  }
}

// 提交表单
async function handleSubmit() {
  if (!formRef.value) return
  await formRef.value.validate(async (valid) => {
    if (!valid) return
    formLoading.value = true
    try {
      const res = await saveVipPackage({ ...form })
      if (res.code === 200) {
        ElMessage.success(form.id ? '更新成功' : '创建成功')
        dialogVisible.value = false
        fetchPackagesList()
      } else {
        ElMessage.error(res.msg || '操作失败')
      }
    } catch (error) {
      ElMessage.error('操作失败')
    } finally {
      formLoading.value = false
    }
  })
}
</script>

<template>
  <div class="app-container">
    <el-card v-loading="loading">
      <template #header>
        <div class="card-header">
          <span>VIP套餐管理</span>
          <el-button type="primary" @click="handleAdd">新增套餐</el-button>
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
            <el-button link type="primary" size="small" @click="handleEdit(row)">编辑</el-button>
            <el-button link type="warning" size="small" @click="handleToggleStatus(row)">
              {{ row.status === 1 ? '下架' : '上架' }}
            </el-button>
            <el-button link type="danger" size="small" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <!-- 新增/编辑弹窗 -->
    <el-dialog v-model="dialogVisible" :title="dialogTitle" width="500px" destroy-on-close>
      <el-form ref="formRef" :model="form" :rules="formRules" label-width="100px">
        <el-form-item label="套餐名称" prop="name">
          <el-input v-model="form.name" placeholder="请输入套餐名称" />
        </el-form-item>
        <el-form-item label="售价(元)" prop="price">
          <el-input-number v-model="form.price" :min="0" :precision="2" style="width: 100%" />
        </el-form-item>
        <el-form-item label="原价(元)">
          <el-input-number v-model="form.original_price" :min="0" :precision="2" style="width: 100%" />
        </el-form-item>
        <el-form-item label="赠送积分">
          <el-input-number v-model="form.points" :min="0" style="width: 100%" />
        </el-form-item>
        <el-form-item label="套餐描述">
          <el-input v-model="form.description" type="textarea" :rows="3" placeholder="请输入套餐描述" />
        </el-form-item>
        <el-form-item label="状态">
          <el-radio-group v-model="form.status">
            <el-radio :label="1">上架</el-radio>
            <el-radio :label="0">下架</el-radio>
          </el-radio-group>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="formLoading" @click="handleSubmit">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<style scoped>
.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
</style>
