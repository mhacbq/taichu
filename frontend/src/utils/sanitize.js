import DOMPurify from 'dompurify'

/**
 * HTML净化工具
 * 用于净化AI返回的内容，防止XSS攻击
 */

// 允许的HTML标签（白名单）
const ALLOWED_TAGS = [
  'p', 'br', 'strong', 'b', 'em', 'i', 'u', 'span', 'div',
  'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
  'ul', 'ol', 'li',
  'blockquote', 'code', 'pre',
  'table', 'thead', 'tbody', 'tr', 'th', 'td'
]

// 允许的HTML属性
const ALLOWED_ATTR = [
  'class', 'id', 'style'
]

/**
 * 净化HTML内容
 * @param {string} html - 需要净化的HTML内容
 * @param {boolean} allowFormatting - 是否允许格式化标签
 * @returns {string} - 净化后的HTML
 */
export function sanitizeHtml(html, allowFormatting = true) {
  if (!html || typeof html !== 'string') {
    return ''
  }

  if (!allowFormatting) {
    // 纯文本模式，去除所有HTML标签
    return html.replace(/<[^>]*>/g, '')
  }

  // 配置DOMPurify
  const config = {
    ALLOWED_TAGS,
    ALLOWED_ATTR,
    KEEP_CONTENT: true,
    // 禁止javascript伪协议
    FORBID_ATTR: ['onerror', 'onload', 'onclick', 'onmouseover'],
    // 禁止data URI（除了图片）
    ALLOW_DATA_ATTR: false,
    // 自定义钩子，进一步增强安全性
    uponSanitizeElement: (node, data) => {
      // 移除所有事件处理器
      if (node.hasAttributes()) {
        const attrs = node.attributes
        for (let i = attrs.length - 1; i >= 0; i--) {
          const attrName = attrs[i].name
          if (attrName.startsWith('on')) {
            node.removeAttribute(attrName)
          }
        }
      }
    }
  }

  return DOMPurify.sanitize(html, config)
}

/**
 * 净化纯文本（完全去除HTML）
 * @param {string} text - 需要净化的文本
 * @returns {string} - 净化后的纯文本
 */
export function sanitizeText(text) {
  if (!text || typeof text !== 'string') {
    return ''
  }
  // 先去除HTML标签
  let clean = text.replace(/<[^>]*>/g, '')
  // 再去除可能的HTML实体
  const txt = document.createElement('textarea')
  txt.innerHTML = clean
  clean = txt.value
  return clean.trim()
}

/**
 * 检查字符串是否包含危险的HTML
 * @param {string} html - 需要检查的字符串
 * @returns {boolean} - 是否包含危险内容
 */
export function containsDangerousHtml(html) {
  if (!html || typeof html !== 'string') {
    return false
  }

  const dangerousPatterns = [
    /<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi,
    /javascript:/gi,
    /on\w+\s*=/gi,
    /<iframe\b[^<]*(?:(?!<\/iframe>)<[^<]*)*<\/iframe>/gi,
    /<object\b[^<]*(?:(?!<\/object>)<[^<]*)*<\/object>/gi,
    /<embed\b[^<]*(?:(?!<\/embed>)<[^<]*)*<\/embed>/gi,
  ]

  return dangerousPatterns.some(pattern => pattern.test(html))
}

/**
 * 截断文本
 * @param {string} text - 原始文本
 * @param {number} maxLength - 最大长度
 * @returns {string} - 截断后的文本
 */
export function truncateText(text, maxLength = 100) {
  if (!text || text.length <= maxLength) {
    return text
  }
  return text.substring(0, maxLength) + '...'
}

export default {
  sanitizeHtml,
  sanitizeText,
  containsDangerousHtml,
  truncateText
}
