<template>
  <div class="app-container">
    <el-card shadow="never" class="adjust-card" header="批量积分调整">
      <el-form :model="form" label-width="120px" style="max-width: 600px">
        <el-form-item label="对象类型">
          <el-radio-group v-model="form.targetType">
            <el-radio label="specific">特定用户</el-radio>
            <el-radio label="all">全站用户</el-radio>
            <el-radio label="active">活跃用户(30天内登录)</el-radio>
          </el-radio-group>
        </el-form-item>
        
        <el-form-item v-if="form.targetType === 'specific'" label="用户ID/手机号">
          <el-input
            v-model="form.targets"
            type="textarea"
            rows="5"
            placeholder="请输入ID或手机号，多个请用逗号或换行分隔"
          />
        </el-form-item>

        <el-form-item label="调整类型">
          <el-radio-group v-model="form.type">
            <el-radio label="add">增加积分</el-radio>
            <el-radio label="reduce">扣除积分</el-radio>
          </el-radio-group>
        </el-form-item>

        <el-form-item label="变动数量">
          <el-input-number v-model="form.amount" :min="1" />
        </el-form-item>

        <el-form-item label="调整原因">
          <el-input v-model="form.reason" type="textarea" rows="3" placeholder="必填，将显示在用户的积分记录中" />
        </el-form-item>

        <el-form-item>
          <el-button type="primary" :loading="submitting" @click="handleSubmit">立即执行</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <el-card shadow="never" style="margin-top: 20px" header="最近调整记录">
      <el-table :data="recentAdjustments" stripe>
        <el-table-column prop="created_at" label="时间" width="180" />
        <el-table-column prop="target_desc" label="调整对象" width="150" />
        <el-table-column prop="amount_desc" label="变动数量" width="120" />
        <el-table-column prop="reason" label="原因" min-width="200" />
        <el-table-column prop="operator" label="操作人" width="120" />
        <el-table-column prop="status" label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="row.status === 'success' ? 'success' : 'warning'">
              {{ row.status === 'success' ? '已完成' : '处理中' }}
            </el-tag>
          </template>
        </el-table-column>
      </el-table>
    </el-card>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { adjustPoints } from '@/api/points'

const submitting = ref(false)
const recentAdjustments = ref([])

const form = reactive({
  targetType: 'specific',
  targets: '',
  type: 'add',
  amount: 10,
  reason: ''
})

async function handleSubmit() {
  if (!form.reason) {
    ElMessage.warning('请输入调整原因')
    return
  }
  
  if (form.targetType === 'specific' && !form.targets) {
    ElMessage.warning('请输入调整对象')
    return
  }

  try {
    await ElMessageBox.confirm('确定要执行该批量操作吗？此操作不可逆！', '高危提示', {
      type: 'warning',
      confirmButtonText: '我确定',
      confirmButtonClass: 'el-button--danger'
    })
    
    submitting.value = true
    
    // 构造提交数据
    const submitData = {
      target_type: form.targetType,
      targets: form.targets.split(/[,\n]/).map(t => t.trim()).filter(t => t),
      type: form.type,
      amount: form.amount,
      reason: form.reason
    }

    const res = await adjustPoints(submitData)
    
    if (res.code === 0) {
      ElMessage.success('批量调整任务已提交后台处理')
      resetForm()
      // 可以考虑刷新下方的最近调整记录（如果API支持获取列表的话）
    }
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error(error.message || '操作失败')
    }
  } finally {
    submitting.value = false
  }
}


function resetForm() {
  Object.assign(form, {
    targetType: 'specific',
    targets: '',
    type: 'add',
    amount: 10,
    reason: ''
  })
}
</script>

<style scoped>
.app-container {
  padding: 20px;
}
</style>
