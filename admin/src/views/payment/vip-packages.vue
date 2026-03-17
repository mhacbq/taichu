<template>
  <div class="app-container">
    <div class="action-header">
      <el-button type="primary" @click="handleAdd">
        <el-icon><Plus /></el-icon>添加套餐
      </el-button>
    </div>

    <el-table v-loading="loading" :data="packageList" stripe border>
      <el-table-column type="index" label="#" width="60" />
      <el-table-column prop="package_name" label="套餐名称" width="150" />
      <el-table-column prop="duration" label="时长(天)" width="100" />
      <el-table-column prop="price" label="售价" width="100">
        <template #default="{ row }">
          <span class="price">¥{{ row.price }}</span>
        </template>
      </el-table-column>
      <el-table-column prop="original_price" label="原价" width="100">
        <template #default="{ row }">
          <span class="original-price">¥{{ row.original_price }}</span>
        </template>
      </el-table-column>
      <el-table-column prop="status" label="状态" width="100">
        <template #default="{ row }">
          <el-switch 
            v-model="row.status" 
            :active-value="1" 
            :inactive-value="0" 
            @change="handleStatusChange(row)"
          />
        </template>
      </el-table-column>
      <el-table-column prop="is_recommend" label="推荐" width="100">
        <template #default="{ row }">
          <el-tag :type="row.is_recommend ? 'success' : 'info'">
            {{ row.is_recommend ? '推荐' : '普通' }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="sort_order" label="排序" width="80" />
      <el-table-column label="操作" min-width="150" fixed="right">
        <template #default="{ row }">
          <el-button link type="primary" @click="handleEdit(row)">编辑</el-button>
        </template>
      </el-table-column>
    </el-table>

    <!-- 编辑/新增对话框 -->
    <el-dialog v-model="dialogVisible" :title="form.id ? '编辑套餐' : '添加套餐'" width="600px">
      <el-form :model="form" label-width="100px" :rules="rules" ref="formRef">
        <el-form-item label="套餐名称" prop="package_name">
          <el-input v-model="form.package_name" placeholder="如：月度VIP、年度VIP" />
        </el-form-item>
        <el-form-item label="有效期(天)" prop="duration">
          <el-input-number v-model="form.duration" :min="1" />
        </el-form-item>
        <el-form-item label="销售价格" prop="price">
          <el-input-number v-model="form.price" :min="0" :precision="2" />
        </el-form-item>
        <el-form-item label="划线原价" prop="original_price">
          <el-input-number v-model="form.original_price" :min="0" :precision="2" />
        </el-form-item>
        <el-form-item label="推荐套餐">
          <el-switch v-model="form.is_recommend" :active-value="1" :inactive-value="0" />
        </el-form-item>
        <el-form-item label="排序" prop="sort_order">
          <el-input-number v-model="form.sort_order" :min="0" />
        </el-form-item>
        <el-form-item label="特权描述">
          <el-input v-model="form.description" type="textarea" rows="3" placeholder="简单的套餐介绍" />
        </el-form-item>
        <el-form-item label="状态">
          <el-radio-group v-model="form.status">
            <el-radio :label="1">启用</el-radio>
            <el-radio :label="0">禁用</el-radio>
          </el-radio-group>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSave" :loading="saving">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { Plus } from '@element-plus/icons-vue'
import { getVipPackages, saveVipPackage } from '@/api/payment'

const loading = ref(false)
const saving = ref(false)
const packageList = ref([])
const dialogVisible = ref(false)
const formRef = ref(null)

const form = ref({
  id: null,
  package_name: '',
  duration: 30,
  price: 0,
  original_price: 0,
  description: '',
  features: [],
  sort_order: 0,
  is_recommend: 0,
  status: 1
})

const rules = {
  package_name: [{ required: true, message: '请输入套餐名称', trigger: 'blur' }],
  duration: [{ required: true, message: '请输入时长', trigger: 'blur' }],
  price: [{ required: true, message: '请输入售价', trigger: 'blur' }]
}

onMounted(() => {
  loadPackages()
})

async function loadPackages() {
  loading.value = true
  try {
    const { data } = await getVipPackages()
    packageList.value = data || []
  } finally {
    loading.value = false
  }
}

function handleAdd() {
  form.value = {
    id: null,
    package_name: '',
    duration: 30,
    price: 0,
    original_price: 0,
    description: '',
    features: [],
    sort_order: 0,
    is_recommend: 0,
    status: 1
  }
  dialogVisible.value = true
}

function handleEdit(row) {
  form.value = { ...row }
  dialogVisible.value = true
}

async function handleSave() {
  formRef.value.validate(async (valid) => {
    if (valid) {
      saving.value = true
      try {
        await saveVipPackage(form.value)
        ElMessage.success('保存成功')
        dialogVisible.value = false
        loadPackages()
      } finally {
        saving.value = false
      }
    }
  })
}

async function handleStatusChange(row) {
  try {
    await saveVipPackage(row)
    ElMessage.success('状态更新成功')
  } catch (error) {
    row.status = row.status === 1 ? 0 : 1 // 还原状态
  }
}
</script>

<style lang="scss" scoped>
.action-header {
  margin-bottom: 20px;
}

.price {
  color: #f56c6c;
  font-weight: bold;
}

.original-price {
  color: #909399;
  text-decoration: line-through;
  font-size: 12px;
}
</style>
