<template>
  <div class="bazi-page">
    <div class="container">
      <div class="page-header">
        <BackButton />
        <h1 class="section-title">八字排盘</h1>
      </div>
      
      <div class="bazi-form card">
        <!-- 积分消耗提示 -->
        <div class="points-hint">
          <span class="hint-icon">💎</span>
          <span>本次排盘将消耗 <strong>10 积分</strong></span>
          <span class="current-points">当前积分: {{ currentPoints }}</span>
        </div>

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
          <label>
            出生地点
            <el-tooltip content="真太阳时是根据出生地的经度计算的真实太阳时间，确保八字排盘更准确" placement="top">
              <span class="help-icon">❓</span>
            </el-tooltip>
          </label>
          <el-select-v2
            v-model="location"
            :options="cityOptions"
            placeholder="请选择出生城市"
            class="full-width"
            filterable
            clearable
            :height="200"
          />
          <p class="form-hint">💡 选择出生地点可计算真太阳时，使排盘更准确</p>
        </div>
        
        <el-button 
          type="primary" 
          size="large" 
          @click="showConfirm" 
          :loading="loading"
          :disabled="currentPoints < 10"
        >
          开始排盘
        </el-button>

        <!-- 积分不足提示 -->
        <div v-if="currentPoints < 10" class="insufficient-points">
          <p>💡 积分不足，请先 <router-link to="/profile">签到领取积分</router-link></p>
        </div>
      </div>

      <!-- 确认对话框 -->
      <el-dialog
        v-model="confirmVisible"
        title="确认排盘"
        width="400px"
        class="confirm-dialog"
      >
        <div class="confirm-content">
          <p>本次排盘将消耗 <strong>10 积分</strong></p>
          <p>排盘后可在个人中心查看历史记录</p>
        </div>
        <template #footer>
          <el-button @click="confirmVisible = false">取消</el-button>
          <el-button type="primary" @click="confirmCalculate">确认排盘</el-button>
        </template>
      </el-dialog>

      <div v-if="result" class="bazi-result card">
        <h2>八字排盘结果</h2>
        
        <!-- 日主信息 -->
        <div class="day-master-info">
          <div class="day-master-card">
            <span class="label">日主</span>
            <span class="value">{{ result.bazi.day_master }}</span>
            <span class="wuxing">{{ result.bazi.day_master_wuxing }}</span>
          </div>
        </div>
        
        <!-- 八字排盘表 -->
        <div class="bazi-paipan">
          <div class="paipan-row">
            <div class="paipan-cell header">年柱</div>
            <div class="paipan-cell header">月柱</div>
            <div class="paipan-cell header">日柱</div>
            <div class="paipan-cell header">时柱</div>
          </div>
          <!-- 天干行 -->
          <div class="paipan-row">
            <div class="paipan-cell">
              <span class="gan-text">{{ result.bazi.year.gan }}</span>
              <span class="wuxing-badge" :class="result.bazi.year.gan_wuxing">{{ result.bazi.year.gan_wuxing }}</span>
            </div>
            <div class="paipan-cell">
              <span class="gan-text">{{ result.bazi.month.gan }}</span>
              <span class="wuxing-badge" :class="result.bazi.month.gan_wuxing">{{ result.bazi.month.gan_wuxing }}</span>
            </div>
            <div class="paipan-cell highlight">
              <span class="gan-text">{{ result.bazi.day.gan }}</span>
              <span class="wuxing-badge" :class="result.bazi.day.gan_wuxing">{{ result.bazi.day.gan_wuxing }}</span>
              <span class="rizhu-tag">日主</span>
            </div>
            <div class="paipan-cell">
              <span class="gan-text">{{ result.bazi.hour.gan }}</span>
              <span class="wuxing-badge" :class="result.bazi.hour.gan_wuxing">{{ result.bazi.hour.gan_wuxing }}</span>
            </div>
          </div>
          <!-- 十神行 -->
          <div class="paipan-row shishen-row">
            <div class="paipan-cell shishen-cell">{{ result.bazi.year.shishen }}</div>
            <div class="paipan-cell shishen-cell">{{ result.bazi.month.shishen }}</div>
            <div class="paipan-cell shishen-cell highlight">日主</div>
            <div class="paipan-cell shishen-cell">{{ result.bazi.hour.shishen }}</div>
          </div>
          <!-- 地支行 -->
          <div class="paipan-row">
            <div class="paipan-cell">
              <span class="zhi-text">{{ result.bazi.year.zhi }}</span>
              <span class="wuxing-badge zhi" :class="result.bazi.year.zhi_wuxing">{{ result.bazi.year.zhi_wuxing }}</span>
            </div>
            <div class="paipan-cell">
              <span class="zhi-text">{{ result.bazi.month.zhi }}</span>
              <span class="wuxing-badge zhi" :class="result.bazi.month.zhi_wuxing">{{ result.bazi.month.zhi_wuxing }}</span>
            </div>
            <div class="paipan-cell highlight">
              <span class="zhi-text">{{ result.bazi.day.zhi }}</span>
              <span class="wuxing-badge zhi" :class="result.bazi.day.zhi_wuxing">{{ result.bazi.day.zhi_wuxing }}</span>
            </div>
            <div class="paipan-cell">
              <span class="zhi-text">{{ result.bazi.hour.zhi }}</span>
              <span class="wuxing-badge zhi" :class="result.bazi.hour.zhi_wuxing">{{ result.bazi.hour.zhi_wuxing }}</span>
            </div>
          </div>
          <!-- 藏干行 -->
          <div class="paipan-row canggan-row">
            <div class="paipan-cell canggan-cell">
              <div class="canggan-list">
                <span v-for="(cg, idx) in result.bazi.year.canggan" :key="idx" class="canggan-item">
                  {{ cg }}<small>({{ result.bazi.year.canggan_shishen[idx] }})</small>
                </span>
              </div>
            </div>
            <div class="paipan-cell canggan-cell">
              <div class="canggan-list">
                <span v-for="(cg, idx) in result.bazi.month.canggan" :key="idx" class="canggan-item">
                  {{ cg }}<small>({{ result.bazi.month.canggan_shishen[idx] }})</small>
                </span>
              </div>
            </div>
            <div class="paipan-cell canggan-cell highlight">
              <div class="canggan-list">
                <span v-for="(cg, idx) in result.bazi.day.canggan" :key="idx" class="canggan-item">
                  {{ cg }}<small>({{ result.bazi.day.canggan_shishen[idx] }})</small>
                </span>
              </div>
            </div>
            <div class="paipan-cell canggan-cell">
              <div class="canggan-list">
                <span v-for="(cg, idx) in result.bazi.hour.canggan" :key="idx" class="canggan-item">
                  {{ cg }}<small>({{ result.bazi.hour.canggan_shishen[idx] }})</small>
                </span>
              </div>
            </div>
          </div>
          <!-- 纳音行 -->
          <div class="paipan-row nayin-row">
            <div class="paipan-cell nayin-cell">{{ result.bazi.year.nayin }}</div>
            <div class="paipan-cell nayin-cell">{{ result.bazi.month.nayin }}</div>
            <div class="paipan-cell nayin-cell highlight">{{ result.bazi.day.nayin }}</div>
            <div class="paipan-cell nayin-cell">{{ result.bazi.hour.nayin }}</div>
          </div>
        </div>
        
        <!-- 五行统计 -->
        <div class="wuxing-stats">
          <h3>五行分布</h3>
          <div class="wuxing-bars">
            <div v-for="(count, wx) in result.bazi.wuxing_stats" :key="wx" class="wuxing-bar-item">
              <span class="wuxing-name">{{ wx }}</span>
              <div class="wuxing-bar">
                <div class="wuxing-fill" :class="wx" :style="{ width: (count / 8 * 100) + '%' }"></div>
              </div>
              <span class="wuxing-count">{{ count }}</span>
            </div>
          </div>
        </div>
        
        <div class="bazi-analysis">
          <h3>命理分析</h3>
          <div class="analysis-content" v-html="result.analysis"></div>
        </div>
        
        <!-- 操作按钮 -->
        <div class="result-actions">
          <el-button type="primary" @click="saveResult" :loading="saving">
            <span class="btn-icon">💾</span> 保存结果
          </el-button>
          <el-button @click="shareResult">
            <span class="btn-icon">📤</span> 分享
          </el-button>
          <el-button @click="result = null">
            <span class="btn-icon">🔄</span> 重新排盘
          </el-button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { calculateBazi as calculateBaziApi, getPointsBalance } from '../api'
