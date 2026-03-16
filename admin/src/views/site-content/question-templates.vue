<template>
  <div class="question-templates-manager">
    <el-card class="mb-4">
      <template #header>
        <div class="card-header">
          <span>塔罗问题模板管理</span>
          <el-button type="primary" @click="handleAdd">
            <el-icon><Plus /></el-icon>添加模板
          </el-button>
        </div>
      </template>

      <!-- 搜索 -->
      <el-form :inline="true" :model="queryForm" class="mb-4">
        <el-form-item label="分类">
          <el-select v-model="queryForm.category" placeholder="全部" clearable>
            <el-option label="感情" value="love" />
            <el-option label="事业" value="career" />
            <el-option label="学业" value="study" />
            <el-option label="生活" value="life" />
            <el-option label="抉择" value="choice" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">
            <el-icon><Search /></el-icon>搜索
          </el-button>
        </el-form-item>
      </el-form>

      <!-- 模板列表 -->
      <el-table :data="tableData" v-loading="loading" border>
        <el-table-column type="index" label="序号" width="60" align="center" />
        <el-table-column prop="category" label="分类" width="100">
          <template #default="{ row }">
            <el-tag :type="getCategoryType(row.category)" size="small">
              {{ getCategoryName(row.category) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="question" label="问题模板" min-width="400" />
        <el-table-column prop="use_count" label="使用次数" width="100" align="center" />
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
        <el-form-item label="分类" prop="category">
          <el-select v-model="form.category" placeholder="选择分类">
            <el-option label="感情" value="love" />
            <el-option label="事业" value="career" />
            <el-option label="学业" value="study" />
            <el-option label="生活" value="life" />
            <el-option label="抉择" value="choice" />
          </el-select>
        </el-form-item>
        <el-form-item label="问题模板" prop="question">
          <el-input
            v-model="form.question"
            type="textarea"
            :rows="3"
            placeholder="输入问题模板，例如：我和TA的感情发展如何？"
          />
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
  getQuestionList,
  saveQuestion
} from '@/api/siteContent'

const loading = ref(false)
const submitLoading = ref(false)
const dialogVisible = ref(false)
const dialogTitle = ref('添加模板')
const formRef = ref(null)
const tableData = ref([])
const total = ref(0)

const queryForm = reactive({
  page: 1,
  limit: 10,
  category: null
})

const form = reactive({
  id: null,
  category: 'love',
  question: '',
  sort_order: 0,
  is_enabled: 1
})

const rules = {
  category: [{ required: true, message: '请选择分类', trigger: 'change' }],
  question: [{ required: true, message: '请输入问题模板', trigger: 'blur' }]
}

const categoryMap = {
  love: '感情',
  career: '事业',
  study: '学业',
  life: '生活',
  choice: '抉择'
}

const categoryTypeMap = {
  love: 'danger',
  career: 'primary',
  study: 'success',
  life: 'info',
  choice: 'warning'
}

const getCategoryName = (category) => categoryMap[category] || '其他'
const getCategoryType = (category) => categoryTypeMap[category] || 'info'

// 加载数据
const loadData = async () => {
  loading.value = true
  try {
    const res = await getQuestionList(queryForm)
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
  dialogTitle.value = '添加模板'
  Object.assign(form, {
    id: null,
    category: 'love',
    question: '',
    sort_order: 0,
    is_enabled: 1
  })
  dialogVisible.value = true
}

// 编辑
const handleEdit = (row) => {
  dialogTitle.value = '编辑模板'
  Object.assign(form, row)
  dialogVisible.value = true
}

// 删除
const handleDelete = async (row) => {
  try {
    await ElMessageBox.confirm('确定要删除这个模板吗？', '提示', {
      type: 'warning'
    })
    ElMessage.success('删除成功')
    loadData()
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
    const res = await saveQuestion(form)
    if (res.code === 0) {
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
.question-templates-manager {
  padding: 20px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.pagination-container {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
}
</style>