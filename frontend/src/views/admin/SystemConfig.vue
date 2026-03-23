
<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import {
  getFeatureSwitches, updateFeatures,
  getVipConfig, updateVipConfig,
  getPointsConfig, updatePointsConfig,
  getMarketingConfig, updateMarketingConfig,
  refreshConfigCache
} from '../../api/admin'

const activeTab = ref('features')
const loading = ref(false)
const saving = ref(false)

// ========== 功能开关 ==========
const features = ref({})
const loadFeatures = async () => {
  loading.value = true
  try {
    const res = await getFeatureSwitches()
    if (res.code === 200) {
      features.value = res.data || {}
    }
  } catch (e) {
    ElMessage.error('加载功能开关失败')
  } finally {
    loading.value = false
  }
}
const saveFeatures = async () => {
  saving.value = true
  try {
    const res = await updateFeatures(features.value)
    if (res.code === 200) {
      ElMessage.success('功能开关保存成功')
    } else {
      ElMessage.error(res.message || '保存失败')
    }
  } catch (e) {
    ElMessage.error('保存失败')
  } finally {
    saving.value = false
  }
}

// 功能开关列表定义
const featureList = [
  { key: 'bazi', label: '八字排盘', desc: '八字排盘和AI解盘功能' },
  { key: 'tarot', label: '塔罗占卜', desc: '塔罗牌抽取和解读功能' },
  { key: 'daily', label: '每日运势', desc: '每日运势查看功能' },
  { key: 'hehun', label: '合婚测算', desc: '八字合婚配对功能' },
  { key: 'liuyao', label: '六爻占卜', desc: '六爻起卦和解卦功能' },
  { key: 'qiming', label: '取名建议', desc: '根据八字AI取名功能' },
  { key: 'yearly_fortune', label: '流年运势', desc: '全年运势深度解析功能' },
  { key: 'recharge', label: '积分充值', desc: '积分充值购买功能' },
  { key: 'vip', label: 'VIP会员', desc: 'VIP会员开通和管理' },
]

// ========== VIP配置 ==========
const vipConfig = reactive({
  month_price: '',
  quarter_price: '',
  year_price: '',
  daily_points_multiplier: '',
  paipan_limit: '',
  unlock_basic_report: false,
  unlock_hehun: false,
  unlock_qiming: false,
})
const loadVipConfig = async () => {
  loading.value = true
  try {
    const res = await getVipConfig()
    if (res.code === 200) {
      Object.assign(vipConfig, res.data || {})
    }
  } catch (e) {
    ElMessage.error('加载VIP配置失败')
  } finally {
    loading.value = false
  }
}
const saveVipConfig = async () => {
  saving.value = true
  try {
    const res = await updateVipConfig({ ...vipConfig })
    if (res.code === 200) {
      ElMessage.success('VIP配置保存成功')
    } else {
      ElMessage.error(res.message || '保存失败')
    }
  } catch (e) {
    ElMessage.error('保存失败')
  } finally {
    saving.value = false
  }
}

// ========== 积分配置 ==========
const pointsConfig = reactive({ tasks: {}, costs: {} })
const loadPointsConfig = async () => {
  loading.value = true
  try {
    const res = await getPointsConfig()
    if (res.code === 200) {
      pointsConfig.tasks = res.data?.tasks || {}
      pointsConfig.costs = res.data?.costs || {}
    }
  } catch (e) {
    ElMessage.error('加载积分配置失败')
  } finally {
    loading.value = false
  }
}
const savePointsConfig = async () => {
  saving.value = true
  try {
    const res = await updatePointsConfig({
      tasks: pointsConfig.tasks,
      costs: pointsConfig.costs
    })
    if (res.code === 200) {
      ElMessage.success('积分配置保存成功')
    } else {
      ElMessage.error(res.message || '保存失败')
    }
  } catch (e) {
    ElMessage.error('保存失败')
  } finally {
    saving.value = false
  }
}

// 积分任务列表定义
const taskList = [
  { key: 'sign_daily', label: '每日签到' },
  { key: 'sign_continuous_7', label: '连续签到7天' },
  { key: 'sign_continuous_30', label: '连续签到30天' },
  { key: 'share_app', label: '分享应用' },
  { key: 'invite_friend', label: '邀请好友' },
  { key: 'complete_profile', label: '完善资料' },
  { key: 'first_paipan', label: '首次排盘' },
]

// 积分消耗列表定义
const costList = [
  { key: 'save_record', label: '保存记录' },
  { key: 'share_poster', label: '生成海报' },
  { key: 'unlock_report', label: '解锁报告' },
  { key: 'yearly_fortune', label: '流年运势' },
  { key: 'hehun_basic', label: '基础合婚' },
  { key: 'hehun_standard', label: '标准合婚' },
  { key: 'hehun_professional', label: '专业合婚' },
  { key: 'qiming', label: '取名建议' },
  { key: 'bazi_basic', label: '基础八字' },
  { key: 'bazi_standard', label: '标准八字' },
  { key: 'bazi_professional', label: '专业八字' },
]

