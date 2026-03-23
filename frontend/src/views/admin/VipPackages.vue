<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
  getVipPackages,
  saveVipPackage,
  deleteVipPackage,
  batchUpdateVipPackageStatus
} from '../../api/admin'

const loading = ref(false)
const packages = ref([])
const dialogVisible = ref(false)
const currentPackage = ref({
  id: null,
  name: '',
  price: 0,
  points: 0,
  duration: 1,
  description: '',
  status: 1,
  sort_order: 0
})

const loadPackages = async () => {
  loading.value = true
  try {
    const response = await getVipPackages()
    if (response.code === 200) {
      packages.value = response.data?.list || response.data || []
    } else {
      ElMessage.error(response.message || '加载失败')
    }
  } catch (error) {
    console.error('加载套餐失败:', error)
    ElMessage.error('加载失败')
  } finally {
    loading.value = false
  }
}

const handleAdd = () => {
  currentPackage.value = {
    id: null,
    name: '',
    price: 0,
    points: 0,
    duration: 1,
    description: '',
    status: 1,
    sort_order: 0
  }
  dialogVisible.value = true
}

const handleEdit = (row) => {
  currentPackage.value = { ...row }
  dialogVisible.value = true
}

const handleSave = async () => {
  if (!currentPackage.value.name) {
    ElMessage.warning('请输入套餐名称')
    return
  }

  loading.value = true
  try {
    const response = await saveVipPackage(currentPackage.value)
    if (response.code === 200) {
      ElMessage.success('保存成功')
      dialogVisible.value = false
      loadPackages()
    } else {
      ElMessage.error(response.message || '保存失败')
    }
  } catch (error) {
    console.error('保存失败:', error)
    ElMessage.error('保存失败')
  } finally {
    loading.value = false
  }
}

const handleDelete = async (row) => {
  try {
    await ElMessageBox.confirm(`确定要删除套餐「${row.name}」吗？`, '确认删除', {
      type: 'warning'
    })
    loading.value = true
    const response = await deleteVipPackage(row.id)
    if (response.code === 200) {
      ElMessage.success('删除成功')
      loadPackages()
    } else {
      ElMessage.error(response.message || '删除失败')
    }
  } catch (error) {
    if (error !== 'cancel') {
      console.error('删除失败:', error)
      ElMessage.error('删除失败')
    }
  } finally {
    loading.value = false
  }
}

const handleToggleStatus = async (row) => {
  const newStatus = row.status === 1 ? 0 : 1
  const actionText = newStatus === 1 ? '上架' : '下架'
  try {
    const response = await batchUpdateVipPackageStatus([row.id], newStatus)
    if (response.code === 200) {
      row.status = newStatus
      ElMessage.success(`${actionText}成功`)
    } else {
      ElMessage.error(response.message || `${actionText}失败`)
    }
  } catch (error) {
    console.error(`${actionText}失败:`, error)
    ElMessage.error(`${actionText}失败`)
  }
}

onMounted(() => {
  loadPackages()
})
</script>

<template>
  <div class="admin-vip-packages" v-loading="loading">
    <div class="page-header">
      <h2>VIP套餐管理</h2>
      <el-button type="primary" @click="handleAdd">新增套餐</el-button>
    </div>

    <!-- 套餐列表 -->
    <div class="packages-grid">
      <el-card v-for="pkg in packages" :key="pkg.id" class="package-card">
        <div class="package-header">
          <h3>{{ pkg.name }}</h3>
          <el-tag :type="pkg.status === 1 ? 'success' : 'info'">
            {{ pkg.status === 1 ? '上架' : '下架' }}
          </el-tag>
        </div>
        <div class="package-body">
          <div class="price">¥{{ pkg.price }}</div>
          <div class="points">{{ pkg.points }} 积分</div>
          <div class="duration">{{ pkg.duration }} 个月</div>
          <p class="description">{{ pkg.description }}</p>
        </div>
        <div class="package-footer">
          <el-button size="small" @click="handleEdit(pkg)">编辑</el-button>
          <el-button
            size="small"
            :type="pkg.status === 1 ? 'warning' : 'success'"
            @click="handleToggleStatus(pkg)"
          >{{ pkg.status === 1 ? '下架' : '上架' }}</el-button>
          <el-button size="small" type="danger" @click="handleDelete(pkg)">删除</el-button>
        </div>
      </el-card>
    </div>

    <!-- 编辑对话框 -->
    <el-dialog v-model="dialogVisible" :title="currentPackage.id ? '编辑套餐' : '新增套餐'" width="500px">
      <el-form :model="currentPackage" label-width="100px">
        <el-form-item label="套餐名称">
          <el-input v-model="currentPackage.name" placeholder="请输入套餐名称" />
        </el-form-item>
        <el-form-item label="价格">
          <el-input-number v-model="currentPackage.price" :min="0" :precision="2" />
        </el-form-item>
        <el-form-item label="积分数量">
          <el-input-number v-model="currentPackage.points" :min="0" />
        </el-form-item>
        <el-form-item label="有效期(月)">
          <el-input-number v-model="currentPackage.duration" :min="1" />
        </el-form-item>
        <el-form-item label="描述">
          <el-input v-model="currentPackage.description" type="textarea" :rows="3" placeholder="请输入套餐描述" />
        </el-form-item>
        <el-form-item label="状态">
          <el-switch v-model="currentPackage.status" :active-value="1" :inactive-value="0" />
        </el-form-item>
        <el-form-item label="排序">
          <el-input-number v-model="currentPackage.sort_order" :min="0" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSave" :loading="loading">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<style scoped>
.admin-vip-packages {
  padding: 24px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.packages-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 20px;
}

.package-card {
  border: 2px solid #d4af37;
}

.package-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.package-header h3 {
  margin: 0;
  color: #d4af37;
}

.package-body {
  text-align: center;
  margin-bottom: 20px;
}

.price {
  font-size: 32px;
  font-weight: bold;
  color: #d4af37;
  margin-bottom: 10px;
}

.points {
  font-size: 18px;
  color: #333;
  margin-bottom: 8px;
}

.duration {
  font-size: 14px;
  color: #666;
  margin-bottom: 15px;
}

.description {
  font-size: 14px;
  color: #666;
  line-height: 1.6;
  margin: 0;
}

.package-footer {
  display: flex;
  justify-content: center;
  gap: 10px;
}
</style>