import BackButton from '../components/BackButton.vue'

const birthDate = ref('')
const gender = ref('male')
const location = ref('')
const loading = ref(false)
const result = ref(null)
const currentPoints = ref(0)
const confirmVisible = ref(false)
const saving = ref(false)

// 中国城市数据
const cities = [
  '北京市', '上海市', '广州市', '深圳市', '杭州市', '南京市', '武汉市', '成都市', '西安市',
  '重庆市', '天津市', '苏州市', '长沙市', '郑州市', '沈阳市', '青岛市', '宁波市', '东莞市',
  '佛山市', '合肥市', '大连市', '厦门市', '福州市', '哈尔滨市', '济南市', '温州市', '长春市',
  '石家庄市', '常州市', '泉州市', '南宁市', '贵阳市', '南昌市', '昆明市', '乌鲁木齐市',
  '兰州市', '呼和浩特市', '海口市', '银川市', '西宁市', '拉萨市', '台北市', '香港', '澳门'
]

const cityOptions = computed(() => {
  return cities.map(city => ({
    value: city,
    label: city
  }))
})

// 获取当前积分
const loadPoints = async () => {
  try {
    const response = await getPointsBalance()
    if (response.code === 0) {
      currentPoints.value = response.data.balance
    }
  } catch (error) {
    console.error('获取积分失败:', error)
  }
}

