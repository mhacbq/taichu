<template>
  <div class="almanac-manage">
    <div class="page-header">
      <h2>黄历管理</h2>
      <div class="header-actions">
        <el-button type="success" @click="generateMonth">
          <el-icon><Calendar /></el-icon>
          生成月历
        </el-button>
        <el-button type="primary" @click="openDialog()">
          <el-icon><Plus /></el-icon>
          手动添加
        </el-button>
      </div>
    </div>

    <!-- 日期选择 -->
    <div class="date-selector">
      <el-date-picker
        v-model="selectedMonth"
        type="month"
        placeholder="选择月份"
        format="YYYY年MM月"
        value-format="YYYY-MM"
        @change="loadMonthData"
      />
      <el-button @click="loadToday">查看今日</el-button>
    </div>

    <!-- 月历视图 -->
    <div class="calendar-view" v-loading="loading">
      <div class="calendar-header">
        <div class="month-title">{{ currentMonthTitle }}</div>
        <div class="weekdays">
          <span v-for="day in weekdays" :key="day" class="weekday">{{ day }}</span>
        </div>
      </div>
      <div class="calendar-body">
        <div
          v-for="(date, index) in calendarDates"
          :key="index"
          class="calendar-cell"
          :class="{
            'other-month': !date.isCurrentMonth,
            'today': date.isToday,
            'selected': date.isSelected
          }"
          @click="selectDate(date)"
        >
          <div class="cell-date">
            <span class="solar">{{ date.day }}</span>
            <span class="lunar">{{ date.lunar }}</span>
          </div>
          <div class="cell-ganzhi">{{ date.ganzhi }}</div>
          <div class="cell-yiji">
            <el-tag v-if="date.yi && date.yi.length > 0" type="success" size="small">
              宜{{ date.yi.length }}项
            </el-tag>
            <el-tag v-if="date.ji && date.ji.length > 0" type="danger" size="small">
              忌{{ date.ji.length }}项
            </el-tag>
          </div>
          <div class="cell-shensha" v-if="date.hasShensha">
            <el-icon><StarFilled /></el-icon>
          </div>
        </div>
      </div>
    </div>

    <!-- 编辑弹窗 -->
    <el-dialog
      v-model="dialogVisible"
      title="编辑黄历"
      width="800px"
      class="almanac-dialog"
    >
      <el-form
        ref="formRef"
        :model="form"
        :rules="rules"
        label-width="100px"
      >
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="公历日期" prop="solarDate">
              <el-date-picker
                v-model="form.solarDate"
                type="date"
                placeholder="选择日期"
                style="width: 100%"
                disabled
              />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="农历日期" prop="lunarDate">
              <el-input v-model="form.lunarDate" disabled />
            </el-form-item>
          </el-col>
        </el-row>

        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="干支" prop="ganzhi">
              <el-input v-model="form.ganzhi" disabled />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="纳音">
              <el-input v-model="form.nayin" />
            </el-form-item>
          </el-col>
        </el-row>

        <el-divider>宜忌</el-divider>

        <el-form-item label="宜">
          <el-select-v2
            v-model="form.yi"
            :options="yijiOptions"
            placeholder="选择宜做的事"
            multiple
            clearable
            style="width: 100%"
            :props="{ multiple: true }"
          />
        </el-form-item>

        <el-form-item label="忌">
          <el-select-v2
            v-model="form.ji"
            :options="yijiOptions"
            placeholder="选择忌做的事"
            multiple
            clearable
            style="width: 100%"
            :props="{ multiple: true }"
          />
        </el-form-item>

        <el-divider>冲煞</el-divider>

        <el-row :gutter="20">
          <el-col :span="8">
            <el-form-item label="冲">
              <el-input v-model="form.chong" placeholder="如：龙" />
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="煞方">
              <el-select v-model="form.sha" placeholder="选择方位">
                <el-option label="东" value="东" />
                <el-option label="南" value="南" />
                <el-option label="西" value="西" />
                <el-option label="北" value="北" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="值日">
              <el-select v-model="form.zhiri" placeholder="选择值日">
                <el-option label="建" value="建" />
                <el-option label="除" value="除" />
                <el-option label="满" value="满" />
                <el-option label="平" value="平" />
                <el-option label="定" value="定" />
                <el-option label="执" value="执" />
                <el-option label="破" value="破" />
                <el-option label="危" value="危" />
                <el-option label="成" value="成" />
                <el-option label="收" value="收" />
                <el-option label="开" value="开" />
                <el-option label="闭" value="闭" />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>

        <el-divider>时辰吉凶</el-divider>

        <div class="shichen-section">
          <div v-for="(sc, index) in form.shichen" :key="index" class="shichen-item">
            <span class="shichen-name">{{ sc.name }} ({{ sc.time }})</span>
            <el-radio-group v-model="sc.type" size="small">
              <el-radio-button label="ji">吉</el-radio-button>
              <el-radio-button label="xiaoJi">小吉</el-radio-button>
              <el-radio-button label="ping">平</el-radio-button>
              <el-radio-button label="xiong">凶</el-radio-button>
            </el-radio-group>
            <el-input v-model="sc.yiji" placeholder="宜/忌" size="small" style="width: 150px" />
          </div>
        </div>

        <el-divider>神煞</el-divider>

        <el-form-item label="吉神">
          <el-select-v2
            v-model="form.jishen"
            :options="shenshaOptions"
            placeholder="选择吉神"
            multiple
            clearable
            style="width: 100%"
          />
        </el-form-item>

        <el-form-item label="凶煞">
          <el-select-v2
            v-model="form.xiongsha"
            :options="shenshaOptions"
            placeholder="选择凶煞"
            multiple
            clearable
            style="width: 100%"
          />
        </el-form-item>
      </el-form>

      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitForm" :loading="submitLoading">
          保存
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { Plus, Calendar, StarFilled } from '@element-plus/icons-vue'
import { getAlmanacList, saveAlmanac, generateAlmanacMonth } from '@/api/admin'

