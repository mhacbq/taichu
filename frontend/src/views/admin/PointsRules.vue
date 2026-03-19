<template>
  <div class="points-rules">
    <el-row :gutter="16">
      <!-- 积分规则列表 -->
      <el-col :span="16">
        <el-card shadow="never">
          <template #header>
            <div style="display:flex;justify-content:space-between;align-items:center">
              <span>积分获取/消费规则</span>
              <el-button type="primary" :icon="Plus" size="small" @click="openDialog()">新增规则</el-button>
            </div>
          </template>
          <el-table :data="ruleList" v-loading="loading" stripe border>
            <el-table-column prop="id" label="ID" width="60" />
            <el-table-column prop="code" label="规则代码" width="160">
              <template #default="{ row }">
                <code class="rule-code">{{ row.code }}</code>
              </template>
            </el-table-column>
            <el-table-column prop="name" label="规则名称" min-width="160" />
            <el-table-column prop="type" label="类型" width="90">
              <template #default="{ row }">
                <el-tag size="small" :type="row.type === 'earn' ? 'success' : 'danger'">
                  {{ row.type === 'earn' ? '+ 获取' : '- 消费' }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column prop="points" label="积分数量" width="100">
              <template #default="{ row }">
                <span :class="row.type === 'earn' ? 'text-success' : 'text-danger'">
                  {{ row.type === 'earn' ? '+' : '-' }}{{ row.points }}
                </span>
              </template>
            </el-table-column>
            <el-table-column prop="daily_limit" label="每日上限" width="100">
              <template #default="{ row }">{{ row.daily_limit || '不限' }}</template>
            </el-table-column>
            <el-table-column prop="is_active" label="状态" width="80">
              <template #default="{ row }">
                <el-switch v-model="row.is_active" :active-value="1" :inactive-value="0"
                  @change="toggleRule(row)" />
              </template>
            </el-table-column>
            <el-table-column label="操作" width="120" fixed="right">
              <template #default="{ row }">
                <el-button size="small" type="primary" @click="openDialog(row)">编辑</el-button>
                <el-popconfirm title="确定删除此规则?" @confirm="deleteRule(row.id)">
                  <template #reference>
                    <el-button size="small" type="danger">删除</el-button>
                  </template>
                </el-popconfirm>
              </template>
            </el-table-column>
          </el-table>
        </el-card>
      </el-col>

      <!-- 右侧：积分统计 -->
      <el-col :span="8">
        <el-card shadow="never" header="积分统计概览" v-loading="statsLoading">
          <div class="stat-grid">
            <div class="stat-block">
              <div class="stat-val">{{ stats.total_issued || 0 }}</div>
              <div class="stat-lbl">累计发放</div>
            </div>
            <div class="stat-block">
              <div class="stat-val text-danger">{{ stats.total_consumed || 0 }}</div>
              <div class="stat-lbl">累计消耗</div>
            </div>
            <div class="stat-block">
              <div class="stat-val text-success">{{ stats.total_balance || 0 }}</div>
              <div class="stat-lbl">全站余额</div>
            </div>
            <div class="stat-block">
              <div class="stat-val">{{ stats.today_issued || 0 }}</div>
              <div class="stat-lbl">今日发放</div>
            </div>
          </div>

          <el-divider>今日来源分布</el-divider>
          <div v-if="stats.today_breakdown?.length">
            <div v-for="item in stats.today_breakdown" :key="item.rule_code" class="breakdown-item">
              <span class="bd-name">{{ item.rule_name }}</span>
              <div class="bd-bar-wrap">
                <div class="bd-bar" :style="`width:${item.percent}%`"></div>
              </div>
              <span class="bd-num">+{{ item.points }}</span>
            </div>
          </div>
          <el-empty v-else description="暂无数据" :image-size="50" />
        </el-card>

        <!-- 手动调整积分 -->
        <el-card shadow="never" header="手动调整积分" style="margin-top:16px">
          <el-form :model="adjustForm" label-width="80px">
            <el-form-item label="用户ID">
              <el-input v-model="adjustForm.user_id" placeholder="输入用户ID" />
            </el-form-item>
            <el-form-item label="调整量">
              <el-input-number v-model="adjustForm.points" style="width:100%" />
              <div class="form-tip">正数为增加，负数为扣除</div>
            </el-form-item>
            <el-form-item label="备注">
              <el-input v-model="adjustForm.remark" placeholder="调整原因" />
            </el-form-item>
            <el-form-item>
              <el-button type="primary" :loading="adjusting" @click="doAdjust" style="width:100%">确认调整</el-button>
            </el-form-item>
          </el-form>
        </el-card>
      </el-col>
    </el-row>

    <!-- 规则编辑弹窗 -->
    <el-dialog v-model="dialogVisible" :title="form.id ? '编辑规则' : '新增规则'" width="520px" destroy-on-close>
      <el-form :model="form" :rules="rules" ref="formRef" label-width="100px">
        <el-form-item label="规则代码" prop="code">
          <el-input v-model="form.code" placeholder="如：daily_login, bazi_query" :disabled="!!form.id" />
          <div class="form-tip">唯一标识，创建后不可修改</div>
        </el-form-item>
        <el-form-item label="规则名称" prop="name">
          <el-input v-model="form.name" placeholder="如：每日登录奖励" />
        </el-form-item>
        <el-form-item label="类型" prop="type">
          <el-radio-group v-model="form.type">
            <el-radio value="earn">获取积分</el-radio>
            <el-radio value="consume">消费积分</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="积分数量" prop="points">
          <el-input-number v-model="form.points" :min="1" style="width:100%" />
        </el-form-item>
        <el-form-item label="每日上限">
          <el-input-number v-model="form.daily_limit" :min="0" style="width:100%" placeholder="0表示不限制" />
        </el-form-item>
        <el-form-item label="总上限">
          <el-input-number v-model="form.total_limit" :min="0" style="width:100%" placeholder="0表示不限制" />
        </el-form-item>
        <el-form-item label="描述">
          <el-input v-model="form.description" type="textarea" :rows="2" />
        </el-form-item>
        <el-form-item label="启用">
          <el-switch v-model="form.is_active" :active-value="1" :inactive-value="0" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="saving" @click="saveRule">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { Plus } from '@element-plus/icons-vue'
import { getPointsRules, savePointsRule, deletePointsRule, getPointsStats, adjustUserPoints } from '@/api/admin'

const ruleList = ref([])
const loading = ref(false)
const stats = ref({})
const statsLoading = ref(false)
const dialogVisible = ref(false)
const saving = ref(false)
const formRef = ref(null)
const form = reactive({ id: null, code: '', name: '', type: 'earn', points: 10, daily_limit: 0, total_limit: 0, description: '', is_active: 1 })
const rules = {
  code: [{ required: true, message: '请输入规则代码' }],
  name: [{ required: true, message: '请输入规则名称' }],
  type: [{ required: true }],
  points: [{ required: true }],
}

// 积分手动调整
const adjustForm = reactive({ user_id: '', points: 0, remark: '' })
const adjusting = ref(false)

const loadRules = async () => {
  loading.value = true
  try {
    const res = await getPointsRules()
    ruleList.value = res.data || []
  } catch { ElMessage.error('加载规则失败') } finally { loading.value = false }
}

const loadStats = async () => {
  statsLoading.value = true
  try {
    const res = await getPointsStats()
    stats.value = res.data || {}
  } catch {} finally { statsLoading.value = false }
}

const openDialog = (row = null) => {
  if (row) Object.assign(form, { id: row.id, code: row.code, name: row.name, type: row.type, points: row.points, daily_limit: row.daily_limit || 0, total_limit: row.total_limit || 0, description: row.description || '', is_active: row.is_active })
  else Object.assign(form, { id: null, code: '', name: '', type: 'earn', points: 10, daily_limit: 0, total_limit: 0, description: '', is_active: 1 })
  dialogVisible.value = true
}

const saveRule = async () => {
  await formRef.value.validate()
  saving.value = true
  try {
    await savePointsRule({ ...form })
    ElMessage.success('保存成功')
    dialogVisible.value = false
    loadRules()
  } catch { ElMessage.error('保存失败') } finally { saving.value = false }
}

const deleteRule = async (id) => {
  try {
    await deletePointsRule(id)
    ElMessage.success('删除成功')
    loadRules()
  } catch { ElMessage.error('删除失败') }
}

const toggleRule = async (row) => {
  try {
    await savePointsRule({ id: row.id, is_active: row.is_active })
    ElMessage.success(row.is_active ? '已启用' : '已停用')
  } catch { ElMessage.error('操作失败') }
}

const doAdjust = async () => {
  if (!adjustForm.user_id) return ElMessage.warning('请输入用户ID')
  if (!adjustForm.points) return ElMessage.warning('请输入调整数量')
  adjusting.value = true
  try {
    await adjustUserPoints({ user_id: adjustForm.user_id, points: adjustForm.points, remark: adjustForm.remark })
    ElMessage.success('积分调整成功')
    Object.assign(adjustForm, { user_id: '', points: 0, remark: '' })
    loadStats()
  } catch { ElMessage.error('调整失败') } finally { adjusting.value = false }
}

onMounted(() => { loadRules(); loadStats() })
</script>

<style scoped>
.points-rules { padding: 0; }
.rule-code { background: #ecf5ff; color: #409eff; padding: 2px 6px; border-radius: 4px; font-size: 12px; font-family: monospace; }
.text-success { color: #67c23a; font-weight: 600; }
.text-danger { color: #f56c6c; font-weight: 600; }
.stat-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.stat-block { text-align: center; padding: 14px; background: #f5f7fa; border-radius: 8px; }
.stat-val { font-size: 24px; font-weight: 700; color: #303133; }
.stat-lbl { font-size: 12px; color: #909399; margin-top: 4px; }
.breakdown-item { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; }
.bd-name { width: 80px; font-size: 12px; color: #606266; flex-shrink: 0; }
.bd-bar-wrap { flex: 1; height: 8px; background: #f0f2f5; border-radius: 4px; overflow: hidden; }
.bd-bar { height: 100%; background: #409eff; border-radius: 4px; transition: width .3s; }
.bd-num { width: 50px; text-align: right; font-size: 12px; color: #67c23a; font-weight: 600; }
.form-tip { font-size: 12px; color: #c0c4cc; margin-top: 4px; }
</style>
