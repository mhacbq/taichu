<template>
  <div class="config-admin">
    <div class="header">
      <h2>系统配置管理</h2>
      <el-button type="primary" @click="refreshCache">
        <el-icon><Refresh /></el-icon>
        刷新缓存
      </el-button>
    </div>

    <el-tabs v-model="activeTab" type="border-card">
      <!-- 功能开关 -->
      <el-tab-pane label="功能开关" name="features">
        <div class="feature-switches">
          <h3>功能开关控制</h3>
          <p class="desc">开启或关闭前端功能模块，修改后立即生效</p>
          
          <div class="switch-grid">
            <div 
              v-for="(item, key) in features" 
              :key="key"
              class="switch-item"
              :class="{ 'enabled': item.enabled }"
            >
              <div class="switch-info">
                <span class="feature-name">{{ item.name }}</span>
                <el-tag :type="item.enabled ? 'success' : 'info'" size="small">
                  {{ item.enabled ? '已开启' : '已关闭' }}
                </el-tag>
              </div>
              <el-switch
                v-model="item.enabled"
                @change="(val) => handleUpdateFeature(key, val)"
                :loading="savingFeatures[key]"
              />
            </div>
          </div>
          
          <div class="batch-actions">
            <el-button @click="enableAllFeatures">全部开启</el-button>
            <el-button @click="disableAllFeatures">全部关闭</el-button>
            <el-button type="primary" @click="saveAllFeatures">保存所有修改</el-button>
          </div>
        </div>
      </el-tab-pane>

      <!-- VIP配置 -->
      <el-tab-pane label="VIP配置" name="vip">
        <div class="vip-config">
          <h3>VIP会员配置</h3>
          
          <el-form :model="vipForm" label-width="150px" class="config-form">
            <el-divider>价格设置</el-divider>
            
            <el-form-item label="月度VIP价格">
              <el-input-number v-model="vipForm.month_price" :min="1" :precision="2" />
              <span class="unit">元/月</span>
            </el-form-item>
            
            <el-form-item label="季度VIP价格">
              <el-input-number v-model="vipForm.quarter_price" :min="1" :precision="2" />
              <span class="unit">元/季</span>
            </el-form-item>
            
            <el-form-item label="年度VIP价格">
              <el-input-number v-model="vipForm.year_price" :min="1" :precision="2" />
              <span class="unit">元/年</span>
            </el-form-item>
            
            <el-divider>权益设置</el-divider>
            
            <el-form-item label="积分倍数">
              <el-input-number v-model="vipForm.daily_points_multiplier" :min="1" :max="10" />
              <span class="hint">VIP用户签到积分倍数</span>
            </el-form-item>
            
            <el-form-item label="每日排盘次数">
              <el-input-number v-model="vipForm.paipan_limit" :min="-1" />
              <span class="hint">-1表示无限次</span>
            </el-form-item>
            
            <el-form-item label="解锁基础报告">
              <el-switch v-model="vipForm.unlock_basic_report" />
            </el-form-item>
            
            <el-form-item label="解锁合婚功能">
              <el-switch v-model="vipForm.unlock_hehun" />
            </el-form-item>
            
            <el-form-item label="解锁取名功能">
              <el-switch v-model="vipForm.unlock_qiming" />
            </el-form-item>
            
            <el-form-item>
              <el-button type="primary" @click="saveVipConfig" :loading="saving.vip">
                保存VIP配置
              </el-button>
            </el-form-item>
          </el-form>
        </div>
      </el-tab-pane>

      <!-- 积分配置 -->
      <el-tab-pane label="积分配置" name="points">
        <div class="points-config">
          <h3>积分获取配置</h3>
          
          <el-form :model="pointsForm" label-width="150px" class="config-form">
            <el-divider>每日任务</el-divider>
            
            <el-form-item label="每日签到">
              <el-input-number v-model="pointsForm.tasks.sign_daily" :min="0" />
              <span class="unit">积分</span>
            </el-form-item>
            
            <el-form-item label="连续7天签到">
              <el-input-number v-model="pointsForm.tasks.sign_continuous_7" :min="0" />
              <span class="unit">额外积分</span>
            </el-form-item>
            
            <el-form-item label="连续30天签到">
              <el-input-number v-model="pointsForm.tasks.sign_continuous_30" :min="0" />
              <span class="unit">额外积分</span>
            </el-form-item>
            
            <el-divider>一次性任务</el-divider>
            
            <el-form-item label="完善资料">
              <el-input-number v-model="pointsForm.tasks.complete_profile" :min="0" />
              <span class="unit">积分</span>
            </el-form-item>
            
            <el-form-item label="首次排盘">
              <el-input-number v-model="pointsForm.tasks.first_paipan" :min="0" />
              <span class="unit">积分</span>
            </el-form-item>
            
            <el-form-item label="绑定微信">
              <el-input-number v-model="pointsForm.tasks.bind_wechat" :min="0" />
              <span class="unit">积分</span>
            </el-form-item>
            
            <el-form-item label="关注公众号">
              <el-input-number v-model="pointsForm.tasks.follow_mp" :min="0" />
              <span class="unit">积分</span>
            </el-form-item>
            
            <el-divider>社交任务</el-divider>
            
            <el-form-item label="分享小程序">
              <el-input-number v-model="pointsForm.tasks.share_app" :min="0" />
              <span class="unit">积分</span>
            </el-form-item>
            
            <el-form-item label="邀请好友">
              <el-input-number v-model="pointsForm.tasks.invite_friend" :min="0" />
              <span class="unit">积分</span>
            </el-form-item>
            
            <el-form-item label="浏览文章">
              <el-input-number v-model="pointsForm.tasks.browse_article" :min="0" />
              <span class="unit">积分</span>
            </el-form-item>
            
            <el-divider>积分消耗</el-divider>
            
            <el-form-item label="保存记录">
              <el-input-number v-model="pointsForm.costs.save_record" :min="0" />
              <span class="unit">积分</span>
            </el-form-item>
            
            <el-form-item label="分享海报">
              <el-input-number v-model="pointsForm.costs.share_poster" :min="0" />
              <span class="unit">积分</span>
            </el-form-item>
            
            <el-form-item label="解锁详细报告">
              <el-input-number v-model="pointsForm.costs.unlock_report" :min="0" />
              <span class="unit">积分</span>
            </el-form-item>
            
            <el-form-item label="流年运势">
              <el-input-number v-model="pointsForm.costs.yearly_fortune" :min="0" />
              <span class="unit">积分</span>
            </el-form-item>
            
            <el-form-item label="大运分析">
              <el-input-number v-model="pointsForm.costs.dayun_analysis" :min="0" />
              <span class="unit">积分</span>
            </el-form-item>
            
            <el-form-item label="运势K线图">
              <el-input-number v-model="pointsForm.costs.dayun_chart" :min="0" />
              <span class="unit">积分</span>
            </el-form-item>
            
            <el-form-item label="八字合婚">
              <el-input-number v-model="pointsForm.costs.hehun" :min="0" />
              <span class="unit">积分</span>
            </el-form-item>
            
            <el-form-item label="取名建议">
              <el-input-number v-model="pointsForm.costs.qiming" :min="0" />
              <span class="unit">积分</span>
            </el-form-item>
            
            <el-form-item label="吉日查询">
              <el-input-number v-model="pointsForm.costs.jiri" :min="0" />
              <span class="unit">积分</span>
            </el-form-item>
            
            <el-form-item>
              <el-button type="primary" @click="savePointsConfig" :loading="saving.points">
                保存积分配置
              </el-button>
            </el-form-item>
          </el-form>
        </div>
      </el-tab-pane>

      <!-- 营销配置 -->
      <el-tab-pane label="营销配置" name="marketing">
        <div class="marketing-config">
          <h3>营销与优惠配置</h3>
          
          <el-form :model="marketingForm" label-width="150px" class="config-form">
            <el-divider>限时优惠</el-divider>
            
            <el-form-item label="开启限时优惠">
              <el-switch v-model="marketingForm.limited_offer.enabled" />
            </el-form-item>
            
            <template v-if="marketingForm.limited_offer.enabled">
              <el-form-item label="折扣幅度">
                <el-slider v-model="marketingForm.limited_offer.discount" :max="90" show-stops :step="5" />
                <span class="unit">{{ marketingForm.limited_offer.discount }}% off</span>
              </el-form-item>
              
              <el-form-item label="开始时间">
                <el-date-picker
                  v-model="marketingForm.limited_offer.start_time"
                  type="datetime"
                  placeholder="选择开始时间"
                  format="YYYY-MM-DD HH:mm:ss"
                  value-format="YYYY-MM-DD HH:mm:ss"
                />
              </el-form-item>
              
              <el-form-item label="结束时间">
                <el-date-picker
                  v-model="marketingForm.limited_offer.end_time"
                  type="datetime"
                  placeholder="选择结束时间"
                  format="YYYY-MM-DD HH:mm:ss"
                  value-format="YYYY-MM-DD HH:mm:ss"
                />
              </el-form-item>
            </template>
            
            <el-divider>新用户优惠</el-divider>
            
            <el-form-item label="开启新用户优惠">
              <el-switch v-model="marketingForm.new_user.enabled" />
            </el-form-item>
            
            <template v-if="marketingForm.new_user.enabled">
              <el-form-item label="新用户折扣">
                <el-slider v-model="marketingForm.new_user.discount" :max="90" show-stops :step="5" />
                <span class="unit">{{ marketingForm.new_user.discount }}% off</span>
              </el-form-item>
              
              <el-form-item label="有效期">
                <el-input-number v-model="marketingForm.new_user.valid_hours" :min="1" :max="168" />
                <span class="unit">小时</span>
              </el-form-item>
            </template>
            
            <el-divider>充值配置</el-divider>
            
            <el-form-item label="开启充值">
              <el-switch v-model="marketingForm.recharge.enabled" />
            </el-form-item>
            
            <el-form-item label="充值比例">
              <el-input-number v-model="marketingForm.recharge.ratio" :min="1" />
              <span class="unit">积分/元</span>
            </el-form-item>
            
            <el-form-item>
              <el-button type="primary" @click="saveMarketingConfig" :loading="saving.marketing">
                保存营销配置
              </el-button>
            </el-form-item>
          </el-form>
        </div>
      </el-tab-pane>
    </el-tabs>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { Refresh } from '@element-plus/icons-vue'
