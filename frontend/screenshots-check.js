import { chromium } from 'playwright';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// 需要截图的页面列表
const pages = [
  { name: 'home', url: 'http://localhost:5173/', title: '首页' },
  { name: 'bazi', url: 'http://localhost:5173/bazi', title: '八字排盘' },
  { name: 'tarot', url: 'http://localhost:5173/tarot', title: '塔罗占卜' },
  { name: 'liuyao', url: 'http://localhost:5173/liuyao', title: '六爻占卜' },
  { name: 'daily', url: 'http://localhost:5173/daily', title: '每日运势' },
  { name: 'hehun', url: 'http://localhost:5173/hehun', title: '八字合婚' }
];

// 视口尺寸配置
const viewports = {
  desktop: { width: 1920, height: 1080 },
  mobile: { width: 375, height: 812 }
};

const screenshotsDir = path.join(__dirname, '../screenshots');

async function takeScreenshots() {
  console.log('🚀 开始视觉检查...\n');
  
  // 启动浏览器
  const browser = await chromium.launch({
    headless: true,
    args: ['--disable-gpu', '--no-sandbox']
  });

  const results = [];

  for (const viewportName of Object.keys(viewports)) {
    const viewport = viewports[viewportName];
    
    console.log(`\n📱 ${viewportName === 'desktop' ? '桌面端' : '移动端'}检查 (${viewport.width}x${viewport.height})`);
    console.log('='.repeat(60));

    const context = await browser.newContext({
      viewport,
      userAgent: viewportName === 'mobile' 
        ? 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15E148 Safari/604.1'
        : 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
    });

    const page = await context.newPage();

    for (const pageConfig of pages) {
      try {
        console.log(`  📄 检查: ${pageConfig.title}`);
        
        // 导航到页面
        await page.goto(pageConfig.url, { 
          waitUntil: 'networkidle',
          timeout: 30000
        });

        // 等待页面完全加载
        await page.waitForTimeout(2000);

        // 生成文件名
        const filename = `${pageConfig.name}-${viewportName}-${Date.now()}.png`;
        const filepath = path.join(screenshotsDir, filename);

        // 截取完整页面
        await page.screenshot({
          path: filepath,
          fullPage: true
        });

        console.log(`    ✅ 截图已保存: ${filename}`);

        // 检查控制台错误
        const consoleErrors = [];
        page.on('console', msg => {
          if (msg.type() === 'error') {
            consoleErrors.push(msg.text());
          }
        });

        results.push({
          page: pageConfig.title,
          viewport: viewportName,
          success: true,
          filename,
          errors: consoleErrors.length
        });

      } catch (error) {
        console.error(`    ❌ 错误: ${error.message}`);
        results.push({
          page: pageConfig.title,
          viewport: viewportName,
          success: false,
          error: error.message
        });
      }
    }

    await context.close();
  }

  await browser.close();

  // 生成报告
  console.log('\n' + '='.repeat(60));
  console.log('📊 视觉检查报告\n');
  
  let successCount = 0;
  let failCount = 0;
  let errorCount = 0;

  results.forEach(result => {
    if (result.success) {
      successCount++;
      console.log(`✅ ${result.page} (${result.viewport}): ${result.filename}`);
      if (result.errors > 0) {
        console.log(`   ⚠️  发现 ${result.errors} 个控制台错误`);
        errorCount++;
      }
    } else {
      failCount++;
      console.log(`❌ ${result.page} (${result.viewport}): ${result.error}`);
    }
  });

  console.log('\n' + '='.repeat(60));
  console.log(`总计: ${successCount} 成功, ${failCount} 失败, ${errorCount} 页面有控制台错误`);
  console.log(`截图保存位置: ${screenshotsDir}`);
}

takeScreenshots().catch(error => {
  console.error('❌ 视觉检查失败:', error);
  process.exit(1);
});
