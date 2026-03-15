import request from './request'

/**
 * 内容编辑器 API
 * 支持可视化编辑的内容管理
 */

/**
 * 获取页面内容
 * @param {string} pageId - 页面ID
 */
export function getPage(pageId) {
  return request({
    url: `/content/page/${pageId}`,
    method: 'get'
  })
}

/**
 * 保存页面内容
 * @param {string} pageId - 页面ID
 * @param {Object} data - 页面数据
 */
export function savePage(pageId, data) {
  return request({
    url: `/content/page/${pageId}`,
    method: 'post',
    data
  })
}

/**
 * 自动保存页面（草稿）
 * @param {string} pageId - 页面ID
 * @param {Object} data - 页面数据
 */
export function autoSave(pageId, data) {
  return request({
    url: `/content/page/${pageId}/autosave`,
    method: 'post',
    data
  })
}

/**
 * 获取草稿
 * @param {string} pageId - 页面ID
 */
export function getDraft(pageId) {
  return request({
    url: `/content/page/${pageId}/draft`,
    method: 'get'
  })
}

/**
 * 获取页面版本历史
 * @param {string} pageId - 页面ID
 * @param {Object} params - 查询参数
 */
export function getVersions(pageId, params = {}) {
  return request({
    url: `/content/page/${pageId}/versions`,
    method: 'get',
    params
  })
}

/**
 * 恢复版本
 * @param {number} versionId - 版本ID
 */
export function restoreVersion(versionId) {
  return request({
    url: `/content/version/${versionId}/restore`,
    method: 'post'
  })
}

/**
 * 预览版本
 * @param {number} versionId - 版本ID
 */
export function previewVersion(versionId) {
  return request({
    url: `/content/version/${versionId}/preview`,
    method: 'get'
  })
}

/**
 * 导出页面
 * @param {string} pageId - 页面ID
 */
export function exportPage(pageId) {
  return request({
    url: `/content/page/${pageId}/export`,
    method: 'get'
  })
}

/**
 * 导入页面
 * @param {Object} data - 页面数据
 */
export function importPage(data) {
  return request({
    url: '/content/page/import',
    method: 'post',
    data
  })
}

/**
 * 获取所有页面列表
 * @param {Object} params - 查询参数
 */
export function getPages(params = {}) {
  return request({
    url: '/content/pages',
    method: 'get',
    params
  })
}

/**
 * 删除页面
 * @param {string} pageId - 页面ID
 */
export function deletePage(pageId) {
  return request({
    url: `/content/page/${pageId}`,
    method: 'delete'
  })
}

/**
 * 获取块配置
 * @param {string} type - 块类型
 */
export function getBlockConfig(type) {
  return request({
    url: `/content/block-config/${type}`,
    method: 'get'
  })
}

/**
 * 上传图片
 * @param {FormData} data - 表单数据
 */
export function uploadImage(data) {
  return request({
    url: '/upload/image',
    method: 'post',
    data,
    headers: {
      'Content-Type': 'multipart/form-data'
    }
  })
}

/**
 * 批量更新块
 * @param {string} pageId - 页面ID
 * @param {Array} blocks - 块数据
 */
export function updateBlocks(pageId, blocks) {
  return request({
    url: `/content/page/${pageId}/blocks`,
    method: 'put',
    data: { blocks }
  })
}

/**
 * 更新单个块
 * @param {string} pageId - 页面ID
 * @param {string} blockId - 块ID
 * @param {Object} data - 块数据
 */
export function updateBlock(pageId, blockId, data) {
  return request({
    url: `/content/page/${pageId}/block/${blockId}`,
    method: 'put',
    data
  })
}

/**
 * 删除块
 * @param {string} pageId - 页面ID
 * @param {string} blockId - 块ID
 */
export function deleteBlock(pageId, blockId) {
  return request({
    url: `/content/page/${pageId}/block/${blockId}`,
    method: 'delete'
  })
}

/**
 * 排序块
 * @param {string} pageId - 页面ID
 * @param {Array} blockIds - 块ID列表
 */
export function sortBlocks(pageId, blockIds) {
  return request({
    url: `/content/page/${pageId}/sort`,
    method: 'put',
    data: { blockIds }
  })
}