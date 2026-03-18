import axios from 'axios'
import request from './request'

const baseURL = import.meta.env.VITE_APP_BASE_API || '/api/admin'

function buildAuthHeaders() {
  const token = localStorage.getItem('admin-token') || ''
  return token ? { Authorization: `Bearer ${token}` } : {}
}

export function getStatistics(options = {}) {
  return request({
    url: '/dashboard/statistics',
    method: 'get',
    ...options
  })
}

export function getTrendData(params, options = {}) {
  return request({
    url: '/dashboard/trend',
    method: 'get',
    params,
    ...options
  })
}

export function getRealtimeData(params, options = {}) {
  return request({
    url: '/dashboard/realtime',
    method: 'get',
    params,
    ...options
  })
}

export function getChartData(type, params, options = {}) {
  return request({
    url: `/dashboard/chart/${type}`,
    method: 'get',
    params,
    ...options
  })
}

export function getPendingFeedback(options = {}) {
  return request({
    url: '/dashboard/pending-feedback',
    method: 'get',
    ...options
  })
}

export function refreshDashboardStats(data = {}, options = {}) {
  return request({
    url: '/dashboard/refresh-stats',
    method: 'post',
    data,
    ...options
  })
}


export function exportRealtimeDashboard(params = {}) {
  return axios({
    baseURL,
    url: '/dashboard/export-realtime',
    method: 'get',
    params,
    responseType: 'blob',
    headers: buildAuthHeaders()
  })
}
