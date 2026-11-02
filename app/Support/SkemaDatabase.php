<?php

namespace App\Support;

/**
 * Pemeriksa kesiapan skema database.
 *
 * Latar belakang: ketika sebuah versi baru aplikasi menambah kolom/tabel lewat migrasi tetapi
 * pengguna belum menjalankan `php artisan migrate`, fitur-fitur gagal DIAM-DIAM (mis. harga
 * tampil "Rp 0" dan tombol "Ajukan" tidak bereaksi). Kelas ini dipakai middleware
 * PeriksaMigrasi dan perintah `php artisan perkap:periksa` untuk mendeteksi kondisi itu
 * dan menampilkan petunjuk yang jelas.
 */
class SkemaDatabase
{
    /**
     * Nama migrasi yang ada di database/migrations tetapi belum tercatat sudah dijalankan.
     *
     * @return list<string>
     */
    public static function migrasiTertunda(): array
    {
        $migrator = app('migrator');

        if (! $migrator->repositoryExists()) {
            return ['(tabel "migrations" belum ada — database masih kosong)'];
        }

        $berkas = $migrator->getMigrationFiles(array_merge($migrator->paths(), [database_path('migrations')]));
        $sudahJalan = $migrator->getRepository()->getRan();

        return array_values(array_diff(array_keys($berkas), $sudahJalan));
    }
}
