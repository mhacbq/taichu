<template>
  <div class="app-container">
    <div class="table-operations">
      <el-button type="primary" @click="handleAdd">
        <el-icon><Plus /></el-icon>新增分类
      </el-button>
    </div>

    <el-card shadow="never">
      <el-table :data="categoryList" v-loading="loading" stripe>
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="name" label="分类名称" width="150" />
        <el-table-column prop="code" label="分类标识" width="150" />
        <el-table-column prop="description" label="描述" min-width="200" />
        <el-table-column prop="sort" label="排序" width="80" />
        <el-table-column label="操作" width="150" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" @click="handleEdit(row)">编辑</el-button>
            <el-button link type="danger" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <el-dialog v-model="dialog.visible" :title="dialog.title" width="500px">
      <el-form :model="dialog.form" label-width="100px">
        <el-form-item label="分类名称">
          <el-input v-model="dialog.form.name" />
        </el-form-item>
        <el-form-item label="分类标识">
          <el-input v-model="dialog.form.code" />
        </el-form-item>
        <el-form-item label="排序">
          <el-input-number v-model="dialog.form.sort" :min="0" />
        </el-form-item>
        <el-form-item label="描述">
          <el-input v-model="dialog.form.description" type="textarea" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialog.visible = false">取消</el-button>
        <el-button type="primary" @click="submitForm">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getFeedbackCategories, saveFeedbackCategory, deleteFeedbackCategory } from '@/api/feedback'
import { Plus } from '@element-plus/icons-vue'

const loading = ref(false)
const categoryList = ref([])

const dialog = reactive({
  visible: false,
  isEdit: false,
  title: '新增分类',
  form: { id: null, name: '', code: '', sort: 0, description: '' }
})

onMounted(() => {
  loadCategories()
})

async function loadCategories() {
  loading.value = true
  try {
    const res = await getFeedbackCategories()
    if (res.code === 0) {
      categoryList.value = res.data
    }
  } catch (error) {
    ElMessage.error('加载分类失败')
  } finally {
    loading.value = false
  }
}

function handleAdd() {
  dialog.title = '新增分类'
  dialog.isEdit = false
  dialog.form = { id: null, name: '', code: '', sort: 0, description: '' }
  dialog.visible = true
}

function handleEdit(row) {
  dialog.title = '编辑分类'
  dialog.isEdit = true
  Object.assign(dialog.form, row)
  dialog.visible = true
}

async function handleDelete(row) {
  try {
    await ElMessageBox.confirm('确定要删除该分类吗？', '提示', { type: 'warning' })
    const res = await deleteFeedbackCategory(row.id)
    if (res.code === 0) {
      ElMessage.success('删除成功')
      loadCategories()
    }
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('删除失败')
    }
  }
}

async function submitForm() {
  if (!dialog.form.name || !dialog.form.code) {
    ElMessage.warning('请填写分类名称和标识')
    return
  }

  try {
    const res = await saveFeedbackCategory(dialog.form)
    if (res.code === 0) {
      ElMessage.success('保存成功')
      dialog.visible = false
      loadCategories()
    }
  } catch (error) {
    ElMessage.error('保存失败')
  }
}

</script>

<style scoped>
.app-container {
  padding: 20px;
}
.table-operations {
  margin-bottom: 20px;
}
</style>
