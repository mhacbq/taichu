<template>
  <div class="bazi-page">
    <div class="container">
      <h1 class="section-title">八字排盘</h1>
      
      <div class="bazi-form card">
        <div class="form-group">
          <label>出生日期</label>
          <el-date-picker
            v-model="birthDate"
            type="datetime"
            placeholder="选择出生日期时间"
            format="YYYY-MM-DD HH:mm"
            value-format="YYYY-MM-DD HH:mm:ss"
            class="full-width"
          />
        </div>
        
        <div class="form-group">
          <label>性别</label>
          <el-radio-group v-model="gender">
            <el-radio label="male">男</el-radio>
            <el-radio label="female">女</el-radio>
          </el-radio-group>
        </div>
        
        <div class="form-group">
          <label>出生地点（用于真太阳时计算）</label>
          <el-input v-model="location" placeholder="如：北京市" />
        </div>
        
        <el-button type="primary" size="large" @click="calculateBazi" :loading="loading">
          开始排盘
        </el-button>
      </div>

      <div v-if="result" class="bazi-result card">
        <h2>八字排盘结果</h2>
        <div class="bazi-paipan">
          <div class="paipan-row">
            <div class="paipan-cell header">年柱</div>
            <div class="paipan-cell header">月柱</div>
            <div class="paipan-cell header">日柱</div>
            <div class="paipan-cell header">时柱</div>
          </div>
          <div class="paipan-row">
            <div class="paipan-cell">{{ result.bazi.year.gan }}</div>
            <div class="paipan-cell">{{ result.bazi.month.gan }}</div>
            <div class="paipan-cell highlight">{{ result.bazi.day.gan }}</div>
            <div class="paipan-cell">{{ result.bazi.hour.gan }}</div>
          </div>
          <div class="paipan-row">
            <div class="paipan-cell">{{ result.bazi.year.zhi }}</div>
            <div class="paipan-cell">{{ result.bazi.month.zhi }}</div>
            <div class="paipan-cell highlight">{{ result.bazi.day.zhi }}</div>
            <div class="paipan-cell">{{ result.bazi.hour.zhi }}</div>
          </div>
        </div>
        
        <div class="bazi-analysis">
          <h3>命理分析</h3>
          <div class="analysis-content" v-html="result.analysis"></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { ElMessage } from 'element-plus'
import { calculateBazi as calculateBaziApi } from '../api'

const birthDate = ref('')
const gender = ref('male')
const location = ref('')
const loading = ref(false)
const result = ref(null)

const calculateBazi = async () => {
  if (!birthDate.value) {
    ElMessage.warning('请选择出生日期')
    return
  }
  
  loading.value = true
  try {
    const response = await calculateBaziApi({
      birthDate: birthDate.value,
      gender: gender.value,
      location: location.value,
    })
    
    if (response.code === 0) {
      result.value = response.data
      ElMessage.success('排盘成功')
    } else {
      ElMessage.error(response.message || '排盘失败')
    }
  } catch (error) {
    ElMessage.error('网络错误，请稍后重试')
    console.error(error)
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.bazi-page {
  padding: 60px 0;
}

.bazi-form {
  max-width: 600px;
  margin: 0 auto 40px;
}

.form-group {
  margin-bottom: 25px;
}

.form-group label {
  display: block;
  margin-bottom: 10px;
  color: rgba(255, 255, 255, 0.8);
  font-size: 14px;
}

.full-width {
  width: 100%;
}

.bazi-result {
  max-width: 800px;
  margin: 0 auto;
}

.bazi-result h2 {
  text-align: center;
  margin-bottom: 30px;
  color: #fff;
}

.bazi-paipan {
  background: rgba(0, 0, 0, 0.3);
  border-radius: 15px;
  padding: 30px;
  margin-bottom: 30px;
}

.paipan-row {
  display: flex;
  justify-content: space-around;
  margin-bottom: 15px;
}

.paipan-row:last-child {
  margin-bottom: 0;
}

.paipan-cell {
  flex: 1;
  text-align: center;
  padding: 20px;
  font-size: 28px;
  font-weight: bold;
  color: #fff;
}

.paipan-cell.header {
  font-size: 16px;
  color: rgba(255, 255, 255, 0.6);
  font-weight: normal;
}

.paipan-cell.highlight {
  color: #e94560;
  background: rgba(233, 69, 96, 0.1);
  border-radius: 10px;
}

.bazi-analysis {
  background: rgba(0, 0, 0, 0.2);
  border-radius: 15px;
  padding: 30px;
}

.bazi-analysis h3 {
  margin-bottom: 20px;
  color: #fff;
  text-align: center;
}

.analysis-content {
  color: rgba(255, 255, 255, 0.8);
  line-height: 1.8;
  white-space: pre-line;
}
</style>
