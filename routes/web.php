<?php

use App\Http\Controllers\Admin\AgeRatingController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FilmController;
use App\Http\Controllers\Admin\FilmImportController;
use App\Http\Controllers\Admin\FilmPosterController;
use App\Http\Controllers\Admin\GenreController;
use App\Http\Controllers\Admin\MemberController;
use App\Http\Controllers\Admin\NewsController;
use App\Http\Controllers\Admin\NobarController;
use App\Http\Controllers\Admin\PromoController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ScheduleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SnackMenuController;
use App\Http\Controllers\Admin\SnackPreparationController;
use App\Http\Controllers\Admin\TicketScannerController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicBookingController;
use App\Http\Controllers\PublicCinemaController;
use App\Http\Controllers\PublicInfoController;
use App\Http\Controllers\PublicMemberController;
use App\Http\Controllers\QrCodeController;
use App\Http\Controllers\StyleguideController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicCinemaController::class, 'index'])->name('home');
Route::get('/jadwal', [PublicCinemaController::class, 'schedule'])->name('schedule.index');
Route::get('/jadwal/{date}', [PublicCinemaController::class, 'schedule'])
    ->where('date', '\d{4}-\d{2}-\d{2}')
    ->name('schedule.date');

// Public Film Catalog & Detail
Route::get('/film', [PublicCinemaController::class, 'filmIndex'])->name('films.index');
Route::get('/film/{slug}', [PublicCinemaController::class, 'filmShow'])->name('films.show');

// Public Promo Catalog & Detail
Route::get('/promo', [PublicCinemaController::class, 'promoIndex'])->name('promos.index');
Route::get('/promo/{slug}', [PublicCinemaController::class, 'promoShow'])->name('promos.show');

// Media Routes (independent of storage symlink)
Route::get('/media/nobar/{filename}', [MediaController::class, 'nobarPoster'])->name('media.nobar');
Route::get('/media/berita/{filename}', [MediaController::class, 'articleCover'])->name('media.article');

// Public Nobar & News Routes
Route::get('/nobar', [PublicInfoController::class, 'nobarIndex'])->name('nobar.index');
Route::get('/nobar/{slug}', [PublicInfoController::class, 'nobarShow'])->name('nobar.show');
Route::get('/nobar/{slug}/kalender', [PublicInfoController::class, 'nobarCalendar'])->name('nobar.calendar');

Route::get('/kabar', [PublicInfoController::class, 'articleIndex'])->name('articles.index');
Route::get('/kabar/{slug}', [PublicInfoController::class, 'articleShow'])->name('articles.show');

// Public Signed Digital Member Card
Route::get('/member/{code}', [PublicMemberController::class, 'show'])
    ->name('public.member.card')
    ->middleware('throttle:60,1');

// Helpful aliases & graceful redirects for FAQ & Harga and accidental trailing quotes
Route::redirect('/faq', '/kabar?tab=harga-faq');
Route::redirect('/harga', '/kabar?tab=harga-faq');
Route::redirect('/harga-faq', '/kabar?tab=harga-faq');
Route::redirect('/faq-harga', '/kabar?tab=harga-faq');
Route::redirect('/faq&harga', '/kabar?tab=harga-faq');
Route::redirect('/tarif', '/kabar?tab=harga-faq');
Route::redirect('/kabar\'', '/kabar?tab=harga-faq');
Route::get('/booking/{session}', [PublicBookingController::class, 'create'])->name('booking.create');
Route::get('/booking/{session}/ketersediaan', [PublicBookingController::class, 'availabilityJson'])
    ->middleware('throttle:60,1')
    ->name('booking.availability');
