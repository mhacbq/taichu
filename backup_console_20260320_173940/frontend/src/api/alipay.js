import request from './request'

/**
 * 支付宝支付API
 */

/**
 * 创建支付宝订单（PC网站支付）
 * @param {Object} data - 订单数据
 * @param {number} data.amount - 充值金额
 */
export const createAlipayOrder = (data) => {
  return request({
    url: '/api/alipay/create-order',
    method: 'post',
    data
  })
}

/**
 * 创建支付宝手机网站支付
 * @param {Object} data - 订单数据
 * @param {number} data.amount - 充值金额
 */
export const createAlipayMobileOrder = (data) => {
  return request({
    url: '/api/alipay/create-mobile-order',
    method: 'post',
    data
  })
}

/**
 * 查询支付宝订单状态
 * @param {string} orderNo - 订单号
 */
export const queryAlipayOrder = (orderNo) => {
  return request({
    url: '/api/alipay/query-order',
    method: 'get',
    params: { order_no: orderNo }
  })
}
