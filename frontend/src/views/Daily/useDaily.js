import { ref, onMounted, onUnmounted, computed } from 'vue'
import { ElMessage } from 'element-plus'
import { MagicStick, Briefcase, Money, Sunny, StarFilled } from '@element-plus/icons-vue'
import { getDailyFortune } from '../../api'

export function useDaily() {
const solarDate = ref('')
const lunarDate = ref('')
const isLoading = ref(true)
const fortune = ref(null)
const isLoggedIn = ref(false)
const activeNames = ref([])
const error = ref(false)
const errorMessage = ref('')
const dailyLoginRoute = { path: '/login', query: { redirect: '/daily' } }

// 生日相关
const showBirthdayDialog = ref(false)
const userBirthDate = ref('')
const birthdayLoading = ref(false)

const dailyStatus = computed(() => {
  if (isLoading.value) return 'loading'
  if (error.value) return 'error'
  if (fortune.value) return 'success'
  return 'empty'
})

// 明日预告相关状态
const showTomorrowPreview = ref(false)
const tomorrowStarCount = ref(0)
const tomorrowSummary = ref('')

const checkTomorrowPreview = () => {
  const now = new Date()
  const hour = now.getHours()
  // 18:00 后显示明日预告
  if (hour >= 18) {
    showTomorrowPreview.value = true
    // 模拟明日运势数据，实际应从后端获取
    tomorrowStarCount.value = Math.floor(Math.random() * 3) + 3 // 3-5星
    tomorrowSummary.value = '明日运势平稳，适合按部就班推进计划，注意劳逸结合。'
  } else {
    showTomorrowPreview.value = false
  }
}

const loadTomorrowFortune = () => {
  ElMessage.info('明日运势功能开发中，敬请期待')
}

const addToCalendar = () => {
  if (!fortune.value) return

  const title = `太初命理 - 今日运势 (${solarDate.value})`
  let description = `综合评分：${fortune.value.overallScore}分\n`
  description += `运势简评：${fortune.value.summary}\n\n`

  if (hasYiItems.value) {
    description += `宜：${fortune.value.yi.join('、')}\n`
  }
  if (hasJiItems.value) {
    description += `忌：${fortune.value.ji.join('、')}\n`
  }

  if (personalizedFortune.value?.advice) {
    description += `\n专属建议：${personalizedFortune.value.advice}`
  }

  // 生成 ICS 文件内容
  const now = new Date()
  const dtstamp = now.toISOString().replace(/[-:]/g, '').split('.')[0] + 'Z'

  // 设置为全天事件
  const dateStr = solarDate.value.replace(/-/g, '')
  const dtstart = dateStr

  // 结束日期为开始日期的下一天
  const nextDay = new Date(solarDate.value)
  nextDay.setDate(nextDay.getDate() + 1)
  const dtend = nextDay.toISOString().split('T')[0].replace(/-/g, '')

  const icsContent = [
    'BEGIN:VCALENDAR',
    'VERSION:2.0',
    'PRODID:-//Taichu//Daily Fortune//CN',
    'BEGIN:VEVENT',
    `DTSTAMP:${dtstamp}`,
    `DTSTART;VALUE=DATE:${dtstart}`,
    `DTEND;VALUE=DATE:${dtend}`,
    `SUMMARY:${title}`,
    `DESCRIPTION:${description.replace(/\n/g, '\\n')}`,
    'END:VEVENT',
    'END:VCALENDAR'
  ].join('\r\n')

  const blob = new Blob([icsContent], { type: 'text/calendar;charset=utf-8' })
  const link = document.createElement('a')
  link.href = window.URL.createObjectURL(blob)
  link.download = `今日运势_${dateStr}.ics`
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)

  ElMessage.success('已生成日历文件，请在下载后打开以添加到系统日历')
}

const wuxingIconMap = {
  '金': '🪙',
  '木': '🌿',
  '水': '💧',
  '火': '🔥',
  '土': '🪨',
}

const luckLevelConfig = {
  '大吉': { icon: '🌟', stars: 5, color: '#52c41a' },
  '吉': { icon: '✨', stars: 4, color: '#73d13d' },
  '小吉': { icon: '🍀', stars: 3, color: '#95de64' },
  '平': { icon: '☯', stars: 3, color: '#d4af37' },
  '小凶': { icon: '🌧', stars: 2, color: '#ff9c6e' },
  '凶': { icon: '⚡', stars: 1, color: '#ff7875' },
  '大凶': { icon: '🌩', stars: 1, color: '#f5222d' },
}

