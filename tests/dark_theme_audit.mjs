import { chromium } from 'playwright-core';

const CHROME_PATH = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const BASE_URL = process.env.BASE_URL || 'http://synemax.test';

// Luminance calculation helper in JS
function calculateLuminance(r, g, b) {
  const [rs, gs, bs] = [r, g, b].map(c => {
    c = c / 255;
    return c <= 0.04045 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
  });
  return 0.2126 * rs + 0.7152 * gs + 0.0722 * bs;
}

async function runThemeAudit() {
  console.log('=== MEMULAI AUDIT TEMA GELAP & TERANG ADMIN ===');
  console.log('Base URL:', BASE_URL);

  const browser = await chromium.launch({
    executablePath: CHROME_PATH,
    headless: true,
  });

  const context = await browser.newContext({
    viewport: { width: 1366, height: 768 },
  });

  const page = await context.newPage();
  page.setDefaultTimeout(30000);

  // 1. Authenticate using local QA login route
  console.log('\nLogin ke admin...');
  await page.goto(`${BASE_URL}/qa-admin-login`, { waitUntil: 'domcontentloaded' });
  await page.waitForTimeout(1000);

  const pagesToAudit = [
    { name: 'Dashboard', path: '/admin' },
    { name: 'Jadwal', path: '/admin/jadwal' },
    { name: 'Booking', path: '/admin/booking' },
    { name: 'Katalog Film (Daftar)', path: '/admin/films' },
    { name: 'Form Tambah Film', path: '/admin/films/buat' },
    { name: 'Impor Film', path: '/admin/films/impor' },
    { name: 'Poster Massal', path: '/admin/films/posters' },
    { name: 'Klasifikasi Usia', path: '/admin/age-ratings' },
    { name: 'Promo (Daftar)', path: '/admin/promos' },
    { name: 'Promo (Form)', path: '/admin/promos/buat' },
    { name: 'Hero (Daftar)', path: '/admin/banners' },
    { name: 'Hero (Form)', path: '/admin/banners/buat' },
    { name: 'Nobar', path: '/admin/nobar' },
    { name: 'Kabar / Berita', path: '/admin/berita' },
    { name: 'Jajanan', path: '/admin/jajanan' },
    { name: 'Daftar Siapkan', path: '/admin/jajanan/siapkan' },
    { name: 'Laporan', path: '/admin/laporan' },
    { name: 'Pengaturan (Umum)', path: '/admin/pengaturan?tab=umum' },
    { name: 'Pengaturan (Navbar)', path: '/admin/pengaturan?tab=navbar' },
    { name: 'Pengaturan (Tiket)', path: '/admin/pengaturan?tab=tiket' },
    { name: 'Pengaturan (Kontak)', path: '/admin/pengaturan?tab=kontak' },
    { name: 'Pengaturan (Sistem)', path: '/admin/pengaturan?tab=sistem' },
  ];

  const results = {
    darkModeFindings: [],
    lightModeFindings: [],
  };

  for (const item of pagesToAudit) {
    const fullUrl = `${BASE_URL}${item.path}`;
    let loaded = false;
    for (let attempt = 1; attempt <= 3; attempt++) {
      try {
        await page.goto(fullUrl, { waitUntil: 'load', timeout: 15000 });
        loaded = true;
        break;
      } catch (e) {
        if (attempt === 3) throw e;
        await page.waitForTimeout(600);
      }
    }
    await page.waitForTimeout(500);

    // ==========================================
    // 1. Audit TEMA GELAP (Dark Mode)
    // ==========================================
    await page.evaluate(() => {
      document.documentElement.classList.add('dark');
      localStorage.setItem('theme', 'dark');
    });
    await page.waitForTimeout(300);

    const darkViolations = await page.evaluate(() => {
      function getEffectiveBg(el) {
        let cur = el;
        while (cur && cur !== document.documentElement) {
          const style = window.getComputedStyle(cur);
          const bg = style.backgroundColor;
          if (bg && bg !== 'rgba(0, 0, 0, 0)' && bg !== 'transparent') {
            const m = bg.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)/);
            if (m) {
              return [parseInt(m[1], 10), parseInt(m[2], 10), parseInt(m[3], 10)];
            }
          }
          cur = cur.parentElement;
        }
        return [7, 9, 14]; // Default dark canvas #07090E
      }

      function calcLuminance(r, g, b) {
        const [rs, gs, bs] = [r, g, b].map(c => {
          c = c / 255;
          return c <= 0.04045 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
        });
        return 0.2126 * rs + 0.7152 * gs + 0.0722 * bs;
      }

      const elements = Array.from(document.querySelectorAll('input, textarea, select, div, section, form, table, tbody, tr, td, th'));
      const violations = [];

      for (const el of elements) {
        const rect = el.getBoundingClientRect();
        const area = rect.width * rect.height;
        if (area <= 1000 || rect.width <= 0 || rect.height <= 0) continue;

        // Skip hidden elements
        const style = window.getComputedStyle(el);
        if (style.display === 'none' || style.visibility === 'hidden' || style.opacity === '0') continue;

        // Allowlist for dark mode (Ticket card, QR, video iframe, allowlist markers)
        if (
          el.closest('.ticket-paper-card') ||
          el.closest('[data-allow-light-in-dark]') ||
          el.closest('#ticket-preview') ||
          el.closest('.qr-code-container') ||
          el.tagName === 'IFRAME' ||
          el.tagName === 'CANVAS' ||
          el.tagName === 'IMG' ||
          el.tagName === 'SVG'
        ) {
          continue;
        }

        const [r, g, b] = getEffectiveBg(el);
        const lum = calcLuminance(r, g, b);

        // Fail if luminance > 0.8 in dark mode outside allowlist
        if (lum > 0.8) {
          violations.push({
            tag: el.tagName.toLowerCase(),
            id: el.id || null,
            name: el.getAttribute('name') || null,
            classes: el.className ? el.className.toString().slice(0, 80) : '',
            area: Math.round(area),
            rgb: `rgb(${r}, ${g}, ${b})`,
            luminance: Number(lum.toFixed(3)),
          });
        }
      }
      return violations;
    });

    if (darkViolations.length > 0) {
      results.darkModeFindings.push({ page: item.name, violations: darkViolations });
      console.log(`❌ [DARK] ${item.name}: Ditemukan ${darkViolations.length} elemen berlatar putih/terang!`);
      darkViolations.forEach(v => console.log(`   - ${v.tag}${v.id ? '#' + v.id : ''}${v.name ? '[name=' + v.name + ']' : ''}: area=${v.area}px² rgb=${v.rgb} lum=${v.luminance}`));
    } else {
      console.log(`✅ [DARK] ${item.name}: Bersih (0 elemen berlatar > 0.8)`);
    }

    // ==========================================
    // 2. Audit TEMA TERANG (Light Mode)
    // ==========================================
    await page.evaluate(() => {
      document.documentElement.classList.remove('dark');
      localStorage.setItem('theme', 'light');
    });
    await page.waitForTimeout(300);

    const lightViolations = await page.evaluate(() => {
      function getEffectiveBg(el) {
        let cur = el;
        while (cur && cur !== document.documentElement) {
          const style = window.getComputedStyle(cur);
          const bg = style.backgroundColor;
          if (bg && bg !== 'rgba(0, 0, 0, 0)' && bg !== 'transparent') {
            const m = bg.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)/);
            if (m) {
              return [parseInt(m[1], 10), parseInt(m[2], 10), parseInt(m[3], 10)];
            }
          }
          cur = cur.parentElement;
        }
        return [255, 255, 255]; // Default light canvas
      }

      function calcLuminance(r, g, b) {
        const [rs, gs, bs] = [r, g, b].map(c => {
          c = c / 255;
          return c <= 0.04045 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
        });
        return 0.2126 * rs + 0.7152 * gs + 0.0722 * bs;
      }

      const elements = Array.from(document.querySelectorAll('input, textarea, select, div, section, form, table, tbody, tr, td, th'));
      const violations = [];

      for (const el of elements) {
        const rect = el.getBoundingClientRect();
        const area = rect.width * rect.height;
        if (area <= 1000 || rect.width <= 0 || rect.height <= 0) continue;

        // Skip hidden elements
        const style = window.getComputedStyle(el);
        if (style.display === 'none' || style.visibility === 'hidden' || style.opacity === '0') continue;

        // Allowlist for light mode (Video player, dark badge/pill, black button, pre/code)
        if (
          el.closest('.ticket-paper-card') ||
          el.closest('[data-allow-dark-in-light]') ||
          el.closest('.aspect-video') ||
          el.tagName === 'IFRAME' ||
          el.tagName === 'PRE' ||
          el.tagName === 'CODE' ||
          el.tagName === 'BUTTON' ||
          el.tagName === 'CANVAS' ||
          el.tagName === 'IMG' ||
          el.tagName === 'SVG'
        ) {
          continue;
        }

        const [r, g, b] = getEffectiveBg(el);
        const lum = calcLuminance(r, g, b);

        // Fail if luminance < 0.15 in light mode outside allowlist
        if (lum < 0.15) {
          violations.push({
            tag: el.tagName.toLowerCase(),
            id: el.id || null,
            name: el.getAttribute('name') || null,
            classes: el.className ? el.className.toString().slice(0, 80) : '',
            area: Math.round(area),
            rgb: `rgb(${r}, ${g}, ${b})`,
            luminance: Number(lum.toFixed(3)),
          });
        }
      }
      return violations;
    });

    if (lightViolations.length > 0) {
      results.lightModeFindings.push({ page: item.name, violations: lightViolations });
      console.log(`❌ [LIGHT] ${item.name}: Ditemukan ${lightViolations.length} elemen berlatar gelap/hitam di tema terang!`);
      lightViolations.forEach(v => console.log(`   - ${v.tag}${v.id ? '#' + v.id : ''}${v.name ? '[name=' + v.name + ']' : ''}: area=${v.area}px² rgb=${v.rgb} lum=${v.luminance}`));
    } else {
      console.log(`✅ [LIGHT] ${item.name}: Bersih (0 elemen berlatar < 0.15)`);
    }
  }

  await browser.close();

  const totalDark = results.darkModeFindings.reduce((sum, p) => sum + p.violations.length, 0);
  const totalLight = results.lightModeFindings.reduce((sum, p) => sum + p.violations.length, 0);

  console.log('\n=========================================');
  console.log(`RINGKASAN AUDIT TEMA:`);
  console.log(`- Pelanggaran Tema Gelap (> 0.8 di luar allowlist): ${totalDark}`);
  console.log(`- Pelanggaran Tema Terang (< 0.15 di luar allowlist): ${totalLight}`);
  console.log('=========================================');

  if (totalDark > 0 || totalLight > 0) {
    console.error('Audit Tema GAGAL! Terdapat elemen dengan kontras/luminans menyalahi token.');
    process.exit(1);
  } else {
    console.log('Semua 22 halaman admin LOLOS audit tema gelap & terang!');
    process.exit(0);
  }
}

runThemeAudit().catch(err => {
  console.error('Error saat menjalankan audit tema:', err);
  process.exit(1);
});
