import { ref, reactive, computed } from 'vue'
import { reportAdminUiError } from '@/utils/dev-error'

/**
 * 表格组合式函数
 * @param {Object} options
 * @param {Function} options.fetchApi - 获取数据的API函数
 * @param {Object} options.defaultParams - 默认参数
 * @returns {Object}
 */
export function useTable(options = {}) {
  const { fetchApi, defaultParams = {} } = options

  // 加载状态
  const loading = ref(false)
  
  // 数据列表
  const dataList = ref([])
  
  // 总条数
  const total = ref(0)
  
  // 选中的行
  const selectedRows = ref([])
  
  // 查询参数
  const queryParams = reactive({
    page: 1,
    pageSize: 20,
    ...defaultParams
  })
  
  // 分页配置
  const pagination = computed(() => ({
    page: queryParams.page,
    pageSize: queryParams.pageSize,
    total: total.value
  }))

  /**
   * 加载数据
   */
  async function loadData(params = {}) {
    if (!fetchApi) return
    
    loading.value = true
    try {
      const res = await fetchApi({ ...queryParams, ...params })
      dataList.value = res.data?.list || []
      total.value = res.data?.total || 0
      return res
    } catch (error) {
      reportAdminUiError('useTable', 'load_data_failed', error, {
        page: queryParams.page,
        page_size: queryParams.pageSize,
        has_fetch_api: typeof fetchApi === 'function'
      })
      throw error
    } finally {
      loading.value = false
    }
  }

  /**
   * 搜索
   */
  function handleSearch() {
    queryParams.page = 1
    loadData()
  }

  /**
   * 重置
   */
  function handleReset() {
    Object.keys(queryParams).forEach(key => {
      if (!['page', 'pageSize'].includes(key)) {
        queryParams[key] = defaultParams[key] || ''
      }
    })
    queryParams.page = 1
    loadData()
  }

  /**
   * 页码改变
   */
  function handlePageChange(page) {
    queryParams.page = page
    loadData()
  }

  /**
   * 每页条数改变
   */
  function handleSizeChange(size) {
    queryParams.page = 1
    queryParams.pageSize = size
    loadData()
  }

  /**
   * 选择改变
   */
  function handleSelectionChange(selection) {
    selectedRows.value = selection
  }

  /**
   * 刷新当前页
   */
  function refresh() {
    loadData()
  }

  return {
    loading,
    dataList,
    total,
    selectedRows,
    queryParams,
    pagination,
    loadData,
    handleSearch,
    handleReset,
    handlePageChange,
    handleSizeChange,
    handleSelectionChange,
    refresh
  }
}