// ========== 营销配置 ==========
const marketingConfig = reactive({
  limited_offer: { enabled: false, discount: '', start_time: '', end_time: '' },
  new_user: { enabled: false, discount: '', valid_hours: '' },
  recharge: { enabled: false, ratio: '' },
})
const loadMarketingConfig = async () => {
  loading.value = true
  try {
    const res = await getMarketingConfig()
    if (res.code === 200) {
      if (res.data?.limited_offer) Object.assign(marketingConfig.limited_offer, res.data.limited_offer)
      if (res.data?.new_user) Object.assign(marketingConfig.new_user, res.data.new_user)
      if (res.data?.recharge) Object.assign(marketingConfig.recharge, res.data.recharge)
    }
  } catch (e) {
    ElMessage.error('加载营销配置失败')
  } finally {
    loading.value = false
  }
}
const saveMarketingConfig = async () => {
  saving.value = true
  try {
    const res = await updateMarketingConfig({ ...marketingConfig })
    if (res.code === 200) {
      ElMessage.success('营销配置保存成功')
    } else {
      ElMessage.error(res.message || '保存失败')
    }
  } catch (e) {
    ElMessage.error('保存失败')
  } finally {
    saving.value = false
  }
}

// ========== 切换Tab加载数据 ==========
const handleTabChange = (tab) => {
  switch (tab) {
    case 'features': loadFeatures(); break
    case 'vip': loadVipConfig(); break
    case 'points': loadPointsConfig(); break
    case 'marketing': loadMarketingConfig(); break
  }
}

// 刷新缓存
const handleRefreshCache = async () => {
  try {
    const res = await refreshConfigCache()
    if (res.code === 200) {
      ElMessage.success('缓存已刷新')
    } else {
      ElMessage.error(res.message || '刷新失败')
    }
  } catch (e) {
    ElMessage.error('刷新缓存失败')
  }
}

onMounted(() => {
  loadFeatures()
})
</script>

