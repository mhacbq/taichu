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
const TEST_PHONE = process.argv[3] || '13800138000';
const TEST_SMS_CODE = process.argv[4] || '123456';
const ADMIN_USER = process.argv[5] || 'admin';
const ADMIN_PASS = process.argv[6] || 'admin123';

// ============ 工具函数 ============
let userToken = '';
let adminToken = '';
const results = { pass: [], warn: [], fail: [], slow: [] };
const startTime = Date.now();

// 支持重试的 HTTP 请求（网络错误自动重试1次）
function request(method, path, body, token, retries = 1) {
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
    req.on('error', async (e) => {
      if (retries > 0) {
        // 等待 500ms 后重试
        await new Promise(r => setTimeout(r, 500));
        resolve(request(method, path, body, token, retries - 1));
      } else {
        resolve({ status: 0, error: e.message });
      }
    });
    req.on('timeout', () => {
      req.destroy();
      resolve({ status: 0, error: 'timeout' });
    });
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

// 检查接口：code===0 且有 data，附带耗时统计
async function checkApi(label, method, path, body, token, extraCheck) {
  const t0 = Date.now();
  const r = await request(method, path, body, token);
  const elapsed = Date.now() - t0;
  const timeTag = elapsed > 2000 ? ` [⏱️ ${elapsed}ms]` : '';

  if (elapsed > 2000) {
    results.slow.push(`⏱️  ${label} — ${elapsed}ms`);
  }

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
  results.pass.push(`✅ ${label}${timeTag}`);
  return body2.data;
}

// ============ 测试用例 ============

async function testPublicApis() {
  console.log('\n📋 [1/5] 公开接口检查（并行）...');

  // 所有公开接口无依赖，并行执行
  await Promise.all([
    checkApi('健康检查 /api/health', 'GET', '/api/health', null, null,
      d => d && d.status === 'ok' ? true : 'status 字段缺失'),
    checkApi('首页统计 /api/stats/home', 'GET', '/api/stats/home', null, null,
      d => d && typeof d === 'object' ? true : 'data 为空'),
    checkApi('积分规则 /api/points/rules', 'GET', '/api/points/rules', null, null,
      d => Array.isArray(d) ? true : '应返回数组'),
    checkApi('VIP套餐 /api/vip/packages', 'GET', '/api/vip/packages', null, null,
      d => Array.isArray(d) ? true : '应返回数组'),
    checkApi('客户端配置 /api/config/client', 'GET', '/api/config/client', null, null,
      d => d && typeof d === 'object' ? true : 'data 为空'),
    checkApi('功能开关 /api/config/features', 'GET', '/api/config/features', null, null),
    checkApi('FAQ公开 /api/site/faqs', 'GET', '/api/site/faqs', null, null,
      d => Array.isArray(d) ? true : '应返回数组'),
    checkApi('首页内容 /api/site/home', 'GET', '/api/site/home', null, null,
      d => d && typeof d === 'object' ? true : 'data 为空'),
    checkApi('用户评价 /api/site/testimonials', 'GET', '/api/site/testimonials', null, null),
    checkApi('塔罗牌阵 /api/site/spreads', 'GET', '/api/site/spreads', null, null,
      d => Array.isArray(d) ? true : '应返回数组'),
    checkApi('SEO配置 /api/seo/active-configs', 'GET', '/api/seo/active-configs', null, null),
  ]);
}

async function testAuth() {
  console.log('\n🔐 [2/5] 认证流程检查...');

  // 短信和管理后台登录可并行
  const [smsData, adminData] = await Promise.all([
    checkApi('发送短信验证码', 'POST', '/api/sms/send-code',
      { phone: TEST_PHONE, type: 'login' }, null,
      d => d !== null && d !== undefined ? true : 'data 为空'),
    checkApi('管理后台登录', 'POST', '/api/maodou/auth/login',
      { username: ADMIN_USER, password: ADMIN_PASS }, null,
      d => d && d.token ? true : 'token 字段缺失'),
  ]);

  // 前台登录依赖短信（串行）
  const loginData = await checkApi('前台登录（手机号+验证码）', 'POST', '/api/auth/phone-login',
    { phone: TEST_PHONE, code: TEST_SMS_CODE }, null,
    d => d && d.token ? true : 'token 字段缺失');

  if (loginData && loginData.token) {
    userToken = loginData.token;
    check('前台 Token 获取成功', !!userToken);
  } else {
    warn('前台登录失败，后续需要 JWT 的接口将跳过', `手机号: ${TEST_PHONE} 验证码: ${TEST_SMS_CODE}`);
  }

  if (adminData && adminData.token) {
    adminToken = adminData.token;
    check('管理后台 Token 获取成功', !!adminToken);
  } else {
    warn('管理后台登录失败，后台接口将跳过', `用户名: ${ADMIN_USER}`);
  }
}

async function testUserApis() {
  if (!userToken) { console.log('\n⏭️  [3/5] 跳过用户接口（未登录）'); return; }
  console.log('\n👤 [3/5] 用户核心功能检查（并行）...');

  // 所有用户查询接口无依赖，并行执行
  await Promise.all([
    checkApi('用户信息 /api/auth/userinfo', 'GET', '/api/auth/userinfo', null, userToken,
      d => {
        if (!d) return 'data 为空';
        if (!d.id) return 'id 字段缺失';
        if (!d.phone && !d.username) return 'phone/username 字段缺失';
        return true;
      }),
    checkApi('积分余额 /api/points/balance', 'GET', '/api/points/balance', null, userToken,
      d => d !== null && d !== undefined ? true : 'data 为空'),
    checkApi('积分历史 /api/points/history', 'GET', '/api/points/history', null, userToken,
      d => Array.isArray(d) || (d && Array.isArray(d.list)) ? true : '应返回数组或含 list 字段'),
    checkApi('每日运势 /api/daily/fortune', 'GET', '/api/daily/fortune', null, userToken,
      d => {
        if (!d) return 'data 为空';
        if (!d.content && !d.summary) return 'content/summary 字段缺失';
        return true;
      }),
    checkApi('每日运气 /api/daily/luck', 'GET', '/api/daily/luck', null, userToken),
    checkApi('签到状态 /api/daily/checkin-status', 'GET', '/api/daily/checkin-status', null, userToken,
      d => d !== null && d !== undefined ? true : 'data 为空'),
    checkApi('八字历史 /api/paipan/history', 'GET', '/api/paipan/history', null, userToken,
      d => Array.isArray(d) || (d && Array.isArray(d.list)) ? true : '应返回数组或含 list 字段'),
    checkApi('塔罗历史 /api/tarot/history', 'GET', '/api/tarot/history', null, userToken,
      d => Array.isArray(d) || (d && Array.isArray(d.list)) ? true : '应返回数组或含 list 字段'),
    checkApi('六爻历史 /api/liuyao/history', 'GET', '/api/liuyao/history', null, userToken,
      d => Array.isArray(d) || (d && Array.isArray(d.list)) ? true : '应返回数组或含 list 字段'),
    checkApi('合婚历史 /api/hehun/history', 'GET', '/api/hehun/history', null, userToken,
      d => Array.isArray(d) || (d && Array.isArray(d.list)) ? true : '应返回数组或含 list 字段'),
    checkApi('VIP状态 /api/vip/status', 'GET', '/api/vip/status', null, userToken,
      d => d !== null && d !== undefined ? true : 'data 为空'),
    checkApi('VIP信息 /api/vip/info', 'GET', '/api/vip/info', null, userToken),
    checkApi('VIP权益 /api/vip/benefits', 'GET', '/api/vip/benefits', null, userToken),
    checkApi('充值选项 /api/payment/options', 'GET', '/api/payment/options', null, userToken,
      d => Array.isArray(d) ? true : '应返回数组'),
    checkApi('任务列表 /api/tasks/list', 'GET', '/api/tasks/list', null, userToken,
      d => Array.isArray(d) || (d && Array.isArray(d.list)) ? true : '应返回数组或含 list 字段'),
    checkApi('通知列表 /api/notifications', 'GET', '/api/notifications', null, userToken),
    checkApi('邀请信息 /api/share/invite-info', 'GET', '/api/share/invite-info', null, userToken),
  ]);
}

async function testCoreFeatures() {
  if (!userToken) { console.log('\n⏭️  [4/5] 跳过核心功能（未登录）'); return; }
  console.log('\n⚡ [4/5] 核心业务功能检查（并行）...');

  await Promise.all([
    checkApi('八字排盘 /api/paipan/bazi', 'POST', '/api/paipan/bazi', {
      birthDate: '1990-06-15 12:00', gender: 'male',
      location: '', calendarType: 'solar'
    }, userToken, d => {
      if (!d) return 'data 为空';
      if (!d.year_pillar && !d.yearPillar && !d.bazi) return '八字柱信息缺失';
      return true;
    }),
    checkApi('合婚定价 /api/hehun/pricing', 'GET', '/api/hehun/pricing', null, userToken,
      d => d && (d.price !== undefined || d.points !== undefined) ? true : 'price/points 字段缺失'),
    checkApi('六爻定价 /api/liuyao/pricing', 'GET', '/api/liuyao/pricing', null, userToken,
      d => d && (d.price !== undefined || d.points !== undefined) ? true : 'price/points 字段缺失'),
    checkApi('年运积分 /api/fortune/points-cost', 'GET', '/api/fortune/points-cost', null, userToken,
      d => d !== null && d !== undefined ? true : 'data 为空'),
    checkApi('塔罗抽牌 /api/tarot/draw', 'POST', '/api/tarot/draw',
      { spread: 'single' }, userToken,
      d => {
        if (!d) return 'data 为空';
        if (!d.cards && !d.card) return 'cards/card 字段缺失';
        return true;
      }),
    checkApi('每日签到 /api/daily/checkin', 'POST', '/api/daily/checkin', {}, userToken),
  ]);
}

async function testAdminApis() {
  if (!adminToken) { console.log('\n⏭️  [5/5] 跳过管理后台（未登录）'); return; }
  console.log('\n🔧 [5/5] 管理后台关键接口检查（并行）...');

  await Promise.all([
    // 仪表盘
    checkApi('仪表盘统计', 'GET', '/api/maodou/dashboard/statistics', null, adminToken,
      d => d && typeof d === 'object' ? true : 'data 为空'),
    checkApi('仪表盘实时数据', 'GET', '/api/maodou/dashboard/realtime', null, adminToken),

    // 用户管理
    checkApi('用户列表', 'GET', '/api/maodou/users', null, adminToken,
      d => Array.isArray(d) || (d && Array.isArray(d.list)) ? true : '应返回数组或含 list 字段'),

    // 积分管理
    checkApi('积分规则管理', 'GET', '/api/maodou/points/rules', null, adminToken,
      d => Array.isArray(d) ? true : '应返回数组'),
    checkApi('积分记录', 'GET', '/api/maodou/points/records', null, adminToken),

    // VIP套餐
    checkApi('VIP套餐列表', 'GET', '/api/maodou/vip-packages/list', null, adminToken,
      d => Array.isArray(d) || (d && Array.isArray(d.list)) ? true : '应返回数组或含 list 字段'),

    // 支付管理
    checkApi('支付订单', 'GET', '/api/maodou/payment/orders', null, adminToken,
      d => Array.isArray(d) || (d && Array.isArray(d.list)) ? true : '应返回数组或含 list 字段'),
    checkApi('支付配置', 'GET', '/api/maodou/payment/config', null, adminToken),
    checkApi('支付统计', 'GET', '/api/maodou/payment/stats', null, adminToken),

    // AI管理
    checkApi('AI配置', 'GET', '/api/maodou/ai/config', null, adminToken),
    checkApi('AI提示词列表', 'GET', '/api/maodou/ai-prompts/list', null, adminToken,
      d => Array.isArray(d) || (d && Array.isArray(d.list)) ? true : '应返回数组或含 list 字段'),
    checkApi('AI提示词类型（静态路由测试）', 'GET', '/api/maodou/ai-prompts/types', null, adminToken,
      d => Array.isArray(d) ? true : '应返回数组'),

    // SEO管理
    checkApi('SEO页面类型（静态路由测试）', 'GET', '/api/maodou/seo/page-types', null, adminToken,
      d => Array.isArray(d) ? true : '应返回数组'),

    // 内容管理
    checkApi('八字内容记录', 'GET', '/api/maodou/content/bazi', null, adminToken),
    checkApi('塔罗内容记录', 'GET', '/api/maodou/content/tarot', null, adminToken),
    checkApi('每日运势管理', 'GET', '/api/maodou/content/daily', null, adminToken),
    checkApi('塔罗牌管理', 'GET', '/api/maodou/tarot-cards', null, adminToken),

    // 系统管理
    checkApi('系统设置', 'GET', '/api/maodou/system/settings', null, adminToken,
      d => d && typeof d === 'object' ? true : 'data 为空'),
    checkApi('短信配置', 'GET', '/api/maodou/sms/config', null, adminToken),
    checkApi('操作日志', 'GET', '/api/maodou/logs/operation', null, adminToken),

    // 订单套餐
    checkApi('订单套餐（静态路由测试）', 'GET', '/api/maodou/order/packages', null, adminToken,
      d => Array.isArray(d) ? true : '应返回数组'),

    // 反馈与邀请
    checkApi('反馈列表', 'GET', '/api/maodou/feedback', null, adminToken,
      d => Array.isArray(d) || (d && Array.isArray(d.list)) ? true : '应返回数组或含 list 字段'),
    checkApi('邀请管理', 'GET', '/api/maodou/invites', null, adminToken),
  ]);
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

  const totalElapsed = ((Date.now() - startTime) / 1000).toFixed(1);

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

  if (results.slow.length > 0) {
    console.log(`\n🐢 慢接口（响应 >2s，共 ${results.slow.length} 个）`);
    results.slow.forEach(r => console.log('  ' + r));
  }

  if (results.pass.length > 0) {
    console.log(`\n🟢 通过（${results.pass.length} 个）`);
    results.pass.forEach(r => console.log('  ' + r));
  }

  console.log('\n' + '='.repeat(60));
  const total = results.pass.length + results.warn.length + results.fail.length;
  console.log(`总计: ${total} 项 | 通过: ${results.pass.length} | 警告: ${results.warn.length} | 失败: ${results.fail.length} | 总耗时: ${totalElapsed}s`);
  console.log('='.repeat(60));

  process.exit(results.fail.length > 0 ? 1 : 0);
}

main().catch(e => { console.error('脚本异常:', e); process.exit(1); });