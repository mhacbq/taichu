import { ref, reactive, toRaw } from 'vue'
import { reportAdminUiError } from '@/utils/dev-error'

/**
 * 表单组合式函数
 * @param {Object} options
 * @param {Object} options.defaultForm - 默认表单数据
 * @param {Object} options.rules - 表单验证规则
 * @param {Function} options.submitApi - 提交API
 * @param {Function} options.successCallback - 成功回调
 * @returns {Object}
 */
export function useForm(options = {}) {
  const { defaultForm = {}, rules = {}, submitApi, successCallback } = options

  // 表单实例
  const formRef = ref(null)
  
  // 表单数据
  const formData = reactive({ ...defaultForm })
  
  // 提交状态
  const submitting = ref(false)
  
  // 是否编辑模式
  const isEdit = ref(false)

  /**
   * 设置表单数据
   */
  function setForm(data) {
    Object.assign(formData, { ...defaultForm, ...data })
  }

  /**
   * 重置表单
   */
  function resetForm() {
    Object.assign(formData, defaultForm)
    formRef.value?.resetFields()
  }

  /**
   * 验证表单
   */
  async function validateForm() {
    return formRef.value?.validate().catch(() => false)
  }

  /**
   * 提交表单
   */
  async function handleSubmit() {
    const valid = await validateForm()
    if (!valid) return

    if (!submitApi) {
      successCallback?.(toRaw(formData))
      return
    }

    submitting.value = true
    try {
      const res = await submitApi(toRaw(formData))
      successCallback?.(res)
      return res
    } catch (error) {
      reportAdminUiError('useForm', 'submit_failed', error, {
        is_edit: isEdit.value,
        has_submit_api: typeof submitApi === 'function'
      })
      throw error
    } finally {
      submitting.value = false
    }
  }

  /**
   * 清除验证
   */
  function clearValidate() {
    formRef.value?.clearValidate()
  }

  return {
    formRef,
    formData,
    submitting,
    isEdit,
    rules,
    setForm,
    resetForm,
    validateForm,
    handleSubmit,
    clearValidate
  }
}
