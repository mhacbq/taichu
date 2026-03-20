<template>
  <div class="content-records">
    <el-tabs v-model="activeTab" @tab-change="handleTabChange">
      <!-- 八字排盘记录 -->
      <el-tab-pane label="🔮 八字排盘" name="bazi">
        <div class="tab-toolbar">
          <el-input v-model="baziQuery.keyword" placeholder="搜索用户名/UID" clearable style="width:200px" @change="loadBazi" />
          <el-date-picker v-model="baziQuery.dateRange" type="daterange" range-separator="至"
            start-placeholder="开始日期" end-placeholder="结束日期" style="width:280px" @change="loadBazi" />
          <el-button :icon="Search" @click="loadBazi">搜索</el-button>
          <el-button :icon="Refresh" @click="resetBazi">重置</el-button>
        </div>
        <el-table :data="baziList" v-loading="baziLoading" stripe border style="width:100%">
          <el-table-column prop="id" label="ID" width="80" />
          <el-table-column prop="user_name" label="用户" width="120" />
          <el-table-column prop="name" label="姓名" width="100" />
          <el-table-column prop="birth_time" label="出生时间" width="160" />
          <el-table-column prop="gender" label="性别" width="70">
            <template #default="{ row }">{{ row.gender === 1 ? '男' : '女' }}</template>
          </el-table-column>
          <el-table-column prop="mode" label="解读深度" width="100">
            <template #default="{ row }">
              <el-tag size="small" :type="row.mode === 'premium' ? 'warning' : 'info'">
                {{ row.mode === 'premium' ? '深度' : '基础' }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="created_at" label="创建时间" width="160" />
          <el-table-column label="操作" width="120" fixed="right">
            <template #default="{ row }">
              <el-button size="small" @click="viewBaziDetail(row)">详情</el-button>
              <el-popconfirm title="确定删除?" @confirm="deleteBazi(row.id)">
                <template #reference>
                  <el-button size="small" type="danger">删除</el-button>
                </template>
              </el-popconfirm>
            </template>
          </el-table-column>
        </el-table>
        <el-pagination v-model:current-page="baziQuery.page" v-model:page-size="baziQuery.pageSize"
          :total="baziTotal" layout="total,sizes,prev,pager,next" @change="loadBazi"
          style="margin-top:16px;justify-content:flex-end" />
      </el-tab-pane>

      <!-- 塔罗占卜记录 -->
      <el-tab-pane label="🃏 塔罗占卜" name="tarot">
        <div class="tab-toolbar">
          <el-input v-model="tarotQuery.keyword" placeholder="搜索用户名/问题" clearable style="width:200px" @change="loadTarot" />
          <el-date-picker v-model="tarotQuery.dateRange" type="daterange" range-separator="至"
            start-placeholder="开始日期" end-placeholder="结束日期" style="width:280px" @change="loadTarot" />
          <el-button :icon="Search" @click="loadTarot">搜索</el-button>
          <el-button :icon="Refresh" @click="resetTarot">重置</el-button>
        </div>
        <el-table :data="tarotList" v-loading="tarotLoading" stripe border style="width:100%">
          <el-table-column prop="id" label="ID" width="80" />
          <el-table-column prop="user_name" label="用户" width="120" />
          <el-table-column prop="spread_name" label="牌阵" width="120" />
          <el-table-column prop="question" label="问题" min-width="200" show-overflow-tooltip />
          <el-table-column prop="card_count" label="牌数" width="70" />
          <el-table-column prop="is_shared" label="已分享" width="80">
            <template #default="{ row }">
              <el-tag size="small" :type="row.is_shared ? 'success' : 'info'">{{ row.is_shared ? '是' : '否' }}</el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="created_at" label="创建时间" width="160" />
          <el-table-column label="操作" width="120" fixed="right">
            <template #default="{ row }">
              <el-button size="small" @click="viewTarotDetail(row)">详情</el-button>
              <el-popconfirm title="确定删除?" @confirm="deleteTarot(row.id)">
                <template #reference>
                  <el-button size="small" type="danger">删除</el-button>
                </template>
              </el-popconfirm>
            </template>
          </el-table-column>
        </el-table>
        <el-pagination v-model:current-page="tarotQuery.page" v-model:page-size="tarotQuery.pageSize"
          :total="tarotTotal" layout="total,sizes,prev,pager,next" @change="loadTarot"
          style="margin-top:16px;justify-content:flex-end" />
      </el-tab-pane>

      <!-- 每日运势管理 -->
      <el-tab-pane label="☀️ 每日运势" name="daily">
        <div class="tab-toolbar">
          <el-date-picker v-model="dailyQuery.date" type="date" placeholder="选择日期" style="width:180px" @change="loadDaily" />
          <el-button type="primary" :icon="Plus" @click="openDailyDialog()">新增运势</el-button>
          <el-button :icon="Refresh" @click="resetDaily">重置</el-button>
        </div>
        <el-table :data="dailyList" v-loading="dailyLoading" stripe border style="width:100%">
          <el-table-column prop="id" label="ID" width="80" />
          <el-table-column prop="date" label="日期" width="120" />
          <el-table-column prop="title" label="标题" min-width="160" show-overflow-tooltip />
          <el-table-column prop="summary" label="摘要" min-width="200" show-overflow-tooltip />
          <el-table-column prop="lucky_color" label="幸运色" width="90">
            <template #default="{ row }">
              <span v-if="row.lucky_color" class="color-dot" :style="`background:${row.lucky_color}`"></span>
              {{ row.lucky_color }}
            </template>
          </el-table-column>
          <el-table-column prop="created_at" label="创建时间" width="160" />
          <el-table-column label="操作" width="140" fixed="right">
            <template #default="{ row }">
              <el-button size="small" type="primary" @click="openDailyDialog(row)">编辑</el-button>
              <el-popconfirm title="确定删除?" @confirm="deleteDaily(row.id)">
                <template #reference>
                  <el-button size="small" type="danger">删除</el-button>
                </template>
              </el-popconfirm>
            </template>
          </el-table-column>
        </el-table>
        <el-pagination v-model:current-page="dailyQuery.page" v-model:page-size="dailyQuery.pageSize"
          :total="dailyTotal" layout="total,sizes,prev,pager,next" @change="loadDaily"
          style="margin-top:16px;justify-content:flex-end" />
      </el-tab-pane>
    </el-tabs>

    <!-- 八字详情弹窗 -->
    <el-dialog v-model="baziDetailVisible" title="八字排盘详情" width="700px" destroy-on-close>
      <div class="detail-content" v-if="baziDetail">
        <el-descriptions :column="2" border>
          <el-descriptions-item label="用户">{{ baziDetail.user_name }}</el-descriptions-item>
          <el-descriptions-item label="姓名">{{ baziDetail.name }}</el-descriptions-item>
          <el-descriptions-item label="出生时间">{{ baziDetail.birth_time }}</el-descriptions-item>
          <el-descriptions-item label="性别">{{ baziDetail.gender === 1 ? '男' : '女' }}</el-descriptions-item>
          <el-descriptions-item label="解读深度">{{ baziDetail.mode === 'premium' ? '深度解读' : '基础解读' }}</el-descriptions-item>
          <el-descriptions-item label="精度">{{ baziDetail.precision }}</el-descriptions-item>
        </el-descriptions>
        <div class="detail-section" v-if="baziDetail.result">
          <h4>排盘结果</h4>
          <pre class="json-preview">{{ JSON.stringify(baziDetail.result, null, 2) }}</pre>
        </div>
      </div>
    </el-dialog>

    <!-- 塔罗详情弹窗 -->
    <el-dialog v-model="tarotDetailVisible" title="塔罗占卜详情" width="700px" destroy-on-close>
      <div class="detail-content" v-if="tarotDetail">
        <el-descriptions :column="2" border>
          <el-descriptions-item label="用户">{{ tarotDetail.user_name }}</el-descriptions-item>
          <el-descriptions-item label="牌阵">{{ tarotDetail.spread_name }}</el-descriptions-item>
          <el-descriptions-item label="问题" :span="2">{{ tarotDetail.question }}</el-descriptions-item>
          <el-descriptions-item label="是否分享">{{ tarotDetail.is_shared ? '已分享' : '未分享' }}</el-descriptions-item>
          <el-descriptions-item label="分享码">{{ tarotDetail.share_code || '-' }}</el-descriptions-item>
        </el-descriptions>
        <div class="detail-section" v-if="tarotDetail.cards">
          <h4>抽到的牌</h4>
          <el-tag v-for="c in tarotDetail.cards" :key="c.id" style="margin:4px">
            {{ c.name }} ({{ c.reversed ? '逆位' : '正位' }})
          </el-tag>
        </div>
      </div>
    </el-dialog>

    <!-- 每日运势编辑弹窗 -->
    <el-dialog v-model="dailyDialogVisible" :title="dailyForm.id ? '编辑运势' : '新增运势'" width="600px" destroy-on-close>
      <el-form :model="dailyForm" :rules="dailyRules" ref="dailyFormRef" label-width="90px">
        <el-form-item label="日期" prop="date">
          <el-date-picker v-model="dailyForm.date" type="date" value-format="YYYY-MM-DD" style="width:100%" />
        </el-form-item>
        <el-form-item label="标题" prop="title">
          <el-input v-model="dailyForm.title" placeholder="如：今日运势总览" />
        </el-form-item>
        <el-form-item label="摘要" prop="summary">
          <el-input v-model="dailyForm.summary" type="textarea" :rows="3" placeholder="运势摘要" />
        </el-form-item>
        <el-form-item label="幸运色">
          <el-input v-model="dailyForm.lucky_color" placeholder="#FF5722" style="width:120px;margin-right:8px" />
          <span class="color-dot" :style="`background:${dailyForm.lucky_color||'#ddd'}`"></span>
        </el-form-item>
        <el-form-item label="宜">
          <el-input v-model="dailyForm.yi" placeholder="逗号分隔，如：出行,签约" />
        </el-form-item>
        <el-form-item label="忌">
          <el-input v-model="dailyForm.ji" placeholder="逗号分隔，如：搬家,开业" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dailyDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="dailySaving" @click="saveDaily">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { Search, Refresh, Plus } from '@element-plus/icons-vue'
import {
  getBaziRecords, getBaziDetail, deleteBaziRecord,
  getTarotRecords, getTarotDetail, deleteTarotRecord,
  getDailyFortuneList, createDailyFortune, updateDailyFortune, deleteDailyFortune
} from '@/api/admin'

const activeTab = ref('bazi')

// ── 八字 ────────────────────────────────────────────
const baziList = ref([])
const baziTotal = ref(0)
const baziLoading = ref(false)
const baziQuery = reactive({ page: 1, pageSize: 15, keyword: '', dateRange: null })
const baziDetailVisible = ref(false)
const baziDetail = ref(null)

const loadBazi = async () => {
  baziLoading.value = true
  try {
    const params = { page: baziQuery.page, pageSize: baziQuery.pageSize, keyword: baziQuery.keyword }
    if (baziQuery.dateRange?.length === 2) {
      params.start = baziQuery.dateRange[0]
      params.end = baziQuery.dateRange[1]
    }
    const res = await getBaziRecords(params)
    baziList.value = res.data?.list || res.data || []
    baziTotal.value = res.data?.total || 0
  } catch (e) { ElMessage.error('加载失败') } finally { baziLoading.value = false }
}

const resetBazi = () => { Object.assign(baziQuery, { page: 1, keyword: '', dateRange: null }); loadBazi() }

const viewBaziDetail = async (row) => {
  try {
    const res = await getBaziDetail(row.id)
    baziDetail.value = res.data
    baziDetailVisible.value = true
  } catch { ElMessage.error('加载详情失败') }
}

const deleteBazi = async (id) => {
  try {
    await deleteBaziRecord(id)
    ElMessage.success('删除成功')
    loadBazi()
  } catch { ElMessage.error('删除失败') }
}

// ── 塔罗 ────────────────────────────────────────────
const tarotList = ref([])
const tarotTotal = ref(0)
const tarotLoading = ref(false)
const tarotQuery = reactive({ page: 1, pageSize: 15, keyword: '', dateRange: null })
const tarotDetailVisible = ref(false)
const tarotDetail = ref(null)

const loadTarot = async () => {
  tarotLoading.value = true
  try {
    const params = { page: tarotQuery.page, pageSize: tarotQuery.pageSize, keyword: tarotQuery.keyword }
    if (tarotQuery.dateRange?.length === 2) {
      params.start = tarotQuery.dateRange[0]
      params.end = tarotQuery.dateRange[1]
    }
    const res = await getTarotRecords(params)
    tarotList.value = res.data?.list || res.data || []
    tarotTotal.value = res.data?.total || 0
  } catch { ElMessage.error('加载失败') } finally { tarotLoading.value = false }
}

const resetTarot = () => { Object.assign(tarotQuery, { page: 1, keyword: '', dateRange: null }); loadTarot() }

const viewTarotDetail = async (row) => {
  try {
    const res = await getTarotDetail(row.id)
    tarotDetail.value = res.data
    tarotDetailVisible.value = true
  } catch { ElMessage.error('加载详情失败') }
}

const deleteTarot = async (id) => {
  try {
    await deleteTarotRecord(id)
    ElMessage.success('删除成功')
    loadTarot()
  } catch { ElMessage.error('删除失败') }
}

// ── 每日运势 ─────────────────────────────────────────
const dailyList = ref([])
const dailyTotal = ref(0)
const dailyLoading = ref(false)
const dailyQuery = reactive({ page: 1, pageSize: 15, date: null })
const dailyDialogVisible = ref(false)
const dailySaving = ref(false)
const dailyFormRef = ref(null)
const dailyForm = reactive({ id: null, date: '', title: '', summary: '', lucky_color: '', yi: '', ji: '' })
const dailyRules = {
  date: [{ required: true, message: '请选择日期' }],
  title: [{ required: true, message: '请输入标题' }],
}

const loadDaily = async () => {
  dailyLoading.value = true
  try {
    const params = { page: dailyQuery.page, pageSize: dailyQuery.pageSize }
    if (dailyQuery.date) params.date = dailyQuery.date
    const res = await getDailyFortuneList(params)
    dailyList.value = res.data?.list || res.data || []
    dailyTotal.value = res.data?.total || 0
  } catch { ElMessage.error('加载失败') } finally { dailyLoading.value = false }
}

const resetDaily = () => { Object.assign(dailyQuery, { page: 1, date: null }); loadDaily() }

const openDailyDialog = (row = null) => {
  if (row) {
    Object.assign(dailyForm, { id: row.id, date: row.date, title: row.title, summary: row.summary, lucky_color: row.lucky_color, yi: Array.isArray(row.yi) ? row.yi.join(',') : row.yi, ji: Array.isArray(row.ji) ? row.ji.join(',') : row.ji })
  } else {
    Object.assign(dailyForm, { id: null, date: '', title: '', summary: '', lucky_color: '', yi: '', ji: '' })
  }
  dailyDialogVisible.value = true
}

const saveDaily = async () => {
  await dailyFormRef.value.validate()
  dailySaving.value = true
  try {
    const payload = { ...dailyForm, yi: dailyForm.yi ? dailyForm.yi.split(',').map(s => s.trim()) : [], ji: dailyForm.ji ? dailyForm.ji.split(',').map(s => s.trim()) : [] }
    if (dailyForm.id) {
      await updateDailyFortune(dailyForm.id, payload)
    } else {
      await createDailyFortune(payload)
    }
    ElMessage.success('保存成功')
    dailyDialogVisible.value = false
    loadDaily()
  } catch { ElMessage.error('保存失败') } finally { dailySaving.value = false }
}

const deleteDaily = async (id) => {
  try {
    await deleteDailyFortune(id)
    ElMessage.success('删除成功')
    loadDaily()
  } catch { ElMessage.error('删除失败') }
}

const handleTabChange = (tab) => {
  if (tab === 'bazi' && !baziList.value.length) loadBazi()
  if (tab === 'tarot' && !tarotList.value.length) loadTarot()
  if (tab === 'daily' && !dailyList.value.length) loadDaily()
}

onMounted(() => { loadBazi() })
</script>

<style scoped>
.content-records { padding: 0; }
.tab-toolbar { display: flex; gap: 10px; align-items: center; margin-bottom: 16px; flex-wrap: wrap; }
.detail-content { padding: 4px; }
.detail-section { margin-top: 16px; }
.detail-section h4 { margin-bottom: 8px; color: #606266; }
.json-preview { background: #f5f7fa; border-radius: 6px; padding: 12px; font-size: 12px; max-height: 300px; overflow: auto; white-space: pre-wrap; }
.color-dot { display: inline-block; width: 16px; height: 16px; border-radius: 50%; border: 1px solid #ddd; vertical-align: middle; margin-right: 4px; }
</style>