import { 
  getFeatureSwitches, 
  updateFeature, 
  updateFeatures,
  getVipConfig,
  updateVipConfig,
  getPointsConfig,
  updatePointsConfig,
  getMarketingConfig,
  updateMarketingConfig,
  refreshConfigCache
} from '../../api/admin'

const activeTab = ref('features')
const saving = ref({
  vip: false,
  points: false,
  marketing: false,
})

// 功能开关
const features = ref({})
const savingFeatures = ref({})

// VIP配置
const vipForm = ref({
  month_price: 19.9,
  quarter_price: 49,
  year_price: 168,
  daily_points_multiplier: 2,
  paipan_limit: -1,
  unlock_basic_report: true,
  unlock_hehun: true,
  unlock_qiming: false,
})

// 积分配置
const pointsForm = ref({
  tasks: {
    sign_daily: 10,
    sign_continuous_7: 20,
    sign_continuous_30: 50,
    share_app: 20,
    invite_friend: 50,
    complete_profile: 30,
    first_paipan: 20,
    bind_wechat: 30,
    follow_mp: 20,
    browse_article: 5,
  },
  costs: {
    save_record: 10,
    share_poster: 20,
    unlock_report: 50,
    yearly_fortune: 30,
    dayun_analysis: 50,
    dayun_chart: 30,
    hehun: 80,
    qiming: 100,
    jiri: 20,
  }
})