const personalizedRelationGuides = {
  '天合地合': {
    title: '新际遇有成',
    description: '你内心深处一直在等待某个时机，而今天，那种感觉终于对了。你不需要解释太多，事情会自然而然地朝你期待的方向走——这不是运气，而是你一直以来的积累在今天开花。',
  },
  '天合地会': {
    title: '守成之事，近亲相助',
    description: '你有一种让人感到安心的气质，今天这种特质会为你带来意想不到的助力。那些你以为不会开口的人，今天可能会主动向你靠近。',
  },
  '天合地刑': {
    title: '外合心不合',
    description: '你善于在别人面前保持体面，但今天你的内心可能比外表更复杂。那种说不清道不明的别扭感，其实是你在提醒自己：有些情绪需要被看见，而不是被压下去。',
  },
  '天比地合': {
    title: '以智取之',
    description: '你有一种别人不容易察觉的洞察力，今天这种能力会在关键时刻发挥作用。你不需要强迫任何人，只需要做你自己，合适的人自然会被你吸引过来。',
  },
  '天比地冲': {
    title: '外象平安，内实空虚',
    description: '你有时会用忙碌来掩盖内心的空洞感，今天这种感觉可能会更明显。表面的平静背后，你其实比任何人都清楚，有些事情还没有真正落地。',
  },
  '天比地刑': {
    title: '处于长期内争',
    description: '你对自己的要求很高，有时甚至有些苛刻。今天这种内在的张力可能会向外投射，让你觉得周围的人都不太对劲——但也许，需要和解的对象只是你自己。',
  },
  '天克地冲': {
    title: '创业性的碰钉子',
    description: '你不是那种轻易认输的人，但今天你可能会遇到一种特殊的阻力——它不来自外部，而是来自某种时机上的错位。越是用力，越是感到吃力，这不是你的问题，只是今天不是那个适合强攻的时机。',
  },
  '克天地冲': {
    title: '偿债式的说好话',
    description: '你其实比外表看起来更在意别人的看法，尽管你不常承认这一点。今天你可能会发现自己处于一种被动的位置，但这并不意味着你失去了主动权——只是换了一种方式在运作。',
  },
  '天生地冲': {
    title: '小有成就',
    description: '你有一种在混乱中找到秩序的天赋，今天这种能力会在不经意间为你带来收获。那些看似偶然的小事，背后往往有你自己都没意识到的努力在支撑。',
  },
  '生天地冲': {
    title: '因小祸而得福',
    description: '你有一种别人羡慕的韧性，总能在跌倒后重新站起来。今天可能会有一个小小的挫折，但你内心深处其实已经知道，这不过是更好的事情到来之前的一个小插曲。',
  },
  '天生地合': {
    title: '助他人之故而获利',
    description: '你是那种愿意为别人付出的人，而今天，这种善意会以一种你意想不到的方式回到你身上。你不需要刻意去追求什么，只需要做你一直在做的事。',
  },
  '生天地合': {
    title: '借他人之力而有成',
    description: '你身边一直有人在默默关注你，只是你可能还没有意识到。今天，某个人会以一种恰到好处的方式出现，而你需要做的，只是允许自己接受这份帮助。',
  },
  '天生地刑': {
    title: '自找麻烦',
    description: '你有一颗真诚想帮助别人的心，但今天你的好意可能会被误读，或者带来你没有预料到的复杂局面。有时候，最好的帮助是给对方空间，而不是亲自下场。',
  },
  '生天地刑': {
    title: '他人加于自己的麻烦',
    description: '你有一种容易被别人依赖的气场，这是你的魅力，但今天它可能会把你带入一个你本不需要参与的局面。你的冷静和理性，是今天最重要的保护。',
  },
  '地比天克': {
    title: '有信心中应付强对手',
    description: '你比自己以为的更有实力，只是有时候你自己不太相信这一点。今天你会遇到某种考验，而你内心深处其实早就准备好了——你只需要相信那个已经准备好的自己。',
  },
  '地比克天': {
    title: '渐失信心中坚持努力',
    description: '你是一个对自己有要求的人，正因如此，今天那种“怎么努力都差一口气”的感觉会格外刺痛。但这不是你的终点，只是一个让你重新审视方向的信号——守住已有的，比强行突破更明智。',
  },
  '天克地刑': {
    title: '以债养债',
    description: '你有时会用行动来回避思考，用忙碌来掩盖某种深层的不安。今天这种模式可能会让事情变得更复杂，而不是更简单。真正的解决，往往需要你先停下来，面对那个你一直在绕开的问题。',
  },
  '克天地刑': {
    title: '抵押偿债',
    description: '你习惯独自承担压力，不太愿意让别人看到你吃力的一面。今天这种压力可能会以一种具体的形式出现，提醒你：懂得保留余地，是一种智慧，不是退缩。',
  },
  '伏吟': {
    title: '多此一举',
    description: '你是那种不轻易放弃的人，但今天的努力可能会让你感到一种说不清的疲惫——不是因为你不够努力，而是方向需要重新校准。停下来，往往比继续走更需要勇气。',
  },
  '空亡': {
    title: '事倍而功半',
    description: '你付出的努力，并不总是能立刻看到回报——今天尤其如此。这不是你的失败，而是某种积累在悄悄发生。有时候，什么都不做，也是一种选择。',
  },
  '普通': {
    title: '运势平稳',
    description: '你是一个内心比外表更丰富的人，今天这种平静的表面下，其实有很多细腻的感受在流动。不是每一天都需要大起大落，有时候，平稳本身就是一种珍贵的状态。',
  }
}

