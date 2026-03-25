const { chromium } = require('/usr/local/node/lib/node_modules/playwright');
const path = require('path');
const fs = require('fs');

const BASE_URL = 'http://localhost:5173';
const OUT_DIR = '/data/workspace/taichu/screenshots';

const pages = [
  { name: 'home', path: '/', title: '首页' },
  { name: 'bazi', path: '/bazi', title: '八字排盘' },
  { name: 'tarot', path: '/tarot', title: '塔罗占卜' },
  { name: 'liuyao', path: '/liuyao', title: '六爻占卜' },
  { name: 'daily', path: '/daily', title: '每日运势' },
  { name: 'hehun', path: '/hehun', title: '八字合婚' },
];

(async () => {
  if (!fs.existsSync(OUT_DIR)) fs.mkdirSync(OUT_DIR, { recursive: true });

  const browser = await chromium.launch({
    args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage'],
    headless: true,
  });

  // 移动端视口（375x812 iPhone X）
  const mobileContext = await browser.newContext({
    viewport: { width: 390, height: 844 },
    deviceScaleFactor: 2,
  });

  // 桌面端视口
  const desktopContext = await browser.newContext({
    viewport: { width: 1440, height: 900 },
    deviceScaleFactor: 1,
  });

  for (const page of pages) {
    console.log(`\n截图: ${page.title} (${page.path})`);

    // 移动端截图
    const mobilePage = await mobileContext.newPage();
    const mobileErrors = [];
    mobilePage.on('console', msg => {
      if (msg.type() === 'error') mobileErrors.push(msg.text());
    });
    try {
      await mobilePage.goto(BASE_URL + page.path, { waitUntil: 'domcontentloaded', timeout: 20000 });
      // 等待 Vue 渲染完成
      await mobilePage.waitForTimeout(3000);
      // 等待 #app 有内容
      await mobilePage.waitForFunction(() => {
        const app = document.getElementById('app');
        return app && app.children.length > 0;
      }, { timeout: 10000 });
      await mobilePage.waitForTimeout(1000);
      
      const bodyHeight = await mobilePage.evaluate(() => document.body.scrollHeight);
      console.log(`  移动端页面高度: ${bodyHeight}px`);
      
      await mobilePage.screenshot({
        path: path.join(OUT_DIR, `${page.name}-mobile.png`),
        fullPage: true,
      });
      console.log(`  ✅ 移动端截图完成`);
      if (mobileErrors.length > 0) {
        console.log(`  ⚠️  控制台错误: ${mobileErrors.slice(0, 3).join(' | ')}`);
      }
    } catch (e) {
      console.log(`  ❌ 移动端截图失败: ${e.message}`);
    } finally {
      await mobilePage.close();
    }

    // 桌面端截图
    const desktopPage = await desktopContext.newPage();
    try {
      await desktopPage.goto(BASE_URL + page.path, { waitUntil: 'domcontentloaded', timeout: 20000 });
      await desktopPage.waitForTimeout(3000);
      await desktopPage.waitForFunction(() => {
        const app = document.getElementById('app');
        return app && app.children.length > 0;
      }, { timeout: 10000 });
      await desktopPage.waitForTimeout(1000);
      
      const bodyHeight = await desktopPage.evaluate(() => document.body.scrollHeight);
      console.log(`  桌面端页面高度: ${bodyHeight}px`);
      
      await desktopPage.screenshot({
        path: path.join(OUT_DIR, `${page.name}-desktop.png`),
        fullPage: true,
      });
      console.log(`  ✅ 桌面端截图完成`);
    } catch (e) {
      console.log(`  ❌ 桌面端截图失败: ${e.message}`);
    } finally {
      await desktopPage.close();
    }
  }

  await browser.close();
  console.log('\n🎉 所有截图完成！保存在:', OUT_DIR);
  const files = fs.readdirSync(OUT_DIR);
  console.log('文件列表:', files.join(', '));
})();