Route::post('/booking/{session}', [PublicBookingController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('booking.store');
Route::get('/pembayaran/qris', [PublicBookingController::class, 'qrisImage'])->name('payment.qris');
Route::get('/pembayaran/{code}', [PublicBookingController::class, 'paymentView'])->name('booking.payment');
Route::get('/bayar/{code}', fn (string $code) => redirect()->route('booking.payment', ['code' => $code], 301));
Route::get('/tiket/{code}', [PublicBookingController::class, 'ticketView'])->name('booking.ticket');
Route::get('/qr-code', [QrCodeController::class, 'show'])
    ->middleware('throttle:60,1')
    ->name('qr.code');
Route::get('/booking/{code}/sukses', [PublicBookingController::class, 'success'])->name('booking.success');
Route::get('/booking/{code}/status-ringkas', [PublicBookingController::class, 'statusJson'])->name('booking.status.json');
Route::get('/booking/{code}/bukti', [PublicBookingController::class, 'showProof'])->name('booking.proof.show');
Route::post('/booking/{code}/bukti', [PublicBookingController::class, 'uploadProof'])
    ->middleware('throttle:10,1')
    ->name('booking.proof.upload');
Route::get('/tiket', [PublicBookingController::class, 'ticketIndex'])->name('booking.ticket.index');
Route::post('/tiket/tambah', [PublicBookingController::class, 'lookupAndAdd'])
    ->middleware('throttle:10,1')
    ->name('booking.ticket.add');
Route::post('/tiket/{code}/batal', [PublicBookingController::class, 'cancel'])
    ->middleware('throttle:5,1')
    ->name('booking.cancel');
Route::redirect('/cek-booking', '/tiket', 301)->name('booking.lookup.form');
Route::post('/cek-booking', [PublicBookingController::class, 'lookupAndAdd'])
    ->middleware('throttle:10,1')
    ->name('booking.lookup');
Route::post('/cek-booking/{code}/batal', [PublicBookingController::class, 'cancel'])
    ->middleware('throttle:5,1');
Route::post('/cek-booking/{code}/ubah-film', [PublicBookingController::class, 'changeFilm'])
    ->middleware('throttle:10,1')
    ->name('booking.change-film');
Route::post('/cek-booking/{code}/pindah-sesi', [PublicBookingController::class, 'reschedule'])
    ->middleware('throttle:10,1')
    ->name('booking.reschedule');
Route::post('/cek-booking/{code}/upgrade', [PublicBookingController::class, 'upgrade'])
    ->middleware('throttle:10,1')
    ->name('booking.upgrade');
Route::get('/styleguide', StyleguideController::class)->name('styleguide');

Route::get('/dashboard', function () {
    return redirect()->route('admin.dashboard');
})->middleware(['auth', 'admin'])->name('dashboard');

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/films', [FilmController::class, 'index'])->name('films.index');
    Route::post('/films/bulk-action', [FilmController::class, 'bulkAction'])->name('films.bulk_action');
    Route::get('/films/impor/template', [FilmImportController::class, 'downloadTemplate'])->name('films.import.template');
    Route::get('/films/impor', [FilmImportController::class, 'create'])->name('films.import.create');
    Route::post('/films/impor/preview', [FilmImportController::class, 'preview'])->name('films.import.preview');
    Route::post('/films/impor/process-chunk', [FilmImportController::class, 'processChunk'])->name('films.import.chunk');
    Route::get('/films/impor/lanjut/{batchToken}', [FilmImportController::class, 'resume'])->name('films.import.resume');
    Route::get('/films/impor/report-errors/{batchToken}', [FilmImportController::class, 'downloadErrorReport'])->name('films.import.report_errors');
    Route::post('/films/impor/confirm', [FilmImportController::class, 'confirm'])->name('films.import.confirm');
    Route::post('/films/impor/rollback/{batchId}', [FilmImportController::class, 'rollback'])->name('films.import.rollback');
    Route::get('/films/export', [FilmImportController::class, 'exportCatalog'])->name('films.export');

    // Dedicated bulk poster management
    Route::get('/films/posters', [FilmPosterController::class, 'index'])->name('films.posters.index');
    Route::post('/films/posters', [FilmPosterController::class, 'upload'])->name('films.posters.upload');
    Route::post('/films/posters/process-single', [FilmPosterController::class, 'processSingle'])->name('films.posters.process_single');
    Route::post('/films/posters/upload-zip', [FilmPosterController::class, 'uploadZip'])->name('films.posters.upload_zip');
    Route::post('/films/posters/process-batch-item', [FilmPosterController::class, 'processBatchItem'])->name('films.posters.process_batch_item');
    Route::post('/films/posters/cleanup-batch', [FilmPosterController::class, 'cleanupBatch'])->name('films.posters.cleanup_batch');
    Route::post('/films/posters/cleanup-storage', [FilmPosterController::class, 'cleanStorage'])->name('films.posters.cleanup_storage');
    Route::post('/films/posters/empty-quarantine', [FilmPosterController::class, 'emptyQuarantine'])->name('films.posters.empty_quarantine');
    Route::post('/films/posters/assign', [FilmPosterController::class, 'assign'])->name('films.posters.assign');
    Route::post('/films/posters/assign-batch', [FilmPosterController::class, 'assignBatch'])->name('films.posters.assign_batch');
    Route::post('/films/posters/undo-batch', [FilmPosterController::class, 'undoBatch'])->name('films.posters.undo_batch');
    Route::post('/films/posters/remove-matched', [FilmPosterController::class, 'removeMatched'])->name('films.posters.remove_matched');
    Route::delete('/films/posters/{filename}', [FilmPosterController::class, 'destroy'])->name('films.posters.destroy')->where('filename', '.*');
    Route::redirect('/films/create', '/admin/films/buat');
    Route::get('/films/buat', [FilmController::class, 'create'])->name('films.create');
    Route::post('/films', [FilmController::class, 'store'])->name('films.store');
    Route::get('/films/{film}/ubah', [FilmController::class, 'edit'])->name('films.edit');
    Route::put('/films/{film}', [FilmController::class, 'update'])->name('films.update');
    Route::delete('/films/{film}', [FilmController::class, 'destroy'])->name('films.destroy');
    Route::patch('/films/{film}/status', [FilmController::class, 'toggleStatus'])->name('films.status');
    Route::post('/films/{film}/duplikat', [FilmController::class, 'duplicate'])->name('films.duplicate');
    Route::post('/films/{id}/pulihkan', [FilmController::class, 'restore'])->name('films.restore');
    Route::delete('/films/{id}/hapus-permanen', [FilmController::class, 'forceDestroy'])->name('films.force_destroy');

    // Age Ratings CRUD
    Route::get('/age-ratings', [AgeRatingController::class, 'index'])->name('age_ratings.index');
    Route::post('/age-ratings', [AgeRatingController::class, 'store'])->name('age_ratings.store');
    Route::put('/age-ratings/{ageRating}', [AgeRatingController::class, 'update'])->name('age_ratings.update');
    Route::delete('/age-ratings/{ageRating}', [AgeRatingController::class, 'destroy'])->name('age_ratings.destroy');
    Route::patch('/age-ratings/{ageRating}/toggle', [AgeRatingController::class, 'toggle'])->name('age_ratings.toggle');
    Route::post('/age-ratings/reorder', [AgeRatingController::class, 'reorder'])->name('age_ratings.reorder');

    // Genres CRUD
    Route::get('/genres', [GenreController::class, 'index'])->name('genres.index');
    Route::post('/genres', [GenreController::class, 'store'])->name('genres.store');
    Route::put('/genres/{genre}', [GenreController::class, 'update'])->name('genres.update');
    Route::post('/genres/{genre}/merge', [GenreController::class, 'merge'])->name('genres.merge');
    Route::delete('/genres/{genre}', [GenreController::class, 'destroy'])->name('genres.destroy');
    Route::patch('/genres/{genre}/toggle', [GenreController::class, 'toggle'])->name('genres.toggle');
    Route::post('/genres/reorder', [GenreController::class, 'reorder'])->name('genres.reorder');
    Route::get('/jadwal', [ScheduleController::class, 'index'])->name('schedule.index');
    Route::post('/jadwal/generate', [ScheduleController::class, 'generate'])->name('schedule.generate');
    Route::post('/jadwal/template', [ScheduleController::class, 'storeTemplate'])->name('schedule.templates.store');
    Route::patch('/jadwal/template/{template}', [ScheduleController::class, 'updateTemplate'])->name('schedule.templates.update');
    Route::delete('/jadwal/template/{template}', [ScheduleController::class, 'destroyTemplate'])->name('schedule.templates.destroy');
    Route::post('/jadwal/libur', [ScheduleController::class, 'storeHoliday'])->name('schedule.holidays.store');
    Route::delete('/jadwal/libur/{holiday}', [ScheduleController::class, 'destroyHoliday'])->name('schedule.holidays.destroy');
    Route::post('/jadwal/tutup', [ScheduleController::class, 'storeBlockedDate'])->name('schedule.blocked.store');
    Route::post('/jadwal/sesi', [ScheduleController::class, 'storeSession'])->name('schedule.sessions.store');
    Route::patch('/jadwal/sesi/{session}', [ScheduleController::class, 'updateSession'])->name('schedule.sessions.update');
    Route::delete('/jadwal/sesi/{session}', [ScheduleController::class, 'destroySession'])->name('schedule.sessions.destroy');
    Route::patch('/jadwal/sesi/{session}/toggle', [ScheduleController::class, 'toggleSessionStatus'])->name('schedule.sessions.toggle');
    Route::post('/jadwal/sesi/{session}/selesaikan', [ScheduleController::class, 'resolveSession'])->name('schedule.sessions.resolve');
    Route::post('/jadwal/hari/toggle', [ScheduleController::class, 'toggleDaySessions'])->name('schedule.day.toggle');
    Route::patch('/density', [DashboardController::class, 'updateDensity'])->name('density.update');
    Route::get('/polling/status', [DashboardController::class, 'pollStatus'])->name('polling.status');
    Route::get('/booking', [BookingController::class, 'index'])->name('bookings.index');
    Route::get('/booking/search', [BookingController::class, 'liveSearch'])->name('bookings.live_search');
    Route::get('/booking/{booking}', [BookingController::class, 'show'])->name('bookings.show');
    Route::get('/booking/{booking}/tiket', [BookingController::class, 'ticket'])->name('bookings.ticket');
    Route::patch('/booking/{booking}/jajan', [BookingController::class, 'updateSnacks'])->name('bookings.snacks.update');
    Route::patch('/booking/{booking}/jajan/status', [BookingController::class, 'updateSnackStatuses'])->name('bookings.snacks.status');
    Route::patch('/booking/{booking}/status', [BookingController::class, 'updateStatus'])->name('bookings.status');
    Route::get('/booking/{booking}/bukti', [BookingController::class, 'proof'])->name('bookings.proof');
    Route::get('/jajanan', [SnackMenuController::class, 'index'])->name('snacks.index');
    Route::post('/jajanan/kategori', [SnackMenuController::class, 'storeCategory'])->name('snacks.categories.store');
    Route::patch('/jajanan/kategori/{category}', [SnackMenuController::class, 'updateCategory'])->name('snacks.categories.update');
    Route::delete('/jajanan/kategori/{category}', [SnackMenuController::class, 'destroyCategory'])->name('snacks.categories.destroy');
    Route::get('/jajanan/menu/buat', [SnackMenuController::class, 'create'])->name('snacks.create');
    Route::post('/jajanan/menu', [SnackMenuController::class, 'store'])->name('snacks.store');
    Route::get('/jajanan/menu/{menuItem}/ubah', [SnackMenuController::class, 'edit'])->name('snacks.edit');
    Route::put('/jajanan/menu/{menuItem}', [SnackMenuController::class, 'update'])->name('snacks.update');
    Route::delete('/jajanan/menu/{menuItem}', [SnackMenuController::class, 'destroy'])->name('snacks.destroy');
    Route::patch('/jajanan/menu/{menuItem}/tersedia', [SnackMenuController::class, 'toggleAvailability'])->name('snacks.availability');
    Route::patch('/jajanan/menu/{menuItem}/stok', [SnackMenuController::class, 'adjustStock'])->name('snacks.stock');
    Route::get('/jajanan/siapkan', [SnackPreparationController::class, 'index'])->name('snacks.preparation.index');
    Route::get('/jajanan/sesi/{session}', [SnackPreparationController::class, 'show'])->name('snacks.preparation.show');
    Route::patch('/jajanan/sesi/{session}/status', [SnackPreparationController::class, 'updateSessionStatuses'])->name('snacks.preparation.status');
    Route::patch('/jajanan/pesanan/{bookingItem}/status', [SnackPreparationController::class, 'updateItemStatus'])->name('snacks.items.status');
    Route::patch('/pelanggan/{profile}/blokir', [BookingController::class, 'toggleCustomerBlock'])->name('customers.toggle-block');

    // Admin Nobar
    Route::get('/nobar', [NobarController::class, 'index'])->name('nobar.index');
    Route::get('/nobar/buat', [NobarController::class, 'create'])->name('nobar.create');
    Route::post('/nobar', [NobarController::class, 'store'])->name('nobar.store');
    Route::get('/nobar/{nobar}/ubah', [NobarController::class, 'edit'])->name('nobar.edit');
    Route::put('/nobar/{nobar}', [NobarController::class, 'update'])->name('nobar.update');
    Route::delete('/nobar/{nobar}', [NobarController::class, 'destroy'])->name('nobar.destroy');
    Route::patch('/nobar/{nobar}/status', [NobarController::class, 'updateStatus'])->name('nobar.status');
    Route::post('/nobar/{nobar}/duplikat', [NobarController::class, 'duplicate'])->name('nobar.duplicate');

    // Admin Banner Hero
    Route::get('/banners', [BannerController::class, 'index'])->name('banners.index');
    Route::redirect('/banners/create', '/admin/banners/buat');
    Route::get('/banners/buat', [BannerController::class, 'create'])->name('banners.create');
    Route::post('/banners', [BannerController::class, 'store'])->name('banners.store');
    Route::get('/banners/{banner}/ubah', [BannerController::class, 'edit'])->name('banners.edit');
    Route::put('/banners/{banner}', [BannerController::class, 'update'])->name('banners.update');
    Route::delete('/banners/{banner}', [BannerController::class, 'destroy'])->name('banners.destroy');
    Route::post('/banners/{id}/pulihkan', [BannerController::class, 'restore'])->name('banners.restore');
    Route::patch('/banners/{banner}/status', [BannerController::class, 'toggleStatus'])->name('banners.status');
    Route::post('/banners/reorder', [BannerController::class, 'reorder'])->name('banners.reorder');

    // Admin Promo
    Route::get('/promos', [PromoController::class, 'index'])->name('promos.index');
    Route::redirect('/promos/create', '/admin/promos/buat');
    Route::get('/promos/buat', [PromoController::class, 'create'])->name('promos.create');
    Route::post('/promos', [PromoController::class, 'store'])->name('promos.store');
    Route::get('/promos/{promo}/ubah', [PromoController::class, 'edit'])->name('promos.edit');
    Route::put('/promos/{promo}', [PromoController::class, 'update'])->name('promos.update');
    Route::delete('/promos/{promo}', [PromoController::class, 'destroy'])->name('promos.destroy');
    Route::post('/promos/{id}/pulihkan', [PromoController::class, 'restore'])->name('promos.restore');
    Route::patch('/promos/{promo}/status', [PromoController::class, 'toggleStatus'])->name('promos.status');
    Route::post('/promos/{promo}/duplikat', [PromoController::class, 'duplicate'])->name('promos.duplicate');
    Route::post('/promos/reorder', [PromoController::class, 'reorder'])->name('promos.reorder');

    // Admin Kabar / Berita
    Route::get('/berita', [NewsController::class, 'index'])->name('articles.index');
    Route::get('/berita/buat', [NewsController::class, 'create'])->name('articles.create');
    Route::post('/berita', [NewsController::class, 'store'])->name('articles.store');
    Route::get('/berita/{article}/ubah', [NewsController::class, 'edit'])->name('articles.edit');
    Route::put('/berita/{article}', [NewsController::class, 'update'])->name('articles.update');
    Route::delete('/berita/{article}', [NewsController::class, 'destroy'])->name('articles.destroy');
    Route::post('/berita/{id}/pulihkan', [NewsController::class, 'restore'])->name('articles.restore');

    Route::get('/pengaturan', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('/pengaturan', [SettingController::class, 'update'])->name('settings.update');
    Route::delete('/pengaturan/qris', [SettingController::class, 'destroyQris'])->name('settings.qris.destroy');
    Route::post('/pengaturan/cache-clear', [SettingController::class, 'clearCache'])->name('settings.cache.clear');
    Route::post('/pengaturan/reset', [SettingController::class, 'resetDefaults'])->name('settings.reset');
    Route::post('/pengaturan/delete-sample', [SettingController::class, 'deleteSampleData'])->name('settings.delete_sample');
    Route::post('/pengaturan/telegram/test', [SettingController::class, 'testTelegram'])->name('settings.telegram.test');
    Route::post('/pengaturan/telegram/detect', [SettingController::class, 'detectTelegramChats'])->name('settings.telegram.detect');
    Route::post('/pengaturan/telegram/retry/{id}', [SettingController::class, 'retryTelegramNotification'])->name('settings.telegram.retry');
    Route::get('/laporan', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/laporan/export.csv', [ReportController::class, 'export'])->name('reports.export');

    // Ticket Scanner
    Route::get('/scan', [TicketScannerController::class, 'index'])->name('scan.index');
    Route::post('/scan/cari', [TicketScannerController::class, 'lookup'])->name('scan.lookup')->middleware('throttle:120,1');
    Route::post('/scan/hadir', [TicketScannerController::class, 'checkIn'])->name('scan.checkin')->middleware('throttle:120,1');
    Route::post('/scan/batal-hadir', [TicketScannerController::class, 'undo'])->name('scan.undo');
    Route::get('/scan/sesi-bookings', [TicketScannerController::class, 'sessionBookings'])->name('scan.session_bookings');
    Route::post('/scan/member-hadir', [TicketScannerController::class, 'memberCheckIn'])->name('scan.member_checkin')->middleware('throttle:120,1');
    Route::post('/scan/member-batal-hadir', [TicketScannerController::class, 'memberUndo'])->name('scan.member_undo');
    Route::post('/scan/member-reward-settle', [TicketScannerController::class, 'memberRewardSettle'])->name('scan.member_reward_settle');

    // Program Member & Loyalitas
    Route::get('/members', [MemberController::class, 'index'])->name('members.index');
    Route::get('/members/scan', [MemberController::class, 'scan'])->name('members.scan');
    Route::get('/members/create', [MemberController::class, 'create'])->name('members.create');
    Route::post('/members', [MemberController::class, 'store'])->name('members.store');
    Route::get('/members/kartu', [MemberController::class, 'printBatch'])->name('members.print_batch');
    Route::get('/members/export.csv', [MemberController::class, 'exportCsv'])->name('members.export');
    Route::get('/members/{member}', [MemberController::class, 'show'])->name('members.show');
    Route::get('/members/{member}/edit', [MemberController::class, 'edit'])->name('members.edit');
    Route::put('/members/{member}', [MemberController::class, 'update'])->name('members.update');
    Route::delete('/members/{member}', [MemberController::class, 'destroy'])->name('members.destroy');
    Route::post('/members/{member}/toggle-status', [MemberController::class, 'toggleStatus'])->name('members.toggle_status');
    Route::post('/members/{member}/new-link', [MemberController::class, 'regenerateLink'])->name('members.regenerate_link');
    Route::post('/members/{member}/opening-visits', [MemberController::class, 'updateOpeningVisits'])->name('members.update_opening_visits');
    Route::post('/members/{member}/void-visit/{visit}', [MemberController::class, 'voidVisit'])->name('members.void_visit');
    Route::post('/members/{member}/settle-reward/{reward}', [MemberController::class, 'settleReward'])->name('members.settle_reward');
    Route::get('/members/{member}/kartu', [MemberController::class, 'printSingle'])->name('members.print_single');

    // Aliases for admin navigation
    Route::redirect('/schedule', '/admin/jadwal');
    Route::redirect('/articles', '/admin/berita');
    Route::get('/settings', fn (Request $r) => redirect('/admin/pengaturan'.($r->getQueryString() ? '?'.$r->getQueryString() : '')));
    Route::redirect('/snacks/preparation', '/admin/jajanan/siapkan');
    Route::redirect('/reports', '/admin/laporan');
});

// Alias /scan dan /members mengarah ke panel admin
Route::get('/scan', fn () => redirect()->route('admin.scan.index'))->middleware('auth');
Route::get('/scan-member', fn () => redirect()->route('admin.members.scan'))->middleware('auth');
Route::get('/members', fn () => redirect()->route('admin.members.index'))->middleware('auth');

require __DIR__.'/auth.php';