<template>
  <div class="admin-manage-page">
    <div class="page-header">
      <h2>系统配置</h2>
      <el-button type="warning" size="small" @click="handleRefreshCache">刷新配置缓存</el-button>
    </div>

    <el-tabs v-model="activeTab" @tab-change="handleTabChange">
      <!-- 功能开关 -->
      <el-tab-pane label="功能开关" name="features">
        <div class="config-section" v-loading="loading">
          <p class="section-desc">控制各功能模块的启用/禁用状态，关闭后用户将无法访问对应功能。</p>
          <div class="feature-grid">
            <div class="feature-item" v-for="item in featureList" :key="item.key">
              <div class="feature-info">
                <span class="feature-label">{{ item.label }}</span>
                <span class="feature-desc">{{ item.desc }}</span>
              </div>
              <el-switch v-model="features[item.key]" />
            </div>
          </div>
          <div class="config-actions">
            <el-button type="primary" :loading="saving" @click="saveFeatures">保存功能开关</el-button>
          </div>
        </div>
      </el-tab-pane>

      <!-- VIP配置 -->
      <el-tab-pane label="VIP配置" name="vip">
        <div class="config-section" v-loading="loading">
          <p class="section-desc">配置VIP会员的价格和权益。</p>
          <el-form label-width="140px" class="config-form">
            <h4 class="form-subtitle">💰 价格配置</h4>
            <el-form-item label="月卡价格（元）">
              <el-input-number v-model="vipConfig.month_price" :min="0" :precision="2" />
            </el-form-item>
            <el-form-item label="季卡价格（元）">
              <el-input-number v-model="vipConfig.quarter_price" :min="0" :precision="2" />
            </el-form-item>
            <el-form-item label="年卡价格（元）">
              <el-input-number v-model="vipConfig.year_price" :min="0" :precision="2" />
            </el-form-item>

            <h4 class="form-subtitle">🎁 权益配置</h4>
            <el-form-item label="积分倍率">
              <el-input-number v-model="vipConfig.daily_points_multiplier" :min="1" :max="10" />
              <span class="form-tip">VIP用户每日签到积分倍率</span>
            </el-form-item>
            <el-form-item label="每日排盘次数">
              <el-input-number v-model="vipConfig.paipan_limit" :min="0" />
              <span class="form-tip">0表示无限制</span>
            </el-form-item>
            <el-form-item label="解锁基础报告">
              <el-switch v-model="vipConfig.unlock_basic_report" />
            </el-form-item>
            <el-form-item label="解锁合婚功能">
              <el-switch v-model="vipConfig.unlock_hehun" />
            </el-form-item>
            <el-form-item label="解锁取名功能">
              <el-switch v-model="vipConfig.unlock_qiming" />
            </el-form-item>
          </el-form>
          <div class="config-actions">
            <el-button type="primary" :loading="saving" @click="saveVipConfig">保存VIP配置</el-button>
          </div>
        </div>
      </el-tab-pane>

      <!-- 积分配置 -->
      <el-tab-pane label="积分配置" name="points">
        <div class="config-section" v-loading="loading">
          <p class="section-desc">配置积分获取任务和各功能的积分消耗。</p>

          <h4 class="form-subtitle">📈 积分获取（任务积分）</h4>
          <el-form label-width="140px" class="config-form">
            <el-form-item v-for="item in taskList" :key="item.key" :label="item.label">
              <el-input-number v-model="pointsConfig.tasks[item.key]" :min="0" />
              <span class="form-tip">积分</span>
            </el-form-item>
          </el-form>

          <h4 class="form-subtitle">📉 积分消耗</h4>
          <el-form label-width="140px" class="config-form">
            <el-form-item v-for="item in costList" :key="item.key" :label="item.label">
              <el-input-number v-model="pointsConfig.costs[item.key]" :min="0" />
              <span class="form-tip">积分</span>
            </el-form-item>
          </el-form>

          <div class="config-actions">
            <el-button type="primary" :loading="saving" @click="savePointsConfig">保存积分配置</el-button>
          </div>
        </div>
      </el-tab-pane>

      <!-- 营销配置 -->
      <el-tab-pane label="营销配置" name="marketing">
        <div class="config-section" v-loading="loading">
          <p class="section-desc">配置限时优惠、新用户折扣、充值比例等营销活动。</p>

          <h4 class="form-subtitle">⏰ 限时优惠</h4>
          <el-form label-width="140px" class="config-form">
            <el-form-item label="启用限时优惠">
              <el-switch v-model="marketingConfig.limited_offer.enabled" />
            </el-form-item>
            <el-form-item label="折扣（%）">
              <el-input-number v-model="marketingConfig.limited_offer.discount" :min="0" :max="100" />
            </el-form-item>
            <el-form-item label="开始时间">
              <el-date-picker v-model="marketingConfig.limited_offer.start_time" type="datetime" value-format="YYYY-MM-DD HH:mm:ss" placeholder="开始时间" />
            </el-form-item>
            <el-form-item label="结束时间">
              <el-date-picker v-model="marketingConfig.limited_offer.end_time" type="datetime" value-format="YYYY-MM-DD HH:mm:ss" placeholder="结束时间" />
            </el-form-item>
          </el-form>

          <h4 class="form-subtitle">🎉 新用户优惠</h4>
          <el-form label-width="140px" class="config-form">
            <el-form-item label="启用新人优惠">
              <el-switch v-model="marketingConfig.new_user.enabled" />
            </el-form-item>
            <el-form-item label="折扣（%）">
              <el-input-number v-model="marketingConfig.new_user.discount" :min="0" :max="100" />
            </el-form-item>
            <el-form-item label="有效时长（小时）">
              <el-input-number v-model="marketingConfig.new_user.valid_hours" :min="1" />
            </el-form-item>
          </el-form>

          <h4 class="form-subtitle">💳 充值配置</h4>
          <el-form label-width="140px" class="config-form">
            <el-form-item label="启用充值">
              <el-switch v-model="marketingConfig.recharge.enabled" />
            </el-form-item>
            <el-form-item label="充值比例">
              <el-input-number v-model="marketingConfig.recharge.ratio" :min="1" :precision="1" />
              <span class="form-tip">1元 = 多少积分</span>
            </el-form-item>
          </el-form>

          <div class="config-actions">
            <el-button type="primary" :loading="saving" @click="saveMarketingConfig">保存营销配置</el-button>
          </div>
        </div>
      </el-tab-pane>
    </el-tabs>
  </div>
</template>

<style scoped>
.admin-manage-page { padding: 24px; }
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.page-header h2 { font-size: 20px; color: #333; margin: 0; }

.config-section { background: #fff; padding: 24px; border-radius: 8px; }
.section-desc { color: #666; margin-bottom: 20px; font-size: 14px; }

.feature-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 16px; }
.feature-item {
  display: flex; justify-content: space-between; align-items: center;
  padding: 16px; background: #f5f7fa; border-radius: 8px;
}
.feature-info { display: flex; flex-direction: column; gap: 4px; }
.feature-label { font-weight: 600; color: #333; }
.feature-desc { font-size: 12px; color: #999; }

.config-form { max-width: 600px; }
.config-actions { margin-top: 24px; padding-top: 16px; border-top: 1px solid #eee; }

.form-subtitle { margin: 24px 0 16px; padding-left: 8px; border-left: 3px solid #409eff; color: #333; font-size: 15px; }
.form-subtitle:first-child { margin-top: 0; }
.form-tip { margin-left: 8px; color: #999; font-size: 12px; }
</style>
