import request from './request'

// 获取支付配置
export function getPaymentConfig() {
  return request({
    url: '/admin/payment/config',
    method: 'get'
  })
}

// 保存支付配置
export function savePaymentConfig(data) {
  return request({
    url: '/admin/payment/config',
    method: 'post',
    data
  })
}

// 获取充值订单列表
export function getRechargeOrders(params) {
  return request({
    url: '/admin/payment/orders',
    method: 'get',
    params
  })
}

// 获取订单详情
export function getOrderDetail(orderNo) {
  return request({
    url: `/admin/payment/orders/${orderNo}`,
    method: 'get'
  })
}

// 更新订单状态
export function updateOrderStatus(orderNo, status) {
  return request({
    url: `/admin/payment/orders/${orderNo}/status`,
    method: 'put',
    data: { status }
  })
}

// 订单退款
export function refundOrder(orderNo, data) {
  return request({
    url: `/admin/payment/orders/${orderNo}/refund`,
    method: 'post',
    data
  })
}

// 获取充值统计
export function getRechargeStats(params) {
  return request({
    url: '/admin/payment/stats',
    method: 'get',
    params
  })
}

// 手动补单
export function manualCompleteOrder(orderNo) {
  return request({
    url: `/admin/payment/orders/${orderNo}/complete`,
    method: 'post'
  })
}
