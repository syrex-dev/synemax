<?php

use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FilmController;
use App\Http\Controllers\Admin\NewsController;
use App\Http\Controllers\Admin\NobarController;
use App\Http\Controllers\Admin\PromoController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ScheduleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SnackMenuController;
use App\Http\Controllers\Admin\SnackPreparationController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicBookingController;
use App\Http\Controllers\PublicCinemaController;
use App\Http\Controllers\PublicInfoController;
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
Route::get('/booking/{session}', [PublicBookingController::class, 'create'])->name('booking.create');
Route::get('/booking/{session}/ketersediaan', [PublicBookingController::class, 'availabilityJson'])
    ->middleware('throttle:60,1')
    ->name('booking.availability');
Route::post('/booking/{session}', [PublicBookingController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('booking.store');
Route::get('/pembayaran/qris', [PublicBookingController::class, 'qrisImage'])->name('payment.qris');
Route::get('/pembayaran/{code}', [PublicBookingController::class, 'paymentView'])->name('booking.payment');
Route::get('/tiket/{code}', [PublicBookingController::class, 'ticketView'])->name('booking.ticket');
Route::get('/booking/{code}/sukses', [PublicBookingController::class, 'success'])->name('booking.success');
Route::get('/booking/{code}/status-ringkas', [PublicBookingController::class, 'statusJson'])->name('booking.status.json');
Route::get('/booking/{code}/bukti', [PublicBookingController::class, 'showProof'])->name('booking.proof.show');
Route::post('/booking/{code}/bukti', [PublicBookingController::class, 'uploadProof'])
    ->middleware('throttle:10,1')
    ->name('booking.proof.upload');
Route::get('/cek-booking', [PublicBookingController::class, 'lookupForm'])->name('booking.lookup.form');
Route::post('/cek-booking', [PublicBookingController::class, 'lookup'])
    ->middleware('throttle:10,1')
    ->name('booking.lookup');
Route::post('/cek-booking/{code}/batal', [PublicBookingController::class, 'cancel'])
    ->middleware('throttle:5,1')
    ->name('booking.cancel');
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
    Route::get('/films/buat', [FilmController::class, 'create'])->name('films.create');
    Route::post('/films', [FilmController::class, 'store'])->name('films.store');
    Route::get('/films/{film}/ubah', [FilmController::class, 'edit'])->name('films.edit');
    Route::put('/films/{film}', [FilmController::class, 'update'])->name('films.update');
    Route::delete('/films/{film}', [FilmController::class, 'destroy'])->name('films.destroy');
    Route::patch('/films/{film}/status', [FilmController::class, 'toggleStatus'])->name('films.status');
    Route::post('/films/{film}/duplikat', [FilmController::class, 'duplicate'])->name('films.duplicate');
    Route::get('/jadwal', [ScheduleController::class, 'index'])->name('schedule.index');
    Route::post('/jadwal/generate', [ScheduleController::class, 'generate'])->name('schedule.generate');
    Route::post('/jadwal/template', [ScheduleController::class, 'storeTemplate'])->name('schedule.templates.store');
    Route::patch('/jadwal/template/{template}', [ScheduleController::class, 'updateTemplate'])->name('schedule.templates.update');
    Route::delete('/jadwal/template/{template}', [ScheduleController::class, 'destroyTemplate'])->name('schedule.templates.destroy');
    Route::post('/jadwal/libur', [ScheduleController::class, 'storeHoliday'])->name('schedule.holidays.store');
    Route::delete('/jadwal/libur/{holiday}', [ScheduleController::class, 'destroyHoliday'])->name('schedule.holidays.destroy');
    Route::post('/jadwal/tutup', [ScheduleController::class, 'storeBlockedDate'])->name('schedule.blocked.store');
    Route::patch('/jadwal/sesi/{session}', [ScheduleController::class, 'updateSession'])->name('schedule.sessions.update');
    Route::patch('/jadwal/sesi/{session}/toggle', [ScheduleController::class, 'toggleSessionStatus'])->name('schedule.sessions.toggle');
    Route::post('/jadwal/sesi/{session}/selesaikan', [ScheduleController::class, 'resolveSession'])->name('schedule.sessions.resolve');
    Route::post('/jadwal/hari/toggle', [ScheduleController::class, 'toggleDaySessions'])->name('schedule.day.toggle');
    Route::patch('/density', [DashboardController::class, 'updateDensity'])->name('density.update');
    Route::get('/polling/status', [DashboardController::class, 'pollStatus'])->name('polling.status');
    Route::get('/booking', [BookingController::class, 'index'])->name('bookings.index');
    Route::get('/booking/{booking}', [BookingController::class, 'show'])->name('bookings.show');
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
    Route::get('/banners/buat', [BannerController::class, 'create'])->name('banners.create');
    Route::post('/banners', [BannerController::class, 'store'])->name('banners.store');
    Route::get('/banners/{banner}/ubah', [BannerController::class, 'edit'])->name('banners.edit');
    Route::put('/banners/{banner}', [BannerController::class, 'update'])->name('banners.update');
    Route::delete('/banners/{banner}', [BannerController::class, 'destroy'])->name('banners.destroy');
    Route::patch('/banners/{banner}/status', [BannerController::class, 'toggleStatus'])->name('banners.status');
    Route::post('/banners/reorder', [BannerController::class, 'reorder'])->name('banners.reorder');

    // Admin Promo
    Route::get('/promos', [PromoController::class, 'index'])->name('promos.index');
    Route::get('/promos/buat', [PromoController::class, 'create'])->name('promos.create');
    Route::post('/promos', [PromoController::class, 'store'])->name('promos.store');
    Route::get('/promos/{promo}/ubah', [PromoController::class, 'edit'])->name('promos.edit');
    Route::put('/promos/{promo}', [PromoController::class, 'update'])->name('promos.update');
    Route::delete('/promos/{promo}', [PromoController::class, 'destroy'])->name('promos.destroy');
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

    Route::get('/pengaturan', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('/pengaturan', [SettingController::class, 'update'])->name('settings.update');
    Route::delete('/pengaturan/qris', [SettingController::class, 'destroyQris'])->name('settings.qris.destroy');
    Route::post('/pengaturan/cache-clear', [SettingController::class, 'clearCache'])->name('settings.cache.clear');
    Route::post('/pengaturan/reset', [SettingController::class, 'resetDefaults'])->name('settings.reset');
    Route::get('/laporan', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/laporan/export.csv', [ReportController::class, 'export'])->name('reports.export');

    // Aliases for admin navigation
    Route::redirect('/schedule', '/admin/jadwal');
    Route::redirect('/articles', '/admin/berita');
    Route::get('/settings', fn (Request $r) => redirect('/admin/pengaturan'.($r->getQueryString() ? '?'.$r->getQueryString() : '')));
    Route::redirect('/snacks/preparation', '/admin/jajanan/siapkan');
    Route::redirect('/reports', '/admin/laporan');
});

require __DIR__.'/auth.php';
