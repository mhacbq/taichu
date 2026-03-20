/**
 * 格式化时间
 * @param {string|Date} date - 日期
 * @returns {string} 格式化后的时间字符串
 */
export function formatTime(date) {
  if (!date) return ''
  
  const now = new Date()
  const target = new Date(date)
  const diff = now.getTime() - target.getTime()
  
  // 小于1分钟
  if (diff < 60000) {
    return '刚刚'
  }
  
  // 小于1小时
  if (diff < 3600000) {
    return Math.floor(diff / 60000) + '分钟前'
  }
  
  // 小于24小时
  if (diff < 86400000) {
    return Math.floor(diff / 3600000) + '小时前'
  }
  
  // 小于7天
  if (diff < 604800000) {
    return Math.floor(diff / 86400000) + '天前'
  }
  
  // 超过7天，显示具体日期
  const year = target.getFullYear()
  const month = String(target.getMonth() + 1).padStart(2, '0')
  const day = String(target.getDate()).padStart(2, '0')
  
  // 如果是今年，不显示年份
  if (year === now.getFullYear()) {
    return `${month}-${day}`
  }
  
  return `${year}-${month}-${day}`
}

/**
 * 格式化完整日期时间
 * @param {string|Date} date - 日期
 * @returns {string} 格式化后的日期时间字符串
 */
export function formatDateTime(date) {
  if (!date) return ''
  
  const target = new Date(date)
  const year = target.getFullYear()
  const month = String(target.getMonth() + 1).padStart(2, '0')
  const day = String(target.getDate()).padStart(2, '0')
  const hours = String(target.getHours()).padStart(2, '0')
  const minutes = String(target.getMinutes()).padStart(2, '0')
  
  return `${year}-${month}-${day} ${hours}:${minutes}`
}

/**
 * 格式化日期
 * @param {string|Date} date - 日期
 * @returns {string} 格式化后的日期字符串
 */
export function formatDate(date) {
  if (!date) return ''
  
  const target = new Date(date)
  const year = target.getFullYear()
  const month = String(target.getMonth() + 1).padStart(2, '0')
  const day = String(target.getDate()).padStart(2, '0')
  
  return `${year}-${month}-${day}`
}

/**
 * 格式化数字（千分位）
 * @param {number} num - 数字
 * @returns {string} 格式化后的数字字符串
 */
export function formatNumber(num) {
  if (num === undefined || num === null) return '0'
  return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',')
}

/**
 * 格式化积分变动
 * @param {number} points - 积分
 * @returns {string} 带符号的积分字符串
 */
export function formatPoints(points) {
  if (points > 0) {
    return `+${points}`
  }
  return String(points)
}
