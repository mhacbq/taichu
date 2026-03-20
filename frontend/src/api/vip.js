import request from './request'

/**
 * 获取VIP套餐列表
 */
export const getVipPackages = () => {
  return request({
    url: '/vip/packages',
    method: 'get'
  })
}

/**
 * 购买VIP套餐
 * @param {Object} data - 购买数据
 * @param {number} data.package_id - 套餐ID
 * @param {string} data.payment_method - 支付方式：alipay, wechat
 */
export const purchaseVip = (data) => {
  return request({
    url: '/vip/purchase',
    method: 'post',
    data
  })
}

/**
 * 获取用户VIP状态
 */
export const getUserVipStatus = () => {
  return request({
    url: '/vip/status',
    method: 'get'
  })
}

/**
 * 获取VIP购买记录
 */
export const getVipRecords = (params) => {
  return request({
    url: '/vip/records',
    method: 'get',
    params
  })
}
