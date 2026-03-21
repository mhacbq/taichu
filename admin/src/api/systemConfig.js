import request from './request'

/**
 * 获取系统配置列表
 */
export function getSystemConfigs(params) {
  return request({
    url: '/api/admin/system-config',
    method: 'get',
    params
  })
}

/**
 * 保存系统配置
 */
export function saveSystemConfigs(data) {
  return request({
    url: '/api/admin/system-config/save',
    method: 'post',
    data
  })
}

/**
 * 测试支付配置
 */
export function testPaymentConfig(data) {
  return request({
    url: '/api/admin/system-config/test-payment',
    method: 'post',
    data
  })
}

/**
 * 测试AI服务配置
 */
export function testAIConfig() {
  return request({
    url: '/api/admin/system-config/test-ai',
    method: 'post'
  })
}

/**
 * 导出配置
 */
export function exportSystemConfig(params) {
  return request({
    url: '/api/admin/system-config/export',
    method: 'get',
    params,
    responseType: 'blob'
  })
}

// 配置分组常量
export const CONFIG_GROUPS = {
  PAYMENT: 'payment',
  AI: 'ai',
  PUSH: 'push',
  SMS: 'sms'
}

// 配置分组名称映射
export const CONFIG_GROUP_NAMES = {
  [CONFIG_GROUPS.PAYMENT]: '支付配置',
  [CONFIG_GROUPS.AI]: 'AI服务配置',
  [CONFIG_GROUPS.PUSH]: '推送服务配置',
  [CONFIG_GROUPS.SMS]: '短信服务配置'
}

// 支付配置字段定义
export const PAYMENT_CONFIG_FIELDS = {
  wechat: [
    { key: 'wechat_mch_id', label: '微信支付商户号', type: 'text', required: true },
    { key: 'wechat_app_id', label: '微信应用ID', type: 'text', required: true },
    { key: 'wechat_api_key', label: '微信支付API密钥', type: 'password', required: true, sensitive: true },
    { key: 'wechat_api_cert', label: '微信支付证书', type: 'textarea', required: false, sensitive: true, placeholder: '请粘贴apiclient_cert.pem的内容' },
    { key: 'wechat_api_key_pem', label: '微信支付私钥', type: 'textarea', required: false, sensitive: true, placeholder: '请粘贴apiclient_key.pem的内容' },
    { key: 'wechat_notify_url', label: '支付回调通知URL', type: 'text', required: true },
    { key: 'wechat_is_enabled', label: '启用微信支付', type: 'switch', required: false }
  ],
  alipay: [
    { key: 'alipay_app_id', label: '支付宝应用ID', type: 'text', required: true },
    { key: 'alipay_private_key', label: '支付宝应用私钥', type: 'password', required: true, sensitive: true },
    { key: 'alipay_public_key', label: '支付宝公钥', type: 'textarea', required: true, sensitive: true, placeholder: '请粘贴支付宝公钥' },
    { key: 'alipay_notify_url', label: '异步通知URL', type: 'text', required: true },
    { key: 'alipay_return_url', label: '同步跳转URL', type: 'text', required: true },
    { key: 'alipay_is_enabled', label: '启用支付宝支付', type: 'switch', required: false }
  ]
}

// AI配置字段定义
export const AI_CONFIG_FIELDS = [
  { key: 'ai_api_key', label: 'AI服务API密钥', type: 'password', required: true, sensitive: true },
  { key: 'ai_api_url', label: 'AI服务API地址', type: 'text', required: true },
  { key: 'ai_model', label: 'AI模型名称', type: 'text', required: true },
  { key: 'ai_max_tokens', label: '最大输出Token数', type: 'number', required: true, min: 1, max: 8192 },
  { key: 'ai_timeout', label: '请求超时时间(秒)', type: 'number', required: true, min: 10, max: 300 },
  { key: 'ai_enable_streaming', label: '启用流式输出', type: 'switch', required: false },
  { key: 'ai_enable_thinking', label: '启用思维链', type: 'switch', required: false },
  { key: 'ai_cost_points', label: 'AI解盘消耗积分', type: 'number', required: true, min: 0 },
  { key: 'ai_enable_bazi', label: '启用八字分析', type: 'switch', required: false },
  { key: 'ai_enable_tarot', label: '启用塔罗分析', type: 'switch', required: false },
  { key: 'ai_is_enabled', label: '启用AI服务', type: 'switch', required: false }
]

// 推送配置字段定义
export const PUSH_CONFIG_FIELDS = [
  { key: 'push_provider', label: '推送服务提供商', type: 'select', required: false, options: [
    { label: '不使用', value: '' },
    { label: '极光推送', value: 'jpush' },
    { label: 'FCM', value: 'fcm' },
    { label: 'Webhook', value: 'webhook' }
  ]},
  { key: 'jpush_app_key', label: '极光推送AppKey', type: 'text', required: false, sensitive: true },
  { key: 'jpush_master_secret', label: '极光推送MasterSecret', type: 'password', required: false, sensitive: true },
  { key: 'fcm_server_key', label: 'FCM服务端密钥', type: 'password', required: false, sensitive: true },
  { key: 'webhook_url', label: '自定义Webhook地址', type: 'text', required: false },
  { key: 'webhook_bearer', label: 'Webhook Bearer Token', type: 'password', required: false, sensitive: true },
  { key: 'push_is_enabled', label: '启用消息推送', type: 'switch', required: false }
]

// 短信配置字段定义
export const SMS_CONFIG_FIELDS = [
  { key: 'sms_test_mode', label: '测试模式', type: 'switch', required: false, description: '测试模式下使用固定验证码' },
  { key: 'sms_test_code', label: '测试验证码', type: 'text', required: false, description: '仅在测试模式下有效' },
  { key: 'tencent_secret_id', label: '腾讯云SecretId', type: 'password', required: false, sensitive: true },
  { key: 'tencent_secret_key', label: '腾讯云SecretKey', type: 'password', required: false, sensitive: true },
  { key: 'tencent_sdk_app_id', label: '腾讯云SDKAppId', type: 'text', required: false, sensitive: true },
  { key: 'tencent_sign_name', label: '短信签名', type: 'text', required: false },
  { key: 'tencent_template_code', label: '短信模板ID', type: 'text', required: false },
  { key: 'sms_is_enabled', label: '启用短信服务', type: 'switch', required: false }
]
