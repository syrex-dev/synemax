import { chromium } from 'playwright-core';
import axeCore from 'axe-core';
import fs from 'node:fs';
import path from 'node:path';

const CHROME_PATH = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const BASE_URL = process.env.BASE_URL || 'http://synemax.test';
const QA_EMAIL = process.env.QA_EMAIL || 'qa_admin@synemax.test';
const QA_PASSWORD = process.env.QA_PASSWORD || 'QaPassword123!';

/**
 * Scan raw color classes across all Blade files in resources/views
 */
function scanBladeColorTokens() {
  console.log('\n=============================================');
  console.log('SCANNER TOKEN WARNA BLADE (resources/views)');
  console.log('=============================================');

  const viewsDir = path.resolve('resources/views');
  const rawColorPatterns = [
    { name: 'text-amber-*', regex: /\btext-amber-[1-9]00\b/g },
    { name: 'bg-amber-*', regex: /\bbg-amber-[1-9]00\b/g },
    { name: 'border-amber-*', regex: /\bborder-amber-[1-9]00\b/g },
    { name: 'text-emerald-*', regex: /\btext-emerald-[1-9]00\b/g },
    { name: 'bg-emerald-*', regex: /\bbg-emerald-[1-9]00\b/g },
    { name: 'text-green-*', regex: /\btext-green-[1-9]00\b/g },
    { name: 'bg-green-*', regex: /\bbg-green-[1-9]00\b/g },
    { name: 'text-cyan-*', regex: /\btext-cyan-[1-9]00\b/g },
    { name: 'bg-cyan-*', regex: /\bbg-cyan-[1-9]00\b/g },
    { name: 'text-red-*', regex: /\btext-red-[1-9]00\b/g },
    { name: 'bg-red-*', regex: /\bbg-red-[1-9]00\b/g },
  ];

  const counts = {};
  rawColorPatterns.forEach(p => (counts[p.name] = 0));

  function scanDir(dir) {
    const entries = fs.readdirSync(dir, { withFileTypes: true });
    for (const ent of entries) {
      const fullPath = path.join(dir, ent.name);
      if (ent.isDirectory()) {
        scanDir(fullPath);
      } else if (ent.isFile() && ent.name.endsWith('.blade.php')) {
        const content = fs.readFileSync(fullPath, 'utf-8');
        rawColorPatterns.forEach(p => {
          const matches = content.match(p.regex);
          if (matches) {
            counts[p.name] += matches.length;
          }
        });
      }
    }
  }

  scanDir(viewsDir);

  for (const [name, count] of Object.entries(counts)) {
    console.log(`- ${name.padEnd(20)} : ${count} kemunculan`);
  }

  return counts;
}

async function runAxeAudit() {
  console.log('=============================================');
  console.log('AUDIT WARNA & KONTRAS WCAG AA DENGAN AXE-CORE');
  console.log('=============================================');
  console.log('Target BASE_URL:', BASE_URL);

  const browser = await chromium.launch({
    executablePath: CHROME_PATH,
    headless: true,
  });

  const page = await browser.newPage({ viewport: { width: 1366, height: 768 } });

  // Login as admin via standard /login
  console.log(`\nMelakukan login admin melalui ${BASE_URL}/login...`);
  try {
    await page.goto(`${BASE_URL}/login`, { waitUntil: 'domcontentloaded', timeout: 15000 });
    await page.fill('input#email', QA_EMAIL);
    await page.fill('input#password', QA_PASSWORD);
    await page.click('button[type="submit"]');
    await page.waitForURL('**/admin**', { timeout: 15000 });
    console.log('Berhasil login sebagai admin.');
  } catch (err) {
    console.warn(`Peringatan: Tidak dapat login ke admin otomatis (${err.message}). Melanjutkan audit halaman publik.`);
  }

  const urls = [
    { name: 'Beranda (Home)', url: `${BASE_URL}/` },
    { name: 'Katalog Film', url: `${BASE_URL}/film` },
    { name: 'Jadwal Tayang', url: `${BASE_URL}/jadwal` },
    { name: 'Admin Dashboard', url: `${BASE_URL}/admin` },
    { name: 'Admin Booking List', url: `${BASE_URL}/admin/booking` },
    { name: 'Admin Klasifikasi Usia', url: `${BASE_URL}/admin/age-ratings` },
    { name: 'Admin Genre Film', url: `${BASE_URL}/admin/genres` },
    { name: 'Admin Pengaturan', url: `${BASE_URL}/admin/pengaturan` },
  ];

  let totalViolations = 0;
  const violationSummary = [];

  for (const item of urls) {
    console.log(`\nMemeriksa: ${item.name} (${item.url})...`);
    try {
      await page.goto(item.url, { waitUntil: 'domcontentloaded', timeout: 15000 });
      await page.waitForTimeout(800);

      // Inject axe-core
      await page.evaluate(axeCore.source);

      const results = await page.evaluate(async () => {
        return await window.axe.run(document, {
          runOnly: {
            type: 'rule',
            values: ['color-contrast'],
          },
        });
      });

      const contrastViolations = results.violations || [];
      if (contrastViolations.length === 0) {
        console.log(`  LULUS: 0 pelanggaran color-contrast pada ${item.name}`);
        violationSummary.push({ page: item.name, violations: 0 });
      } else {
        console.log(`  Ditemukan ${contrastViolations.length} isu color-contrast pada ${item.name}:`);
        let pageNodes = 0;
        contrastViolations.forEach(v => {
          console.log(`   - Rule: ${v.id} (${v.nodes.length} elemen terdampak)`);
          v.nodes.slice(0, 3).forEach(n => {
            console.log(`     * Target: ${n.target.join(' ')}`);
            console.log(`       Penyebab: ${n.failureSummary ? n.failureSummary.split('\n')[0] : 'Contrast ratio low'}`);
          });
          pageNodes += v.nodes.length;
        });
        totalViolations += contrastViolations.length;
        violationSummary.push({ page: item.name, violations: contrastViolations.length, nodes: pageNodes });
      }
    } catch (pageErr) {
      console.error(`  Gagal memuat ${item.name}:`, pageErr.message);
      violationSummary.push({ page: item.name, error: pageErr.message });
    }
  }

  await browser.close();

  // Run raw color scan
  const colorCounts = scanBladeColorTokens();

  console.log('\n=============================================');
  console.log(`TOTAL PELANGGARAN COLOR-CONTRAST AXE-CORE: ${totalViolations}`);
  console.log('=============================================');

  return { totalViolations, violationSummary, colorCounts };
}

runAxeAudit().catch(err => {
  console.error('Audit axe error:', err);
  process.exit(1);
});
