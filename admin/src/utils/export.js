/**
 * 数据导出工具函数
 */

/**
 * 导出JSON数据
 */
export function exportJson(data, filename = 'data.json') {
  const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' })
  downloadBlob(blob, filename)
}

/**
 * 导出CSV数据
 */
export function exportCsv(data, columns, filename = 'data.csv') {
  // 生成表头
  const headers = columns.map(col => col.label).join(',')
  
  // 生成数据行
  const rows = data.map(row => {
    return columns.map(col => {
      const value = getNestedValue(row, col.prop)
      // 处理包含逗号或换行符的值
      const cell = String(value || '').replace(/"/g, '""')
      if (cell.includes(',') || cell.includes('\n') || cell.includes('"')) {
        return `"${cell}"`
      }
      return cell
    }).join(',')
  }).join('\n')
  
  // 添加BOM以支持中文
  const bom = '\uFEFF'
  const csv = bom + headers + '\n' + rows
  
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8' })
  downloadBlob(blob, filename)
}

/**
 * 导出Excel数据（简单实现，使用CSV格式）
 */
export function exportExcel(data, columns, filename = 'data.xlsx') {
  // 实际项目中可以使用 xlsx 库
  // 这里使用CSV作为简单替代
  exportCsv(data, columns, filename.replace('.xlsx', '.csv'))
}

/**
 * 下载Blob文件
 */
function downloadBlob(blob, filename) {
  const url = window.URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = filename
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  window.URL.revokeObjectURL(url)
}

/**
 * 获取嵌套属性值
 */
function getNestedValue(obj, path) {
  if (!path) return ''
  const keys = path.split('.')
  let value = obj
  for (const key of keys) {
    value = value?.[key]
    if (value === undefined || value === null) return ''
  }
  return value
}

/**
 * 表格列配置转CSV列
 */
export function tableColumnsToCsvColumns(columns) {
  return columns
    .filter(col => col.prop && !col.type?.includes('selection') && col.prop !== 'operation')
    .map(col => ({
      prop: col.prop,
      label: col.label
    }))
}
