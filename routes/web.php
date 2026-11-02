<?php

use App\Http\Controllers\Admin\AlatController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\LaporanController;
use App\Http\Controllers\Admin\PeminjamanController;
use App\Http\Controllers\Admin\PenggunaController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\NotifikasiController;
use App\Http\Controllers\User\KartuAnggotaController;
use App\Http\Controllers\User\KatalogController;
use App\Http\Controllers\User\PengajuanController;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\VerifikasiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Halaman utama -> redirect sesuai status login & role
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    if (auth()->check()) {
        return redirect(auth()->user()->isAdmin() ? route('admin.dashboard') : route('user.dashboard'));
    }

    return redirect()->route('login');
});

/*
|--------------------------------------------------------------------------
| AUTH
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);

    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    // throttle:daftar = batas PERCOBAAN kirim formulir per IP/jam (anti spam/bot). Batas JUMLAH AKUN
    // per IP (mis. 3 per 24 jam) diperiksa di dalam controller (App\Support\BatasPendaftaran).
    Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('throttle:daftar');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| VERIFIKASI ID ANGGOTA — publik (boleh dibuka tanpa login), dibatasi 30 permintaan/menit per IP
|--------------------------------------------------------------------------
*/
Route::middleware('throttle:verifikasi')->group(function () {
    Route::get('/verifikasi', [VerifikasiController::class, 'index'])->name('verifikasi');
    Route::get('/verifikasi/{kode}', [VerifikasiController::class, 'show'])->name('verifikasi.cek');
});

/*
|--------------------------------------------------------------------------
| NOTIFIKASI — dipakai bersama admin & peminjam (bell icon + badge)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->prefix('notifikasi')->name('notifikasi.')->group(function () {
    Route::get('/', [NotifikasiController::class, 'index'])->name('index');
    Route::patch('/{id}/baca', [NotifikasiController::class, 'tandaiDibaca'])->name('baca');
    Route::patch('/baca-semua', [NotifikasiController::class, 'tandaiSemuaDibaca'])->name('baca-semua');
});

/*
|--------------------------------------------------------------------------
| AREA PEMINJAM (role: peminjam)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:peminjam'])->prefix('user')->name('user.')->group(function () {
    Route::get('/dashboard', [KatalogController::class, 'dashboard'])->name('dashboard');

    Route::get('/kartu-anggota', [KartuAnggotaController::class, 'show'])->name('kartu');

    Route::get('/pengajuan', [PengajuanController::class, 'index'])->name('pengajuan.index');
    Route::get('/pengajuan/buat', [PengajuanController::class, 'create'])->name('pengajuan.create');
    Route::post('/pengajuan', [PengajuanController::class, 'store'])->name('pengajuan.store');
    Route::get('/pengajuan/{pengajuan}', [PengajuanController::class, 'show'])->name('pengajuan.show');
    Route::delete('/pengajuan/{pengajuan}', [PengajuanController::class, 'batalkan'])->name('pengajuan.batalkan');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
});

/*
|--------------------------------------------------------------------------
| AREA ADMIN / PETUGAS PERKAP (role: petugas_perkap)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:petugas_perkap'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::get('/perlengkapan', [AlatController::class, 'index'])->name('perlengkapan.index');
    Route::get('/perlengkapan/tambah', [AlatController::class, 'create'])->name('perlengkapan.create');
    Route::post('/perlengkapan', [AlatController::class, 'store'])->name('perlengkapan.store');
    Route::get('/perlengkapan/{alat}/edit', [AlatController::class, 'edit'])->name('perlengkapan.edit');
    Route::put('/perlengkapan/{alat}', [AlatController::class, 'update'])->name('perlengkapan.update');
    Route::delete('/perlengkapan/{alat}', [AlatController::class, 'destroy'])->name('perlengkapan.destroy');

    Route::get('/peminjaman', [PeminjamanController::class, 'index'])->name('peminjaman.index');
    Route::get('/peminjaman/{peminjaman}', [PeminjamanController::class, 'show'])->name('peminjaman.show');
    Route::patch('/peminjaman/{peminjaman}/setujui', [PeminjamanController::class, 'approve'])->name('peminjaman.approve');
    Route::patch('/peminjaman/{peminjaman}/tolak', [PeminjamanController::class, 'reject'])->name('peminjaman.reject');
    Route::patch('/peminjaman/{peminjaman}/kembalikan', [PeminjamanController::class, 'kembalikan'])->name('peminjaman.kembalikan');

    Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');
    Route::get('/laporan/pdf', [LaporanController::class, 'exportPdf'])->name('laporan.pdf');
    Route::get('/laporan/excel', [LaporanController::class, 'exportExcel'])->name('laporan.excel');
    Route::post('/laporan/{pengajuan}/peringatan', [LaporanController::class, 'kirimPeringatan'])->name('laporan.peringatan');

    // Kelola akun peminjam: cari/verifikasi, catat pelanggaran, blokir, hapus, pulihkan.
    // withTrashed(): akun yang sudah dihapus tetap bisa dibuka (untuk dipulihkan) oleh petugas.
    Route::get('/pengguna', [PenggunaController::class, 'index'])->name('pengguna.index');
    Route::get('/pengguna/{pengguna}', [PenggunaController::class, 'show'])->withTrashed()->name('pengguna.show');
    Route::post('/pengguna/{pengguna}/blokir', [PenggunaController::class, 'blokir'])->withTrashed()->name('pengguna.blokir');
    Route::post('/pengguna/{pengguna}/buka-blokir', [PenggunaController::class, 'bukaBlokir'])->withTrashed()->name('pengguna.buka-blokir');
    Route::delete('/pengguna/{pengguna}', [PenggunaController::class, 'destroy'])->withTrashed()->name('pengguna.destroy');
    Route::post('/pengguna/{pengguna}/pulihkan', [PenggunaController::class, 'pulihkan'])->withTrashed()->name('pengguna.pulihkan');
    Route::post('/pengguna/{pengguna}/pelanggaran', [PenggunaController::class, 'catatPelanggaran'])->withTrashed()->name('pengguna.pelanggaran.store');
    Route::delete('/pelanggaran/{pelanggaran}', [PenggunaController::class, 'hapusPelanggaran'])->name('pelanggaran.destroy');

    // Admin juga bisa kelola profil sendiri — dipakai bareng dg controller & view milik peminjam,
    // view-nya otomatis extend layout admin (lihat @extends dinamis di user/profile.blade.php)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
});
