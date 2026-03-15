import { ref } from 'vue'

/**
 * 弹窗组合式函数
 * @param {Object} options
 * @returns {Object}
 */
export function useDialog(options = {}) {
  const { onOpen, onClose, onConfirm } = options

  // 弹窗显示状态
  const visible = ref(false)
  
  // 弹窗数据
  const dialogData = ref({})
  
  // 加载状态
  const loading = ref(false)

  /**
   * 打开弹窗
   */
  function open(data = {}) {
    dialogData.value = data
    visible.value = true
    onOpen?.(data)
  }

  /**
   * 关闭弹窗
   */
  function close() {
    visible.value = false
    onClose?.()
  }

  /**
   * 确认
   */
  async function confirm(callback) {
    loading.value = true
    try {
      await callback?.()
      onConfirm?.()
      close()
    } finally {
      loading.value = false
    }
  }

  return {
    visible,
    dialogData,
    loading,
    open,
    close,
    confirm
  }
}
