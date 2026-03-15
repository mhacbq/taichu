/**
 * 本地存储工具函数
 */

const STORAGE_PREFIX = 'taichu_admin_'

/**
 * 设置本地存储
 * @param {string} key
 * @param {*} value
 * @param {number} expire - 过期时间（分钟）
 */
export function setStorage(key, value, expire) {
  const data = {
    value,
    time: Date.now(),
    expire: expire ? expire * 60 * 1000 : null
  }
  localStorage.setItem(STORAGE_PREFIX + key, JSON.stringify(data))
}

/**
 * 获取本地存储
 * @param {string} key
 * @returns {*}
 */
export function getStorage(key) {
  const item = localStorage.getItem(STORAGE_PREFIX + key)
  if (!item) return null
  
  try {
    const data = JSON.parse(item)
    
    // 检查是否过期
    if (data.expire && Date.now() - data.time > data.expire) {
      removeStorage(key)
      return null
    }
    
    return data.value
  } catch {
    return item
  }
}

/**
 * 移除本地存储
 * @param {string} key
 */
export function removeStorage(key) {
  localStorage.removeItem(STORAGE_PREFIX + key)
}

/**
 * 清空本地存储
 */
export function clearStorage() {
  // 只清空带有前缀的存储
  Object.keys(localStorage)
    .filter(key => key.startsWith(STORAGE_PREFIX))
    .forEach(key => localStorage.removeItem(key))
}

/**
 * 设置Session存储
 * @param {string} key
 * @param {*} value
 */
export function setSession(key, value) {
  sessionStorage.setItem(STORAGE_PREFIX + key, JSON.stringify(value))
}

/**
 * 获取Session存储
 * @param {string} key
 * @returns {*}
 */
export function getSession(key) {
  const item = sessionStorage.getItem(STORAGE_PREFIX + key)
  if (!item) return null
  
  try {
    return JSON.parse(item)
  } catch {
    return item
  }
}

/**
 * 移除Session存储
 * @param {string} key
 */
export function removeSession(key) {
  sessionStorage.removeItem(STORAGE_PREFIX + key)
}

/**
 * 设置Cookie
 * @param {string} key
 * @param {*} value
 * @param {number} days - 过期天数
 */
export function setCookie(key, value, days = 7) {
  const expires = new Date(Date.now() + days * 24 * 60 * 60 * 1000).toUTCString()
  document.cookie = `${STORAGE_PREFIX}${key}=${encodeURIComponent(value)};expires=${expires};path=/`
}

/**
 * 获取Cookie
 * @param {string} key
 * @returns {string|null}
 */
export function getCookie(key) {
  const match = document.cookie.match(new RegExp('(^| )' + STORAGE_PREFIX + key + '=([^;]*)(;|$)'))
  return match ? decodeURIComponent(match[2]) : null
}

/**
 * 移除Cookie
 * @param {string} key
 */
export function removeCookie(key) {
  document.cookie = `${STORAGE_PREFIX}${key}=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/`
}
