import request from './request'

export function getKnowledgeArticles(params, options = {}) {
  return request({
    url: '/knowledge/articles',
    method: 'get',
    params,
    ...options
  })
}

export function getKnowledgeArticleDetail(id, options = {}) {
  return request({
    url: `/knowledge/articles/${id}`,
    method: 'get',
    ...options
  })
}

export function createKnowledgeArticle(data, options = {}) {
  return request({
    url: '/knowledge/articles',
    method: 'post',
    data,
    ...options
  })
}

export function updateKnowledgeArticle(id, data, options = {}) {
  return request({
    url: `/knowledge/articles/${id}`,
    method: 'put',
    data,
    ...options
  })
}

export function deleteKnowledgeArticle(id, options = {}) {
  return request({
    url: `/knowledge/articles/${id}`,
    method: 'delete',
    ...options
  })
}

export function getKnowledgeCategories(params, options = {}) {
  return request({
    url: '/knowledge/categories',
    method: 'get',
    params,
    ...options
  })
}

export function saveKnowledgeCategory(data, options = {}) {
  return request({
    url: '/knowledge/categories',
    method: 'post',
    data,
    ...options
  })
}

export function deleteKnowledgeCategory(id, options = {}) {
  return request({
    url: `/knowledge/categories/${id}`,
    method: 'delete',
    ...options
  })
}
