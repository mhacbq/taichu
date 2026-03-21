<template>
  <div class="settings-manage">
    <div class="page-header">
      <h2>系统配置</h2>
    </div>

    <el-card>
      <el-tabs v-model="activeTab">
        <el-tab-pane label="基础设置" name="basic">
          <el-form :model="basicForm" label-width="150px" style="max-width: 600px;">
            <el-form-item label="网站名称">
              <el-input v-model="basicForm.site_name" placeholder="请输入网站名称" />
            </el-form-item>
            <el-form-item label="网站标题">
              <el-input v-model="basicForm.site_title" placeholder="请输入网站标题" />
            </el-form-item>
            <el-form-item label="网站关键词">
              <el-input v-model="basicForm.site_keywords" type="textarea" :rows="2" placeholder="请输入网站关键词" />
            </el-form-item>
            <el-form-item label="网站描述">
              <el-input v-model="basicForm.site_description" type="textarea" :rows="3" placeholder="请输入网站描述" />
            </el-form-item>
            <el-form-item label="备案号">
              <el-input v-model="basicForm.icp" placeholder="请输入备案号" />
            </el-form-item>
            <el-form-item label="Logo">
              <el-upload
                class="avatar-uploader"
                :show-file-list="false"
                :on-success="handleLogoSuccess"
                action="/api/admin/upload"
              >
                <img v-if="basicForm.logo" :src="basicForm.logo" class="avatar" />
                <el-icon v-else class="avatar-uploader-icon"><Plus /></el-icon>
              </el-upload>
            </el-form-item>
            <el-form-item>
              <el-button type="primary" @click="saveBasic">保存设置</el-button>
            </el-form-item>
          </el-form>
        </el-tab-pane>

        <el-tab-pane label="积分设置" name="points">
          <el-form :model="pointsForm" label-width="150px" style="max-width: 600px;">
            <el-form-item label="注册赠送积分">
              <el-input-number v-model="pointsForm.register_points" :min="0" :max="99999" />
            </el-form-item>
            <el-form-item label="每日登录积分">
              <el-input-number v-model="pointsForm.daily_login_points" :min="0" :max="9999" />
            </el-form-item>
            <el-form-item label="分享奖励积分">
              <el-input-number v-model="pointsForm.share_points" :min="0" :max="9999" />
            </el-form-item>
            <el-form-item label="反馈奖励积分">
              <el-input-number v-model="pointsForm.feedback_points" :min="0" :max="9999" />
            </el-form-item>
            <el-form-item label="八字分析消耗积分">
              <el-input-number v-model="pointsForm.bazi_consume" :min="0" :max="9999" />
            </el-form-item>
            <el-form-item>
              <el-button type="primary" @click="savePoints">保存设置</el-button>
            </el-form-item>
          </el-form>
        </el-tab-pane>

        <el-tab-pane label="邮箱设置" name="email">
          <el-form :model="emailForm" label-width="150px" style="max-width: 600px;">
            <el-form-item label="SMTP服务器">
              <el-input v-model="emailForm.smtp_host" placeholder="如：smtp.qq.com" />
            </el-form-item>
            <el-form-item label="SMTP端口">
              <el-input-number v-model="emailForm.smtp_port" :min="1" :max="65535" />
            </el-form-item>
            <el-form-item label="发件人邮箱">
              <el-input v-model="emailForm.smtp_user" placeholder="请输入发件人邮箱" />
            </el-form-item>
            <el-form-item label="邮箱密码/授权码">
              <el-input v-model="emailForm.smtp_pass" type="password" placeholder="请输入邮箱密码" show-password />
            </el-form-item>
            <el-form-item label="发件人名称">
              <el-input v-model="emailForm.smtp_name" placeholder="请输入发件人名称" />
            </el-form-item>
            <el-form-item label="测试邮箱">
              <el-input v-model="testEmail" placeholder="输入测试邮箱" style="width: 300px; margin-right: 10px;" />
              <el-button type="primary" @click="testEmailConnection">测试发送</el-button>
            </el-form-item>
            <el-form-item>
              <el-button type="primary" @click="saveEmail">保存设置</el-button>
            </el-form-item>
          </el-form>
        </el-tab-pane>

        <el-tab-pane label="支付设置" name="payment">
          <el-form :model="paymentForm" label-width="150px" style="max-width: 600px;">
            <el-form-item label="微信支付商户号">
              <el-input v-model="paymentForm.wechat_mch_id" placeholder="请输入微信支付商户号" />
            </el-form-item>
            <el-form-item label="微信支付API密钥">
              <el-input v-model="paymentForm.wechat_api_key" type="password" placeholder="请输入API密钥" show-password />
            </el-form-item>
            <el-form-item label="支付宝AppID">
              <el-input v-model="paymentForm.alipay_app_id" placeholder="请输入支付宝AppID" />
            </el-form-item>
            <el-form-item label="支付宝应用私钥">
              <el-input v-model="paymentForm.alipay_private_key" type="textarea" :rows="3" placeholder="请输入应用私钥" />
            </el-form-item>
            <el-form-item>
              <el-button type="primary" @click="savePayment">保存设置</el-button>
            </el-form-item>
          </el-form>
        </el-tab-pane>
      </el-tabs>
    </el-card>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { Plus } from '@element-plus/icons-vue'