// 显示确认对话框
const showConfirm = () => {
  if (!birthDate.value) {
    ElMessage.warning('请选择出生日期')
    return
  }
  if (currentPoints.value < 10) {
    ElMessage.warning('积分不足，请先签到领取积分')
    return
  }
  confirmVisible.value = true
}

// 确认排盘
const confirmCalculate = async () => {
  confirmVisible.value = false
  await calculateBazi()
}

const calculateBazi = async () => {
  loading.value = true
  try {
    const response = await calculateBaziApi({
      birthDate: birthDate.value,
      gender: gender.value,
      location: location.value,
    })
    
    if (response.code === 0) {
      result.value = response.data
      currentPoints.value = response.data.remaining_points
      ElMessage.success('排盘成功')
    } else {
      ElMessage.error(response.message || '排盘失败')
      // 如果是积分不足，刷新积分
      if (response.code === 403) {
        loadPoints()
      }
    }
  } catch (error) {
    ElMessage.error('网络错误，请稍后重试')
    console.error(error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadPoints()
})

// 保存结果
const saveResult = async () => {
  saving.value = true
  try {
    // 保存到本地存储
    const savedResults = JSON.parse(localStorage.getItem('bazi_saved') || '[]')
    savedResults.unshift({
      id: result.value.id,
      date: new Date().toISOString(),
      bazi: result.value.bazi,
      analysis: result.value.analysis
    })
    // 最多保存50条
    if (savedResults.length > 50) {
      savedResults.pop()
    }
    localStorage.setItem('bazi_saved', JSON.stringify(savedResults))
    ElMessage.success('保存成功，可在个人中心查看')
  } catch (error) {
    ElMessage.error('保存失败')
  } finally {
    saving.value = false
  }
}

// 分享结果
const shareResult = () => {
  const shareText = `我在太初命理进行了八字排盘\n` +
    `日主：${result.value.bazi.day_master}（${result.value.bazi.day_master_wuxing}）\n` +
    `八字：${result.value.bazi.year.gan}${result.value.bazi.year.zhi} ${result.value.bazi.month.gan}${result.value.bazi.month.zhi} ${result.value.bazi.day.gan}${result.value.bazi.day.zhi} ${result.value.bazi.hour.gan}${result.value.bazi.hour.zhi}\n` +
    `快来测测你的八字吧！`
  
  if (navigator.share) {
    navigator.share({
      title: '我的八字排盘结果',
      text: shareText
    })
  } else {
    // 复制到剪贴板
    navigator.clipboard.writeText(shareText).then(() => {
      ElMessage.success('分享内容已复制到剪贴板')
    }).catch(() => {
      ElMessage.error('复制失败，请手动复制')
    })
  }
}
</script>

<style scoped>
.bazi-page {
  padding: 60px 0;
}

.page-header {
  display: flex;
  align-items: center;
  gap: 20px;
  margin-bottom: 30px;
}

.page-header .section-title {
  margin: 0;
}

.bazi-form {
  max-width: 600px;
  margin: 0 auto 40px;
}

.points-hint {
  background: linear-gradient(135deg, rgba(233, 69, 96, 0.1), rgba(255, 107, 107, 0.1));
  border: 1px solid rgba(233, 69, 96, 0.3);
  border-radius: 10px;
  padding: 15px 20px;
  margin-bottom: 25px;
  display: flex;
  align-items: center;
  gap: 10px;
  color: rgba(255, 255, 255, 0.9);
}

.hint-icon {
  font-size: 20px;
}

.current-points {
  margin-left: auto;
  color: #ffd700;
  font-weight: 500;
}

.insufficient-points {
  margin-top: 15px;
  padding: 12px;
  background: rgba(245, 108, 108, 0.1);
  border: 1px solid rgba(245, 108, 108, 0.3);
  border-radius: 8px;
  text-align: center;
}

.insufficient-points a {
  color: #e94560;
  text-decoration: underline;
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

.help-icon {
  margin-left: 5px;
  cursor: help;
  opacity: 0.7;
}

.form-hint {
  color: rgba(255, 255, 255, 0.5);
  font-size: 12px;
  margin-top: 8px;
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

/* 日主信息 */
.day-master-info {
  display: flex;
  justify-content: center;
  margin-bottom: 30px;
}

.day-master-card {
  background: linear-gradient(135deg, rgba(233, 69, 96, 0.2), rgba(255, 107, 107, 0.2));
  border: 2px solid rgba(233, 69, 96, 0.5);
  border-radius: 15px;
  padding: 20px 40px;
  display: flex;
  align-items: center;
  gap: 15px;
}

.day-master-card .label {
  font-size: 14px;
  color: rgba(255, 255, 255, 0.7);
}

.day-master-card .value {
  font-size: 36px;
  font-weight: bold;
  color: #e94560;
}

.day-master-card .wuxing {
  background: rgba(233, 69, 96, 0.3);
  padding: 5px 12px;
  border-radius: 20px;
  font-size: 14px;
  color: #fff;
}

/* 排盘表格样式 */
.paipan-cell {
  flex: 1;
  text-align: center;
  padding: 15px 10px;
  font-size: 24px;
  font-weight: bold;
  color: #fff;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 5px;
  position: relative;
}

.paipan-cell.header {
  font-size: 16px;
  color: rgba(255, 255, 255, 0.6);
  font-weight: normal;
  padding: 10px;
}

.paipan-cell.highlight {
  background: rgba(233, 69, 96, 0.1);
  border-radius: 10px;
}

.gan-text, .zhi-text {
  font-size: 28px;
}

.wuxing-badge {
  font-size: 11px;
  padding: 2px 8px;
  border-radius: 10px;
  background: rgba(255, 255, 255, 0.1);
  font-weight: normal;
}

.wuxing-badge.金 { background: rgba(255, 215, 0, 0.3); color: #ffd700; }
.wuxing-badge.木 { background: rgba(34, 139, 34, 0.3); color: #90ee90; }
.wuxing-badge.水 { background: rgba(30, 144, 255, 0.3); color: #87ceeb; }
.wuxing-badge.火 { background: rgba(255, 69, 0, 0.3); color: #ff6347; }
.wuxing-badge.土 { background: rgba(139, 69, 19, 0.3); color: #deb887; }

.wuxing-badge.zhi {
  opacity: 0.8;
}

.rizhu-tag {
  font-size: 10px;
  background: #e94560;
  color: #fff;
  padding: 2px 6px;
  border-radius: 4px;
  position: absolute;
  top: 5px;
  right: 5px;
}

/* 十神行 */
.shishen-row {
  background: rgba(255, 255, 255, 0.05);
  border-radius: 8px;
  margin: 5px 0;
}

.shishen-cell {
  font-size: 14px;
  color: rgba(255, 255, 255, 0.7);
  padding: 8px;
}

/* 藏干行 */
.canggan-row {
  margin-top: 5px;
}

.canggan-cell {
  font-size: 12px;
  padding: 10px 5px;
  min-height: 60px;
}

.canggan-list {
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.canggan-item {
  color: rgba(255, 255, 255, 0.8);
}

.canggan-item small {
  color: rgba(255, 255, 255, 0.5);
  font-size: 10px;
  margin-left: 2px;
}

/* 纳音行 */
.nayin-row {
  margin-top: 5px;
  background: rgba(255, 215, 0, 0.05);
  border-radius: 8px;
}

.nayin-cell {
  font-size: 12px;
  color: rgba(255, 215, 0, 0.9);
  padding: 8px;
}

/* 五行统计 */
.wuxing-stats {
  background: rgba(0, 0, 0, 0.2);
  border-radius: 15px;
  padding: 25px;
  margin: 30px 0;
}

.wuxing-stats h3 {
  text-align: center;
  margin-bottom: 20px;
  color: #fff;
}

.wuxing-bars {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.wuxing-bar-item {
  display: flex;
  align-items: center;
  gap: 15px;
}

.wuxing-name {
  width: 30px;
  font-weight: bold;
  color: #fff;
}

.wuxing-bar {
  flex: 1;
  height: 20px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 10px;
  overflow: hidden;
}

.wuxing-fill {
  height: 100%;
  border-radius: 10px;
  transition: width 0.5s ease;
}

.wuxing-fill.金 { background: linear-gradient(90deg, #ffd700, #ffec8b); }
.wuxing-fill.木 { background: linear-gradient(90deg, #228b22, #90ee90); }
.wuxing-fill.水 { background: linear-gradient(90deg, #1e90ff, #87ceeb); }
.wuxing-fill.火 { background: linear-gradient(90deg, #ff4500, #ff6347); }
.wuxing-fill.土 { background: linear-gradient(90deg, #8b4513, #deb887); }

.wuxing-count {
  width: 30px;
  text-align: center;
  color: rgba(255, 255, 255, 0.8);
}

/* 操作按钮 */
.result-actions {
  display: flex;
  justify-content: center;
  gap: 15px;
  margin-top: 30px;
  flex-wrap: wrap;
}

.result-actions .btn-icon {
  margin-right: 5px;
}

@media (max-width: 768px) {
  .paipan-cell {
    font-size: 18px;
    padding: 10px 5px;
  }
  
  .gan-text, .zhi-text {
    font-size: 22px;
  }
  
  .shishen-cell {
    font-size: 12px;
  }
  
  .canggan-cell {
    font-size: 10px;
    min-height: 50px;
  }
  
  .nayin-cell {
    font-size: 10px;
  }
  
  .day-master-card .value {
    font-size: 28px;
  }
}
</style>