const syncLoginState = () => {
  if (typeof window === 'undefined') {
    return
  }

  isLoggedIn.value = Boolean(window.localStorage.getItem('token'))
}

const personalizedFortune = computed(() => {
  const payload = fortune.value?.personalized
  return payload && typeof payload === 'object' ? payload : null
})

const personalizedRelationMeta = computed(() => {
  const relation = personalizedFortune.value?.relation || ''
  return personalizedRelationGuides[relation] || {
    title: '今日能量提示',
    description: '今日个性化运势已生成，可结合下方建议安排重点事项。',
  }
})

const isPersonalizedPayloadInvalid = computed(() => {
  if (!isLoggedIn.value || !personalizedFortune.value) {
    return false
  }

  if (personalizedFortune.value.hasBazi === false) {
    return false
  }

  const requiredFields = ['dayMaster', 'todayGanZhi', 'relation', 'luckLevel', 'advice']
  return personalizedFortune.value.hasBazi !== true || requiredFields.some((key) => {
    return typeof personalizedFortune.value[key] !== 'string' || personalizedFortune.value[key].trim() === ''
  })
})

const dailyPersonalizedState = computed(() => {
  if (!fortune.value) {
    return 'hidden'
  }

  if (!isLoggedIn.value) {
    return 'guest'
  }

  if (isPersonalizedPayloadInvalid.value) {
    return 'error'
  }

  if (personalizedFortune.value?.hasBazi) {
    return 'ready'
  }

  return 'no_bazi'
})

const overallStarCount = computed(() => {
  const score = Number(fortune.value?.overallScore ?? 0)
  if (!Number.isFinite(score) || score <= 0) {
    return 0
  }

  if (score >= 85) return 5
  if (score >= 70) return 4
  if (score >= 55) return 3
  if (score >= 40) return 2
  return 1
})

const overallStarLabel = computed(() => {
  if (overallStarCount.value <= 0) {
    return '评分整理中'
  }

  return `视觉评级：${overallStarCount.value} 星`
})

const getScoreColor = (score) => {

  if (score >= 80) return '#67c23a'
  if (score >= 60) return '#e6a23c'
  return '#f56c6c'
}

const getScoreClass = (score) => {
  if (score >= 80) return 'excellent'
  if (score >= 60) return 'good'
  return 'normal'
}


const getAspectIcon = (aspectName = '') => {
  if (aspectName.includes('事业')) return Briefcase
  if (aspectName.includes('财')) return Money
  if (aspectName.includes('感情') || aspectName.includes('爱情')) return StarFilled
  if (aspectName.includes('健康')) return Sunny
  return MagicStick
}

const hasAspectCards = computed(() => {
  return Array.isArray(fortune.value?.aspects) && fortune.value.aspects.some((aspect) => {
    return Boolean(aspect?.name || aspect?.description || Number.isFinite(Number(aspect?.score)))
  })
})

const hasYiItems = computed(() => Array.isArray(fortune.value?.yi) && fortune.value.yi.length > 0)
const hasJiItems = computed(() => Array.isArray(fortune.value?.ji) && fortune.value.ji.length > 0)
const hasLuckySection = computed(() => hasYiItems.value || hasJiItems.value)

