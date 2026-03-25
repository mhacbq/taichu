import { ref, computed, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { suggestNames, getQimingHistory, getClientConfig } from '../../api'
import { sanitizeHtml } from '../../utils/sanitize'

export function useQiming() {
  // 客户端配置
  const clientConfig = ref(null)

  // 积分消耗配置（从接口动态获取）
  const qimingCost = computed(() => clientConfig.value?.points?.costs?.qiming || 100)

  const form = ref({
    surname: '',
    gender: 'male',
    birthDate: '',
    birthHour: '',
    style: '',
    taboos: ''
  })

  const loading = ref(false)
  const result = ref(null)

  const formattedResult = computed(() => {
    if (!result.value) return ''

    try {
      // 尝试解析 JSON
      const data = JSON.parse(result.value)
      if (data.names && Array.isArray(data.names)) {
        let html = '<div class="name-suggestions">'
        data.names.forEach((item, index) => {
          html += `
            <div class="name-card">
              <div class="name-card-index">${index + 1}</div>
              <h3 class="name-title">${item.name}</h3>
              <div class="name-detail">
                <div class="detail-item">
                  <span class="detail-label">寓意</span>
                  <p>${item.meaning}</p>
                </div>
                <div class="detail-item">
                  <span class="detail-label">五行</span>
                  <p>${item.wuxing}</p>
                </div>
                <div class="detail-item">
                  <span class="detail-label">音律</span>
                  <p>${item.phonetics}</p>
                </div>
              </div>
            </div>
          `
        })
        html += '</div>'
        return sanitizeHtml(html)
      }
    } catch (e) {
      // 如果不是 JSON，直接返回文本（处理换行）
      return sanitizeHtml(result.value.replace(/\n/g, '<br>'))
    }

    return sanitizeHtml(result.value.replace(/\n/g, '<br>'))
  })

  const submitForm = async () => {
    if (!form.value.surname) {
      ElMessage.warning('请输入姓氏')
      return
    }
    if (!form.value.birthDate) {
      ElMessage.warning('请选择出生日期')
      return
    }

    loading.value = true
    try {
      const response = await suggestNames({
        surname: form.value.surname,
        gender: form.value.gender,
        birth_date: form.value.birthDate,
        birth_hour: form.value.birthHour,
        style: form.value.style,
        taboos: form.value.taboos
      })

      if (response.code === 200) {
        result.value = response.data.result
        ElMessage.success('取名成功！')
      } else {
        ElMessage.error(response.message || '取名失败')
      }
    } catch (error) {
      ElMessage.error('网络错误，请稍后重试')
    } finally {
      loading.value = false
    }
  }

  const resetForm = () => {
    result.value = null
  }

  // 历史记录
  const historyList = ref([])
  const historyLoading = ref(false)
  const historyTotal = ref(0)
  const historyPage = ref(1)
  const historyPageSize = ref(10)
  const showHistory = ref(false)

  const loadHistory = async () => {
    historyLoading.value = true
    try {
      const response = await getQimingHistory({
        page: historyPage.value,
        page_size: historyPageSize.value
      })
      if (response.code === 200) {
        historyList.value = response.data.list || []
        historyTotal.value = response.data.total || 0
      }
    } catch (error) {
      ElMessage.error('获取历史记录失败')
    } finally {
      historyLoading.value = false
    }
  }

  const toggleHistory = () => {
    showHistory.value = !showHistory.value
    if (showHistory.value && historyList.value.length === 0) {
      loadHistory()
    }
  }

  const handleHistoryPageChange = (page) => {
    historyPage.value = page
    loadHistory()
  }

  const viewHistoryResult = (item) => {
    result.value = item.name_suggestions || item.result || ''
    showHistory.value = false
  }

  // 加载客户端配置
  const loadClientConfig = async () => {
    try {
      const response = await getClientConfig()
      if (response.code === 200) {
        clientConfig.value = response.data
      }
    } catch (error) {
      console.error('加载客户端配置失败:', error)
    }
  }

  // 组件挂载时加载配置
  onMounted(() => {
    loadClientConfig()
  })

  return {
    form,
    loading,
    result,
    qimingCost,
    formattedResult,
    submitForm,
    resetForm,
    historyList,
    historyLoading,
    historyTotal,
    historyPage,
    historyPageSize,
    showHistory,
    loadHistory,
    toggleHistory,
    handleHistoryPageChange,
    viewHistoryResult,
  }
}