const loading = ref(false)
const submitLoading = ref(false)
const dialogVisible = ref(false)
const formRef = ref(null)

// 月份选择
const selectedMonth = ref('')
const currentMonthTitle = computed(() => {
  if (!selectedMonth.value) return ''
  const [year, month] = selectedMonth.value.split('-')
  return `${year}年${month}月`
})

// 星期
const weekdays = ['日', '一', '二', '三', '四', '五', '六']

// 宜忌选项
const yijiOptions = [
  { value: '嫁娶', label: '嫁娶' },
  { value: '纳采', label: '纳采' },
  { value: '订盟', label: '订盟' },
  { value: '祭祀', label: '祭祀' },
  { value: '祈福', label: '祈福' },
  { value: '求嗣', label: '求嗣' },
  { value: '开光', label: '开光' },
  { value: '出火', label: '出火' },
  { value: '出行', label: '出行' },
  { value: '拆卸', label: '拆卸' },
  { value: '修造', label: '修造' },
  { value: '动土', label: '动土' },
  { value: '入宅', label: '入宅' },
  { value: '移徙', label: '移徙' },
  { value: '安床', label: '安床' },
  { value: '栽种', label: '栽种' },
  { value: '纳畜', label: '纳畜' },
  { value: '安葬', label: '安葬' },
  { value: '入殓', label: '入殓' },
  { value: '破土', label: '破土' }
]

// 神煞选项（从后台获取）
const shenshaOptions = [
  { value: '天乙贵人', label: '天乙贵人' },
  { value: '文昌贵人', label: '文昌贵人' },
  { value: '天德贵人', label: '天德贵人' },
  { value: '月德贵人', label: '月德贵人' },
  { value: '福星贵人', label: '福星贵人' },
  { value: '桃花', label: '桃花' },
  { value: '羊刃', label: '羊刃' },
  { value: '劫煞', label: '劫煞' }
]

// 表单
const form = ref({
  solarDate: '',
  lunarDate: '',
  ganzhi: '',
  nayin: '',
  yi: [],
  ji: [],
  chong: '',
  sha: '',
  zhiri: '',
  shichen: [],
  jishen: [],
  xiongsha: []
})

