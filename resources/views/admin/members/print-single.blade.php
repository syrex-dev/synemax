<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Kartu Member - {{ $member->name }}</title>
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
        /* Kartu Standar ID Card 85.6mm x 53.98mm */
        .member-card {
            width: 85.6mm;
            height: 54mm;
            background-color: #ffffff;
            color: #000000;
            border: 1px solid #e2e8f0;
            border-radius: 4mm;
            padding: 4mm 5mm;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1.5px solid #000000;
            padding-bottom: 2mm;
        }
        .brand-title {
            font-size: 12pt;
            font-weight: 900;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .card-badge {
            font-size: 7pt;
            font-weight: 800;
            background-color: #000000;
            color: #ffffff;
            padding: 1.5px 6px;
            border-radius: 3px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .card-body {
            display: flex;
            align-items: center;
            gap: 3.5mm;
            margin-top: 2.5mm;
        }
        .member-photo {
            width: 22mm;
            height: 22mm;
            border-radius: 3mm;
            object-fit: cover;
            border: 1px solid #cbd5e1;
            background-color: #f8fafc;
        }
        .member-avatar-placeholder {
            width: 22mm;
            height: 22mm;
            border-radius: 3mm;
            border: 1px solid #cbd5e1;
            background-color: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16pt;
            font-weight: 800;
            color: #64748b;
        }
        .member-info {
            flex: 1;
            overflow: hidden;
        }
        .member-name {
            font-size: 11pt;
            font-weight: 800;
            line-height: 1.2;
            color: #000000;
            margin-bottom: 1mm;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .member-cycle-tag {
            font-size: 7pt;
            font-weight: 600;
            color: #475569;
        }
        .qr-section {
            width: 25mm;
            height: 25mm;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .qr-section svg {
            width: 100%;
            height: 100%;
        }
        .card-footer {
            border-top: 1px solid #e2e8f0;
            padding-top: 1.5mm;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .code-display {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 8.5pt;
            font-weight: 800;
            letter-spacing: 0.5px;
            color: #000000;
        }
        .card-notice {
            font-size: 5.5pt;
            color: #64748b;
            text-align: right;
            max-width: 40mm;
            line-height: 1.2;
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
        <a href="{{ route('admin.members.show', $member) }}" class="btn-close">Kembali</a>
    </div>

    <div class="member-card">
        <div class="card-header">
            <span class="brand-title">{{ setting('site_name', 'SynemaX') }}</span>
            <span class="card-badge">KARTU MEMBER</span>
        </div>

        <div class="card-body">
            @if ($member->photo_path)
                <img src="{{ $member->photoUrl() }}" alt="{{ $member->name }}" class="member-photo">
            @else
                <div class="member-avatar-placeholder">
                    {{ strtoupper(substr($member->name, 0, 1)) }}
                </div>
            @endif

            <div class="member-info">
                <div class="member-name">{{ $member->name }}</div>
                <div class="member-cycle-tag">Program Kunjungan Berhadiah</div>
            </div>

            <div class="qr-section">
                {!! generate_inline_qr_svg($member->formattedCode(), 95, 0) !!}
            </div>
        </div>

        <div class="card-footer">
            <span class="code-display">{{ $member->formattedCode() }}</span>
            <span class="card-notice">Tunjukkan kartu ini saat datang ke bioskop untuk mencatat kunjungan & reward.</span>
        </div>
    </div>

    <x-member.card-download-script :member="$member" />
</body>
</html>