// 营销配置
const marketingForm = ref({
  limited_offer: {
    enabled: false,
    discount: 50,
    start_time: '',
    end_time: '',
  },
  new_user: {
    enabled: true,
    discount: 50,
    valid_hours: 24,
  },
  recharge: {
    enabled: true,
    ratio: 10,
  }
})

// 加载功能开关
const loadFeatures = async () => {
  try {
    const res = await getFeatureSwitches()
    if (res.code === 200) {
      features.value = res.data
    }
  } catch (error) {
    console.error('加载功能开关失败:', error)
  }
}

// 更新单个功能开关
const handleUpdateFeature = async (key, enabled) => {
  savingFeatures.value[key] = true
  try {
    const res = await updateFeature(key, enabled)
    if (res.code === 200) {
      ElMessage.success('功能开关已更新')
    } else {
      ElMessage.error(res.message)
      // 恢复原状态
      features.value[key].enabled = !enabled
    }
  } catch (error) {
    ElMessage.error('更新失败')
    features.value[key].enabled = !enabled
  } finally {
    savingFeatures.value[key] = false
  }
}

// 全部开启
const enableAllFeatures = () => {
  Object.keys(features.value).forEach(key => {
    features.value[key].enabled = true
  })
}

// 全部关闭
const disableAllFeatures = () => {
  Object.keys(features.value).forEach(key => {
    features.value[key].enabled = false
  })
}

