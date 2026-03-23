<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getAlmanacList, getAlmanacDetail, saveAlmanac, deleteAlmanac, generateAlmanacMonth, getAlmanacMonths } from '../../api/admin'

const loading = ref(false)
const generating = ref(false)
const list = ref([])
const total = ref(0)
const currentPage = ref(1)
const pageSize = ref(31)
const months = ref([])

// 年月选择
const currentYear = ref(new Date().getFullYear())
const currentMonth = ref(new Date().getMonth() + 1)

// 详情弹窗
const detailVisible = ref(false)
const detailData = ref(null)
const detailLoading = ref(false)

// 编辑弹窗
const editVisible = ref(false)
const editForm = ref({
  id: 0,
  solar_date: '',
  lunar_date: '',
  ganzhi: '',
  zhiri: '',
  yi: '',
  ji: '',
  jishen: '',
  xiongsha: '',
  sha: ''
})

// 年份选项（2020-2035，与LunarService数据范围一致）
const yearOptions = computed(() => {
  const options = []
  for (let y = 2020; y <= 2035; y++) {
    options.push(y)
  }
  return options
})

// 月份选项
const monthOptions = [
  { value: 1, label: '一月' }, { value: 2, label: '二月' }, { value: 3, label: '三月' },
  { value: 4, label: '四月' }, { value: 5, label: '五月' }, { value: 6, label: '六月' },
  { value: 7, label: '七月' }, { value: 8, label: '八月' }, { value: 9, label: '九月' },
  { value: 10, label: '十月' }, { value: 11, label: '十一月' }, { value: 12, label: '十二月' }
]

// 当前选中月份是否已有数据
const currentMonthInfo = computed(() => {
  const key = `${currentYear.value}-${String(currentMonth.value).padStart(2, '0')}`
  return months.value.find(m => m.month === key)
})

// 十二建除对应标签颜色
const zhiriTagType = (zhiri) => {
  const jiMap = { '建': 'primary', '除': 'success', '满': 'warning', '成': 'success', '开': 'success', '定': 'primary', '收': 'primary' }
  const xiongMap = { '破': 'danger', '危': 'danger', '闭': 'info', '执': 'info', '平': '' }
  return jiMap[zhiri] || xiongMap[zhiri] || ''
}

// 格式化数组字段为逗号分隔文本
const formatArrayField = (val) => {
  if (!val) return '-'
  if (Array.isArray(val)) return val.join('、') || '-'
  if (typeof val === 'string') {
    try {
      const parsed = JSON.parse(val)
      if (Array.isArray(parsed)) return parsed.join('、') || '-'
    } catch (e) { /* 非JSON */ }
    return val || '-'
  }
  return String(val)
}

// 加载黄历列表
const loadList = async () => {
  loading.value = true
  try {
    const res = await getAlmanacList({
      year: currentYear.value,
      month: currentMonth.value,
      page: currentPage.value,
      pageSize: pageSize.value
    })
    if (res.code === 200) {
      list.value = res.data.list || []
      total.value = res.data.total || 0
    } else {
      ElMessage.error(res.message || '加载失败')
    }
  } catch (e) {
    console.error('加载黄历列表失败:', e)
    ElMessage.error('加载失败')
  } finally {
    loading.value = false
  }
}

// 加载已生成月份
const loadMonths = async () => {
  try {
    const res = await getAlmanacMonths()
    if (res.code === 200) {
      months.value = res.data || []
    }
  } catch (e) {
    console.error('加载月份列表失败:', e)
  }
}

// 一键生成当月黄历
const handleGenerate = async () => {
  try {
    await ElMessageBox.confirm(
      `确定要生成 ${currentYear.value}年${currentMonth.value}月 的黄历数据吗？已有的日期不会被覆盖。`,
      '生成黄历',
      { type: 'info', confirmButtonText: '确定生成', cancelButtonText: '取消' }
    )
    generating.value = true
    const res = await generateAlmanacMonth(currentYear.value, currentMonth.value)
    if (res.code === 200) {
      const count = res.data?.generated ?? 0
      ElMessage.success(count > 0 ? `成功生成 ${count} 条黄历数据` : '当月数据已存在，无需重复生成')
      loadList()
      loadMonths()
    } else {
      ElMessage.error(res.message || '生成失败')
    }
  } catch (e) {
    if (e !== 'cancel') ElMessage.error('生成失败')
  } finally {
    generating.value = false
  }
}

// 查看详情
const handleView = async (row) => {
  detailVisible.value = true
  detailLoading.value = true
  try {
    const dateStr = row.solar_date || row.date
    const res = await getAlmanacDetail(dateStr)
    if (res.code === 200) {
      detailData.value = res.data
    } else {
      ElMessage.error(res.message || '加载详情失败')
    }
  } catch (e) {
    ElMessage.error('加载详情失败')
  } finally {
    detailLoading.value = false
  }
}

