<?php

use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FilmController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ScheduleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SnackMenuController;
use App\Http\Controllers\Admin\SnackPreparationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicBookingController;
use App\Http\Controllers\PublicCinemaController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicCinemaController::class, 'index'])->name('home');
Route::get('/jadwal/{date}', [PublicCinemaController::class, 'schedule'])
    ->where('date', '\d{4}-\d{2}-\d{2}')
    ->name('schedule.date');
Route::get('/booking/{session}', [PublicBookingController::class, 'create'])->name('booking.create');
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
    Route::get('/jadwal', [ScheduleController::class, 'index'])->name('schedule.index');
    Route::post('/jadwal/generate', [ScheduleController::class, 'generate'])->name('schedule.generate');
    Route::post('/jadwal/template', [ScheduleController::class, 'storeTemplate'])->name('schedule.templates.store');
    Route::patch('/jadwal/template/{template}', [ScheduleController::class, 'updateTemplate'])->name('schedule.templates.update');
    Route::delete('/jadwal/template/{template}', [ScheduleController::class, 'destroyTemplate'])->name('schedule.templates.destroy');
    Route::post('/jadwal/libur', [ScheduleController::class, 'storeHoliday'])->name('schedule.holidays.store');
    Route::delete('/jadwal/libur/{holiday}', [ScheduleController::class, 'destroyHoliday'])->name('schedule.holidays.destroy');
    Route::post('/jadwal/tutup', [ScheduleController::class, 'storeBlockedDate'])->name('schedule.blocked.store');
    Route::delete('/jadwal/tutup/{blockedDate}', [ScheduleController::class, 'destroyBlockedDate'])->name('schedule.blocked.destroy');
    Route::patch('/jadwal/sesi/{session}', [ScheduleController::class, 'updateSession'])->name('schedule.sessions.update');
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
    Route::get('/pengaturan', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('/pengaturan', [SettingController::class, 'update'])->name('settings.update');
    Route::delete('/pengaturan/qris', [SettingController::class, 'destroyQris'])->name('settings.qris.destroy');
    Route::get('/laporan', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/laporan/export.csv', [ReportController::class, 'export'])->name('reports.export');
});

require __DIR__.'/auth.php';
