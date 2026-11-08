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
            color: #000000;
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
        }

        .member-card {
            width: 85.6mm;
            height: 54mm;
            background-color: #ffffff;
            color: #000000;
            border: 1px dashed #94a3b8;
            border-radius: 3.5mm;
            padding: 3.5mm 4.5mm;
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
            border-bottom: 1.2px solid #000000;
            padding-bottom: 1.5mm;
        }
        .brand-title {
            font-size: 11pt;
            font-weight: 900;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .card-badge {
            font-size: 6.5pt;
            font-weight: 800;
            background-color: #000000;
            color: #ffffff;
            padding: 1px 5px;
            border-radius: 2px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .card-body {
            display: flex;
            align-items: center;
            gap: 3mm;
            margin-top: 2mm;
        }
        .member-photo {
            width: 20mm;
            height: 20mm;
            border-radius: 2.5mm;
            object-fit: cover;
            border: 1px solid #cbd5e1;
            background-color: #f8fafc;
        }
        .member-avatar-placeholder {
            width: 20mm;
            height: 20mm;
            border-radius: 2.5mm;
            border: 1px solid #cbd5e1;
            background-color: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14pt;
            font-weight: 800;
            color: #64748b;
        }
        .member-info {
            flex: 1;
            overflow: hidden;
        }
        .member-name {
            font-size: 10pt;
            font-weight: 800;
            line-height: 1.2;
            color: #000000;
            margin-bottom: 1mm;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .member-cycle-tag {
            font-size: 6.5pt;
            font-weight: 600;
            color: #475569;
        }
        .qr-section {
            width: 22mm;
            height: 22mm;
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
            font-size: 8pt;
            font-weight: 800;
            letter-spacing: 0.5px;
            color: #000000;
        }
        .card-notice {
            font-size: 5pt;
            color: #64748b;
            text-align: right;
            max-width: 38mm;
            line-height: 1.1;
        }

        @media print {
            body {
                background-color: #ffffff;
                padding: 0;
            }
            .no-print-bar {
                display: none !important;
            }
            .a4-sheet {
                box-shadow: none;
                padding: 0;
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <div class="no-print-bar">
        <button type="button" class="btn-print" onclick="window.print()">Cetak Lembar A4 ({{ $members->count() }} Kartu)</button>
        <a href="{{ route('admin.members.index') }}" class="btn-close">Kembali ke Daftar</a>
    </div>

    <div class="a4-sheet">
        @foreach ($members as $member)
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
                        {!! generate_inline_qr_svg($member->formattedCode(), 85, 0) !!}
                    </div>
                </div>

                <div class="card-footer">
                    <span class="code-display">{{ $member->formattedCode() }}</span>
                    <span class="card-notice">Tunjukkan kartu ini saat datang ke bioskop untuk mencatat kunjungan & reward.</span>
                </div>
            </div>
        @endforeach
    </div>
</body>
</html>