const rules = {
  solarDate: [{ required: true, message: '请选择日期', trigger: 'change' }],
  yi: [{ required: true, message: '请输入宜事项', trigger: 'blur' }],
  ji: [{ required: true, message: '请输入忌事项', trigger: 'blur' }],
  ganzhi: [{ required: true, message: '请输入干支', trigger: 'blur' }],
  sha: [{ required: true, message: '请输入煞方位', trigger: 'blur' }],
  zhiri: [{ required: true, message: '请输入值日星宿', trigger: 'blur' }]
}

// 默认时辰数据
const defaultShichen = [
  { name: '子时', time: '23:00-01:00', type: 'xiong', yiji: '' },
  { name: '丑时', time: '01:00-03:00', type: 'xiong', yiji: '' },
  { name: '寅时', time: '03:00-05:00', type: 'ji', yiji: '' },
  { name: '卯时', time: '05:00-07:00', type: 'ji', yiji: '' },
  { name: '辰时', time: '07:00-09:00', type: 'xiaoJi', yiji: '' },
  { name: '巳时', time: '09:00-11:00', type: 'ji', yiji: '' },
  { name: '午时', time: '11:00-13:00', type: 'xiaoJi', yiji: '' },
  { name: '未时', time: '13:00-15:00', type: 'ji', yiji: '' },
  { name: '申时', time: '15:00-17:00', type: 'xiong', yiji: '' },
  { name: '酉时', time: '17:00-19:00', type: 'ji', yiji: '' },
  { name: '戌时', time: '19:00-21:00', type: 'ping', yiji: '' },
  { name: '亥时', time: '21:00-23:00', type: 'xiong', yiji: '' }
]

// 日历数据
const calendarDates = ref([])

// 生成日历数据
const generateCalendar = (year, month) => {
  const dates = []
  const firstDay = new Date(year, month - 1, 1)
  const lastDay = new Date(year, month, 0)
  const startDate = new Date(firstDay)
  startDate.setDate(startDate.getDate() - firstDay.getDay())

  const today = new Date()

  for (let i = 0; i < 42; i++) {
    const date = new Date(startDate)
    date.setDate(startDate.getDate() + i)

    const isCurrentMonth = date.getMonth() === month - 1
    const isToday = date.toDateString() === today.toDateString()

    dates.push({
      date: date,
      day: date.getDate(),
      isCurrentMonth,
      isToday,
      isSelected: false,
      lunar: '初一', // 实际应计算农历
      ganzhi: '甲子',
      yi: ['嫁娶', '出行'],
      ji: ['动土', '安葬'],
      hasShensha: true
    })
  }

  calendarDates.value = dates
}

const loadMonthData = async () => {
  if (!selectedMonth.value) return
  const [year, month] = selectedMonth.value.split('-')
  generateCalendar(parseInt(year), parseInt(month))
  // 从后台加载该月真实黄历数据
  try {
    const startDate = `${year}-${month}-01`
    const endDate = `${year}-${month}-31`
    const res = await getAlmanacList({ start_date: startDate, end_date: endDate, page_size: 35 })
    if (res.code === 200) {
      const almanacMap = {}
      ;(res.data?.list || []).forEach(item => {
        almanacMap[item.date] = item
      })
      // 将黄历数据合并到日历格子
      calendarDates.value = calendarDates.value.map(d => {
        if (almanacMap[d.date]) {
          const a = almanacMap[d.date]
          return {
            ...d,
            hasData: true,
            yi: Array.isArray(a.yi) ? a.yi : (typeof a.yi === 'string' ? JSON.parse(a.yi || '[]') : []),
            ji: Array.isArray(a.ji) ? a.ji : (typeof a.ji === 'string' ? JSON.parse(a.ji || '[]') : []),
            ganzhi: a.ganzhi || d.ganzhi,
            lunar: a.lunar_date || d.lunar,
          }
        }
        return { ...d, hasData: false }
      })
    }
  } catch (e) {
    console.error('加载黄历数据失败:', e)
  }
}

