import { chromium } from 'playwright-core';
import fs from 'fs';

const CHROME_PATH = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const BASE_URL = process.env.BASE_URL || 'http://synemax.test';

async function auditTouchScrollers() {
  console.log('=== MEMULAI AUDIT SCROLLER SENTUH MOBILE ===');
  console.log('Base URL:', BASE_URL);

  const browser = await chromium.launch({
    executablePath: CHROME_PATH,
    headless: true,
  });

  const context = await browser.newContext({
    viewport: { width: 390, height: 844 },
    isMobile: true,
    hasTouch: true,
    userAgent: 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
  });

  const page = await context.newPage();
  page.setDefaultTimeout(30000);

  const results = [];

  const pagesToTest = [
    { name: 'Beranda (Home)', url: `${BASE_URL}/` },
    { name: 'Jadwal (Schedule)', url: `${BASE_URL}/jadwal` },
    { name: 'Katalog Film (/film)', url: `${BASE_URL}/film` },
  ];

  for (const p of pagesToTest) {
    console.log(`\nNavigasi ke ${p.name}: ${p.url}`);
    let navSuccess = false;
    for (let attempt = 1; attempt <= 3; attempt++) {
      try {
        await page.goto(p.url, { waitUntil: 'domcontentloaded' });
        navSuccess = true;
        break;
      } catch (err) {
        if (attempt === 3) throw err;
        await page.waitForTimeout(600);
      }
    }
    await page.waitForTimeout(1000);

    // Audit scrollers on page
    const scrollersData = await page.evaluate(() => {
      const allElements = Array.from(document.querySelectorAll('*'));
      const scrollers = [];

      allElements.forEach((el, idx) => {
        const style = window.getComputedStyle(el);
        const overflowX = style.overflowX;
        const isScrollable = (overflowX === 'auto' || overflowX === 'scroll') && el.scrollWidth > el.clientWidth;

        if (isScrollable) {
          // Identify element
          let name = el.getAttribute('aria-label') || el.getAttribute('data-scroller') || el.className;
          if (el.closest('[aria-label="Katalog Film"]') || el.classList.contains('snap-x')) {
            name = 'Poster Film Carousel';
          } else if (el.closest('[aria-label="Pilih tanggal nonton"]')) {
            name = 'Strip Tanggal (Schedule)';
          } else if (el.closest('[x-ref="genreScroller"]') || el.getAttribute('x-ref') === 'genreScroller') {
            name = 'Chip Genre Filter Bar';
          } else if (el.classList.contains('carousel-viewport')) {
            const parentLabel = el.closest('[aria-label]')?.getAttribute('aria-label') || 'Carousel';
            name = `x-ui.carousel: ${parentLabel}`;
          }

          // Traverse ancestor touch-action
          let cur = el;
          let chain = [];
          let allowsPanX = true;

          while (cur && cur !== document.documentElement) {
            const cs = window.getComputedStyle(cur);
            const ta = cs.touchAction;
            chain.push({ tag: cur.tagName.toLowerCase(), id: cur.id, touchAction: ta });
            if (ta.includes('pan-y') && !ta.includes('pan-x') && !ta.includes('auto') && !ta.includes('manipulation')) {
              allowsPanX = false;
            }
            if (ta === 'none') {
              allowsPanX = false;
            }
            cur = cur.parentElement;
          }

          const rect = el.getBoundingClientRect();
          scrollers.push({
            name,
            tagName: el.tagName.toLowerCase(),
            className: el.className.slice(0, 50),
            scrollWidth: el.scrollWidth,
            clientWidth: el.clientWidth,
            scrollLeft: el.scrollLeft,
            computedTouchAction: style.touchAction,
            allowsPanX,
            rect: { x: rect.x, y: rect.y, width: rect.width, height: rect.height },
          });
        }
      });

      return scrollers;
    });

    console.log(`Ditemukan ${scrollersData.length} elemen overflow-x scrollable di ${p.name}`);

    for (let i = 0; i < scrollersData.length; i++) {
      const s = scrollersData[i];
      console.log(`  [${i + 1}] ${s.name}: touch-action="${s.computedTouchAction}", allowsPanX=${s.allowsPanX}`);

      // Perform real touch swipe test via CDP
      const cdp = await context.newCDPSession(page);

      // Scroll element into view first
      await page.evaluate((idx) => {
        const scrollers = Array.from(document.querySelectorAll('*')).filter(el => {
          const st = window.getComputedStyle(el);
          return (st.overflowX === 'auto' || st.overflowX === 'scroll') && el.scrollWidth > el.clientWidth;
        });
        if (scrollers[idx]) {
          scrollers[idx].scrollIntoView({ block: 'center' });
        }
      }, i);

      await page.waitForTimeout(300);

      const beforeScroll = await page.evaluate((idx) => {
        const scrollers = Array.from(document.querySelectorAll('*')).filter(el => {
          const st = window.getComputedStyle(el);
          return (st.overflowX === 'auto' || st.overflowX === 'scroll') && el.scrollWidth > el.clientWidth;
        });
        return scrollers[idx] ? scrollers[idx].scrollLeft : -1;
      }, i);

      const freshRect = await page.evaluate((idx) => {
        const scrollers = Array.from(document.querySelectorAll('*')).filter(el => {
          const st = window.getComputedStyle(el);
          return (st.overflowX === 'auto' || st.overflowX === 'scroll') && el.scrollWidth > el.clientWidth;
        });
        if (!scrollers[idx]) return null;
        const r = scrollers[idx].getBoundingClientRect();
        return { x: r.x + r.width * 0.7, y: r.y + r.height * 0.5 };
      }, i);

      let gestureMoved = false;
      let afterScroll = beforeScroll;

      if (freshRect && freshRect.y > 0 && freshRect.y < 844) {
        try {
          const startX = freshRect.x;
          const startY = freshRect.y;
          await cdp.send('Input.dispatchTouchEvent', {
            type: 'touchStart',
            touchPoints: [{ x: Math.round(startX), y: Math.round(startY), id: 0 }]
          });
          for (let step = 1; step <= 8; step++) {
            await cdp.send('Input.dispatchTouchEvent', {
              type: 'touchMove',
              touchPoints: [{ x: Math.round(startX - step * 25), y: Math.round(startY), id: 0 }]
            });
            await new Promise(r => setTimeout(r, 20));
          }
          await cdp.send('Input.dispatchTouchEvent', {
            type: 'touchEnd',
            touchPoints: []
          });

          await page.waitForTimeout(600);

          afterScroll = await page.evaluate((idx) => {
            const scrollers = Array.from(document.querySelectorAll('*')).filter(el => {
              const st = window.getComputedStyle(el);
              return (st.overflowX === 'auto' || st.overflowX === 'scroll') && el.scrollWidth > el.clientWidth;
            });
            return scrollers[idx] ? scrollers[idx].scrollLeft : -1;
          }, i);

          gestureMoved = afterScroll > beforeScroll + 5;
        } catch (e) {
          console.warn('    CDP scroll gesture error:', e.message);
        }
      }

      console.log(`    Uji Sentuh Nyata: scrollLeft ${beforeScroll} -> ${afterScroll} (Bisa Geser: ${gestureMoved ? 'YA' : 'TIDAK'})`);

      results.push({
        page: p.name,
        name: s.name,
        scrollWidth: s.scrollWidth,
        clientWidth: s.clientWidth,
        computedTouchAction: s.computedTouchAction,
        allowsPanX: s.allowsPanX,
        beforeScroll,
        afterScroll,
        gestureMoved,
      });
    }
  }

  await browser.close();

  console.log('\n=== TABEL HASIL AUDIT SCROLLER SENTUH ===');
  console.log('| Halaman | Elemen Scroller | touch-action Computed | Efektif Pan-X | Hasil Geser Sentuh CDP |');
  console.log('|---|---|---|---|---|');
  results.forEach(r => {
    console.log(`| ${r.page} | ${r.name} | \`${r.computedTouchAction}\` | ${r.allowsPanX ? 'YA' : 'TIDAK'} | ${r.gestureMoved ? 'BERHASIL' : 'GAGAL'} |`);
  });

  return results;
}

auditTouchScrollers().catch(err => {
  console.error('Audit fatal error:', err);
  process.exit(1);
});