const activeTab = ref('basic')
const testEmail = ref('')

const basicForm = reactive({
  site_name: '',
  site_title: '',
  site_keywords: '',
  site_description: '',
  icp: '',
  logo: ''
})

const pointsForm = reactive({
  register_points: 0,
  daily_login_points: 0,
  share_points: 0,
  feedback_points: 0,
  bazi_consume: 0
})

const emailForm = reactive({
  smtp_host: '',
  smtp_port: 465,
  smtp_user: '',
  smtp_pass: '',
  smtp_name: ''
})

const paymentForm = reactive({
  wechat_mch_id: '',
  wechat_api_key: '',
  alipay_app_id: '',
  alipay_private_key: ''
})

const fetchSettings = async () => {
  try {
    const response = await fetch('/api/admin/settings')
    const data = await response.json()
    
    if (data.code === 200) {
      Object.assign(basicForm, data.data.basic || {})
      Object.assign(pointsForm, data.data.points || {})
      Object.assign(emailForm, data.data.email || {})
      Object.assign(paymentForm, data.data.payment || {})
    }
  } catch (error) {
    ElMessage.error('获取配置失败')
  }
}

const saveBasic = async () => {
  try {
    const response = await fetch('/api/admin/settings/basic', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(basicForm)
    })
    const data = await response.json()
    
    if (data.code === 200) {
      ElMessage.success('保存成功')
    } else {
      ElMessage.error(data.message || '保存失败')
    }
  } catch (error) {
    ElMessage.error('保存失败')
  }
}

const savePoints = async () => {
  try {
    const response = await fetch('/api/admin/settings/points', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(pointsForm)
    })
    const data = await response.json()
    
    if (data.code === 200) {
      ElMessage.success('保存成功')
    } else {
      ElMessage.error(data.message || '保存失败')
    }
  } catch (error) {
    ElMessage.error('保存失败')
  }
}

const saveEmail = async () => {
  try {
    const response = await fetch('/api/admin/settings/email', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(emailForm)
    })
    const data = await response.json()
    
    if (data.code === 200) {
      ElMessage.success('保存成功')
    } else {
      ElMessage.error(data.message || '保存失败')
    }
  } catch (error) {
    ElMessage.error('保存失败')
  }
}

const testEmailConnection = async () => {
  if (!testEmail.value) {
    ElMessage.warning('请输入测试邮箱')
    return
  }
  
  try {
    const response = await fetch('/api/admin/settings/test-email', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email: testEmail.value })
    })
    const data = await response.json()
    
    if (data.code === 200) {
      ElMessage.success('测试邮件已发送')
    } else {
      ElMessage.error(data.message || '发送失败')
    }
  } catch (error) {
    ElMessage.error('发送失败')
  }
}

const savePayment = async () => {
  try {
    const response = await fetch('/api/admin/settings/payment', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(paymentForm)
    })
    const data = await response.json()
    
    if (data.code === 200) {
      ElMessage.success('保存成功')
    } else {
      ElMessage.error(data.message || '保存失败')
    }
  } catch (error) {
    ElMessage.error('保存失败')
  }
}

const handleLogoSuccess = (res) => {
  if (res.code === 200) {
    basicForm.logo = res.data.url
    ElMessage.success('上传成功')
  } else {
    ElMessage.error('上传失败')
  }
}

onMounted(() => {
  fetchSettings()
})
</script>

<style scoped>
.page-header {
  margin-bottom: 20px;
}
.page-header h2 {
  margin: 0;
  font-size: 20px;
  color: #333;
}
.avatar-uploader {
  border: 1px dashed var(--el-border-color);
  border-radius: 6px;
  cursor: pointer;
  position: relative;
  overflow: hidden;
  width: 178px;
  height: 178px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.avatar-uploader:hover {
  border-color: var(--el-color-primary);
}
.avatar {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.avatar-uploader-icon {
  font-size: 28px;
  color: #8c939d;
}
</style>
