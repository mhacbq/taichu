<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { getPointsConfig, updatePointsConfig } from '../../api/admin'

const loading = ref(false)
const saving = ref(false)
const activeTab = ref('tasks')

// 积分获取任务（可编辑积分数量）
const tasks = ref({})
// 积分消耗配置（可编辑积分数量）
const costs = ref({})

// 任务规则的展示配置
const taskDefs = [
  { key: 'sign_daily', label: '每日签到', desc: '每天签到一次获得积分' },
  { key: 'sign_continuous_7', label: '连续7天签到', desc: '连续签到7天额外奖励' },
  { key: 'sign_continuous_30', label: '连续30天签到', desc: '连续签到30天额外奖励' },
  { key: 'share_app', label: '分享小程序', desc: '分享小程序给好友获得积分' },
  { key: 'invite_friend', label: '邀请好友（充值奖励）', desc: '好友首次充值后，邀请人获得积分奖励（无人数上限）' },
  { key: 'invite_register', label: '邀请好友（注册奖励）', desc: '好友填写邀请码注册时，双方立即各获得积分（每人最多邀请10人享此奖励）' },
  { key: 'complete_profile', label: '完善资料', desc: '首次完善个人资料获得积分' },
  { key: 'first_paipan', label: '首次排盘', desc: '首次进行八字排盘获得积分' },
  { key: 'bind_wechat', label: '绑定微信', desc: '绑定微信账号获得积分' },
  { key: 'follow_mp', label: '关注公众号', desc: '关注公众号获得积分' },
  { key: 'browse_article', label: '浏览文章', desc: '浏览知识库文章获得积分' },
]

// 消耗规则的展示配置
const costDefs = [
  { key: 'bazi', label: '八字分析（普通版）', desc: '不调用AI，基础八字分析消耗积分' },
  { key: 'bazi_ai', label: '八字分析（专家版·AI）', desc: '调用AI深度分析，消耗积分更多' },
  { key: 'hehun_ai', label: '合婚分析（AI版）', desc: '调用AI深度分析合婚，消耗积分' },
  { key: 'liuyao_ai', label: '六爻占卜（AI版）', desc: '调用AI深度分析六爻，消耗积分' },
  { key: 'tarot', label: '塔罗牌（普通版）', desc: '塔罗牌占卜消耗积分' },
  { key: 'tarot_ai', label: '塔罗牌（AI版）', desc: '调用AI解读塔罗，消耗积分更多' },
  { key: 'qiming', label: '取名建议', desc: '取名建议消耗积分' },
  { key: 'jiri', label: '吉日查询', desc: '吉日查询消耗积分' },
  { key: 'save_record', label: '保存记录', desc: '保存排盘/占卜记录消耗积分' },
  { key: 'share_poster', label: '生成分享海报', desc: '生成分享海报消耗积分' },
  { key: 'unlock_report', label: '解锁完整报告', desc: '解锁完整分析报告消耗积分' },
  { key: 'yearly_fortune', label: '流年运势', desc: '查看流年运势消耗积分' },
  { key: 'dayun_analysis', label: '大运分析', desc: '查看大运分析消耗积分' },
  { key: 'dayun_chart', label: '运势K线图', desc: '查看运势K线图消耗积分' },
]

const loadConfig = async () => {
  loading.value = true
  try {
    const res = await getPointsConfig()
    if (res.code === 200) {
      // tasks: { sign_daily: { name, points }, ... }
      const rawTasks = res.data?.tasks || {}
      const rawCosts = res.data?.costs || {}

      // 转为可编辑的 { key: points } 结构
      const tasksMap = {}
      for (const [key, val] of Object.entries(rawTasks)) {
        tasksMap[key] = typeof val === 'object' ? (val.points ?? 0) : (val ?? 0)
      }
      tasks.value = tasksMap

      const costsMap = {}
      for (const [key, val] of Object.entries(rawCosts)) {
        costsMap[key] = typeof val === 'object' ? (val.points ?? val ?? 0) : (val ?? 0)
      }
      costs.value = costsMap
    } else {
      ElMessage.error(res.message || '加载失败')
    }
  } catch (e) {
    console.error('加载积分配置失败:', e)
    ElMessage.error('加载失败')
  } finally {
    loading.value = false
  }
}

const handleSave = async () => {
  saving.value = true
  try {
    const res = await updatePointsConfig({
      tasks: tasks.value,
      costs: costs.value,
    })
    if (res.code === 200) {
      ElMessage.success('保存成功')
    } else {
      ElMessage.error(res.message || '保存失败')
    }
  } catch (e) {
    console.error('保存积分配置失败:', e)
    ElMessage.error('保存失败')
  } finally {
    saving.value = false
  }
}

onMounted(loadConfig)
</script>

<template>
  <div class="points-rules">
    <div class="page-header">
      <h2>积分规则配置</h2>
      <el-button type="primary" :loading="saving" @click="handleSave">保存配置</el-button>
    </div>

    <el-tabs v-model="activeTab" v-loading="loading">
      <!-- 积分获取规则 -->
      <el-tab-pane label="积分获取规则" name="tasks">
        <div class="rule-tip">
          <el-alert type="info" :closable="false" title="以下为用户完成各类任务可获得的积分数量，设为 0 表示该任务不奖励积分" />
        </div>
        <el-table :data="taskDefs" stripe>
          <el-table-column prop="label" label="规则名称" width="180" />
          <el-table-column prop="desc" label="说明" min-width="220" show-overflow-tooltip />
          <el-table-column label="奖励积分" width="180">
            <template #default="{ row }">
              <el-input-number
                v-model="tasks[row.key]"
                :min="0"
                :max="99999"
                controls-position="right"
                size="small"
              />
            </template>
          </el-table-column>
        </el-table>
      </el-tab-pane>

      <!-- 积分消耗规则 -->
      <el-tab-pane label="积分消耗规则" name="costs">
        <div class="rule-tip">
          <el-alert type="warning" :closable="false" title="以下为用户使用各功能需要消耗的积分数量，设为 0 表示该功能免费使用" />
        </div>
        <el-table :data="costDefs" stripe>
          <el-table-column prop="label" label="功能名称" width="180" />
          <el-table-column prop="desc" label="说明" min-width="220" show-overflow-tooltip />
          <el-table-column label="消耗积分" width="180">
            <template #default="{ row }">
              <el-input-number
                v-model="costs[row.key]"
                :min="0"
                :max="99999"
                controls-position="right"
                size="small"
              />
            </template>
          </el-table-column>
        </el-table>
      </el-tab-pane>
    </el-tabs>
  </div>
</template>

<style scoped>
.points-rules {
  padding: 24px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.rule-tip {
  margin-bottom: 16px;
}
</style>