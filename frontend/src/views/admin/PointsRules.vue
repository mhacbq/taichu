<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
  getPointsRules,
  savePointsRule,
  deletePointsRule
} from '../../api/admin'

const loading = ref(false)
const rules = ref([])
const dialogVisible = ref(false)
const dialogTitle = ref('新增规则')
const currentForm = ref({
  type: '',
  points: 0,
  description: ''
})

const loadRules = async () => {
  loading.value = true
  try {
    const response = await getPointsRules()
    if (response.code === 200) {
      rules.value = response.data || []
    } else {
      ElMessage.error(response.message || '加载失败')
    }
  } catch (error) {
    console.error('加载积分规则失败:', error)
    ElMessage.error('加载失败')
  } finally {
    loading.value = false
  }
}

const handleAdd = () => {
  dialogTitle.value = '新增规则'
  currentForm.value = { type: '', points: 0, description: '' }
  dialogVisible.value = true
}

const handleEdit = (row) => {
  dialogTitle.value = '编辑规则'
  currentForm.value = { ...row }
  dialogVisible.value = true
}

const handleDelete = async (row) => {
  try {
    await ElMessageBox.confirm('确定要删除这条规则吗？', '确认删除', {
      type: 'warning'
    })
    
    const response = await deletePointsRule(row.id)
    if (response.code === 200) {
      ElMessage.success('删除成功')
      loadRules()
    } else {
      ElMessage.error(response.message || '删除失败')
    }
  } catch (error) {
    if (error !== 'cancel') {
      console.error('删除失败:', error)
      ElMessage.error('删除失败')
    }
  }
}

const handleSubmit = async () => {
  if (!currentForm.value.type) {
    ElMessage.warning('请选择规则类型')
    return
  }

  if (currentForm.value.points <= 0) {
    ElMessage.warning('请输入积分数值')
    return
  }

  try {
    const response = await savePointsRule(currentForm.value)
    if (response.code === 200) {
      ElMessage.success('保存成功')
      dialogVisible.value = false
      loadRules()
    } else {
      ElMessage.error(response.message || '保存失败')
    }
  } catch (error) {
    console.error('保存失败:', error)
    ElMessage.error('保存失败')
  }
}

onMounted(() => {
  loadRules()
})
</script>

<template>
  <div class="admin-points-rules">
    <div class="page-header">
      <h2>积分规则</h2>
      <el-button type="primary" @click="handleAdd">新增规则</el-button>
    </div>

    <!-- 规则列表 -->
    <div class="table-container">
      <el-table v-loading="loading" :data="rules" stripe>
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="type" label="类型" min-width="150">
          <template #default="{ row }">
            <el-tag>{{ row.type }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="points" label="积分数值" width="120" />
        <el-table-column prop="description" label="描述" min-width="200" show-overflow-tooltip />
        <el-table-column prop="created_at" label="创建时间" width="180" />
        <el-table-column label="操作" width="150" fixed="right">
          <template #default="{ row }">
            <el-button size="small" @click="handleEdit(row)">编辑</el-button>
            <el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <!-- 弹窗 -->
    <el-dialog
      v-model="dialogVisible"
      :title="dialogTitle"
      width="500px"
    >
      <el-form :model="currentForm" label-width="100px">
        <el-form-item label="规则类型">
          <el-select v-model="currentForm.type" placeholder="请选择">
            <el-option label="每日签到" value="daily_sign" />
            <el-option label="八字排盘" value="bazi_analysis" />
            <el-option label="塔罗占卜" value="tarot_draw" />
            <el-option label="分享奖励" value="share" />
            <el-option label="邀请好友" value="invite" />
          </el-select>
        </el-form-item>
        <el-form-item label="积分数值">
          <el-input-number
            v-model="currentForm.points"
            :min="1"
            :max="10000"
            controls-position="right"
          />
        </el-form-item>
        <el-form-item label="描述">
          <el-input
            v-model="currentForm.description"
            type="textarea"
            :rows="3"
            placeholder="请输入规则描述"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSubmit">确认</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<style scoped>
.admin-points-rules {
  padding: 24px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.table-container {
  background: white;
  padding: 20px;
  border-radius: 8px;
}
</style>
