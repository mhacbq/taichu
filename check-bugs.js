#!/usr/bin/env node
/**
 * 太初网站全自动 Bug 检查脚本
 * 用法: node check-bugs.js [base_url] [phone] [password]
 * 示例: node check-bugs.js http://localhost:8080 13800138000 123456
 */

const http = require('http');
const https = require('https');

// ============ 配置 ============
const BASE_URL = process.argv[2] || 'http://localhost:8080';
const TEST_PHONE = process.argv[3] || '13800138000';  // 测试手机号（测试模式下会自动注册）
const TEST_SMS_CODE = process.argv[4] || '123456';    // 测试验证码（SMS_TEST_MODE=true 时固定有效）
const ADMIN_USER = process.argv[5] || 'admin';
const ADMIN_PASS = process.argv[6] || 'admin123';

// ============ 工具函数 ============
let userToken = '';
let adminToken = '';
const results = { pass: [], warn: [], fail: [] };

function request(method, path, body, token) {
  return new Promise((resolve) => {
    const url = new URL(BASE_URL + path);
    const lib = url.protocol === 'https:' ? https : http;
    const bodyStr = body ? JSON.stringify(body) : '';
    const options = {
      hostname: url.hostname,
      port: url.port || (url.protocol === 'https:' ? 443 : 80),
      path: url.pathname + url.search,
      method,
      headers: {
        'Content-Type': 'application/json',
        'Content-Length': Buffer.byteLength(bodyStr),
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
      },
      timeout: 8000,
    };
    const req = lib.request(options, (res) => {
      let data = '';
      res.on('data', (chunk) => (data += chunk));
      res.on('end', () => {
        try {
          resolve({ status: res.statusCode, body: JSON.parse(data) });
        } catch {
          resolve({ status: res.statusCode, body: data });
        }
      });
    });
    req.on('error', (e) => resolve({ status: 0, error: e.message }));
    req.on('timeout', () => { req.destroy(); resolve({ status: 0, error: 'timeout' }); });
    if (bodyStr) req.write(bodyStr);
    req.end();
  });
}

function check(name, condition, detail = '') {
  if (condition) {
    results.pass.push(`✅ ${name}`);
  } else {
    results.fail.push(`❌ ${name}${detail ? ' — ' + detail : ''}`);
  }
}

function warn(name, detail = '') {
  results.warn.push(`⚠️  ${name}${detail ? ' — ' + detail : ''}`);
}

// 检查接口：code===0 且有 data
async function checkApi(label, method, path, body, token, extraCheck) {
  const r = await request(method, path, body, token);
  if (r.status === 0) {
    results.fail.push(`❌ ${label} — 网络错误: ${r.error}`);
    return null;
  }
  if (r.status === 404) {
    results.fail.push(`❌ ${label} — 404 路由未注册`);
    return null;
  }
  if (r.status === 401) {
    results.fail.push(`❌ ${label} — 401 认证失败`);
    return null;
  }
  if (r.status === 500) {
    results.fail.push(`❌ ${label} — 500 服务器错误`);
    return null;
  }
  const body2 = r.body;
  if (typeof body2 !== 'object' || body2 === null) {
    results.fail.push(`❌ ${label} — 返回非 JSON`);
    return null;
  }
  // 核心检查：业务码必须是 0，不是 200
  if (body2.code === 200) {
    results.fail.push(`❌ ${label} — code=200（应为0），历史高频Bug`);
    return null;
  }
  if (body2.code !== 0) {
    // 区分"正常业务拒绝"和"真实错误"
    if ([1001, 1002, 4001, 4002, 4003].includes(body2.code)) {
      warn(label, `业务拒绝 code=${body2.code} msg=${body2.msg}`);
    } else {
      results.fail.push(`❌ ${label} — code=${body2.code} msg=${body2.msg}`);
    }
    return null;
  }
  if (extraCheck) {
    const extraResult = extraCheck(body2.data);
    if (extraResult !== true) {
      results.fail.push(`❌ ${label} — 字段缺失: ${extraResult}`);
      return body2.data;
    }
  }
  results.pass.push(`✅ ${label}`);
  return body2.data;
}

// ============ 测试用例 ============