// 打开编辑弹窗
const handleEdit = (row) => {
  editForm.value = {
    id: row.id || 0,
    solar_date: row.solar_date || row.date || '',
    lunar_date: row.lunar_date || '',
    ganzhi: row.ganzhi || '',
    zhiri: row.zhiri || '',
    yi: formatArrayField(row.yi),
    ji: formatArrayField(row.ji),
    jishen: formatArrayField(row.jishen),
    xiongsha: formatArrayField(row.xiongsha),
    sha: row.sha || ''
  }
  editVisible.value = true
}

// 保存编辑
const handleSave = async () => {
  const form = editForm.value
  if (!form.solar_date) {
    ElMessage.warning('日期不能为空')
    return
  }
  try {
    const payload = {
      id: form.id,
      solar_date: form.solar_date,
      lunar_date: form.lunar_date,
      ganzhi: form.ganzhi,
      zhiri: form.zhiri,
      yi: form.yi.split('、').filter(Boolean),
      ji: form.ji.split('、').filter(Boolean),
      jishen: form.jishen.split('、').filter(Boolean),
      xiongsha: form.xiongsha.split('、').filter(Boolean),
      sha: form.sha
    }
    const res = await saveAlmanac(payload)
    if (res.code === 200) {
      ElMessage.success('保存成功')
      editVisible.value = false
      loadList()
    } else {
      ElMessage.error(res.message || '保存失败')
    }
  } catch (e) {
    ElMessage.error('保存失败')
  }
}

// 删除
const handleDelete = async (row) => {
  const dateStr = row.solar_date || row.date
  try {
    await ElMessageBox.confirm(`确定要删除 ${dateStr} 的黄历数据吗？`, '确认删除', { type: 'warning' })
    const res = await deleteAlmanac(dateStr)
    if (res.code === 200) {
      ElMessage.success('删除成功')
      loadList()
      loadMonths()
    } else {
      ElMessage.error(res.message || '删除失败')
    }
  } catch (e) {
    if (e !== 'cancel') ElMessage.error('删除失败')
  }
}

// 切换年月时自动刷新
watch([currentYear, currentMonth], () => {
  currentPage.value = 1
  loadList()
})

const handlePageChange = (page) => {
  currentPage.value = page
  loadList()
}

const handleSizeChange = (size) => {
  pageSize.value = size
  currentPage.value = 1
  loadList()
}

onMounted(() => {
  loadList()
  loadMonths()
})
</script>

