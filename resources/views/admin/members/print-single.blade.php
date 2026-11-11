@php
    $logoRaw = setting('site_logo');
    $logoUrl = $logoRaw ? (str_starts_with($logoRaw, '/') || str_starts_with($logoRaw, 'http') ? $logoRaw : \Illuminate\Support\Facades\Storage::url($logoRaw)) : null;
    $displayName = $member->nickname ? "{$member->name} ({$member->nickname})" : $member->name;
    $mascotUrl = asset('images/member-mascot.jpg');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Cetak Kartu Member - {{ $displayName }}</title>
    <style>
        @page {
            size: A4;
            margin: 10mm;
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        .hidden {
            display: none !important;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #f1f5f9;
            color: #0f172a;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        .no-print-bar {
            margin-bottom: 24px;
            display: flex;
            gap: 12px;
        }
        .btn-print {
            background-color: #f59e0b;
            color: #000;
            font-weight: 700;
            font-size: 14px;
            padding: 10px 20px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .btn-download {
            background-color: #0f172a;
            color: #ffffff;
            font-weight: 700;
            font-size: 14px;
            padding: 10px 20px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-download:hover {
            background-color: #1e293b;
        }
        .btn-close {
            background-color: #fff;
            color: #334155;
            font-weight: 600;
            font-size: 14px;
            padding: 10px 18px;
            border-radius: 10px;
            border: 1px solid #cbd5e1;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-mark-issued {
            background-color: #fef3c7;
            color: #92400e;
            font-weight: 700;
            font-size: 14px;
            padding: 10px 18px;
            border-radius: 10px;
            border: 1px solid #f59e0b;
            cursor: pointer;
        }
        .btn-mark-issued.is-issued {
            background-color: #dcfce7;
            color: #166534;
            border-color: #22c55e;
            cursor: default;
        }

        /* Kartu Standar ID Card ISO/IEC 7810 ID-1: 85.6mm x 53.98mm */
        .member-card {
            width: 85.6mm;
            height: 54mm;
            background-color: #ffffff;
            color: #0f172a;
            border: 1px solid #cbd5e1;
            border-bottom: 2.5mm solid #f59e0b;
            border-radius: 3.5mm;
            padding: 3.5mm 4.5mm 2.5mm 4.5mm;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            page-break-inside: avoid;
            break-inside: avoid;
            overflow: hidden;
        }
        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1.2px solid #e2e8f0;
            padding-bottom: 1.8mm;
        }
        .brand-area {
            display: flex;
            align-items: center;
            gap: 2mm;
        }
        .brand-logo {
            max-height: 6.5mm;
            max-width: 34mm;
            object-fit: contain;
        }
        .brand-title {
            font-size: 11pt;
            font-weight: 900;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            color: #0f172a;
        }
        .card-badge {
            font-size: 6pt;
            font-weight: 800;
            background-color: #0f172a;
            color: #f59e0b;
            border: 0.8px solid #d97706;
            padding: 1.2px 5px;
            border-radius: 2.5px;
            letter-spacing: 0.6px;
            text-transform: uppercase;
        }
        .card-body {
            display: flex;
            align-items: center;
            gap: 3mm;
            margin-top: 1.5mm;
            margin-bottom: 1.5mm;
            flex: 1;
        }
        .mascot-frame {
            width: 22mm;
            height: 22mm;
            border-radius: 3mm;
            overflow: hidden;
            border: 1.2px solid #f59e0b;
            background-color: #ffffff;
            box-shadow: 0 1px 2px rgba(0,0,0,0.06);
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .mascot-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .member-info {
            flex: 1;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .member-label {
            font-size: 5pt;
            font-weight: 700;
            color: #94a3b8;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            margin-bottom: 0.4mm;
        }
        .member-name {
            font-size: 9.5pt;
            font-weight: 800;
            line-height: 1.2;
            color: #0f172a;
            margin-bottom: 1.2mm;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .member-id-label {
            font-size: 4.8pt;
            font-weight: 700;
            color: #94a3b8;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            margin-bottom: 0.3mm;
        }
        .code-display {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 8.5pt;
            font-weight: 800;
            letter-spacing: 0.5px;
            color: #b45309;
        }
        .qr-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex-shrink: 0;
        }
        .qr-section {
            width: 22mm;
            height: 22mm;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 2mm;
            padding: 0.8mm;
        }
        .qr-section svg {
            width: 100%;
            height: 100%;
            display: block;
        }
        .qr-caption {
            font-size: 4.5pt;
            font-weight: 700;
            color: #94a3b8;
            letter-spacing: 0.4px;
            margin-top: 0.4mm;
            text-transform: uppercase;
        }
        .card-footer {
            border-top: 0.8px solid #f1f5f9;
            padding-top: 1mm;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 5pt;
            color: #64748b;
        }
        .footer-site {
            font-family: ui-monospace, monospace;
            font-weight: 600;
            color: #94a3b8;
        }

        @media print {
            body {
                background-color: #ffffff;
                padding: 0;
                justify-content: flex-start;
            }
            .no-print-bar {
                display: none !important;
            }
            .member-card {
                box-shadow: none;
                border: 1px dashed #94a3b8;
                border-bottom: 2.5mm solid #f59e0b;
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <div class="no-print-bar">
        <button type="button" class="btn-print" onclick="window.print()">Cetak Kartu</button>
        <button type="button" id="btn-download-member-card" class="btn-download" onclick="downloadMemberCardPng()">
            <svg style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
            </svg>
            <span id="btn-download-text">Download Gambar (PNG)</span>
        </button>
        <button
            type="button"
            id="btn-mark-physical-card"
            class="btn-mark-issued {{ $member->hasPhysicalCard() ? 'is-issued' : '' }}"
            onclick="markPhysicalCardIssued()"
            {{ $member->hasPhysicalCard() ? 'disabled' : '' }}
        >
            {{ $member->hasPhysicalCard() ? 'Sudah Ada Kartu Fisik' : 'Tandai Sudah Dicetak' }}
        </button>
        <a href="{{ route('admin.members.show', $member) }}" class="btn-close">Kembali</a>
    </div>

    <div class="member-card">
        <div class="card-header">
            <div class="brand-area">
                @if ($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ setting('site_name', 'SynemaX') }}" class="brand-logo">
                @else
                    <span class="brand-title">{{ setting('site_name', 'SynemaX') }}</span>
                @endif
            </div>
            <span class="card-badge">KARTU MEMBER</span>
        </div>

        <div class="card-body">
            <div class="mascot-frame">
                <img src="{{ $mascotUrl }}" alt="Maskot SynemaX" class="mascot-img">
            </div>

            <div class="member-info">
                <span class="member-label">Nama Member</span>
                <div class="member-name">{{ $displayName }}</div>
                <span class="member-id-label">ID Member</span>
                <span class="code-display">{{ $member->formattedCode() }}</span>
            </div>

            <div class="qr-wrapper">
                <div class="qr-section">
                    {!! generate_inline_qr_svg($member->formattedCode(), 95, 0) !!}
                </div>
                <span class="qr-caption">Scan Disini</span>
            </div>
        </div>

        <div class="card-footer">
            <span>Studio Bioskop Mini Privat</span>
            <span class="footer-site">synemax.web.id</span>
        </div>
    </div>

    <x-member.card-download-script :member="$member" />
    <script>
        async function markPhysicalCardIssued() {
            const btn = document.getElementById('btn-mark-physical-card');
            if (!btn || btn.disabled) return;
            btn.disabled = true;
            btn.textContent = 'Menyimpan...';
            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const res = await fetch(@json(route('admin.members.toggle_physical_card', $member)), {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ has_physical_card: true }),
                });
                if (res.ok) {
                    btn.classList.add('is-issued');
                    btn.textContent = 'Sudah Ada Kartu Fisik';
                } else {
                    btn.disabled = false;
                    btn.textContent = 'Tandai Sudah Dicetak';
                }
            } catch (e) {
                btn.disabled = false;
                btn.textContent = 'Tandai Sudah Dicetak';
            }
        }
    </script>
</body>
</html>