async function testPublicApis() {
  console.log('\n📋 [1/5] 公开接口检查...');

  // 健康检查
  await checkApi('健康检查 /api/health', 'GET', '/api/health', null, null,
    d => d && d.status === 'ok' ? true : 'status 字段缺失');

  // 统计数据
  await checkApi('首页统计 /api/stats/home', 'GET', '/api/stats/home', null, null);

  // 积分规则（公开）
  await checkApi('积分规则 /api/points/rules', 'GET', '/api/points/rules', null, null);

  // VIP套餐（公开）
  await checkApi('VIP套餐 /api/vip/packages', 'GET', '/api/vip/packages', null, null);

  // 客户端配置
  await checkApi('客户端配置 /api/config/client', 'GET', '/api/config/client', null, null);

  // FAQ公开接口
  await checkApi('FAQ公开 /api/site/faqs', 'GET', '/api/site/faqs', null, null);

  // SEO配置
  await checkApi('SEO配置 /api/seo/active-configs', 'GET', '/api/seo/active-configs', null, null);
}

async function testAuth() {
  console.log('\n🔐 [2/5] 认证流程检查...');

  // 第一步：发送短信验证码（测试模式下会返回 test_code）
  const smsData = await checkApi('发送短信验证码', 'POST', '/api/sms/send-code',
    { phone: TEST_PHONE, type: 'login' }, null,
    d => d !== null && d !== undefined ? true : 'data 为空');

  // 第二步：用手机号 + 验证码登录（测试模式固定验证码 123456）
  const loginData = await checkApi('前台登录（手机号+验证码）', 'POST', '/api/auth/phone-login',
    { phone: TEST_PHONE, code: TEST_SMS_CODE }, null,
    d => d && d.token ? true : 'token 字段缺失');

  if (loginData && loginData.token) {
    userToken = loginData.token;
    check('前台 Token 获取成功', !!userToken);
  } else {
    warn('前台登录失败，后续需要 JWT 的接口将跳过', `手机号: ${TEST_PHONE} 验证码: ${TEST_SMS_CODE}`);
  }

  // 管理后台登录
  const adminData = await checkApi('管理后台登录', 'POST', '/api/maodou/auth/login',
    { username: ADMIN_USER, password: ADMIN_PASS }, null,
    d => d && d.token ? true : 'token 字段缺失');

  if (adminData && adminData.token) {
    adminToken = adminData.token;
    check('管理后台 Token 获取成功', !!adminToken);
  } else {
    warn('管理后台登录失败，后台接口将跳过', `用户名: ${ADMIN_USER}`);
  }
}

async function testUserApis() {
  if (!userToken) { console.log('\n⏭️  [3/5] 跳过用户接口（未登录）'); return; }
  console.log('\n👤 [3/5] 用户核心功能检查...');

  // 用户信息
  await checkApi('用户信息 /api/auth/userinfo', 'GET', '/api/auth/userinfo', null, userToken,
    d => d && d.id ? true : 'id 字段缺失');

  // 积分余额
  await checkApi('积分余额 /api/points/balance', 'GET', '/api/points/balance', null, userToken,
    d => d !== null && d !== undefined ? true : 'data 为空');

  // 积分历史
  await checkApi('积分历史 /api/points/history', 'GET', '/api/points/history', null, userToken);

  // 每日运势
  await checkApi('每日运势 /api/daily/fortune', 'GET', '/api/daily/fortune', null, userToken);

  // 签到状态
  await checkApi('签到状态 /api/daily/checkin-status', 'GET', '/api/daily/checkin-status', null, userToken);

  // 八字历史
  await checkApi('八字历史 /api/paipan/history', 'GET', '/api/paipan/history', null, userToken);

  // 塔罗历史
  await checkApi('塔罗历史 /api/tarot/history', 'GET', '/api/tarot/history', null, userToken);

  // 六爻历史
  await checkApi('六爻历史 /api/liuyao/history', 'GET', '/api/liuyao/history', null, userToken);

  // 合婚历史
  await checkApi('合婚历史 /api/hehun/history', 'GET', '/api/hehun/history', null, userToken);

  // VIP状态
  await checkApi('VIP状态 /api/vip/status', 'GET', '/api/vip/status', null, userToken);

  // 支付选项
  await checkApi('充值选项 /api/payment/options', 'GET', '/api/payment/options', null, userToken);

  // 任务列表
  await checkApi('任务列表 /api/tasks/list', 'GET', '/api/tasks/list', null, userToken);

  // 通知列表
  await checkApi('通知列表 /api/notifications', 'GET', '/api/notifications', null, userToken);
}

