import fs from 'node:fs';
import path from 'node:path';
import { chromium } from 'playwright-core';
import jsQR from 'jsqr';

const CHROME_PATH = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const BASE_URL = process.env.BASE_URL || 'http://synemax.test';

function countPdfPages(pdfBuffer) {
  const binaryString = pdfBuffer.toString('binary');
  const matches = binaryString.match(/\/Type\s*\/Page\b/g);
  return matches ? matches.length : 0;
}

async function runTicketPdfAudit() {
  console.log('====================================================');
  console.log('AUDIT TIKET PDF 1 HALAMAN & INLINE SVG QR DECODE');
  console.log('====================================================');
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

  // 1. Login to admin
  console.log('\n[1/3] Login ke admin...');
  await page.goto(`${BASE_URL}/qa-admin-login`, { waitUntil: 'domcontentloaded' });
  await page.waitForTimeout(1000);

  // Load fixtures
  const fixturesPath = path.resolve('tests/ticket_audit_fixtures.json');
  if (!fs.existsSync(fixturesPath)) {
    throw new Error('File tests/ticket_audit_fixtures.json tidak ditemukan! Jalankan php tests/seed_ticket_audit_bookings.php terlebih dahulu.');
  }
  const fixtures = JSON.parse(fs.readFileSync(fixturesPath, 'utf-8'));

  // Scenarios to test: 0, 3, 6, 12 snacks
  const overflowMap = {
    0: null,
    3: null,
    6: '+3 item jajan lainnya',
    12: '+9 item jajan lainnya',
  };

  const scenarios = fixtures.scenarios.map(s => ({
    id: s.id,
    code: s.code,
    snackCount: s.snack_count,
    expectedOverflow: overflowMap[s.snack_count],
    ticketUrl: s.ticket_url || `${BASE_URL}/admin/booking/${s.id}/tiket`,
  }));

  const themes = ['light', 'dark'];
  let totalTests = 0;
  let passedTests = 0;
  const failureDetails = [];

  console.log('\n[2/3] Memeriksa Tiap Skenario (Light & Dark Theme)...');

  for (const theme of themes) {
    console.log(`\n--- MODE TEMA: ${theme.toUpperCase()} ---`);

    for (const scenario of scenarios) {
      totalTests++;
      const testName = `Tiket [${scenario.code}] (${scenario.snackCount} jajan) - Tema ${theme.toUpperCase()}`;
      process.stdout.write(`Menguji ${testName}... `);

      try {
        const ticketUrl = scenario.ticketUrl;
        await page.goto(ticketUrl, { waitUntil: 'domcontentloaded' });
        await page.waitForTimeout(600);

        // Apply dark mode if required
        if (theme === 'dark') {
          await page.evaluate(() => {
            document.documentElement.classList.add('dark');
            localStorage.setItem('theme', 'dark');
          });
        } else {
          await page.evaluate(() => {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('theme', 'light');
          });
        }
        await page.waitForTimeout(400);

        // A. Decode Inline SVG QR Code
        const qrSvg = await page.evaluate(() => {
          const svg = document.querySelector('#printable-ticket svg');
          return svg ? new XMLSerializer().serializeToString(svg) : null;
        });

        if (!qrSvg) {
          throw new Error('Elemen inline SVG QR code tidak ditemukan di tiket!');
        }

        // Decode QR using canvas in browser
        const decodedCode = await page.evaluate(async (svgString) => {
          return new Promise((resolve) => {
            const img = new Image();
            const canvas = document.createElement('canvas');
            const size = 300;
            canvas.width = size;
            canvas.height = size;
            const ctx = canvas.getContext('2d');

            img.onload = () => {
              ctx.fillStyle = '#ffffff';
              ctx.fillRect(0, 0, size, size);
              ctx.drawImage(img, 0, 0, size, size);
              const imgData = ctx.getImageData(0, 0, size, size);
              
              // We'll return pixel data array to decode with jsQR in node
              resolve({
                data: Array.from(imgData.data),
                width: size,
                height: size
              });
            };
            img.onerror = () => resolve(null);
            img.src = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svgString);
          });
        }, qrSvg);

        if (!decodedCode) {
          throw new Error('Gagal merender QR SVG ke kanvas.');
        }

        const qrResult = jsQR(
          new Uint8ClampedArray(decodedCode.data),
          decodedCode.width,
          decodedCode.height
        );

        if (!qrResult || qrResult.data !== scenario.code) {
          throw new Error(`QR code decode mismatch! Harapan: ${scenario.code}, Hasil: ${qrResult ? qrResult.data : 'Gagal'}`);
        }

        // B. Assert snack row limits in text
        const pageText = await page.evaluate(() => document.body.innerText);

        if (scenario.expectedOverflow) {
          if (!pageText.includes(scenario.expectedOverflow)) {
            throw new Error(`Label overflow '${scenario.expectedOverflow}' tidak ditemukan!`);
          }
        }

        // C. Assert Payment status: "Lunas" (karena sudah terkonfirmasi)
        if (!pageText.includes('Lunas')) {
          throw new Error("Status pembayaran tidak menampilkan 'Lunas' untuk booking terkonfirmasi!");
        }

        // D. Generate PDF using Chromium
        const pdfBuffer = await page.pdf({
          preferCSSPageSize: true,
          displayHeaderFooter: false,
          printBackground: true,
        });

        // E. Assert strictly 1 page
        const pageCount = countPdfPages(pdfBuffer);
        if (pageCount !== 1) {
          throw new Error(`Jumlah halaman PDF melebihi batas! Ditemukan: ${pageCount} halaman (Harus tepat 1 halaman).`);
        }

        console.log(`BERHASIL (QR: ${qrResult.data}, PDF: ${pageCount} Hal, Overflow: ${scenario.expectedOverflow || 'Tepat'})`);
        passedTests++;
      } catch (err) {
        console.log(`GAGAL: ${err.message}`);
        failureDetails.push({ testName, error: err.message });
      }
    }
  }

  // 3. Test Before Confirmation Payment Status on Live Page
  console.log('\n[3/3] Menguji Status Pembayaran Sebelum Konfirmasi Admin (Bayar di tempat)...');
  totalTests++;
  try {
    const pendingTicketUrl = fixtures.pending_cod?.ticket_url || `${BASE_URL}/admin/bookings/${fixtures.pending_cod?.id}/ticket`;
    await page.goto(pendingTicketUrl, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(600);

    const pageText = await page.evaluate(() => document.body.innerText);
    if (pageText.includes('Bayar di tempat')) {
      console.log("Status 'Bayar di tempat' terbukti tampil sebelum konfirmasi admin: BERHASIL");
      passedTests++;
    } else {
      throw new Error("Status 'Bayar di tempat' tidak ditemukan pada booking menunggu konfirmasi.");
    }
  } catch (err) {
    console.log(`GAGAL: ${err.message}`);
    failureDetails.push({ testName: 'Bayar di tempat before confirmation', error: err.message });
  }

  await browser.close();

  console.log('\n====================================================');
  console.log(`RINGKASAN AUDIT TIKET PDF: ${passedTests}/${totalTests} UJI LULUS`);
  console.log('====================================================');

  if (failureDetails.length > 0) {
    console.error('\nRincian Kegagalan:');
    failureDetails.forEach(f => console.error(`- [${f.testName}]: ${f.error}`));
    process.exit(1);
  } else {
    console.log('\nSEMUA PENGUJIAN TIKET PDF 1 HALAMAN & INLINE SVG QR 100% SUKSES!');
    process.exit(0);
  }
}

runTicketPdfAudit().catch((err) => {
  console.error('Fatal error during ticket PDF audit:', err);
  process.exit(1);
});
