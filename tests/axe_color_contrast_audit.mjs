import { chromium } from 'playwright-core';
import axeCore from 'axe-core';

const CHROME_PATH = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const BASE_URL = process.env.BASE_URL || 'http://synemax.test';

async function runAxeAudit() {
  console.log('=============================================');
  console.log('AUDIT WARNA & KONTRAS WCAG AA DENGAN AXE-CORE');
  console.log('=============================================');

  const browser = await chromium.launch({
    executablePath: CHROME_PATH,
    headless: true,
  });

  const page = await browser.newPage({ viewport: { width: 1366, height: 768 } });

  // Login as admin first
  await page.goto(`${BASE_URL}/qa-admin-login`);
  await page.waitForTimeout(500);

  const urls = [
    { name: 'Beranda (Home)', url: `${BASE_URL}/` },
    { name: 'Katalog Film', url: `${BASE_URL}/film` },
    { name: 'Jadwal Tayang', url: `${BASE_URL}/jadwal` },
    { name: 'Admin Dashboard', url: `${BASE_URL}/admin` },
    { name: 'Admin Booking List', url: `${BASE_URL}/admin/booking` },
    { name: 'Admin Klasifikasi Usia', url: `${BASE_URL}/admin/klasifikasi-usia` },
    { name: 'Admin Pengaturan', url: `${BASE_URL}/admin/pengaturan` },
  ];

  let totalViolations = 0;

  for (const item of urls) {
    console.log(`\nMemeriksa: ${item.name} (${item.url})...`);
    await page.goto(item.url, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(800);

    // Inject axe-core
    await page.evaluate(axeCore.source);

    const results = await page.evaluate(async () => {
      return await window.axe.run(document, {
        runOnly: {
          type: 'rule',
          values: ['color-contrast']
        }
      });
    });

    const contrastViolations = results.violations || [];
    if (contrastViolations.length === 0) {
      console.log(`✅ LULUS: 0 pelanggaran color-contrast pada ${item.name}`);
    } else {
      console.log(`⚠️ Ditemukan ${contrastViolations.length} isu color-contrast pada ${item.name}:`);
      contrastViolations.forEach(v => {
        console.log(`   - Rule: ${v.id} (${v.nodes.length} elemen terdampak)`);
      });
      totalViolations += contrastViolations.length;
    }
  }

  await browser.close();
  console.log('\n=============================================');
  console.log(`TOTAL PELANGGARAN COLOR-CONTRAST: ${totalViolations}`);
  console.log('=============================================');
}

runAxeAudit().catch(err => {
  console.error('Audit axe error:', err);
  process.exit(1);
});
