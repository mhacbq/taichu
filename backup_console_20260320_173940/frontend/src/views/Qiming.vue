<template>
  <div class="qiming-page">
    <div class="container">
      <PageHeroHeader
        title="取名建议"
        subtitle="结合生辰八字与五行，由AI为新生儿推荐寓意美好的名字。"
        :icon="EditPen"
      />

      <div class="qiming-form card" v-if="!result">
        <div class="form-group">
          <label>姓氏 <span class="required">*</span></label>
          <el-input v-model="form.surname" placeholder="请输入姓氏" maxlength="10" clearable />
        </div>

        <div class="form-group">
          <label>性别 <span class="required">*</span></label>
          <el-radio-group v-model="form.gender">
            <el-radio label="male">男</el-radio>
            <el-radio label="female">女</el-radio>
          </el-radio-group>
        </div>

        <div class="form-group">
          <label>出生日期 <span class="required">*</span></label>
          <el-date-picker
            v-model="form.birthDate"
            type="date"
            placeholder="选择出生日期"
            format="YYYY-MM-DD"
            value-format="YYYY-MM-DD"
            class="full-width"
          />
        </div>

        <div class="form-group">
          <label>出生时间（可选）</label>
          <el-time-picker
            v-model="form.birthHour"
            placeholder="选择出生时间"
            format="HH:mm"
            value-format="HH:mm:ss"
            class="full-width"
          />
        </div>

        <div class="form-group">
          <label>期望风格（可选）</label>
          <el-input v-model="form.style" placeholder="如：诗意、大气、文雅等" maxlength="20" clearable />
        </div>

        <div class="form-group">
          <label>忌讳字/词（可选）</label>
          <el-input v-model="form.taboos" placeholder="如：避免使用生僻字等" maxlength="50" clearable />
        </div>

        <el-button 
          type="primary" 
          size="large" 
          @click="submitForm" 
          :loading="loading"
          :disabled="loading"
          class="submit-btn"
        >
          开始取名 (消耗 100 积分)
        </el-button>
      </div>

      <div v-else class="qiming-result card">
        <div class="result-header">
          <h2>取名建议结果</h2>
          <el-button @click="resetForm" size="small">重新取名</el-button>
        </div>
        
        <div class="result-content rich-content" v-html="formattedResult"></div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { ElMessage } from 'element-plus'
import { EditPen } from '@element-plus/icons-vue'
import PageHeroHeader from '../components/PageHeroHeader.vue'
import { suggestNames } from '../api'
import { sanitizeHtml } from '../utils/sanitize'

const form = ref({
  surname: '',
  gender: 'male',
  birthDate: '',
  birthHour: '',
  style: '',
  taboos: ''
})

const loading = ref(false)
const result = ref(null)

const formattedResult = computed(() => {
  if (!result.value) return ''
  
  try {
    // 尝试解析 JSON
    const data = JSON.parse(result.value)
    if (data.names && Array.isArray(data.names)) {
      let html = '<div class="name-suggestions">'
      data.names.forEach((item, index) => {
        html += `
          <div class="name-card">
            <h3 class="name-title">${item.name}</h3>
            <div class="name-detail">
              <p><strong>寓意：</strong>${item.meaning}</p>
              <p><strong>五行：</strong>${item.wuxing}</p>
              <p><strong>音律：</strong>${item.phonetics}</p>
            </div>
          </div>
        `
      })
      html += '</div>'
      return sanitizeHtml(html)
    }
  } catch (e) {
    // 如果不是 JSON，直接返回文本（处理换行）
    return sanitizeHtml(result.value.replace(/\n/g, '<br>'))
  }
  
  return sanitizeHtml(result.value.replace(/\n/g, '<br>'))
})

const submitForm = async () => {
  if (!form.value.surname) {
    ElMessage.warning('请输入姓氏')
    return
  }
  if (!form.value.birthDate) {
    ElMessage.warning('请选择出生日期')
    return
  }

  loading.value = true
  try {
    const response = await suggestNames({
      surname: form.value.surname,
      gender: form.value.gender,
      birth_date: form.value.birthDate,
      birth_hour: form.value.birthHour,
      style: form.value.style,
      taboos: form.value.taboos
    })

    if (response.code === 200) {
      result.value = response.data.result
      ElMessage.success('取名成功！')
    } else {
      ElMessage.error(response.message || '取名失败')
    }
  } catch (error) {
    console.error('取名失败:', error)
    ElMessage.error('网络错误，请稍后重试')
  } finally {
    loading.value = false
  }
}

const resetForm = () => {
  result.value = null
}
</script>

<style scoped>
.qiming-page {
  padding: 40px 0;
}

.qiming-form {
  max-width: 600px;
  margin: 0 auto;
  padding: 30px;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 600;
  color: var(--text-primary);
}

.required {
  color: var(--danger-color);
}

.full-width {
  width: 100%;
}

.submit-btn {
  width: 100%;
  margin-top: 20px;
}

.qiming-result {
  max-width: 800px;
  margin: 0 auto;
  padding: 30px;
}

.result-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  padding-bottom: 16px;
  border-bottom: 1px solid var(--border-light);
}

.result-header h2 {
  margin: 0;
  color: var(--text-primary);
}

:deep(.name-suggestions) {
  display: grid;
  gap: 20px;
}

:deep(.name-card) {
  background: var(--bg-secondary);
  border-radius: var(--radius-lg);
  padding: 20px;
  border: 1px solid var(--border-light);
}

:deep(.name-title) {
  margin: 0 0 16px 0;
  color: var(--primary-color);
  font-size: 24px;
  text-align: center;
  border-bottom: 1px dashed var(--border-color);
  padding-bottom: 12px;
}

:deep(.name-detail p) {
  margin: 8px 0;
  line-height: 1.6;
  color: var(--text-regular);
}

:deep(.name-detail strong) {
  color: var(--text-primary);
}
</style>