<template>
  <div class="admin-manage-page">
    <div class="page-header">
      <h2>黄历管理</h2>
      <p class="page-desc">管理每日黄历数据，支持按月自动生成与手动编辑</p>
    </div>

    <!-- 已生成月份概览 -->
    <div class="months-overview" v-if="months.length">
      <el-card shadow="hover">
        <template #header>
          <span class="card-title">已生成月份一览</span>
        </template>
        <div class="month-tags">
          <el-tag
            v-for="m in months"
            :key="m.month"
            :type="m.month === `${currentYear}-${String(currentMonth).padStart(2, '0')}` ? '' : 'info'"
            class="month-tag"
            @click="() => {
              const [y, mo] = m.month.split('-')
              currentYear = parseInt(y)
              currentMonth = parseInt(mo)
            }"
          >
            {{ m.month }} ({{ m.day_count }}天)
          </el-tag>
        </div>
      </el-card>
    </div>

    <!-- 操作栏 -->
    <div class="action-bar">
      <div class="left-actions">
        <el-select v-model="currentYear" placeholder="选择年份" style="width: 120px">
          <el-option v-for="y in yearOptions" :key="y" :label="y + '年'" :value="y" />
        </el-select>
        <el-select v-model="currentMonth" placeholder="选择月份" style="width: 120px">
          <el-option v-for="m in monthOptions" :key="m.value" :label="m.label" :value="m.value" />
        </el-select>
        <el-tag v-if="currentMonthInfo" type="success" class="month-status">
          已生成 {{ currentMonthInfo.day_count }} 天
        </el-tag>
        <el-tag v-else type="warning" class="month-status">
          未生成
        </el-tag>
      </div>
      <div class="right-actions">
        <el-button type="primary" :loading="generating" @click="handleGenerate">
          {{ generating ? '生成中...' : '一键生成当月黄历' }}
        </el-button>
      </div>
    </div>

    <!-- 数据表格 -->
    <div class="table-container">
      <el-table v-loading="loading" :data="list" stripe border>
        <el-table-column prop="solar_date" label="阳历日期" width="120" fixed="left">
          <template #default="{ row }">
            <span class="date-text">{{ row.solar_date || row.date }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="lunar_date" label="农历" min-width="160">
          <template #default="{ row }">
            <span class="lunar-text">{{ row.lunar_date || '-' }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="ganzhi" label="干支" width="180">
          <template #default="{ row }">
            <span class="ganzhi-text">{{ row.ganzhi || '-' }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="zhiri" label="建除" width="80" align="center">
          <template #default="{ row }">
            <el-tag v-if="row.zhiri" :type="zhiriTagType(row.zhiri)" size="small">{{ row.zhiri }}</el-tag>
            <span v-else>-</span>
          </template>
        </el-table-column>
        <el-table-column label="宜" min-width="180">
          <template #default="{ row }">
            <span class="yi-text">{{ formatArrayField(row.yi) }}</span>
          </template>
        </el-table-column>
        <el-table-column label="忌" min-width="160">
          <template #default="{ row }">
            <span class="ji-text">{{ formatArrayField(row.ji) }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="sha" label="煞方" width="70" align="center" />
        <el-table-column label="操作" width="180" fixed="right" align="center">
          <template #default="{ row }">
            <el-button size="small" @click="handleView(row)">详情</el-button>
            <el-button size="small" type="primary" @click="handleEdit(row)">编辑</el-button>
            <el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>

      <el-pagination
        v-if="total > pageSize"
        v-model:current-page="currentPage"
        v-model:page-size="pageSize"
        :total="total"
        :page-sizes="[20, 31, 50]"
        layout="total, sizes, prev, pager, next"
        @current-change="handlePageChange"
        @size-change="handleSizeChange"
        style="margin-top: 20px; justify-content: flex-end;"
      />
    </div>

    <!-- 详情弹窗 -->
    <el-dialog v-model="detailVisible" title="黄历详情" width="750px" destroy-on-close>
      <div v-loading="detailLoading">
        <template v-if="detailData">
          <el-descriptions :column="2" border>
            <el-descriptions-item label="阳历日期">{{ detailData.solar_date || detailData.date }}</el-descriptions-item>
            <el-descriptions-item label="农历">{{ detailData.lunar_date || '-' }}</el-descriptions-item>
            <el-descriptions-item label="干支" :span="2">{{ detailData.ganzhi || '-' }}</el-descriptions-item>
            <el-descriptions-item label="建除">
              <el-tag v-if="detailData.zhiri" :type="zhiriTagType(detailData.zhiri)">{{ detailData.zhiri }}</el-tag>
              <span v-else>-</span>
            </el-descriptions-item>
            <el-descriptions-item label="煞方">{{ detailData.sha || '-' }}</el-descriptions-item>
            <el-descriptions-item label="宜" :span="2">
              <div class="detail-tags">
                <el-tag v-for="item in (Array.isArray(detailData.yi) ? detailData.yi : [])" :key="item" type="success" size="small" class="detail-tag">{{ item }}</el-tag>
                <span v-if="!Array.isArray(detailData.yi) || !detailData.yi.length">{{ formatArrayField(detailData.yi) }}</span>
              </div>
            </el-descriptions-item>
            <el-descriptions-item label="忌" :span="2">
              <div class="detail-tags">
                <el-tag v-for="item in (Array.isArray(detailData.ji) ? detailData.ji : [])" :key="item" type="danger" size="small" class="detail-tag">{{ item }}</el-tag>
                <span v-if="!Array.isArray(detailData.ji) || !detailData.ji.length">{{ formatArrayField(detailData.ji) }}</span>
              </div>
            </el-descriptions-item>
            <el-descriptions-item label="吉神" :span="2">
              <div class="detail-tags">
                <el-tag v-for="item in (Array.isArray(detailData.jishen) ? detailData.jishen : [])" :key="item" type="warning" size="small" class="detail-tag">{{ item }}</el-tag>
                <span v-if="!Array.isArray(detailData.jishen) || !detailData.jishen.length">{{ formatArrayField(detailData.jishen) }}</span>
              </div>
            </el-descriptions-item>
            <el-descriptions-item label="凶煞" :span="2">
              <div class="detail-tags">
                <el-tag v-for="item in (Array.isArray(detailData.xiongsha) ? detailData.xiongsha : [])" :key="item" type="info" size="small" class="detail-tag">{{ item }}</el-tag>
                <span v-if="!Array.isArray(detailData.xiongsha) || !detailData.xiongsha.length">{{ formatArrayField(detailData.xiongsha) }}</span>
              </div>
            </el-descriptions-item>
          </el-descriptions>

          <!-- 时辰数据 -->
          <div v-if="Array.isArray(detailData.shichen) && detailData.shichen.length" class="shichen-section">
            <h4>时辰宜忌</h4>
            <el-table :data="detailData.shichen" border size="small">
              <el-table-column prop="name" label="时辰" width="70" align="center">
                <template #default="{ row }">
                  <span class="shichen-name">{{ row.name }}时</span>
                </template>
              </el-table-column>
              <el-table-column prop="time" label="时间段" width="110" align="center" />
              <el-table-column prop="god" label="值神" width="80" align="center">
                <template #default="{ row }">
                  <el-tag :type="row.type === 'ji' ? 'success' : 'danger'" size="small">{{ row.god }}</el-tag>
                </template>
              </el-table-column>
              <el-table-column label="吉凶" width="70" align="center">
                <template #default="{ row }">
                  <span :class="row.type === 'ji' ? 'ji-color' : 'xiong-color'">{{ row.type === 'ji' ? '吉' : '凶' }}</span>
                </template>
              </el-table-column>
              <el-table-column prop="yiji" label="宜忌说明" min-width="200" />
            </el-table>
          </div>
        </template>
      </div>
    </el-dialog>

    <!-- 编辑弹窗 -->
    <el-dialog v-model="editVisible" title="编辑黄历" width="600px" destroy-on-close>
      <el-form :model="editForm" label-width="80px">
        <el-form-item label="阳历日期">
          <el-input v-model="editForm.solar_date" disabled />
        </el-form-item>
        <el-form-item label="农历">
          <el-input v-model="editForm.lunar_date" placeholder="如：甲辰年 二月初一" />
        </el-form-item>
        <el-form-item label="干支">
          <el-input v-model="editForm.ganzhi" placeholder="如：甲辰年 丙寅月 壬午日" />
        </el-form-item>
        <el-form-item label="建除">
          <el-select v-model="editForm.zhiri" placeholder="选择建除" style="width: 100%">
            <el-option v-for="z in ['建','除','满','平','定','执','破','危','成','收','开','闭']" :key="z" :label="z" :value="z" />
          </el-select>
        </el-form-item>
        <el-form-item label="煞方">
          <el-select v-model="editForm.sha" placeholder="选择煞方" style="width: 100%">
            <el-option v-for="s in ['东','南','西','北']" :key="s" :label="'煞' + s" :value="s" />
          </el-select>
        </el-form-item>
        <el-form-item label="宜">
          <el-input v-model="editForm.yi" placeholder="用顿号分隔，如：祈福、祭祀、出行" />
        </el-form-item>
        <el-form-item label="忌">
          <el-input v-model="editForm.ji" placeholder="用顿号分隔，如：动土、安葬" />
        </el-form-item>
        <el-form-item label="吉神">
          <el-input v-model="editForm.jishen" placeholder="用顿号分隔，如：天恩、岁德" />
        </el-form-item>
        <el-form-item label="凶煞">
          <el-input v-model="editForm.xiongsha" placeholder="用顿号分隔，如：土府、小时" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="editVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSave">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<style scoped>
.admin-manage-page { padding: 24px; }
.page-header { margin-bottom: 20px; }
.page-header h2 { font-size: 20px; color: #333; margin: 0 0 4px 0; }
.page-desc { font-size: 13px; color: #999; margin: 0; }

.months-overview { margin-bottom: 20px; }
.card-title { font-weight: 600; font-size: 14px; }
.month-tags { display: flex; flex-wrap: wrap; gap: 8px; }
.month-tag { cursor: pointer; transition: transform 0.2s; }
.month-tag:hover { transform: scale(1.05); }

.action-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #fff;
  padding: 16px 20px;
  border-radius: 8px;
  margin-bottom: 20px;
}
.left-actions { display: flex; align-items: center; gap: 12px; }
.right-actions { display: flex; align-items: center; gap: 12px; }
.month-status { margin-left: 4px; }

.table-container { background: #fff; padding: 20px; border-radius: 8px; }

.date-text { font-weight: 600; color: #333; }
.lunar-text { color: #B8860B; }
.ganzhi-text { font-size: 13px; color: #666; }
.yi-text { color: #67c23a; font-size: 13px; }
.ji-text { color: #f56c6c; font-size: 13px; }

.detail-tags { display: flex; flex-wrap: wrap; gap: 6px; }
.detail-tag { margin: 0; }

.shichen-section { margin-top: 24px; }
.shichen-section h4 { margin-bottom: 12px; color: #333; font-size: 15px; }
.shichen-name { font-weight: 600; }
.ji-color { color: #67c23a; font-weight: 600; }
.xiong-color { color: #f56c6c; font-weight: 600; }
</style>