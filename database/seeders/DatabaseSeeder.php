<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            AlatSeeder::class,
            PengajuanSeeder::class, // data demo, boleh dihapus panggilannya jika tidak perlu
            DemoPenggunaSeeder::class, // data demo ID Anggota, pelanggaran & blokir (boleh dihapus)
        ]);
    }
}
