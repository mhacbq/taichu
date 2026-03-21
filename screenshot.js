const puppeteer = require('puppeteer');

(async () => {
  const browser = await puppeteer.launch({
    headless: 'new',
    args: ['--no-sandbox', '--disable-setuid-sandbox']
  });
  
  const page = await browser.newPage();
  await page.goto('http://localhost:4173/', { waitUntil: 'networkidle0' });
  
  await page.screenshot({ path: '/data/workspace/taichu/home-page.png', fullPage: true });
  console.log('Screenshot saved to /data/workspace/taichu/home-page.png');
  
  await browser.close();
})();