const loadToday = () => {
  const today = new Date()
  selectedMonth.value = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}`
  loadMonthData()
}

const selectDate = (date) => {
  calendarDates.value.forEach(d => d.isSelected = false)
  date.isSelected = true
  openDialog(date)
}

const openDialog = (date = null) => {
  if (date) {
    form.value = {
      solarDate: date.date,
      lunarDate: date.lunar,
      ganzhi: date.ganzhi,
      nayin: '',
      yi: date.yi || [],
      ji: date.ji || [],
      chong: '',
      sha: '',
      zhiri: '',
      shichen: JSON.parse(JSON.stringify(defaultShichen)),
      jishen: [],
      xiongsha: []
    }
  }
  dialogVisible.value = true
}

const submitForm = async () => {
  const valid = await formRef.value.validate().catch(() => false)
  if (!valid) return

  submitLoading.value = true
  try {
    // 调用API保存
    const res = await saveAlmanac(form.value)
    if (res.code === 200) {
      ElMessage.success('保存成功')

      dialogVisible.value = false
      loadMonthData()
    } else {
      ElMessage.error(res.message || '保存失败')
    }
  } catch (error) {
    console.error('保存黄历失败:', error)
    ElMessage.error('保存失败，请检查网络连接')
  } finally {
    submitLoading.value = false
  }
}

const generateMonth = async () => {
  if (!selectedMonth.value) {
    ElMessage.warning('请先选择月份')
    return
  }

  try {
    loading.value = true
    // 调用API自动生成黄历数据
    const [year, month] = selectedMonth.value.split('-')
    const res = await generateAlmanacMonth(parseInt(year), parseInt(month))
    if (res.code === 200) {
      ElMessage.success(`已成功生成 ${year}年${month}月 的黄历数据`)

      loadMonthData()
    } else {
      ElMessage.error(res.message || '生成失败')
    }
  } catch (error) {
    console.error('生成月历失败:', error)
    ElMessage.error('生成失败，请检查网络连接')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadToday()
})
</script>

<style scoped>
.almanac-manage {
  padding: 20px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.header-actions {
  display: flex;
  gap: 12px;
}

.date-selector {
  display: flex;
  gap: 12px;
  margin-bottom: 20px;
}

/* 日历样式 */
.calendar-view {
  background: white;
  border-radius: 12px;
  padding: 20px;
}

.calendar-header {
  text-align: center;
  margin-bottom: 20px;
}

.month-title {
  font-size: 24px;
  font-weight: 600;
  color: #333;
  margin-bottom: 16px;
}

.weekdays {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 10px;
}

.weekday {
  padding: 10px;
  text-align: center;
  font-weight: 600;
  color: #666;
}

.calendar-body {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 10px;
}

.calendar-cell {
  min-height: 100px;
  padding: 10px;
  border: 1px solid #eee;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s;
  position: relative;
}

.calendar-cell:hover {
  background: #f5f5f5;
}

.calendar-cell.other-month {
  opacity: 0.5;
}

.calendar-cell.today {
  background: #e3f2fd;
  border-color: #2196f3;
}

.calendar-cell.selected {
  background: #fff3e0;
  border-color: #ff9800;
}

.cell-date {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 4px;
}

.cell-date .solar {
  font-size: 18px;
  font-weight: 600;
}

.cell-date .lunar {
  font-size: 11px;
  color: #999;
}

.cell-ganzhi {
  font-size: 12px;
  color: #666;
  margin-bottom: 4px;
}

.cell-yiji {
  display: flex;
  gap: 4px;
  flex-wrap: wrap;
}

.cell-shensha {
  position: absolute;
  top: 5px;
  right: 5px;
  color: #ff9800;
}

/* 时辰编辑 */
.shichen-section {
  display: grid;
  gap: 12px;
}

.shichen-item {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 12px;
  background: #f8f8f8;
  border-radius: 8px;
}

.shichen-name {
  width: 100px;
  font-size: 14px;
  color: #333;
}
</style>
