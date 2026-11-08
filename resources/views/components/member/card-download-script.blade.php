@props([
    'member',
    'qrSelector' => null,
])

@php
    $siteName = setting('site_name', 'SynemaX');
    $logoRaw = setting('site_logo');
    $logoUrl = $logoRaw ? (str_starts_with($logoRaw, '/') || str_starts_with($logoRaw, 'http') ? $logoRaw : \Illuminate\Support\Facades\Storage::url($logoRaw)) : null;
    $displayName = $member->nickname ? "{$member->name} ({$member->nickname})" : $member->name;
    $mascotUrl = asset('images/member-mascot.jpg');
    $formattedCode = $member->formattedCode();
@endphp

{{-- Hidden fallback QR SVG to guarantee availability on any page --}}
<div id="member-qr-fallback-{{ $member->id }}" class="hidden" style="display: none !important;" aria-hidden="true">
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
            const logoUrl = customOptions.logoUrl || @json($logoUrl);
            const memberName = customOptions.memberName || @json($displayName);
            const memberCode = customOptions.memberCode || @json($formattedCode);
            const mascotUrl = customOptions.mascotUrl || @json($mascotUrl);

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

            // 1. Draw Brand Logo or Fallback Text
            let logoRendered = false;
            if (logoUrl) {
                try {
                    const logoImg = new Image();
                    logoImg.crossOrigin = 'anonymous';
                    logoImg.src = logoUrl;
                    await new Promise((res, rej) => {
                        logoImg.onload = () => res();
                        logoImg.onerror = () => rej();
                        setTimeout(() => rej(), 2500);
                    });
                    // Scale logo nicely into header height (max height 50px, max width 240px)
                    const aspect = logoImg.width / logoImg.height;
                    let targetH = 50;
                    let targetW = targetH * aspect;
                    if (targetW > 240) {
                        targetW = 240;
                        targetH = targetW / aspect;
                    }
                    const targetY = 30 + (50 - targetH) / 2;
                    ctx.drawImage(logoImg, 50, targetY, targetW, targetH);
                    logoRendered = true;
                } catch (e) {
                    logoRendered = false;
                }
            }

            if (!logoRendered) {
                ctx.fillStyle = '#0F172A';
                ctx.font = '900 32px system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
                ctx.textAlign = 'left';
                ctx.textBaseline = 'alphabetic';
                ctx.fillText(siteName.toUpperCase(), 50, 70);

                const titleWidth = ctx.measureText(siteName.toUpperCase()).width;
                ctx.fillStyle = '#F59E0B';
                ctx.beginPath();
                ctx.arc(50 + titleWidth + 12, 60, 6, 0, Math.PI * 2);
                ctx.fill();
            }

            // 2. Badge "KARTU MEMBER" (Dark with Gold Accent)
            const badgeW = 180;
            const badgeH = 42;
            const badgeX = width - 50 - badgeW;
            const badgeY = 34;
            drawRoundedRect(badgeX, badgeY, badgeW, badgeH, 10);
            ctx.fillStyle = '#0F172A';
            ctx.fill();
            ctx.strokeStyle = '#D97706';
            ctx.lineWidth = 2;
            ctx.stroke();

            ctx.fillStyle = '#F59E0B';
            ctx.font = '800 15px system-ui, -apple-system, BlinkMacSystemFont, sans-serif';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText('KARTU MEMBER', badgeX + badgeW / 2, badgeY + badgeH / 2);

            // Reset text align for card content
            ctx.textAlign = 'left';
            ctx.textBaseline = 'alphabetic';

            // 3. Mascot Photo Frame (x: 50, y: 145, size: 200)
            const mascotX = 50;
            const mascotY = 145;
            const mascotSize = 200;
            const mascotR = 26;

            let mascotRendered = false;
            if (mascotUrl) {
                try {
                    const mImg = new Image();
                    mImg.crossOrigin = 'anonymous';
                    mImg.src = mascotUrl;
                    await new Promise((res, rej) => {
                        mImg.onload = () => res();
                        mImg.onerror = () => rej();
                        setTimeout(() => rej(), 2500);
                    });

                    ctx.save();
                    drawRoundedRect(mascotX, mascotY, mascotSize, mascotSize, mascotR);
                    ctx.clip();
                    ctx.drawImage(mImg, mascotX, mascotY, mascotSize, mascotSize);
                    ctx.restore();

                    // Gold frame border
                    drawRoundedRect(mascotX, mascotY, mascotSize, mascotSize, mascotR);
                    ctx.strokeStyle = '#F59E0B';
                    ctx.lineWidth = 3;
                    ctx.stroke();

                    mascotRendered = true;
                } catch (e) {
                    mascotRendered = false;
                }
            }

            if (!mascotRendered) {
                drawRoundedRect(mascotX, mascotY, mascotSize, mascotSize, mascotR);
                ctx.fillStyle = '#F1F5F9';
                ctx.fill();
                ctx.strokeStyle = '#F59E0B';
                ctx.lineWidth = 3;
                ctx.stroke();
            }

            // 4. Member Info (x: 285)
            const infoX = 285;

            // Label "NAMA MEMBER"
            ctx.fillStyle = '#94A3B8';
            ctx.font = '700 14px system-ui, -apple-system, sans-serif';
            ctx.fillText('NAMA MEMBER', infoX, 190);

            // Member Name + (Nickname)
            let nameSize = 34;
            if (memberName.length > 25) nameSize = 26;
            else if (memberName.length > 18) nameSize = 30;

            ctx.fillStyle = '#0F172A';
            ctx.font = '800 ' + nameSize + 'px system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
            ctx.fillText(memberName, infoX, 230);

            // Label "ID MEMBER"
            ctx.fillStyle = '#94A3B8';
            ctx.font = '700 14px system-ui, -apple-system, sans-serif';
            ctx.fillText('ID MEMBER', infoX, 290);

            // Code
            ctx.fillStyle = '#B45309';
            ctx.font = '800 30px ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace';
            ctx.fillText(memberCode, infoX, 330);

            // 5. QR Code Container Box (x: 730, y: 135, size: 270)
            const qrBoxX = 730;
            const qrBoxY = 135;
            const qrBoxSize = 270;
            drawRoundedRect(qrBoxX, qrBoxY, qrBoxSize, qrBoxSize, 22);
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
                    ctx.drawImage(qrImg, qrBoxX + 18, qrBoxY + 18, qrBoxSize - 36, qrBoxSize - 36);
                    URL.revokeObjectURL(blobUrl);
                    resolve();
                };
                qrImg.onerror = () => {
                    URL.revokeObjectURL(blobUrl);
                    resolve();
                };
            });

            // 6. Footer Section (y: 520 to 660)
            ctx.fillStyle = '#F8FAFC';
            ctx.fillRect(0, 520, width, 140);
            ctx.strokeStyle = '#E2E8F0';
            ctx.lineWidth = 2;
            ctx.beginPath();
            ctx.moveTo(0, 520);
            ctx.lineTo(width, 520);
            ctx.stroke();

            // Left Footer: Cinema tagline
            ctx.fillStyle = '#64748B';
            ctx.font = '600 18px system-ui, -apple-system, sans-serif';
            ctx.fillText('Studio Bioskop Mini Privat', 50, 585);

            // Right Footer: Website
            ctx.fillStyle = '#94A3B8';
            ctx.font = '600 18px ui-monospace, SFMono-Regular, monospace';
            ctx.textAlign = 'right';
            ctx.fillText('synemax.web.id', width - 50, 585);

            // Bottom Amber Accent bar
            ctx.fillStyle = '#F59E0B';
            ctx.fillRect(0, height - 10, width, 10);

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