// 保存所有功能开关
const saveAllFeatures = async () => {
  const featureData = {}
  Object.keys(features.value).forEach(key => {
    featureData[key] = features.value[key].enabled
  })
  
  try {
    const res = await updateFeatures(featureData)
    if (res.code === 200) {
      ElMessage.success('所有功能开关已保存')
    } else {
      ElMessage.error(res.message)
    }
  } catch (error) {
    ElMessage.error('保存失败')
  }
}

// 加载VIP配置
const loadVipConfig = async () => {
  try {
    const res = await getVipConfig()
    if (res.code === 200) {
      Object.assign(vipForm.value, res.data)
    }
  } catch (error) {
    console.error('加载VIP配置失败:', error)
  }
}

// 保存VIP配置
const saveVipConfig = async () => {
  saving.value.vip = true
  try {
    const res = await updateVipConfig(vipForm.value)
    if (res.code === 200) {
      ElMessage.success('VIP配置已保存')
    } else {
      ElMessage.error(res.message)
    }
  } catch (error) {
    ElMessage.error('保存失败')
  } finally {
    saving.value.vip = false
  }
}

// 加载积分配置
const loadPointsConfig = async () => {
  try {
    const res = await getPointsConfig()
    if (res.code === 200) {
      pointsForm.value.tasks = res.data.tasks
      pointsForm.value.costs = res.data.costs
    }
  } catch (error) {
    console.error('加载积分配置失败:', error)
  }
}

// 保存积分配置
const savePointsConfig = async () => {
  saving.value.points = true
  try {
    const res = await updatePointsConfig(pointsForm.value)
    if (res.code === 200) {
      ElMessage.success('积分配置已保存')
    } else {
      ElMessage.error(res.message)
    }
  } catch (error) {
    ElMessage.error('保存失败')
  } finally {
    saving.value.points = false
  }
}

// 加载营销配置
const loadMarketingConfig = async () => {
  try {
    const res = await getMarketingConfig()
    if (res.code === 200) {
      Object.assign(marketingForm.value, res.data)
    }
  } catch (error) {
    console.error('加载营销配置失败:', error)
  }
}

// 保存营销配置
const saveMarketingConfig = async () => {
  saving.value.marketing = true
  try {
    const res = await updateMarketingConfig(marketingForm.value)
    if (res.code === 200) {
      ElMessage.success('营销配置已保存')
    } else {
      ElMessage.error(res.message)
    }
  } catch (error) {
    ElMessage.error('保存失败')
  } finally {
    saving.value.marketing = false
  }
}

// 刷新缓存
const refreshCache = async () => {
  try {
    const res = await refreshConfigCache()
    if (res.code === 200) {
      ElMessage.success('缓存已刷新')
    }
  } catch (error) {
    ElMessage.error('刷新失败')
  }
}


onMounted(() => {
  loadFeatures()
  loadVipConfig()
  loadPointsConfig()
  loadMarketingConfig()
})
</script>

<style scoped>
.config-admin {
  padding: 20px;
  max-width: 1200px;
  margin: 0 auto;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.header h2 {
  margin: 0;
  color: var(--text-primary);
}

.feature-switches,
.vip-config,
.points-config,
.marketing-config {
  padding: 20px;
}

.feature-switches h3,
.vip-config h3,
.points-config h3,
.marketing-config h3 {
  color: var(--text-primary);
  margin-bottom: 10px;
}

.desc {
  color: var(--text-secondary);
  margin-bottom: 20px;
}

.switch-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 15px;
  margin-bottom: 30px;
}

.switch-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px 20px;
  background: var(--bg-secondary);
  border-radius: 8px;
  border: 1px solid var(--border-light);
  transition: all 0.3s;
}

.switch-item.enabled {
  border-color: #67c23a;
  background: rgba(103, 194, 58, 0.1);
}

.switch-info {
  display: flex;
  align-items: center;
  gap: 10px;
}

.feature-name {
  color: var(--text-primary);
  font-size: 14px;
}

.batch-actions {
  display: flex;
  gap: 10px;
  padding-top: 20px;
  border-top: 1px solid var(--border-light);
}

.config-form {
  max-width: 600px;
}

.unit {
  margin-left: 10px;
  color: var(--text-secondary);
}

.hint {
  margin-left: 10px;
  color: var(--text-muted);
  font-size: 12px;
}

:deep(.el-form-item__label) {
  color: var(--text-secondary);
}

:deep(.el-divider__text) {
  background: var(--bg-card);
  color: var(--text-secondary);
}
</style>
