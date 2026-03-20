import request from './request'

// 发送短信验证码
export function sendSmsCode(data) {
  return request.post('/sms/send-code', data)
}

// 验证短信验证码
export function verifySmsCode(data) {
  return request.post('/sms/verify-code', data)
}

// 手机号登录
export function phoneLogin(data) {
  return request.post('/auth/phone-login', data)
}

// 手机号注册
export function phoneRegister(data) {
  return request.post('/auth/phone-register', data)
}
