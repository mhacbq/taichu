const { chromium } = require('/usr/local/node/lib/node_modules/playwright');
const path = require('path');
const fs = require('fs');

const BASE_URL = 'http://localhost:5173';
const OUT_DIR = '/data/workspace/taichu/screenshots';

(async () => {
  if (!fs.existsSync(OUT_DIR)) fs.mkdirSync(OUT_DIR, { recursive: true });

  const browser = await chromium.launch({
    args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage'],
    headless: true,
  });

  const context = await browser.newContext({
    viewport: { width: 390, height: 844 },
    deviceScaleFactor: 2,
  });

  const page = await context.newPage();
  
  const errors = [];
  const logs = [];
  page.on('console', msg => {
    logs.push(`[${msg.type()}] ${msg.text()}`);
    if (msg.type() === 'error') errors.push(msg.text());
  });
  page.on('pageerror', err => errors.push(`PAGE ERROR: ${err.message}`));
  
  await page.goto(BASE_URL + '/', { waitUntil: 'domcontentloaded', timeout: 20000 });
  await page.waitForTimeout(5000);
  
  // 获取 DOM 状态
  const appContent = await page.evaluate(() => {
    const app = document.getElementById('app');
    return {
      childCount: app ? app.children.length : -1,
      innerHTML: app ? app.innerHTML.substring(0, 500) : 'no app',
      bodyText: document.body.innerText.substring(0, 200),
    };
  });
  
  console.log('App 子元素数量:', appContent.childCount);
  console.log('App innerHTML 前500字符:', appContent.innerHTML);
  console.log('Body 文本:', appContent.bodyText);
  console.log('\n控制台错误:');
  errors.forEach(e => console.log(' -', e));
  console.log('\n控制台日志 (前10条):');
  logs.slice(0, 10).forEach(l => console.log(' -', l));
  
  // 直接截图
  await page.screenshot({
    path: path.join(OUT_DIR, 'debug-home.png'),
    fullPage: true,
  });
  console.log('\n截图已保存: debug-home.png');
  
  await browser.close();
})();