// ====== 黄历数据 ======
const almanac = computed(() => fortune.value?.almanac || null)
const hasAlmanac = computed(() => {
  if (!almanac.value) return false
  return !!(almanac.value.dayGanzhi || almanac.value.zhiri || almanac.value.sha)
})
const ganzhiText = computed(() => fortune.value?.ganzhi || '')

// 建除十二神标签颜色
const zhiriTagType = (zhiri) => {
  const jiMap = { '建': 'primary', '除': 'success', '满': 'warning', '成': 'success', '开': 'success', '定': 'primary', '收': 'primary' }
  const xiongMap = { '破': 'danger', '危': 'danger', '闭': 'info', '执': 'info', '平': '' }
  return jiMap[zhiri] || xiongMap[zhiri] || ''
}

// 建除十二神简要描述
const zhiriDesc = (zhiri) => {
  const desc = {
    '建': '建日万事生发，适合启新动工',
    '除': '除日清旧布新，宜解除扫舍',
    '满': '满日圆满丰盈，利祈福纳财',
    '平': '平日平稳无波，宜修饰会友',
    '定': '定日安定有序，利订盟立券',
    '执': '执日有执有为，宜捕捉纳采',
    '破': '破日宜破旧立新，忌嫁娶开市',
    '危': '危日须谨慎行事，宜祭祀祈福',
    '成': '成日诸事可成，大利嫁娶签约',
    '收': '收日收获入库，宜纳财收账',
    '开': '开日万象更新，利开市求财',
    '闭': '闭日宜静不宜动，利祭祀修筑',
  }
  return desc[zhiri] || ''
}

// 时辰数据
const shichenList = computed(() => {
  if (!almanac.value?.shichen) return []
  return almanac.value.shichen
})
const hasShichen = computed(() => shichenList.value.length > 0)

// 当前时辰高亮
const currentShichenIndex = computed(() => {
  const hour = new Date().getHours()
  // 子时 23:00-00:59 对应 index 0
  if (hour === 23 || hour === 0) return 0
  return Math.floor((hour + 1) / 2)
})

const detailSections = computed(() => {
  const details = fortune.value?.details || {}
  return [
    { key: 'career', title: '事业运势', content: details.career },
    { key: 'wealth', title: '财运运势', content: details.wealth },
    { key: 'love', title: '感情运势', content: details.love },
    { key: 'health', title: '健康运势', content: details.health },
  ].filter((item) => typeof item.content === 'string' && item.content.trim())
})

const defaultExpandedDetailNames = computed(() => {
  return detailSections.value.length ? [detailSections.value[0].key] : []
})

const DAILY_CACHE_KEY = 'daily_fortune_cache'

const getTodayDateStr = () => {
  const d = new Date()
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}

const readDailyCache = () => {
  try {
    const raw = localStorage.getItem(DAILY_CACHE_KEY)
    if (!raw) return null
    const cached = JSON.parse(raw)
    if (cached.date === getTodayDateStr()) return cached.data
    // 日期已过，清除旧缓存
    localStorage.removeItem(DAILY_CACHE_KEY)
    return null
  } catch {
    return null
  }
}

const writeDailyCache = (data) => {
  try {
    localStorage.setItem(DAILY_CACHE_KEY, JSON.stringify({ date: getTodayDateStr(), data }))
  } catch {
    // 缓存写入失败不影响主流程
  }
}

const getColorCode = (colorName) => {
  const colorMap = {
    '红色': '#ff0000',
    '橙色': '#ff7f00',
    '黄色': '#ffff00',
    '绿色': '#00ff00',
    '青色': '#00ffff',
    '蓝色': '#0000ff',
    '紫色': '#800080',
    '粉色': '#ffb6c1',
    '棕色': '#964b00',
    '黑色': '#000000',
    '白色': '#ffffff',
    '灰色': '#808080',
    '金色': '#ffd700',
    '银色': '#c0c0c0',
    '米色': '#f5f5dc',
    '咖啡色': '#6f4e37',
    '藏青色': '#191970',
    '酒红色': '#722f37',
    '天蓝色': '#87ceeb',
    '薄荷绿': '#98ff98'
  }
  return colorMap[colorName] || '#d4af37'
}

