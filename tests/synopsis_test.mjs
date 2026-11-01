import { chromium } from 'playwright-core';

const CHROME_PATH = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const BASE_URL = process.env.BASE_URL || 'http://synemax.test';

const SAMPLES = {
  kosong: '',
  kalimat1: 'Film fiksi ilmiah petualangan luar angkasa yang mengesankan para penonton bioskop.',
  baris4: 'Baris satu dari sinopsis film yang cukup singkat untuk muat dalam tampilan awal.\nBaris dua menjelaskan petualangan yang dialami tokoh utama.\nBaris tiga menceritakan konflik yang terjadi di studio bioskop.\nBaris empat mengakhiri perjalanan cerita tanpa perlu meluap ke baris kelima.',
  baris6: 'Baris satu dari sinopsis film yang lebih panjang.\nBaris dua menceritakan petualangan luar angkasa yang penuh misteri.\nBaris tiga tentang rahasia waktu dan relativitas lubang hitam.\nBaris empat tentang harapan umat manusia di masa depan bumi.\nBaris lima tentang cinta seorang ayah kepada putrinya.\nBaris enam menutup kisah epik penjelajahan galaksi.',
  baris40: Array.from({ length: 40 }, (_, i) => `Paragraf baris ke-${i + 1} menguraikan perjalanan panjang tokoh dalam misi sinematik antariksa`).join('\n'),
};

async function testSynopsis() {
  console.log('=== PENGUJIAN SINOPSIS: 5 PANJANG × 2 VIEWPORT (390 & 1366) ===');
  const browser = await chromium.launch({
    executablePath: CHROME_PATH,
    headless: true,
  });

  const viewports = [
    { name: 'Mobile 390px', width: 390, height: 844 },
    { name: 'Desktop 1366px', width: 1366, height: 768 },
  ];

  const results = [];

  for (const vp of viewports) {
    console.log(`\n--- Pengujian Viewport: ${vp.name} ---`);
    const page = await browser.newPage({ viewport: { width: vp.width, height: vp.height } });
    await page.goto(`${BASE_URL}/film`, { waitUntil: 'domcontentloaded' });

    // Find any film link
    const firstFilmLink = await page.evaluate(() => {
      const a = document.querySelector('a[href*="/film/"]');
      return a ? a.href : null;
    });

    if (!firstFilmLink) {
      console.error('Tidak ada film ditemukan di /film');
      continue;
    }

    await page.goto(firstFilmLink, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(1000);

    for (const [key, text] of Object.entries(SAMPLES)) {
      // Inject synopsis text into the component
      const res = await page.evaluate(async (synopsisText) => {
        const comp = document.querySelector('[x-data*="checkOverflow"]');
        if (!comp) return { error: 'Component x-data not found' };

        const pEl = comp.querySelector('p[x-ref="synopsisText"]');
        const emptyEl = comp.querySelector('p.italic');
        const btn = comp.querySelector('button[type="button"]');

        if (!synopsisText) {
          // Empty case
          if (pEl) pEl.style.display = 'none';
          return {
            key: 'kosong',
            buttonVisible: btn ? window.getComputedStyle(btn).display !== 'none' : false,
            emptyTextVisible: true,
          };
        }

        if (pEl) {
          pEl.style.display = '';
          pEl.innerText = synopsisText;
        }

        // Trigger Alpine checkOverflow
        const alpineData = comp._x_dataStack ? comp._x_dataStack[0] : null;
        if (alpineData && alpineData.checkOverflow) {
          alpineData.expanded = false;
          alpineData.checkOverflow();
        }

        await new Promise(r => setTimeout(r, 100));

        const scrollHeight = pEl.scrollHeight;
        const clientHeight = pEl.clientHeight;
        const btnVisibleBefore = btn ? window.getComputedStyle(btn).display !== 'none' : false;
        const btnTextBefore = btn ? btn.innerText.trim() : '';

        // Test click expand if visible
        let btnTextAfter = '';
        let clientHeightAfter = clientHeight;
        if (btnVisibleBefore) {
          alpineData.toggle();
          await new Promise(r => setTimeout(r, 100));
          btnTextAfter = btn ? btn.innerText.trim() : '';
          clientHeightAfter = pEl.clientHeight;
          // Toggle back
          alpineData.toggle();
          await new Promise(r => setTimeout(r, 100));
        }

        return {
          scrollHeight,
          clientHeight,
          isOverflowing: scrollHeight > clientHeight + 2,
          btnVisibleBefore,
          btnTextBefore,
          btnTextAfter,
          expandedHeight: clientHeightAfter,
        };
      }, text);

      console.log(`  Case [${key}]: scrollHeight=${res.scrollHeight || 0}, clientHeight=${res.clientHeight || 0}, btnVisible=${res.btnVisibleBefore}, text="${res.btnTextBefore}" -> "${res.btnTextAfter || '-'}"`);
      results.push({
        viewport: vp.name,
        case: key,
        ...res
      });
    }

    await page.close();
  }

  await browser.close();

  console.log('\n=== RANGKUMAN HASIL PENGUJIAN SINOPSIS ===');
  console.table(results);
}

testSynopsis().catch(console.error);
