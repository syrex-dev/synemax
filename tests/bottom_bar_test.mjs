import { chromium } from 'playwright-core';

const CHROME_PATH = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const BASE_URL = process.env.BASE_URL || 'http://synemax.test';

async function testBottomBar() {
  console.log('=== PENGUJIAN LABEL BAR BAWAH HP (320, 360, 390, 412 PX) ===');
  const browser = await chromium.launch({
    executablePath: CHROME_PATH,
    headless: true,
  });

  const widths = [320, 360, 390, 412];
  const results = [];

  for (const w of widths) {
    const page = await browser.newPage({
      viewport: { width: w, height: 800 },
      isMobile: true,
      hasTouch: true,
    });

    await page.goto(`${BASE_URL}/`, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(500);

    const check = await page.evaluate(() => {
      const bar = document.querySelector('#mobile-bottom-bar');
      if (!bar) return { error: 'Bottom bar not found' };

      const items = Array.from(bar.querySelectorAll('a, button'));
      const details = items.map(el => {
        const span = el.querySelector('span:last-child');
        const text = span ? span.innerText.trim() : '';
        const fs = span ? window.getComputedStyle(span).fontSize : '';
        const scrollWidth = span ? span.scrollWidth : 0;
        const clientWidth = span ? span.clientWidth : 0;
        const isTruncated = scrollWidth > clientWidth;
        return {
          text,
          fontSize: fs,
          scrollWidth,
          clientWidth,
          isTruncated,
        };
      });

      return {
        itemCount: items.length,
        details,
      };
    });

    for (const d of check.details) {
      console.log(`  [${w}px] Label: "${d.text}", FontSize: ${d.fontSize}, Truncated: ${d.isTruncated ? 'YA (TERPOTONG!)' : 'TIDAK (PAS)'}`);
      results.push({
        width: `${w}px`,
        ...d,
        status: d.isTruncated ? 'GAGAL' : 'LOLOS',
      });
    }

    await page.close();
  }

  await browser.close();

  console.log('\n=== RANGKUMAN PENGUJIAN BAR BAWAH ===');
  console.table(results);
}

testBottomBar().catch(console.error);
