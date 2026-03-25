import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { ElMessage } from 'element-plus'
import { getTarotShare } from '../../api'

export function useTarotShare() {
  const route = useRoute()
  const loading = ref(true)
  const errorMessage = ref('')
  const shareRecord = ref(null)

  // 热门问题
  const hotQuestions = [
    '我最近的财运走向如何？',
    '这段感情值得我继续投入吗？',
    '我应该接受这份新工作offer吗？',
    '如何突破目前的职业瓶颈？',
    '我什么时候能遇到正缘？',
  ]

  // 牌阵位置标签映射
  const spreadPositionLabels = {
    single: ['今日指引'],
    three: ['过去', '现在', '未来'],
    celtic: [
      '当前状态',
      '障碍/挑战',
      '潜意识/基础',
      '过去影响',
      '目标可能',
      '近期发展',
      '你的态度',
      '外部环境',
      '希望/恐惧',
      '最终走向',
    ],
  }

  // ===== 工具方法 =====
  const getPositionLabel = (spreadType, index) => {
    const labels = spreadPositionLabels[spreadType] || []
    return labels[index] || `第${index + 1}张`
  }

  const formatShareTime = (value) => {
    if (!value) return '时间未知'
    const date = new Date(value)
    if (Number.isNaN(date.getTime())) return value
    return date.toLocaleString('zh-CN', { hour12: false })
  }

  // 更新 OG Meta 信息（SEO）
  const updateOGMeta = (data) => {
    if (!data) return

    const title = `塔罗占卜分享 - ${data.spread_name}`
    const description = data.question ? `占问：${data.question}` : '查看我的塔罗占卜结果'

    document.title = `${title} | 太初命理`

    const metaTags = {
      'og:title': title,
      'og:description': description,
      'og:type': 'article',
      'og:site_name': '太初命理',
      'twitter:card': 'summary',
      'twitter:title': title,
      'twitter:description': description,
    }

    Object.entries(metaTags).forEach(([name, content]) => {
      let tag =
        document.querySelector(`meta[property="${name}"]`) ||
        document.querySelector(`meta[name="${name}"]`)
      if (!tag) {
        tag = document.createElement('meta')
        if (name.startsWith('og:')) {
          tag.setAttribute('property', name)
        } else {
          tag.setAttribute('name', name)
        }
        document.head.appendChild(tag)
      }
      tag.setAttribute('content', content)
    })
  }

  // ===== 数据加载 =====
  const loadShareRecord = async () => {
    loading.value = true
    errorMessage.value = ''

    try {
      const code = route.params.code
      const response = await getTarotShare({ code })

      if (response.code === 0) {
        shareRecord.value = response.data
        updateOGMeta(response.data)
        return
      }

      errorMessage.value = response.message || '分享内容加载失败'
    } catch (error) {
      errorMessage.value = error?.response?.data?.message || '分享内容加载失败，请稍后重试'
      ElMessage.error(errorMessage.value)
    } finally {
      loading.value = false
    }
  }

  // ===== 初始化 =====
  onMounted(() => {
    loadShareRecord()
  })

  return {
    loading,
    errorMessage,
    shareRecord,
    hotQuestions,
    getPositionLabel,
    formatShareTime,
    loadShareRecord,
  }
}
