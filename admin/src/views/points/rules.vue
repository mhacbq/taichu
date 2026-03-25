<script setup lang="ts">
import { ref, onMounted, reactive } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getPointsRules, savePointsRules } from '@/api/points'

const loading = ref(false)
const submitting = ref(false)
const rulesList = ref([])

const dialogVisible = ref(false)
const isEdit = ref(false)
const dialogForm = reactive({
  id: '',
  rule_name: '',
  points: 0,
  description: '',
  status: 1
})

onMounted(() => {
  fetchRulesList()
})

async function fetchRulesList() {
  loading.value = true
  try {
    const res = await getPointsRules()
    if (res.code === 0) {
      rulesList.value = res.data.list || []
    }
  } catch (error) {
    ElMessage.error('获取积分规则失败')
  } finally {
    loading.value = false
  }
}

function handleAdd() {
  isEdit.value = false
  Object.assign(dialogForm, {
    id: '',
    rule_name: '',
    points: 0,
    description: '',
    status: 1
  })
  dialogVisible.value = true
}

function handleEdit(row) {
  isEdit.value = true
  Object.assign(dialogForm, {
    id: row.id,
    rule_name: row.rule_name,
    points: row.points,
    description: row.description,
    status: row.status
  })
  dialogVisible.value = true
}

async function handleSubmit() {
  if (!dialogForm.rule_name.trim()) {
    ElMessage.warning('请输入规则名称')
    return
  }
  submitting.value = true
  try {
    const res = await savePointsRules({ ...dialogForm })
    if (res.code === 0) {
      ElMessage.success(isEdit.value ? '更新成功' : '新增成功')
      dialogVisible.value = false
      fetchRulesList()
    } else {
      ElMessage.error(res.message || '操作失败')
    }
  } catch (error) {
    ElMessage.error('操作失败')
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="app-container">
    <el-card v-loading="loading">
      <template #header>
        <div class="card-header">
          <span>积分规则</span>
          <el-button type="primary" @click="handleAdd">新增规则</el-button>
        </div>
      </template>

      <el-table :data="rulesList" stripe>
        <el-table-column prop="id" label="规则标识" width="160" />
        <el-table-column prop="rule_name" label="规则名称" width="150" />
        <el-table-column prop="points" label="积分" width="120">
          <template #default="{ row }">
            <span :style="{ color: row.points > 0 ? '#67C23A' : '#F56C6C' }">
              {{ row.points > 0 ? '+' : '' }}{{ row.points }}
            </span>
          </template>
        </el-table-column>
        <el-table-column prop="description" label="规则描述" show-overflow-tooltip />
        <el-table-column prop="status" label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'info'" size="small">
              {{ row.status === 1 ? '启用' : '禁用' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="150" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" size="small" @click="handleEdit(row)">编辑</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <!-- 编辑/新增对话框 -->
    <el-dialog
      v-model="dialogVisible"
      :title="isEdit ? '编辑规则' : '新增规则'"
      width="500px"
      destroy-on-close
    >
      <el-form :model="dialogForm" label-width="100px">
        <el-form-item label="规则名称" required>
          <el-input v-model="dialogForm.rule_name" placeholder="请输入规则名称" />
        </el-form-item>
        <el-form-item label="积分数值">
          <el-input-number v-model="dialogForm.points" :step="1" />
          <el-text type="info" size="small" style="margin-left: 8px">正数为奖励，负数为消耗</el-text>
        </el-form-item>
        <el-form-item label="规则描述">
          <el-input v-model="dialogForm.description" type="textarea" :rows="3" placeholder="请输入规则描述" />
        </el-form-item>
        <el-form-item label="状态">
          <el-radio-group v-model="dialogForm.status">
            <el-radio :label="1">启用</el-radio>
            <el-radio :label="0">禁用</el-radio>
          </el-radio-group>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="submitting" @click="handleSubmit">保存</el-button>
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
