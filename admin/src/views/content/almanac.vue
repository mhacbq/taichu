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
          <el-button type="success" @click="handleAdd">新增数据</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <el-card shadow="never">
      <el-table :data="almanacList" v-loading="loading" stripe>
        <el-table-column prop="date" label="日期" width="120" />
        <el-table-column prop="suit" label="宜" min-width="150" show-overflow-tooltip />
        <el-table-column prop="avoid" label="忌" min-width="150" show-overflow-tooltip />
        <el-table-column prop="ganzhi" label="干支" width="120" />
        <el-table-column prop="wuxing" label="五行" width="100" />
        <el-table-column label="操作" width="150" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" @click="handleEdit(row)">编辑</el-button>
            <el-button link type="danger" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <el-dialog v-model="dialog.visible" :title="dialog.form.id ? '编辑黄历数据' : '新增黄历数据'" width="600px">
      <el-form :model="dialog.form" label-width="80px" ref="formRef">
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
        <el-button type="primary" :loading="submitLoading" @click="handleSubmit">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { getAlmanacList, createAlmanac, updateAlmanac, deleteAlmanac } from '@/api/content'
import { ElMessage, ElMessageBox } from 'element-plus'

const loading = ref(false)
const submitLoading = ref(false)
const almanacList = ref([])
const total = ref(0)
const queryForm = reactive({
  date: '',
  page: 1,
  limit: 20
})

const dialog = reactive({
  visible: false,
  form: {
    id: null,
    date: '',
    suit: '',
    avoid: '',
    ganzhi: '',
    wuxing: ''
  }
})

const loadData = async () => {
  loading.value = true
  try {
    const res = await getAlmanacList(queryForm)
    if (res.code === 200) {
      almanacList.value = res.data.list
      total.value = res.data.total
    }
  } catch (error) {
    ElMessage.error('加载数据失败')
  } finally {
    loading.value = false
  }
}

const handleSearch = () => {
  queryForm.page = 1
  loadData()
}

const handleAdd = () => {
  dialog.form = {
    id: null,
    date: '',
    suit: '',
    avoid: '',
    ganzhi: '',
    wuxing: ''
  }
  dialog.visible = true
}

const handleEdit = (row) => {
  dialog.form = { ...row }
  dialog.visible = true
}

const handleSubmit = async () => {
  if (!dialog.form.date) {
    ElMessage.warning('请选择日期')
    return
  }
  
  submitLoading.value = true
  try {
    let res
    if (dialog.form.id) {
      res = await updateAlmanac(dialog.form.id, dialog.form)
    } else {
      res = await createAlmanac(dialog.form)
    }
    
    if (res.code === 200) {
      ElMessage.success('保存成功')
      dialog.visible = false
      loadData()
    }
  } catch (error) {
    ElMessage.error('保存失败')
  } finally {
    submitLoading.value = false
  }
}

const handleDelete = (row) => {
  ElMessageBox.confirm('确定要删除该条数据吗？', '提示', {
    confirmButtonText: '确定',
    cancelButtonText: '取消',
    type: 'warning'
  }).then(async () => {
    try {
      const res = await deleteAlmanac(row.id)
      if (res.code === 200) {
        ElMessage.success('删除成功')
        loadData()
      }
    } catch (error) {
      ElMessage.error('删除失败')
    }
  })
}

onMounted(() => {
  loadData()
})
</script>

<style scoped>
.app-container { padding: 20px; }
.search-form { margin-bottom: 20px; }
</style>
