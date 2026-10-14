{{-- Halaman sukses gabungan lama telah digantikan oleh halaman Pembayaran (/pembayaran/{kode}) dan halaman Tiket (/tiket/{kode}) --}}
<script>
    window.location.href = @js(route('booking.ticket', ['code' => $booking->code]));
</script>
<noscript>
    <meta http-equiv="refresh" content="0;url={{ route('booking.ticket', ['code' => $booking->code]) }}">
</noscript>
