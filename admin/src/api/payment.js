import request from './request'

// 获取支付配置
export function getPaymentConfig() {
  return request({
    url: '/payment/config',
    method: 'get'
  })
}

// 保存支付配置
export function savePaymentConfig(data) {
  return request({
    url: '/payment/config',
    method: 'post',
    data
  })
}

// 获取充值订单列表
export function getRechargeOrders(params, options = {}) {
  return request({
    url: '/payment/orders',
    method: 'get',
    params,
    ...options
  })
}

// 获取订单详情
export function getOrderDetail(orderNo, options = {}) {
  return request({
    url: `/payment/orders/${orderNo}`,
    method: 'get',
    ...options
  })
}

// 更新订单状态
export function updateOrderStatus(orderNo, status) {
  return request({
    url: `/payment/orders/${orderNo}/status`,
    method: 'put',
    data: { status }
  })
}

// 订单退款
export function refundOrder(orderNo, data, options = {}) {
  return request({
    url: `/payment/orders/${orderNo}/refund`,
    method: 'post',
    data,
    ...options
  })
}

// 获取充值统计
export function getRechargeStats(params, options = {}) {
  return request({
    url: '/payment/stats',
    method: 'get',
    params,
    ...options
  })
}

// 获取VIP订单列表
export function getVipOrders(params, options = {}) {
  return request({
    url: '/order',
    method: 'get',
    params,
    ...options
  })
}

// 获取VIP订单详情
export function getVipOrderDetail(id, options = {}) {
  return request({
    url: `/order/${id}`,
    method: 'get',
    ...options
  })
}

// VIP订单退款
export function refundVipOrder(data, options = {}) {
  return request({
    url: '/order/refund',
    method: 'post',
    data,
    ...options
  })
}

// 获取VIP套餐列表
export function getVipPackages() {
  return request({
    url: '/vip-packages/list',
    method: 'get'
  })
}

// 获取VIP套餐详情
export function getVipPackageDetail(id) {
  return request({
    url: `/vip-packages/detail/${id}`,
    method: 'get'
  })
}

// 保存VIP套餐
export function saveVipPackage(data) {
  return request({
    url: '/vip-packages/save',
    method: 'post',
    data
  })
}

// 删除VIP套餐
export function deleteVipPackage(id) {
  return request({
    url: `/vip-packages/${id}`,
    method: 'delete'
  })
}

// 批量更新VIP套餐状态
export function batchUpdateVipPackageStatus(data) {
  return request({
    url: '/vip-packages/batch-status',
    method: 'post',
    data
  })
}

// 更新VIP套餐排序
export function updateVipPackageSort(data) {
  return request({
    url: '/vip-packages/update-sort',
    method: 'post',
    data
  })
}

// 获取VIP套餐统计
export function getVipPackagesStats() {
  return request({
    url: '/vip-packages/stats',
    method: 'get'
  })
}

// 手动补单
export function manualCompleteOrder(orderNo, options = {}) {
  return request({
    url: `/payment/orders/${orderNo}/complete`,
    method: 'post',
    ...options
  })
}
