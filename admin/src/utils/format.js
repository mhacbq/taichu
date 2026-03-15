import dayjs from 'dayjs'

/**
 * 格式化日期
 * @param {string|Date} value - 日期值
 * @param {string} format - 格式化模式
 * @returns {string}
 */
export function formatDate(value, format = 'YYYY-MM-DD HH:mm:ss') {
  if (!value) return '-'
  return dayjs(value).format(format)
}

/**
 * 格式化日期（仅日期部分）
 * @param {string|Date} value
 * @returns {string}
 */
export function formatDateOnly(value) {
  return formatDate(value, 'YYYY-MM-DD')
}

/**
 * 格式化时间（仅时间部分）
 * @param {string|Date} value
 * @returns {string}
 */
export function formatTimeOnly(value) {
  return formatDate(value, 'HH:mm:ss')
}

/**
 * 格式化相对时间（如：3天前）
 * @param {string|Date} value
 * @returns {string}
 */
export function formatRelativeTime(value) {
  if (!value) return '-'
  const date = dayjs(value)
  const now = dayjs()
  const diff = now.diff(date, 'second')
  
  if (diff < 60) return '刚刚'
  if (diff < 3600) return `${Math.floor(diff / 60)}分钟前`
  if (diff < 86400) return `${Math.floor(diff / 3600)}小时前`
  if (diff < 604800) return `${Math.floor(diff / 86400)}天前`
  return formatDateOnly(value)
}

/**
 * 格式化金额
 * @param {number} value
 * @param {number} decimals - 小数位数
 * @param {string} prefix - 前缀
 * @returns {string}
 */
export function formatMoney(value, decimals = 2, prefix = '¥') {
  if (value === null || value === undefined) return `${prefix}0.00`
  const num = Number(value)
  if (isNaN(num)) return `${prefix}0.00`
  return `${prefix}${num.toFixed(decimals).replace(/\B(?=(\d{3})+(?!\d))/g, ',')}`
}

/**
 * 格式化数字（添加千分位）
 * @param {number} value
 * @returns {string}
 */
export function formatNumber(value) {
  if (value === null || value === undefined) return '0'
  const num = Number(value)
  if (isNaN(num)) return '0'
  return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',')
}

/**
 * 格式化文件大小
 * @param {number} bytes - 字节数
 * @returns {string}
 */
export function formatFileSize(bytes) {
  if (bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB', 'TB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

/**
 * 格式化百分比
 * @param {number} value
 * @param {number} decimals
 * @returns {string}
 */
export function formatPercent(value, decimals = 2) {
  if (value === null || value === undefined) return '0%'
  return (value * 100).toFixed(decimals) + '%'
}

/**
 * 格式化手机号（隐藏中间4位）
 * @param {string} phone
 * @returns {string}
 */
export function formatPhone(phone) {
  if (!phone) return '-'
  const str = String(phone)
  if (str.length !== 11) return str
  return str.replace(/(\d{3})\d{4}(\d{4})/, '$1****$2')
}

/**
 * 格式化身份证号（隐藏中间部分）
 * @param {string} idCard
 * @returns {string}
 */
export function formatIdCard(idCard) {
  if (!idCard) return '-'
  const str = String(idCard)
  if (str.length !== 18) return str
  return str.replace(/(\d{4})\d{10}(\d{4})/, '$1**********$2')
}

/**
 * 截断文本
 * @param {string} text
 * @param {number} length
 * @param {string} suffix
 * @returns {string}
 */
export function truncate(text, length = 50, suffix = '...') {
  if (!text) return ''
  if (text.length <= length) return text
  return text.substring(0, length) + suffix
}

/**
 * 首字母大写
 * @param {string} str
 * @returns {string}
 */
export function capitalize(str) {
  if (!str) return ''
  return str.charAt(0).toUpperCase() + str.slice(1)
}

/**
 * 下划线转驼峰
 * @param {string} str
 * @returns {string}
 */
export function toCamelCase(str) {
  return str.replace(/_([a-z])/g, (_, letter) => letter.toUpperCase())
}

/**
 * 驼峰转下划线
 * @param {string} str
 * @returns {string}
 */
export function toSnakeCase(str) {
  return str.replace(/[A-Z]/g, letter => `_${letter.toLowerCase()}`)
}