async function testCoreFeatures() {
  if (!userToken) { console.log('\n⏭️  [4/5] 跳过核心功能（未登录）'); return; }
  console.log('\n⚡ [4/5] 核心业务功能检查...');

  // 八字排盘（真实请求）
  await checkApi('八字排盘 /api/paipan/bazi', 'POST', '/api/paipan/bazi', {
    name: '测试用户', gender: 1,
    birth_year: 1990, birth_month: 6, birth_day: 15, birth_hour: 12,
    birth_minute: 0, calendar_type: 'solar'
  }, userToken, d => d ? true : 'data 为空');

  // 合婚定价
  await checkApi('合婚定价 /api/hehun/pricing', 'GET', '/api/hehun/pricing', null, userToken);

  // 六爻定价
  await checkApi('六爻定价 /api/liuyao/pricing', 'GET', '/api/liuyao/pricing', null, userToken);

  // 年运积分消耗
  await checkApi('年运积分 /api/fortune/points-cost', 'GET', '/api/fortune/points-cost', null, userToken);

  // 塔罗抽牌
  await checkApi('塔罗抽牌 /api/tarot/draw', 'POST', '/api/tarot/draw',
    { spread_type: 'single' }, userToken);
}

async function testAdminApis() {
  if (!adminToken) { console.log('\n⏭️  [5/5] 跳过管理后台（未登录）'); return; }
  console.log('\n🔧 [5/5] 管理后台关键接口检查...');

  // 仪表盘
  await checkApi('仪表盘统计', 'GET', '/api/maodou/dashboard/statistics', null, adminToken);

  // 用户列表
  await checkApi('用户列表', 'GET', '/api/maodou/users', null, adminToken);

  // 积分规则
  await checkApi('积分规则管理', 'GET', '/api/maodou/points/rules', null, adminToken);

  // VIP套餐列表
  await checkApi('VIP套餐列表', 'GET', '/api/maodou/vip-packages/list', null, adminToken);

  // 支付订单
  await checkApi('支付订单', 'GET', '/api/maodou/payment/orders', null, adminToken);

  // AI配置
  await checkApi('AI配置', 'GET', '/api/maodou/ai/config', null, adminToken);

  // AI提示词列表
  await checkApi('AI提示词列表', 'GET', '/api/maodou/ai-prompts/list', null, adminToken);

  // AI提示词类型（历史上被动态路由拦截的接口）
  await checkApi('AI提示词类型（静态路由测试）', 'GET', '/api/maodou/ai-prompts/types', null, adminToken);

  // SEO页面类型（历史上被动态路由拦截的接口）
  await checkApi('SEO页面类型（静态路由测试）', 'GET', '/api/maodou/seo/page-types', null, adminToken);

  // 订单套餐（历史上被动态路由拦截的接口）
  await checkApi('订单套餐（静态路由测试）', 'GET', '/api/maodou/order/packages', null, adminToken);

  // 反馈列表
  await checkApi('反馈列表', 'GET', '/api/maodou/feedback', null, adminToken);

  // 系统配置
  await checkApi('系统配置', 'GET', '/api/maodou/system-config', null, adminToken);
}

// ============ 主流程 ============
async function main() {
  console.log('='.repeat(60));
  console.log('🔍 太初网站全自动 Bug 检查');
  console.log(`📡 目标: ${BASE_URL}`);
  console.log(`⏰ 时间: ${new Date().toLocaleString('zh-CN')}`);
  console.log('='.repeat(60));

  await testPublicApis();
  await testAuth();
  await testUserApis();
  await testCoreFeatures();
  await testAdminApis();

  // ============ 输出报告 ============
  console.log('\n' + '='.repeat(60));
  console.log('📊 巡查报告');
  console.log('='.repeat(60));

  if (results.fail.length > 0) {
    console.log(`\n🔴 严重问题（${results.fail.length} 个）`);
    results.fail.forEach(r => console.log('  ' + r));
  }

  if (results.warn.length > 0) {
    console.log(`\n🟡 警告（${results.warn.length} 个）`);
    results.warn.forEach(r => console.log('  ' + r));
  }

  if (results.pass.length > 0) {
    console.log(`\n🟢 通过（${results.pass.length} 个）`);
    results.pass.forEach(r => console.log('  ' + r));
  }

  console.log('\n' + '='.repeat(60));
  const total = results.pass.length + results.warn.length + results.fail.length;
  console.log(`总计: ${total} 项 | 通过: ${results.pass.length} | 警告: ${results.warn.length} | 失败: ${results.fail.length}`);
  console.log('='.repeat(60));

  process.exit(results.fail.length > 0 ? 1 : 0);
}

main().catch(e => { console.error('脚本异常:', e); process.exit(1); });