const saveBirthDate = async () => {
  if (!userBirthDate.value) {
    ElMessage.warning('请选择出生日期')
    return
  }

  birthdayLoading.value = true
  try {
    // 这里需要调用API保存生日
    // 暂时只保存在本地存储，实际项目需要调用后端API
    localStorage.setItem('user_birth_date', userBirthDate.value)
    ElMessage.success('出生日期设置成功')
    showBirthdayDialog.value = false
    // 设置生日后重新加载运势
    loadDailyFortune()
  } catch (err) {
    ElMessage.error('设置失败，请重试')
  } finally {
    birthdayLoading.value = false
  }
}

const loadDailyFortune = async ({ userInitiated = false } = {}) => {
  // 检查是否登录
  if (!isLoggedIn.value) {
    showBirthdayDialog.value = true
    isLoading.value = false
    return
  }

  // 检查用户是否设置了生日
  if (!userBirthDate.value) {
    const savedBirthDate = localStorage.getItem('user_birth_date')
    if (savedBirthDate) {
      userBirthDate.value = savedBirthDate
    } else {
      showBirthdayDialog.value = true
      isLoading.value = false
      return
    }
  }

  // 非用户主动刷新时，优先读取当天缓存
  if (!userInitiated) {
    const cached = readDailyCache()
    if (cached) {
      fortune.value = {
        ...cached,
        yi: cached.yi || [],
        ji: cached.ji || [],
        aspects: cached.aspects || [],
        details: cached.details || {},
        personalized: cached.personalized || null,
        almanac: cached.almanac || null,
        ganzhi: cached.ganzhi || '',
      }
      solarDate.value = cached.date || ''
      lunarDate.value = cached.lunarDate || ''
      activeNames.value = defaultExpandedDetailNames.value
      error.value = false
      isLoading.value = false
      return
    }
  }

  isLoading.value = true
  error.value = false
  errorMessage.value = ''
  solarDate.value = ''
  lunarDate.value = ''

  try {
    const response = await getDailyFortune()
    if (response.code === 200) {
      const data = response.data || {}
      fortune.value = {
        ...data,
        yi: data.yi || [],
        ji: data.ji || [],
        aspects: data.aspects || [],
        details: data.details || {},
        personalized: data.personalized || null,
        almanac: data.almanac || null,
        ganzhi: data.ganzhi || '',
      }
      solarDate.value = data.date || ''
      lunarDate.value = data.lunarDate || ''
      activeNames.value = defaultExpandedDetailNames.value
      error.value = false
      // 成功后写入当天缓存
      writeDailyCache(data)
    } else {
      fortune.value = null
      solarDate.value = ''
      lunarDate.value = ''
      activeNames.value = []
      error.value = true
      errorMessage.value = response.message || '获取运势失败'
      if (userInitiated) {
        ElMessage.error(errorMessage.value)
      }
    }
  } catch (err) {
    fortune.value = null
    solarDate.value = ''
    lunarDate.value = ''
    activeNames.value = []
    error.value = true
    errorMessage.value = '网络错误，请稍后重试'
    if (userInitiated) {
      ElMessage.error(errorMessage.value)
    }
  } finally {

    isLoading.value = false
  }
}



const handleVisibilityChange = () => {
  if (document.visibilityState === 'visible') {
    syncLoginState()
  }
}

onMounted(() => {
  syncLoginState()
  loadDailyFortune()
  checkTomorrowPreview()
  window.addEventListener('storage', syncLoginState)
  document.addEventListener('visibilitychange', handleVisibilityChange)
})

onUnmounted(() => {
  window.removeEventListener('storage', syncLoginState)
  document.removeEventListener('visibilitychange', handleVisibilityChange)
})

return {
  solarDate, lunarDate, isLoading, fortune, isLoggedIn, activeNames,
  error, errorMessage, dailyLoginRoute, showBirthdayDialog, userBirthDate,
  birthdayLoading, showTomorrowPreview, tomorrowStarCount, tomorrowSummary,
  dailyStatus, personalizedFortune, personalizedRelationMeta,
  dailyPersonalizedState, overallStarCount, overallStarLabel,
  hasAspectCards, hasYiItems, hasJiItems, hasLuckySection, detailSections,
  almanac, hasAlmanac, ganzhiText, shichenList, hasShichen, currentShichenIndex,
  wuxingIconMap, luckLevelConfig,
  getScoreColor, getScoreClass, getAspectIcon, getColorCode,
  zhiriTagType, zhiriDesc,
  loadDailyFortune, loadTomorrowFortune, addToCalendar, saveBirthDate,
}
} // end useDaily


