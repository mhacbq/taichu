/**
 * 表单验证工具库
 * 统一全站的表单验证规则
 */

// 手机号验证（中国大陆）
export const validatePhone = (phone) => {
  const reg = /^1[3-9]\d{9}$/
  return reg.test(phone)
}

// 验证码验证（4-6位数字）
export const validateCode = (code) => {
  const reg = /^\d{4,6}$/
  return reg.test(code)
}

// 邮箱验证
export const validateEmail = (email) => {
  const reg = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  return reg.test(email)
}

// 密码验证（6-20位，包含字母和数字）
export const validatePassword = (password) => {
  const reg = /^(?=.*[a-zA-Z])(?=.*\d)[a-zA-Z\d!@#$%^&*]{6,20}$/
  return reg.test(password)
}

// 昵称验证（2-20位字符）
export const validateNickname = (nickname) => {
  const reg = /^[\u4e00-\u9fa5a-zA-Z0-9_\-]{2,20}$/
  return reg.test(nickname)
}

// 出生日期验证
export const validateBirthDate = (date) => {
  if (!date) return false
  
  const birthDate = new Date(date)
  const now = new Date()
  const minDate = new Date('1900-01-01')
  
  return birthDate >= minDate && birthDate <= now
}

// 验证表单字段
export const validateField = (value, rules) => {
  if (!Array.isArray(rules)) {
    rules = [rules]
  }
  
  for (const rule of rules) {
    // 必填验证
    if (rule.required && !value) {
      return { valid: false, message: rule.message || '此项不能为空' }
    }
    
    // 为空时跳过后续验证
    if (!value) {
      continue
    }
    
    // 最小长度
    if (rule.minLength && value.length < rule.minLength) {
      return { valid: false, message: rule.message || `最少${rule.minLength}个字符` }
    }
    
    // 最大长度
    if (rule.maxLength && value.length > rule.maxLength) {
      return { valid: false, message: rule.message || `最多${rule.maxLength}个字符` }
    }
    
    // 最小值
    if (rule.min !== undefined && Number(value) < rule.min) {
      return { valid: false, message: rule.message || `最小值为${rule.min}` }
    }
    
    // 最大值
    if (rule.max !== undefined && Number(value) > rule.max) {
      return { valid: false, message: rule.message || `最大值为${rule.max}` }
    }
    
    // 正则验证
    if (rule.pattern && !rule.pattern.test(value)) {
      return { valid: false, message: rule.message || '格式不正确' }
    }
    
    // 自定义验证函数
    if (rule.validator && typeof rule.validator === 'function') {
      const result = rule.validator(value)
      if (result !== true) {
        return { valid: false, message: result || rule.message || '验证失败' }
      }
    }
  }
  
  return { valid: true }
}

// 验证整个表单
export const validateForm = (data, rules) => {
  const errors = {}
  let isValid = true
  
  for (const [field, fieldRules] of Object.entries(rules)) {
    const result = validateField(data[field], fieldRules)
    if (!result.valid) {
      errors[field] = result.message
      isValid = false
    }
  }
  
  return { isValid, errors }
}

// 预设验证规则
export const rules = {
  // 手机号
  phone: [
    { required: true, message: '请输入手机号' },
    { validator: validatePhone, message: '手机号格式不正确' }
  ],
  
  // 验证码
  code: [
    { required: true, message: '请输入验证码' },
    { minLength: 4, maxLength: 6, message: '验证码为4-6位数字' },
    { pattern: /^\d+$/, message: '验证码只能包含数字' }
  ],
  
  // 密码
  password: [
    { required: true, message: '请输入密码' },
    { minLength: 6, maxLength: 20, message: '密码长度为6-20位' },
    { pattern: /^(?=.*[a-zA-Z])(?=.*\d)/, message: '密码需包含字母和数字' }
  ],
  
  // 昵称
  nickname: [
    { required: true, message: '请输入昵称' },
    { minLength: 2, maxLength: 20, message: '昵称长度为2-20位' },
    { pattern: /^[\u4e00-\u9fa5a-zA-Z0-9_\-]+$/, message: '昵称只能包含中文、字母、数字、下划线和横线' }
  ],
  
  // 邮箱
  email: [
    { required: true, message: '请输入邮箱' },
    { validator: validateEmail, message: '邮箱格式不正确' }
  ],
  
  // 出生日期
  birthDate: [
    { required: true, message: '请选择出生日期' },
    { validator: validateBirthDate, message: '出生日期不正确' }
  ],
  
  // 性别
  gender: [
    { required: true, message: '请选择性别' }
  ],
  
  // 搜索关键词
  keyword: [
    { maxLength: 50, message: '搜索关键词最多50个字符' }
  ],
  
  // 反馈内容
  feedback: [
    { required: true, message: '请输入反馈内容' },
    { minLength: 10, maxLength: 500, message: '反馈内容为10-500个字符' }
  ]
}

// 防抖函数（用于输入验证）
export const debounce = (fn, delay = 300) => {
  let timer = null
  return function (...args) {
    if (timer) {
      clearTimeout(timer)
    }
    timer = setTimeout(() => {
      fn.apply(this, args)
    }, delay)
  }
}

// 节流函数（用于提交按钮）
export const throttle = (fn, delay = 1000) => {
  let lastTime = 0
  return function (...args) {
    const now = Date.now()
    if (now - lastTime > delay) {
      lastTime = now
      fn.apply(this, args)
    }
  }
}

export default {
  validatePhone,
  validateCode,
  validateEmail,
  validatePassword,
  validateNickname,
  validateBirthDate,
  validateField,
  validateForm,
  rules,
  debounce,
  throttle
}
