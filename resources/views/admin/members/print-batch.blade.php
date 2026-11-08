@php
    $logoRaw = setting('site_logo');
    $logoUrl = $logoRaw ? (str_starts_with($logoRaw, '/') || str_starts_with($logoRaw, 'http') ? $logoRaw : \Illuminate\Support\Facades\Storage::url($logoRaw)) : null;
    $mascotUrl = asset('images/member-mascot.jpg');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Lembar Kartu Member A4 ({{ $members->count() }} Kartu)</title>
    <style>
        @page {
            size: A4 portrait;
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

        /* A4 Page Grid Container (190mm usable width x 277mm usable height) */
        .a4-sheet {
            width: 190mm;
            min-height: 270mm;
            background-color: #ffffff;
            display: grid;
            grid-template-columns: repeat(2, 85.6mm);
            grid-auto-rows: 54mm;
            gap: 6mm 10mm;
            padding: 5mm;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            margin-bottom: 24px;
            page-break-after: always;
            break-after: page;
        }
        .a4-sheet:last-of-type {
            margin-bottom: 0;
            page-break-after: auto;
            break-after: auto;
        }

        .member-card {
            width: 85.6mm;
            height: 54mm;
            background-color: #ffffff;
            color: #0f172a;
            border: 1px dashed #cbd5e1;
            border-bottom: 2.5mm solid #f59e0b;
            border-radius: 3.5mm;
            padding: 3.5mm 4.5mm 2.5mm 4.5mm;
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
            padding-bottom: 1.5mm;
        }
        .brand-area {
            display: flex;
            align-items: center;
            gap: 2mm;
        }
        .brand-logo {
            max-height: 6mm;
            max-width: 32mm;
            object-fit: contain;
        }
        .brand-title {
            font-size: 10.5pt;
            font-weight: 900;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            color: #0f172a;
        }
        .card-badge {
            font-size: 5.8pt;
            font-weight: 800;
            background-color: #0f172a;
            color: #f59e0b;
            border: 0.8px solid #d97706;
            padding: 1px 4.5px;
            border-radius: 2.5px;
            letter-spacing: 0.5px;
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
            width: 21mm;
            height: 21mm;
            border-radius: 3mm;
            overflow: hidden;
            border: 1.2px solid #f59e0b;
            background-color: #ffffff;
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
            font-size: 4.8pt;
            font-weight: 700;
            color: #94a3b8;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 0.3mm;
        }
        .member-name {
            font-size: 9pt;
            font-weight: 800;
            line-height: 1.2;
            color: #0f172a;
            margin-bottom: 1mm;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .member-id-label {
            font-size: 4.5pt;
            font-weight: 700;
            color: #94a3b8;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 0.2mm;
        }
        .code-display {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 8pt;
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
            width: 21mm;
            height: 21mm;
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
            font-size: 4.2pt;
            font-weight: 700;
            color: #94a3b8;
            letter-spacing: 0.4px;
            margin-top: 0.3mm;
            text-transform: uppercase;
        }
        .card-footer {
            border-top: 0.8px solid #f1f5f9;
            padding-top: 0.8mm;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 4.8pt;
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
                display: block;
            }
            .no-print-bar {
                display: none !important;
            }
            .a4-sheet {
                box-shadow: none;
                padding: 5mm;
                margin: 0 auto;
                page-break-after: always;
                break-after: page;
            }
            .a4-sheet:last-of-type {
                page-break-after: auto;
                break-after: auto;
            }
        }
    </style>
</head>
<body>
    <div class="no-print-bar">
        <button type="button" class="btn-print" onclick="window.print()">
            Cetak {{ $members->count() }} Kartu ({{ ceil($members->count() / 8) }} Lembar A4)
        </button>
        <a href="{{ route('admin.members.index') }}" class="btn-close">Kembali ke Daftar</a>
    </div>

    @foreach ($members->chunk(8) as $sheetMembers)
        <div class="a4-sheet">
            @foreach ($sheetMembers as $member)
                @php
                    $displayName = $member->nickname ? "{$member->name} ({$member->nickname})" : $member->name;
                @endphp
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
                                {!! generate_inline_qr_svg($member->formattedCode(), 85, 0) !!}
                            </div>
                            <span class="qr-caption">Scan Disini</span>
                        </div>
                    </div>

                    <div class="card-footer">
                        <span>Studio Bioskop Mini Privat</span>
                        <span class="footer-site">synemax.web.id</span>
                    </div>
                </div>
            @endforeach
        </div>
    @endforeach
</body>
</html>
