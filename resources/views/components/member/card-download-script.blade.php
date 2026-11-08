@props([
    'member',
    'qrSelector' => null,
])

@php
    $siteName = setting('site_name', 'SynemaX');
    $settings = app(\App\Services\CinemaSettings::class);
    $cycleMax = $settings->memberFreeVisit();
    $nextPos = $member->nextVisitPosition($cycleMax);
    $inCycle = $nextPos - 1;
    $cycleNum = $member->currentCycle($cycleMax);
    $cycleText = "Kunjungan {$inCycle} dari {$cycleMax} · Siklus {$cycleNum}";
    $progressDesc = $member->progressDescription($settings);
    $photoUrl = $member->photo_path ? $member->photoUrl() : null;
    $formattedCode = $member->formattedCode();
@endphp

{{-- Hidden fallback QR SVG to guarantee availability on any page --}}
<div id="member-qr-fallback-{{ $member->id }}" class="hidden" aria-hidden="true">
    {!! generate_inline_qr_svg($formattedCode, 180, 0) !!}
</div>

<script>
(function() {
    window.downloadMemberCardPng = async function(customOptions = {}) {
        const btn = document.getElementById(customOptions.buttonId || 'btn-download-member-card');
        const btnText = document.getElementById(customOptions.buttonTextId || 'btn-download-text');
        const originalText = btnText ? btnText.textContent : 'Download Kartu (PNG)';

        if (btnText) btnText.textContent = 'Menyiapkan kartu...';
        if (btn) btn.disabled = true;

        try {
            const siteName = customOptions.siteName || @json($siteName);
            const memberName = customOptions.memberName || @json($member->name);
            const memberCode = customOptions.memberCode || @json($formattedCode);
            const cycleText = customOptions.cycleText || @json($cycleText);
            const progressDesc = customOptions.progressDesc || @json($progressDesc);
            const photoUrl = customOptions.photoUrl !== undefined ? customOptions.photoUrl : @json($photoUrl);

            // Locate QR code SVG
            const qrSelectors = [
                customOptions.qrSelector,
                '#member-card-qr svg',
                '.qr-section svg',
                '[data-member-qr] svg',
                '#member-qr-fallback-{{ $member->id }} svg'
            ].filter(Boolean);

            let qrSvg = null;
            for (const sel of qrSelectors) {
                const el = document.querySelector(sel);
                if (el) {
                    qrSvg = el;
                    break;
                }
            }

            if (!qrSvg) {
                throw new Error('Kode QR kartu member tidak ditemukan.');
            }

            // Canvas dimensions matching standard ID card ratio (1050 x 660 px)
            const width = 1050;
            const height = 660;
            const radius = 32;

            const canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = height;
            const ctx = canvas.getContext('2d');

            function drawRoundedRect(x, y, w, h, r) {
                ctx.beginPath();
                ctx.moveTo(x + r, y);
                ctx.lineTo(x + w - r, y);
                ctx.quadraticCurveTo(x + w, y, x + w, y + r);
                ctx.lineTo(x + w, y + h - r);
                ctx.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
                ctx.lineTo(x + r, y + h);
                ctx.quadraticCurveTo(x, y + h, x, y + h - r);
                ctx.lineTo(x, y + r);
                ctx.quadraticCurveTo(x, y, x + r, y);
                ctx.closePath();
            }

            // Clip outer card with rounded corners
            ctx.save();
            drawRoundedRect(0, 0, width, height, radius);
            ctx.clip();

            // Background fill
            ctx.fillStyle = '#FFFFFF';
            ctx.fillRect(0, 0, width, height);

            // Header banner (y: 0 to 110)
            ctx.fillStyle = '#F8FAFC';
            ctx.fillRect(0, 0, width, 110);
            ctx.strokeStyle = '#E2E8F0';
            ctx.lineWidth = 2;
            ctx.beginPath();
            ctx.moveTo(0, 110);
            ctx.lineTo(width, 110);
            ctx.stroke();

            // Brand title
            ctx.fillStyle = '#0F172A';
            ctx.font = '900 32px system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
            ctx.textAlign = 'left';
            ctx.textBaseline = 'alphabetic';
            ctx.fillText(siteName.toUpperCase(), 50, 70);

            // Brand gold dot accent
            const titleWidth = ctx.measureText(siteName.toUpperCase()).width;
            ctx.fillStyle = '#F59E0B';
            ctx.beginPath();
            ctx.arc(50 + titleWidth + 12, 60, 6, 0, Math.PI * 2);
            ctx.fill();

            // Badge "KARTU MEMBER"
            const badgeW = 180;
            const badgeH = 40;
            const badgeX = width - 50 - badgeW;
            const badgeY = 35;
            drawRoundedRect(badgeX, badgeY, badgeW, badgeH, 10);
            ctx.fillStyle = '#0F172A';
            ctx.fill();

            ctx.fillStyle = '#FFFFFF';
            ctx.font = '800 15px system-ui, -apple-system, BlinkMacSystemFont, sans-serif';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText('KARTU MEMBER', badgeX + badgeW / 2, badgeY + badgeH / 2);

            // Reset text align for card content
            ctx.textAlign = 'left';
            ctx.textBaseline = 'alphabetic';

            // Member Photo or Initial Avatar (x: 50, y: 155, size: 170)
            const photoX = 50;
            const photoY = 155;
            const photoSize = 170;
            const photoR = 24;

            let photoRendered = false;
            if (photoUrl) {
                try {
                    const img = new Image();
                    img.crossOrigin = 'anonymous';
                    img.src = photoUrl;
                    await new Promise((resolve, reject) => {
                        img.onload = () => resolve();
                        img.onerror = () => reject();
                        setTimeout(() => reject(), 2500);
                    });

                    ctx.save();
                    drawRoundedRect(photoX, photoY, photoSize, photoSize, photoR);
                    ctx.clip();
                    ctx.drawImage(img, photoX, photoY, photoSize, photoSize);
                    ctx.restore();
                    photoRendered = true;
                } catch (e) {
                    photoRendered = false;
                }
            }

            if (!photoRendered) {
                drawRoundedRect(photoX, photoY, photoSize, photoSize, photoR);
                ctx.fillStyle = '#F1F5F9';
                ctx.fill();
                ctx.strokeStyle = '#CBD5E1';
                ctx.lineWidth = 2;
                ctx.stroke();

                const initial = (memberName || 'M').trim().charAt(0).toUpperCase();
                ctx.fillStyle = '#475569';
                ctx.font = '800 76px system-ui, -apple-system, sans-serif';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(initial, photoX + photoSize / 2, photoY + photoSize / 2);
                ctx.textAlign = 'left';
                ctx.textBaseline = 'alphabetic';
            }

            // Member Info (x: 250)
            const infoX = 250;
            let nameSize = 36;
            if (memberName.length > 25) nameSize = 26;
            else if (memberName.length > 18) nameSize = 30;

            ctx.fillStyle = '#0F172A';
            ctx.font = '800 ' + nameSize + 'px system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
            ctx.fillText(memberName, infoX, 215);

            ctx.fillStyle = '#64748B';
            ctx.font = '600 20px system-ui, -apple-system, sans-serif';
            ctx.fillText('Program Kunjungan Berhadiah', infoX, 255);

            if (cycleText) {
                ctx.fillStyle = '#D97706';
                ctx.font = '700 18px system-ui, -apple-system, sans-serif';
                ctx.fillText(cycleText, infoX, 292);
            }

            if (progressDesc) {
                ctx.fillStyle = '#64748B';
                ctx.font = '500 15px system-ui, -apple-system, sans-serif';
                const trimmedDesc = progressDesc.length > 55 ? progressDesc.substring(0, 52) + '...' : progressDesc;
                ctx.fillText(trimmedDesc, infoX, 322);
            }

            // QR Code Container Box (x: 730, y: 145, size: 270)
            const qrBoxX = 730;
            const qrBoxY = 145;
            const qrBoxSize = 270;
            drawRoundedRect(qrBoxX, qrBoxY, qrBoxSize, qrBoxSize, 20);
            ctx.fillStyle = '#FFFFFF';
            ctx.fill();
            ctx.strokeStyle = '#E2E8F0';
            ctx.lineWidth = 2;
            ctx.stroke();

            // Render QR Code SVG to Canvas Image
            const serializer = new XMLSerializer();
            const svgString = serializer.serializeToString(qrSvg);
            const svgBlob = new Blob([svgString], { type: 'image/svg+xml;charset=utf-8' });
            const blobUrl = URL.createObjectURL(svgBlob);
            const qrImg = new Image();
            qrImg.src = blobUrl;

            await new Promise((resolve) => {
                qrImg.onload = () => {
                    ctx.drawImage(qrImg, qrBoxX + 15, qrBoxY + 15, qrBoxSize - 30, qrBoxSize - 30);
                    URL.revokeObjectURL(blobUrl);
                    resolve();
                };
                qrImg.onerror = () => {
                    URL.revokeObjectURL(blobUrl);
                    resolve();
                };
            });

            // Footer Section (y: 520 to 660)
            ctx.fillStyle = '#F8FAFC';
            ctx.fillRect(0, 520, width, 140);
            ctx.strokeStyle = '#E2E8F0';
            ctx.lineWidth = 2;
            ctx.beginPath();
            ctx.moveTo(0, 520);
            ctx.lineTo(width, 520);
            ctx.stroke();

            // Left Footer: Code
            ctx.fillStyle = '#94A3B8';
            ctx.font = '700 14px system-ui, -apple-system, sans-serif';
            ctx.fillText('ID KARTU MEMBER', 50, 560);

            ctx.fillStyle = '#0F172A';
            ctx.font = '800 28px ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace';
            ctx.fillText(memberCode, 50, 600);

            // Right Footer: Notice
            ctx.fillStyle = '#64748B';
            ctx.font = '500 15px system-ui, -apple-system, sans-serif';
            ctx.textAlign = 'right';
            ctx.fillText('Tunjukkan kartu ini ke petugas saat menonton.', width - 50, 575);
            ctx.fillStyle = '#94A3B8';
            ctx.font = '600 14px ui-monospace, SFMono-Regular, monospace';
            ctx.fillText('synemax.web.id', width - 50, 600);

            // Bottom Amber Accent bar
            ctx.fillStyle = '#F59E0B';
            ctx.fillRect(0, height - 8, width, 8);

            // Restore outer clip & draw card border
            ctx.restore();
            drawRoundedRect(1, 1, width - 2, height - 2, radius);
            ctx.strokeStyle = '#CBD5E1';
            ctx.lineWidth = 2;
            ctx.stroke();

            // Download file
            canvas.toBlob((blob) => {
                if (!blob) throw new Error('Gagal menghasilkan file gambar.');

                const safeCode = memberCode.replace(/[^A-Za-z0-9]/g, '-').toLowerCase();
                const filename = 'kartu-member-synemax-' + safeCode + '.png';
                const fileUrl = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.download = filename;
                a.href = fileUrl;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                setTimeout(() => URL.revokeObjectURL(fileUrl), 2000);

                if (btnText) btnText.textContent = 'Tersimpan!';
                setTimeout(() => {
                    if (btnText) btnText.textContent = originalText;
                    if (btn) btn.disabled = false;
                }, 2000);
            }, 'image/png');

        } catch (error) {
            console.error('Download card error:', error);
            if (btnText) btnText.textContent = 'Gagal mengunduh';
            setTimeout(() => {
                if (btnText) btnText.textContent = originalText;
                if (btn) btn.disabled = false;
            }, 3000);
        }
    };
})();
</script>

