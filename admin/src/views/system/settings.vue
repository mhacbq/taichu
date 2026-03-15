<template>
  <div class="app-container">
    <el-card>
      <template #header>
        <span>系统基础配置</span>
      </template>
      
      <el-form :model="form" label-width="150px">
        <el-divider content-position="left">网站信息</el-divider>
        
        <el-form-item label="网站名称">
          <el-input v-model="form.site_name" style="width: 300px;" />
        </el-form-item>
        
        <el-form-item label="网站Logo">
          <el-upload
            class="avatar-uploader"
            action="/api/upload"
            :show-file-list="false"
            :on-success="handleLogoSuccess"
          >
            <img v-if="form.logo" :src="form.logo" class="avatar" />
            <el-icon v-else class="avatar-uploader-icon"><Plus /></el-icon>
          </el-upload>
        </el-form-item>
        
        <el-form-item label="网站描述">
          <el-input v-model="form.site_description" type="textarea" rows="3" style="width: 400px;" />
        </el-form-item>
        
        <el-divider content-position="left">积分配置</el-divider>
        
        <el-form-item label="注册赠送积分">
          <el-input-number v-model="form.register_points" :min="0" />
        </el-form-item>
        
        <el-form-item label="每日签到积分">
          <el-input-number v-model="form.checkin_points" :min="0" />
        </el-form-item>
        
        <el-form-item label="八字排盘消耗">
          <el-input-number v-model="form.bazi_cost" :min="0" />
        </el-form-item>
        
        <el-form-item label="塔罗占卜消耗">
          <el-input-number v-model="form.tarot_cost" :min="0" />
        </el-form-item>
        
        <el-divider content-position="left">功能开关</el-divider>
        
        <el-form-item label="注册功能">
          <el-switch v-model="form.enable_register" />
        </el-form-item>
        
        <el-form-item label="每日运势">
          <el-switch v-model="form.enable_daily" />
        </el-form-item>
        
        <el-form-item label="用户反馈">
          <el-switch v-model="form.enable_feedback" />
        </el-form-item>
        
        <el-form-item>
          <el-button type="primary" @click="handleSave">保存配置</el-button>
          <el-button @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>
    </el-card>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { getSettings, saveSettings } from '@/api/system'

const form = ref({
  site_name: '太初命理',
  logo: '',
  site_description: '',
  register_points: 100,
  checkin_points: 10,
  bazi_cost: 20,
  tarot_cost: 10,
  enable_register: true,
  enable_daily: true,
  enable_feedback: true
})

const originalForm = ref({})

onMounted(async () => {
  try {
    const { data } = await getSettings()
    if (data) {
      form.value = { ...form.value, ...data }
      originalForm.value = { ...form.value }
    }
  } catch (error) {
    console.error(error)
  }
})

function handleLogoSuccess(res) {
  form.value.logo = res.url
}

async function handleSave() {
  try {
    await saveSettings(form.value)
    ElMessage.success('保存成功')
    originalForm.value = { ...form.value }
  } catch (error) {
    console.error(error)
  }
}

function handleReset() {
  form.value = { ...originalForm.value }
}
</script>

<style lang="scss" scoped>
.avatar-uploader {
  :deep(.el-upload) {
    border: 1px dashed var(--el-border-color);
    border-radius: 6px;
    cursor: pointer;
    position: relative;
    overflow: hidden;
    transition: var(--el-transition-duration-fast);
    
    &:hover {
      border-color: var(--el-color-primary);
    }
  }
}

.avatar-uploader-icon {
  font-size: 28px;
  color: #8c939d;
  width: 100px;
  height: 100px;
  text-align: center;
  line-height: 100px;
}

.avatar {
  width: 100px;
  height: 100px;
  display: block;
  object-fit: contain;
}
</style>
