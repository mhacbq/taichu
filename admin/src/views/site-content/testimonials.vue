<template>
  <div class="testimonials-manager">
    <el-card class="mb-4">
      <template #header>
        <div class="card-header">
          <span>用户评价管理</span>
          <el-button type="primary" @click="handleAdd">
            <el-icon><Plus /></el-icon>添加评价
          </el-button>
        </div>
      </template>

      <!-- 搜索 -->
      <el-form :inline="true" :model="queryForm" class="mb-4">
        <el-form-item label="状态">
          <el-select v-model="queryForm.is_enabled" placeholder="全部" clearable>
            <el-option label="启用" :value="1" />
            <el-option label="禁用" :value="0" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">
            <el-icon><Search /></el-icon>搜索
          </el-button>
        </el-form-item>
      </el-form>

      <!-- 评价列表 -->
      <el-table :data="tableData" v-loading="loading" border>
        <el-table-column type="index" label="序号" width="60" align="center" />
        <el-table-column label="用户" width="150">
          <template #default="{ row }">
            <div class="user-info">
              <el-avatar :size="40" :src="row.avatar || defaultAvatar" />
              <span class="ml-2">{{ row.name }}</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="content" label="评价内容" min-width="300">
          <template #default="{ row }">
            <div class="content-cell">{{ row.content }}</div>
          </template>
        </el-table-column>
        <el-table-column prop="service_type" label="服务类型" width="100">
          <template #default="{ row }">
            <el-tag size="small">{{ getServiceTypeName(row.service_type) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="sort_order" label="排序" width="80" align="center" />
        <el-table-column prop="is_enabled" label="状态" width="80" align="center">
          <template #default="{ row }">
            <el-tag :type="row.is_enabled ? 'success' : 'danger'">
              {{ row.is_enabled ? '启用' : '禁用' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="150" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link @click="handleEdit(row)">
              <el-icon><Edit /></el-icon>编辑
            </el-button>
            <el-button type="danger" link @click="handleDelete(row)">
              <el-icon><Delete /></el-icon>删除
            </el-button>
          </template>
        </el-table-column>
      </el-table>

      <!-- 分页 -->
      <div class="pagination-container">
        <el-pagination
          v-model:current-page="queryForm.page"
          v-model:page-size="queryForm.limit"
          :total="total"
          :page-sizes="[10, 20, 50]"
          layout="total, sizes, prev, pager, next"
          @size-change="handleSizeChange"
          @current-change="handleCurrentChange"
        />
      </div>
    </el-card>

    <!-- 编辑对话框 -->
    <el-dialog
      v-model="dialogVisible"
      :title="dialogTitle"
      width="600px"
      destroy-on-close
    >
      <el-form ref="formRef" :model="form" :rules="rules" label-width="100px">
        <el-form-item label="用户名称" prop="name">
          <el-input v-model="form.name" placeholder="用户昵称" />
        </el-form-item>
        <el-form-item label="头像">
          <div class="avatar-uploader">
            <el-avatar
              :size="80"
              :src="form.avatar || defaultAvatar"
              class="mb-2"
            />
            <el-input
              v-model="form.avatar"
              placeholder="头像URL"
              class="mt-2"
            />
          </div>
        </el-form-item>
        <el-form-item label="评价内容" prop="content">
          <el-input
            v-model="form.content"
            type="textarea"
            :rows="4"
            placeholder="用户评价内容"
          />
        </el-form-item>
        <el-form-item label="服务类型" prop="service_type">
          <el-select v-model="form.service_type" placeholder="选择服务类型">
            <el-option label="八字分析" value="bazi" />
            <el-option label="塔罗测试" value="tarot" />
            <el-option label="每日指南" value="daily" />
          </el-select>
        </el-form-item>
        <el-form-item label="排序">
          <el-input-number v-model="form.sort_order" :min="0" :max="999" />
        </el-form-item>
        <el-form-item label="状态">
          <el-switch
            v-model="form.is_enabled"
            :active-value="1"
            :inactive-value="0"
            active-text="启用"
            inactive-text="禁用"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSubmit" :loading="submitLoading">
          保存
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Plus, Search, Edit, Delete } from '@element-plus/icons-vue'
import {
  getTestimonialList,
  saveTestimonial,
  deleteTestimonial
} from '@/api/siteContent'

const defaultAvatar = 'https://cube.elemecdn.com/3/7c/3ea6beec64369c2642b92c6726f1epng.png'

const loading = ref(false)
const submitLoading = ref(false)
const dialogVisible = ref(false)
const dialogTitle = ref('添加评价')
const formRef = ref(null)
const tableData = ref([])
const total = ref(0)

const queryForm = reactive({
  page: 1,
  limit: 10,
  is_enabled: null
})

const form = reactive({
  id: null,
  name: '',
  avatar: '',
  content: '',
  service_type: 'bazi',
  sort_order: 0,
  is_enabled: 1
})

const rules = {
  name: [{ required: true, message: '请输入用户名称', trigger: 'blur' }],
  content: [{ required: true, message: '请输入评价内容', trigger: 'blur' }],
  service_type: [{ required: true, message: '请选择服务类型', trigger: 'change' }]
}

const serviceTypeMap = {
  bazi: '八字分析',
  tarot: '塔罗测试',
  daily: '每日指南'
}

const getServiceTypeName = (type) => serviceTypeMap[type] || '其他'

// 加载数据
const loadData = async () => {
  loading.value = true
  try {
    const res = await getTestimonialList(queryForm)
    if (res.code === 200) {
      tableData.value = res.data.list
      total.value = res.data.total
    }
  } catch (error) {
    ElMessage.error('加载数据失败')
  } finally {
    loading.value = false
  }
}

// 搜索
const handleSearch = () => {
  queryForm.page = 1
  loadData()
}

// 分页
const handleSizeChange = (val) => {
  queryForm.limit = val
  loadData()
}

const handleCurrentChange = (val) => {
  queryForm.page = val
  loadData()
}

// 添加
const handleAdd = () => {
  dialogTitle.value = '添加评价'
  Object.assign(form, {
    id: null,
    name: '',
    avatar: '',
    content: '',
    service_type: 'bazi',
    sort_order: 0,
    is_enabled: 1
  })
  dialogVisible.value = true
}

// 编辑
const handleEdit = (row) => {
  dialogTitle.value = '编辑评价'
  Object.assign(form, row)
  dialogVisible.value = true
}

// 删除
const handleDelete = async (row) => {
  try {
    await ElMessageBox.confirm('确定要删除这条评价吗？', '提示', {
      type: 'warning'
    })
    const res = await deleteTestimonial(row.id)
    if (res.code === 200) {
      ElMessage.success('删除成功')
      loadData()
    }
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('删除失败')
    }
  }
}

// 提交
const handleSubmit = async () => {
  const valid = await formRef.value.validate().catch(() => false)
  if (!valid) return

  submitLoading.value = true
  try {
    const res = await saveTestimonial(form)
    if (res.code === 200) {
      ElMessage.success('保存成功')
      dialogVisible.value = false
      loadData()
    }
  } catch (error) {
    ElMessage.error('保存失败')
  } finally {
    submitLoading.value = false
  }
}

onMounted(() => {
  loadData()
})
</script>

<style scoped>
.testimonials-manager {
  padding: 20px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.user-info {
  display: flex;
  align-items: center;
}

.content-cell {
  max-height: 60px;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
}

.avatar-uploader {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}

.pagination-container {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
}
</style>