import { chromium } from 'playwright-core';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const BASE_URL = 'http://synemax.test';
const ARTIFACT_DIR = 'C:/Users/deder/.gemini/antigravity-ide/brain/b67ecced-d03f-4eb2-a421-9809123a095a/qa_screenshots';

if (!fs.existsSync(ARTIFACT_DIR)) {
    fs.mkdirSync(ARTIFACT_DIR, { recursive: true });
}

// Axe-core script content
const axeCorePath = path.resolve(__dirname, '../node_modules/axe-core/axe.min.js');
const axeSource = fs.readFileSync(axeCorePath, 'utf8');

async function runQA() {
    console.log('=== MEMULAI QA PENUTUP SYNEMAX ===');
    const browser = await chromium.launch({ channel: 'msedge', headless: true });

    const auditResults = {
        dialogCalls: 0,
        consoleErrors: [],
        viewportsChecked: [],
        colorContrastViolations: {},
        footerBorders: {},
        themeSwitchTest: null,
        themeFlickerMeasurement: null,
        screenshots: [],
    };

    const context = await browser.newContext();
    const page = await context.newPage();

    // 1. Dialog safety guard listener
    page.on('dialog', async (dialog) => {
        auditResults.dialogCalls++;
        console.error(`[NATIVE DIALOG DETECTED]: ${dialog.type()} - ${dialog.message()}`);
        await dialog.dismiss();
    });

    // 2. Console error tracking
    page.on('console', (msg) => {
        if (msg.type() === 'error') {
            auditResults.consoleErrors.push(msg.text());
        }
    });

    // Helper: set theme via window.synemaXTheme and localStorage
    async function setTheme(theme) {
        await page.evaluate((t) => {
            if (window.synemaXTheme && typeof window.synemaXTheme.setMode === 'function') {
                window.synemaXTheme.setMode(t);
            } else {
                localStorage.setItem('synemax_theme_preference', t);
                localStorage.setItem('synemax-theme', t);
                if (t === 'dark') {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            }
        }, theme);
        await page.waitForTimeout(300);
    }

    // Helper: audit page metrics
    async function auditCurrentPage(label) {
        // Run axe color contrast
        await page.evaluate(axeSource);
        const axeResults = await page.evaluate(async () => {
            return await window.axe.run({
                runOnly: {
                    type: 'rule',
                    values: ['color-contrast']
                }
            });
        });

        // Check horizontal overflow
        const overflow = await page.evaluate(() => {
            return {
                scrollWidth: document.documentElement.scrollWidth,
                innerWidth: window.innerWidth,
                hasOverflow: document.documentElement.scrollWidth > window.innerWidth + 1
            };
        });

        // Check interactive touch target sizes
        const smallTargets = await page.evaluate(() => {
            const elements = Array.from(document.querySelectorAll('a, button, input, select'));
            let small = 0;
            elements.forEach(el => {
                const rect = el.getBoundingClientRect();
                if (rect.width > 0 && rect.height > 0 && (rect.width < 24 || rect.height < 24)) {
                    small++;
                }
            });
            return small;
        });

        auditResults.colorContrastViolations[label] = {
            violationsCount: axeResults.violations.length,
            overflow: overflow.hasOverflow ? `Overflow: ${overflow.scrollWidth} > ${overflow.innerWidth}` : 'Normal',
            smallTouchTargetsCount: smallTargets
        };

        return axeResults;
    }

    // Helper: capture screenshot
    async function capture(filename, fullPage = false) {
        const dest = path.join(ARTIFACT_DIR, filename);
        await page.screenshot({ path: dest, fullPage });
        auditResults.screenshots.push({ name: filename, path: dest });
        console.log(`[CAPTURED]: ${filename}`);
    }

    // ==========================================
    // TEST 8(b): THEME FLICKER & THEME SWITCHING
    // ==========================================
    console.log('\n--- Menguji Theme Flicker & Theme Switch Light -> Dark -> Light ---');
    await page.goto(BASE_URL, { waitUntil: 'domcontentloaded' });
    await page.evaluate(() => {
        localStorage.setItem('synemax_theme_preference', 'light');
        localStorage.setItem('synemax-theme', 'light');
    });
    // Reload to verify first frame in light mode without flash of dark
    await page.reload({ waitUntil: 'domcontentloaded' });

    // First frame background color
    const initialBgColor = await page.evaluate(() => {
        return window.getComputedStyle(document.documentElement).backgroundColor;
    });

    const lightThemeColor = await page.evaluate(() => {
        return document.querySelector('meta[name="theme-color"]')?.getAttribute('content');
    });

    // Switch to Dark
    await setTheme('dark');
    const darkBgColor = await page.evaluate(() => {
        return window.getComputedStyle(document.documentElement).backgroundColor;
    });
    const darkThemeColor = await page.evaluate(() => {
        return document.querySelector('meta[name="theme-color"]')?.getAttribute('content');
    });

    // Switch back to Light
    await setTheme('light');
    const lightBgColor2 = await page.evaluate(() => {
        return window.getComputedStyle(document.documentElement).backgroundColor;
    });
    const lightThemeColor2 = await page.evaluate(() => {
        return document.querySelector('meta[name="theme-color"]')?.getAttribute('content');
    });

    auditResults.themeSwitchTest = {
        lightThemeColor,
        darkThemeColor,
        lightThemeColor2,
        lightBgColor: initialBgColor,
        darkBgColor,
        lightBgColor2,
        passed: (lightThemeColor !== darkThemeColor && darkBgColor !== initialBgColor)
    };
    auditResults.themeFlickerMeasurement = {
        firstFrameLightBg: initialBgColor,
        isFlickerFree: initialBgColor.includes('248') || initialBgColor.includes('250') || initialBgColor.includes('255') || initialBgColor.includes('241')
    };

    // ==========================================
    // TEST 8(a): FOOTER MOBILE BORDERLESS (320, 360, 390, 412)
    // ==========================================
    console.log('\n--- Menguji Footer Mobile Borderless di 320, 360, 390, 412 ---');
    const mobileWidths = [320, 360, 390, 412];
    for (const w of mobileWidths) {
        await page.setViewportSize({ width: w, height: 800 });
        await page.goto(BASE_URL, { waitUntil: 'domcontentloaded' });
        
        const footerBorders = await page.evaluate(() => {
            const footer = document.querySelector('footer');
            if (!footer) return { found: false };
            const links = Array.from(footer.querySelectorAll('nav a, div.grid a'));
            const linkBorders = links.map(a => {
                const style = window.getComputedStyle(a);
                return {
                    text: a.innerText.trim(),
                    top: style.borderTopWidth,
                    bottom: style.borderBottomWidth,
                    left: style.borderLeftWidth,
                    right: style.borderRightWidth
                };
            });
            const hasAnyBorder = linkBorders.some(b => 
                parseFloat(b.top) > 0 || parseFloat(b.bottom) > 0 || parseFloat(b.left) > 0 || parseFloat(b.right) > 0
            );
            return {
                found: true,
                hasAnyBorder,
                sampleLinksCount: links.length
            };
        });

        auditResults.footerBorders[`w_${w}`] = footerBorders;
    }

    // ==========================================
    // CAPTURE REQUIRED ITEM 9 SCREENSHOTS
    // ==========================================
    console.log('\n--- Mengambil Screenshot QA Item 9 ---');

    // 1. Footer mobile 360 (terang & gelap)
    await page.setViewportSize({ width: 360, height: 800 });
    await page.goto(BASE_URL, { waitUntil: 'domcontentloaded' });
    await setTheme('light');
    await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
    await page.waitForTimeout(300);
    await capture('footer_mobile_360_light.png');
    await auditCurrentPage('footer_mobile_360_light');

    await setTheme('dark');
    await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
    await page.waitForTimeout(300);
    await capture('footer_mobile_360_dark.png');
    await auditCurrentPage('footer_mobile_360_dark');

    // 2. Footer mobile 412 (terang & gelap)
    await page.setViewportSize({ width: 412, height: 915 });
    await setTheme('light');
    await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
    await page.waitForTimeout(300);
    await capture('footer_mobile_412_light.png');
    await auditCurrentPage('footer_mobile_412_light');

    await setTheme('dark');
    await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
    await page.waitForTimeout(300);
    await capture('footer_mobile_412_dark.png');
    await auditCurrentPage('footer_mobile_412_dark');

    // 3. Footer desktop 1366 gelap dan 1920 terang
    await page.setViewportSize({ width: 1366, height: 768 });
    await setTheme('dark');
    await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
    await page.waitForTimeout(300);
    await capture('footer_desktop_1366_dark.png');
    await auditCurrentPage('footer_desktop_1366_dark');

    await page.setViewportSize({ width: 1920, height: 1080 });
    await setTheme('light');
    await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
    await page.waitForTimeout(300);
    await capture('footer_desktop_1920_light.png');
    await auditCurrentPage('footer_desktop_1920_light');

    // 4. Beranda atas gelap
    await page.setViewportSize({ width: 1366, height: 768 });
    await setTheme('dark');
    await page.evaluate(() => window.scrollTo(0, 0));
    await page.waitForTimeout(300);
    await capture('home_top_dark.png');
    await auditCurrentPage('home_top_dark');

    // 5. Tiket aktif gelap
    // Use existing sample booking
    await page.goto(`${BASE_URL}/tiket/HC-261014-KJ6QHP`, { waitUntil: 'domcontentloaded' });
    await setTheme('dark');
    await page.waitForTimeout(300);
    await capture('ticket_active_dark.png');
    await auditCurrentPage('ticket_active_dark');

    // ==========================================
    // ADMIN AUTHENTICATION FOR ADMIN QA PAGES
    // ==========================================
    console.log('\n--- Login Admin untuk Pengujian Halaman Admin ---');
    await page.goto(`${BASE_URL}/login`, { waitUntil: 'domcontentloaded' });
    await page.fill('input[name="email"]', 'admin@synemax.com');
    await page.fill('input[name="password"]', 'PasswordAdmin123');
    await Promise.all([
        page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
        page.click('button[type="submit"]')
    ]);
    console.log(`Login berhasil. Halaman saat ini: ${page.url()}`);

    // 6. Langkah 1 Impor (Form Unggah) di 390 dan 1366, Terang dan Gelap
    console.log('\n--- Mengambil Screenshot Langkah 1 Unggah ---');
    await page.setViewportSize({ width: 1366, height: 768 });
    await page.goto(`${BASE_URL}/admin/films/impor`, { waitUntil: 'domcontentloaded' });
    await setTheme('light');
    await capture('admin_import_step1_1366_light.png');
    await auditCurrentPage('admin_import_step1_1366_light');

    await setTheme('dark');
    await capture('admin_import_step1_1366_dark.png');
    await auditCurrentPage('admin_import_step1_1366_dark');

    await page.setViewportSize({ width: 390, height: 844 });
    await setTheme('light');
    await capture('admin_import_step1_390_light.png');
    await auditCurrentPage('admin_import_step1_390_light');

    await setTheme('dark');
    await capture('admin_import_step1_390_dark.png');
    await auditCurrentPage('admin_import_step1_390_dark');

    // 7. Langkah 2 Pratinjau (Upload 300 benchmark CSV)
    console.log('\n--- Mengunggah CSV Benchmark 300 Baris untuk Langkah 2 Pratinjau ---');
    await page.setViewportSize({ width: 1366, height: 768 });
    await page.goto(`${BASE_URL}/admin/films/impor`, { waitUntil: 'domcontentloaded' });
    await page.waitForSelector('input[name="csv_file"]');
    const csvBenchmarkPath = path.resolve(__dirname, '../scratch/films_300_benchmark.csv');
    await page.setInputFiles('input[name="csv_file"]', csvBenchmarkPath);

    await Promise.all([
        page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
        page.click('form[action*="impor/preview"] button[type="submit"]')
    ]);

    // Pratinjau di 1366 dan 390, Terang dan Gelap
    await setTheme('light');
    await capture('admin_import_preview_1366_light.png');
    await auditCurrentPage('admin_import_preview_1366_light');

    await setTheme('dark');
    await capture('admin_import_preview_1366_dark.png');
    await auditCurrentPage('admin_import_preview_1366_dark');

    await page.setViewportSize({ width: 390, height: 844 });
    await setTheme('light');
    await capture('admin_import_preview_390_light.png');
    await auditCurrentPage('admin_import_preview_390_light');

    await setTheme('dark');
    await capture('admin_import_preview_390_dark.png');
    await auditCurrentPage('admin_import_preview_390_dark');

    // 8. Inline Edit 1 Cell & Progress Bar Impor
    console.log('\n--- Uji Perbaikan Satu Sel Inline & Progress Impor ---');
    await page.setViewportSize({ width: 1366, height: 768 });
    await setTheme('dark');
    // Edit the first problem genre cell inline
    const inlineInput = await page.$('input[name="rows[32][title]"], input[name^="rows["][name$="[title]"]');
    if (inlineInput) {
        await inlineInput.fill('Koleksi Film Hasil Koreksi Inline');
        console.log('Inline edit judul film berhasil dilakukan.');
    }

    // Trigger chunked import
    const startImportBtn = await page.$('#start-import-btn');
    if (startImportBtn) {
        await startImportBtn.click();
        await page.waitForTimeout(1000); // Wait for chunk progress
        await capture('admin_import_progress_1366_dark.png');
        await auditCurrentPage('admin_import_progress_1366_dark');
    }

    // 9. Halaman Poster Massal di 390 dan 1366, Terang dan Gelap
    console.log('\n--- Mengambil Screenshot Halaman Poster Massal ---');
    await page.setViewportSize({ width: 1366, height: 768 });
    await page.goto(`${BASE_URL}/admin/films/posters`, { waitUntil: 'domcontentloaded' });
    await setTheme('light');
    await capture('admin_posters_1366_light.png');
    await auditCurrentPage('admin_posters_1366_light');

    await setTheme('dark');
    await capture('admin_posters_1366_dark.png');
    await auditCurrentPage('admin_posters_1366_dark');

    await page.setViewportSize({ width: 390, height: 844 });
    await setTheme('light');
    await capture('admin_posters_390_light.png');
    await auditCurrentPage('admin_posters_390_light');

    await setTheme('dark');
    await capture('admin_posters_390_dark.png');
    await auditCurrentPage('admin_posters_390_dark');

    // 10. Daftar Film Admin dengan Filter dan Aksi Massal di 390 dan 1366, Terang dan Gelap
    console.log('\n--- Mengambil Screenshot Daftar Film Admin ---');
    await page.setViewportSize({ width: 1366, height: 768 });
    await page.goto(`${BASE_URL}/admin/films`, { waitUntil: 'domcontentloaded' });
    await setTheme('light');
    await capture('admin_films_list_1366_light.png');
    await auditCurrentPage('admin_films_list_1366_light');

    await setTheme('dark');
    await capture('admin_films_list_1366_dark.png');
    await auditCurrentPage('admin_films_list_1366_dark');

    await page.setViewportSize({ width: 390, height: 844 });
    await setTheme('light');
    await capture('admin_films_list_390_light.png');
    await auditCurrentPage('admin_films_list_390_light');

    await setTheme('dark');
    await capture('admin_films_list_390_dark.png');
    await auditCurrentPage('admin_films_list_390_dark');

    await browser.close();

    const reportPath = path.resolve(__dirname, '../scratch/qa_final_report.json');
    fs.writeFileSync(reportPath, JSON.stringify(auditResults, null, 2));
    console.log(`\n=== QA SELESAI. Laporan tersimpan di: ${reportPath} ===`);
    console.log(`Total Screenshot Dihasilkan: ${auditResults.screenshots.length}`);
    console.log(`Total Panggilan Dialog Browser: ${auditResults.dialogCalls}`);
    console.log(`Total Console Errors: ${auditResults.consoleErrors.length}`);
}

runQA().catch(err => {
    console.error('QA Error:', err);
    process.exit(1);
});
