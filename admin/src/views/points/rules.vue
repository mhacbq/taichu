<template>
  <div class="app-container">
    <div class="table-operations">
      <el-button type="primary" @click="handleAdd">
        <el-icon><Plus /></el-icon>新增规则
      </el-button>
    </div>

    <el-card shadow="never">
      <el-table :data="ruleList" v-loading="loading" stripe>
        <el-table-column prop="name" label="规则名称" width="150" />
        <el-table-column prop="code" label="规则标识" width="150" />
        <el-table-column prop="points" label="变动积分" width="100">
          <template #default="{ row }">
            <span :class="row.points >= 0 ? 'text-success' : 'text-danger'">
              {{ row.points >= 0 ? '+' : '' }}{{ row.points }}
            </span>
          </template>
        </el-table-column>
        <el-table-column prop="description" label="描述" min-width="200" />
        <el-table-column prop="status" label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'info'">
              {{ row.status === 1 ? '启用' : '禁用' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="150" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" @click="handleEdit(row)">编辑</el-button>
            <el-button link :type="row.status === 1 ? 'danger' : 'success'" @click="toggleStatus(row)">
              {{ row.status === 1 ? '禁用' : '启用' }}
            </el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <!-- 规则表单弹窗 -->
    <el-dialog v-model="dialog.visible" :title="dialog.title" width="500px">
      <el-form :model="dialog.form" label-width="100px">
        <el-form-item label="规则名称">
          <el-input v-model="dialog.form.name" placeholder="请输入规则名称" />
        </el-form-item>
        <el-form-item label="规则标识">
          <el-input v-model="dialog.form.code" placeholder="请输入唯一标识" />
        </el-form-item>
        <el-form-item label="变动积分">
          <el-input-number v-model="dialog.form.points" />
        </el-form-item>
        <el-form-item label="规则描述">
          <el-input v-model="dialog.form.description" type="textarea" rows="3" />
        </el-form-item>
        <el-form-item label="状态">
          <el-radio-group v-model="dialog.form.status">
            <el-radio :label="1">启用</el-radio>
            <el-radio :label="0">禁用</el-radio>
          </el-radio-group>
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
import { getPointsRules, savePointsRules } from '@/api/points'
import { Plus } from '@element-plus/icons-vue'

const loading = ref(false)
const ruleList = ref([])

const dialog = reactive({
  visible: false,
  isEdit: false,
  title: '新增规则',
  form: {
    id: null,
    name: '',
    code: '',
    points: 10,
    description: '',
    status: 1
  }
})

onMounted(() => {
  loadRules()
})

async function loadRules() {
  loading.value = true
  try {
    const res = await getPointsRules()
    if (res.code === 200) {
      ruleList.value = res.data
    }
  } catch (error) {
    ElMessage.error('加载规则失败')
  } finally {
    loading.value = false
  }
}

function handleAdd() {
  dialog.title = '新增规则'
  dialog.isEdit = false
  dialog.form = { id: null, name: '', code: '', points: 10, description: '', status: 1 }
  dialog.visible = true
}

function handleEdit(row) {
  dialog.title = '编辑规则'
  dialog.isEdit = true
  Object.assign(dialog.form, row)
  dialog.visible = true
}

async function toggleStatus(row) {
  try {
    await ElMessageBox.confirm(`确定要${row.status === 1 ? '禁用' : '启用'}该规则吗？`, '提示')
    const res = await savePointsRules({
      ...row,
      status: row.status === 1 ? 0 : 1
    })
    if (res.code === 200) {
      ElMessage.success('操作成功')
      loadRules()
    }
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('操作失败')
    }
  }
}

async function submitForm() {
  if (!dialog.form.name || !dialog.form.code) {
    ElMessage.warning('请填写规则名称和标识')
    return
  }
  
  try {
    const res = await savePointsRules(dialog.form)
    if (res.code === 200) {
      ElMessage.success('保存成功')
      dialog.visible = false
      loadRules()
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
.text-success { color: #67C23A; }
.text-danger { color: #F56C6C; }
</style>
