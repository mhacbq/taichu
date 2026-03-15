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
        
        <el-form-item label="AI解盘">
          <el-switch v-model="form.enable_ai_analysis" />
        </el-form-item>
        
        <el-form-item>
          <el-button type="primary" @click="handleSave">保存配置</el-button>
          <el-button @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- AI解盘配置 -->
    <el-card class="mt-4">
      <template #header>
        <span>AI解盘配置</span>
      </template>
      
      <el-form :model="aiForm" label-width="150px">
        <el-alert
          title="AI解盘功能需要配置外部AI API"
          description="支持 DeepSeek、GPT 等兼容 OpenAI API 格式的服务"
          type="info"
          show-icon
          class="mb-4"
        />
        
        <el-form-item label="启用AI解盘">
          <el-switch v-model="aiForm.enable_bazi_analysis" />
        </el-form-item>
        
        <el-form-item label="API地址">
          <el-input 
            v-model="aiForm.api_url" 
            placeholder="https://aiping.cn/api/v1/chat/completions"
            style="width: 400px;"
          />
        </el-form-item>
        
        <el-form-item label="API密钥">
          <el-input 
            v-model="aiForm.api_key" 
            type="password"
            show-password
            placeholder="Bearer Token"
            style="width: 400px;"
          />
          <el-text type="info" size="small" class="ml-2">密钥将以加密形式存储</el-text>
        </el-form-item>
        
        <el-form-item label="模型名称">
          <el-input 
            v-model="aiForm.model" 
            placeholder="DeepSeek-V3.2"
            style="width: 300px;"
          />
        </el-form-item>
        
        <el-form-item label="启用流式输出">
          <el-switch v-model="aiForm.enable_streaming" />
          <el-text type="info" size="small" class="ml-2">逐字显示AI回答</el-text>
        </el-form-item>
        
        <el-form-item label="启用思考过程">
          <el-switch v-model="aiForm.enable_thinking" />
          <el-text type="info" size="small" class="ml-2">显示AI的思考过程</el-text>
        </el-form-item>
        
        <el-form-item label="积分消耗">
          <el-input-number v-model="aiForm.cost_points" :min="0" :max="100" />
          <el-text type="info" size="small" class="ml-2">每次AI解盘消耗的积分</el-text>
        </el-form-item>
        
        <el-form-item>
          <el-button type="primary" @click="handleSaveAiConfig" :loading="savingAi">
            保存AI配置
          </el-button>
          <el-button @click="handleTestAiConnection" :loading="testingAi">
            测试连接
          </el-button>
        </el-form-item>
      </el-form>
    </el-card>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { getSettings, saveSettings } from '@/api/system'
import { getAiConfig, saveAiConfig, testAiConnection } from '@/api/ai'

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
  enable_feedback: true,
  enable_ai_analysis: true
})

const originalForm = ref({})

// AI配置表单
const aiForm = ref({
  enable_bazi_analysis: true,
  api_url: 'https://aiping.cn/api/v1/chat/completions',
  api_key: '',
  model: 'DeepSeek-V3.2',
  enable_streaming: true,
  enable_thinking: false,
  cost_points: 30
})

const originalAiForm = ref({})
const savingAi = ref(false)
const testingAi = ref(false)

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
  
  // 加载AI配置
  try {
    const res = await getAiConfig()
    if (res.code === 0 && res.data) {
      aiForm.value = { ...aiForm.value, ...res.data }
      originalAiForm.value = { ...aiForm.value }
    }
  } catch (error) {
    console.error('加载AI配置失败:', error)
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

// 保存AI配置
async function handleSaveAiConfig() {
  savingAi.value = true
  try {
    await saveAiConfig(aiForm.value)
    ElMessage.success('AI配置保存成功')
    originalAiForm.value = { ...aiForm.value }
  } catch (error) {
    ElMessage.error('保存失败')
  } finally {
    savingAi.value = false
  }
}

// 测试AI连接
async function handleTestAiConnection() {
  testingAi.value = true
  try {
    const res = await testAiConnection(aiForm.value)
    if (res.code === 0) {
      ElMessage.success('连接成功：' + (res.data.model || '未知模型'))
    } else {
      ElMessage.error(res.message || '连接失败')
    }
  } catch (error) {
    ElMessage.error('测试失败')
  } finally {
    testingAi.value = false
  }
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
