<?php

namespace App\Console\Commands;

use App\Models\Alat;
use App\Models\User;
use App\Support\SkemaDatabase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * php artisan perkap:periksa
 *
 * Diagnosis cepat kesiapan sistem. Dibuat karena kegagalan paling sering bukan di kode, melainkan
 * di lingkungan: database belum dimigrasi, .env belum cocok, aset frontend belum dibuild, dsb.
 * Setiap pemeriksaan yang gagal disertai langkah perbaikan.
 */
class PeriksaSistem extends Command
{
    protected $signature = 'perkap:periksa';

    protected $description = 'Periksa kesiapan sistem: database, migrasi, kolom penting, storage, aset, dan akun petugas';

    /** Kolom yang WAJIB ada agar semua fitur berjalan. */
    private const KOLOM_WAJIB = [
        'users' => ['nama', 'email', 'username', 'no_hp', 'role', 'kode_anggota', 'no_hp_normal', 'email_kanonik', 'status_akun', 'ip_daftar', 'syarat_disetujui_pada', 'diblokir_pada', 'alasan_blokir', 'diblokir_oleh'],
        'alats' => ['kode_alat', 'nama_alat', 'kategori', 'deskripsi', 'satuan', 'harga_sewa', 'stok_total', 'stok_tersedia', 'gambar'],
        'pengajuan_peminjaman' => ['kode_pengajuan', 'user_id', 'tanggal_pinjam', 'tanggal_kembali', 'status_pengajuan'],
        'pengajuan_detail' => ['pengajuan_id', 'alat_id', 'jumlah', 'harga_saat_pinjam', 'kondisi_saat_kembali'],
        'notifikasis' => ['user_id', 'judul', 'pesan', 'tipe', 'is_read'],
        'pelanggaran' => ['user_id', 'pengajuan_id', 'jenis', 'keterangan', 'dicatat_oleh'],
    ];

    private int $masalah = 0;

    public function handle(): int
    {
        $this->newLine();
        $this->line('<options=bold>Pemeriksaan sistem Perkap Pramuka</>');
        $this->newLine();

        $this->cek('APP_KEY terisi', fn () => filled(config('app.key')) ?: 'APP_KEY kosong.', 'Jalankan: php artisan key:generate');

        $terhubung = $this->cek(
            'Koneksi database ('.config('database.default').')',
            function () {
                DB::connection()->getPdo();

                return true;
            },
            'Nyalakan MySQL, buat database sesuai DB_DATABASE, lalu cocokkan DB_* di file .env (kemudian: php artisan config:clear)'
        );

        if ($terhubung) {
            $this->cek('Semua migrasi sudah dijalankan', function () {
                $tertunda = SkemaDatabase::migrasiTertunda();

                return $tertunda === [] ?: 'Belum dijalankan: '.implode(', ', $tertunda);
            }, 'Jalankan: php artisan migrate   (data yang ada TIDAK dihapus)');

            foreach (self::KOLOM_WAJIB as $tabel => $kolom) {
                $this->cek("Tabel {$tabel} lengkap", function () use ($tabel, $kolom) {
                    if (! Schema::hasTable($tabel)) {
                        return "Tabel {$tabel} tidak ada.";
                    }
                    $kurang = array_values(array_diff($kolom, Schema::getColumnListing($tabel)));

                    return $kurang === [] ?: 'Kolom hilang: '.implode(', ', $kurang);
                }, 'Jalankan: php artisan migrate');
            }

            if (config('session.driver') === 'database') {
                $this->cek('Tabel sessions ada (SESSION_DRIVER=database)', fn () => Schema::hasTable('sessions') ?: 'Tabel sessions tidak ada.', 'Jalankan: php artisan migrate');
            }

            $this->cek('Ada akun Petugas Perkap (admin)', fn () => User::where('role', 'petugas_perkap')->exists() ?: 'Belum ada akun admin.', 'Jalankan: php artisan db:seed --class=UserSeeder');
            $this->cek('Data perlengkapan tersedia', fn () => Alat::count() > 0 ?: 'Tabel alats kosong.', 'Jalankan: php artisan db:seed --class=AlatSeeder');
        }

        $this->cek('Folder storage & bootstrap/cache dapat ditulis', fn () => (is_writable(storage_path()) && is_writable(base_path('bootstrap/cache'))) ?: 'Folder tidak dapat ditulis.', 'Beri izin tulis pada folder storage dan bootstrap/cache');
        $this->cek('Tautan public/storage (foto alat & avatar)', fn () => file_exists(public_path('storage')) ?: 'Belum ada.', 'Jalankan: php artisan storage:link');
        $this->cek('Aset frontend sudah dibuild', fn () => (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot'))) ?: 'public/build/manifest.json tidak ditemukan.', 'Jalankan: npm install lalu npm run build (atau npm run dev)');

        $this->newLine();
        if ($this->masalah === 0) {
            $this->info('Semua pemeriksaan lulus. Sistem siap dipakai.');

            return self::SUCCESS;
        }

        $this->error("{$this->masalah} pemeriksaan gagal — ikuti saran di atas lalu jalankan perintah ini lagi.");

        return self::FAILURE;
    }

    /** Jalankan satu pemeriksaan; true = lulus, string = pesan masalah. Mengembalikan status lulus. */
    private function cek(string $nama, callable $uji, string $saran): bool
    {
        try {
            $hasil = $uji();
        } catch (Throwable $e) {
            $hasil = $e->getMessage();
        }

        if ($hasil === true) {
            $this->line("  <fg=green>✔</> {$nama}");

            return true;
        }

        $this->masalah++;
        $this->line("  <fg=red>✘</> {$nama}");
        if (is_string($hasil)) {
            $this->line('      <fg=gray>'.str($hasil)->limit(200).'</>');
        }
        $this->line("      <fg=yellow>→ {$saran}</>");

        return false;
    }
}
