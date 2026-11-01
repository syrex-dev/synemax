<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class QrCodeController extends Controller
{
    public function show(Request $request): Response
    {
        $data = trim((string) ($request->query('data') ?? $request->query('code') ?? ''));

        // 1. Validasi keberadaan & panjang (maksimal 32 karakter)
        if ($data === '' || strlen($data) > 32) {
            abort(422, 'Kode booking tidak valid atau melebihi batas panjang 32 karakter.');
        }

        // 2. Validasi format kode booking: hanya alfanumerik dan tanda hubung
        if (! preg_match('/^[A-Z0-9-]{6,32}$/i', $data)) {
            abort(422, 'Format kode booking tidak valid.');
        }

        // 3. Cegah penggunaan sebagai generator QR teks bebas: harus berupa kode booking valid di sistem
        $bookingExists = Booking::query()->where('code', $data)->exists();
        if (! $bookingExists) {
            abort(404, 'Kode booking tidak ditemukan di sistem.');
        }

        $size = min(max((int) $request->query('size', 150), 64), 512);
        $margin = min(max((int) $request->query('margin', 1), 0), 10);

        $renderer = new ImageRenderer(
            new RendererStyle($size, $margin),
            new SvgImageBackEnd
        );

        $writer = new Writer($renderer);
        $svg = $writer->writeString($data, 'UTF-8', ErrorCorrectionLevel::M());

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=604800, immutable',
        ]);
    }
}
