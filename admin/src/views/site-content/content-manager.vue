<template>
  <div class="content-manager">
    <el-card class="mb-4">
      <template #header>
        <div class="card-header">
          <span>页面内容管理</span>
          <el-button type="primary" @click="handleAdd">
            <el-icon><Plus /></el-icon>添加内容
          </el-button>
        </div>
      </template>

      <!-- 页面选择 -->
      <el-form :inline="true" :model="queryForm" class="mb-4">
        <el-form-item label="页面">
          <el-select v-model="queryForm.page" placeholder="选择页面" @change="handlePageChange">
            <el-option label="首页" value="home" />
            <el-option label="八字页" value="bazi" />
            <el-option label="塔罗页" value="tarot" />
            <el-option label="每日运势" value="daily" />
            <el-option label="帮助中心" value="help" />
            <el-option label="登录页" value="login" />
            <el-option label="注册页" value="register" />
          </el-select>
        </el-form-item>
        <el-form-item label="关键字">
          <el-input
            v-model="queryForm.key"
            placeholder="搜索内容键名"
            clearable
            @keyup.enter="handleSearch"
          />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">
            <el-icon><Search /></el-icon>搜索
          </el-button>
        </el-form-item>
      </el-form>

      <!-- 内容列表 -->
      <el-table :data="tableData" v-loading="loading" border>
        <el-table-column type="index" label="序号" width="60" align="center" />
        <el-table-column prop="key" label="键名" width="150" show-overflow-tooltip />
        <el-table-column prop="value" label="内容值" min-width="300">
          <template #default="{ row }">
            <div v-if="row.value && row.value.length > 100" class="text-truncate">
              {{ row.value.substring(0, 100) }}...
            </div>
            <div v-else>{{ row.value }}</div>
          </template>
        </el-table-column>
        <el-table-column prop="description" label="说明" width="180" show-overflow-tooltip />
        <el-table-column prop="sort_order" label="排序" width="80" align="center" />
        <el-table-column prop="is_enabled" label="状态" width="80" align="center">
          <template #default="{ row }">
            <el-tag :type="row.is_enabled ? 'success' : 'danger'">
              {{ row.is_enabled ? '启用' : '禁用' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="180" fixed="right">
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
    </el-card>

    <!-- 编辑对话框 -->
    <el-dialog
      v-model="dialogVisible"
      :title="dialogTitle"
      width="700px"
      destroy-on-close
    >
      <el-form ref="formRef" :model="form" :rules="rules" label-width="100px">
        <el-form-item label="页面" prop="page">
          <el-select v-model="form.page" placeholder="选择页面">
            <el-option label="首页" value="home" />
            <el-option label="八字页" value="bazi" />
            <el-option label="塔罗页" value="tarot" />
            <el-option label="每日运势" value="daily" />
            <el-option label="帮助中心" value="help" />
            <el-option label="登录页" value="login" />
            <el-option label="注册页" value="register" />
          </el-select>
        </el-form-item>
        <el-form-item label="键名" prop="key">
          <el-input v-model="form.key" placeholder="如：hero_title, feature_1_title" />
          <el-text type="info" size="small">建议使用小写字母和下划线</el-text>
        </el-form-item>
        <el-form-item label="内容值" prop="value">
          <el-input
            v-model="form.value"
            type="textarea"
            :rows="6"
            placeholder="输入内容值"
          />
        </el-form-item>
        <el-form-item label="说明">
          <el-input v-model="form.description" placeholder="简要说明此内容的用途" />
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
  getContentList,
  saveContent,
  deleteContent
} from '@/api/siteContent'

const loading = ref(false)
const submitLoading = ref(false)
const dialogVisible = ref(false)
const dialogTitle = ref('添加内容')
const formRef = ref(null)
const tableData = ref([])

const queryForm = reactive({
  page: 'home',
  key: ''
})

const form = reactive({
  id: null,
  page: 'home',
  key: '',
  value: '',
  description: '',
  sort_order: 0,
  is_enabled: 1
})

const rules = {
  page: [{ required: true, message: '请选择页面', trigger: 'change' }],
  key: [{ required: true, message: '请输入键名', trigger: 'blur' }],
  value: [{ required: true, message: '请输入内容值', trigger: 'blur' }]
}

// 加载数据
const loadData = async () => {
  loading.value = true
  try {
    const res = await getContentList({
      page: queryForm.page,
      key: queryForm.key
    })
    if (res.code === 0) {
      tableData.value = res.data
    }
  } catch (error) {
    ElMessage.error('加载数据失败')
  } finally {
    loading.value = false
  }
}

// 页面切换
const handlePageChange = () => {
  loadData()
}

// 搜索
const handleSearch = () => {
  loadData()
}

// 添加
const handleAdd = () => {
  dialogTitle.value = '添加内容'
  Object.assign(form, {
    id: null,
    page: queryForm.page,
    key: '',
    value: '',
    description: '',
    sort_order: 0,
    is_enabled: 1
  })
  dialogVisible.value = true
}

// 编辑
const handleEdit = (row) => {
  dialogTitle.value = '编辑内容'
  Object.assign(form, row)
  dialogVisible.value = true
}

// 删除
const handleDelete = async (row) => {
  try {
    await ElMessageBox.confirm('确定要删除这条内容吗？', '提示', {
      type: 'warning'
    })
    const res = await deleteContent(row.id)
    if (res.code === 0) {
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
    const res = await saveContent(form)
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
.content-manager {
  padding: 20px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.text-truncate {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>