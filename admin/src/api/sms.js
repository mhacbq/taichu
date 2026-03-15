import request from './request'

// 获取短信配置
export function getSmsConfig() {
  return request({
    url: '/admin/sms/config',
    method: 'get'
  })
}

// 保存短信配置
export function saveSmsConfig(data) {
  return request({
    url: '/admin/sms/config',
    method: 'post',
    data
  })
}

// 测试短信发送
export function testSmsSend(data) {
  return request({
    url: '/admin/sms/test',
    method: 'post',
    data
  })
}

// 获取短信统计
export function getSmsStats(params) {
  return request({
    url: '/admin/sms/stats',
    method: 'get',
    params
  })
}

// 获取短信发送记录
export function getSmsRecords(params) {
  return request({
    url: '/admin/sms/records',
    method: 'get',
    params
  })
}
