<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * 2 akun demo wajib (sesuai catatan brief) + 2 akun peminjam tambahan
     * supaya data contoh pengajuan di PengajuanSeeder lebih bervariasi.
     */
    public function run(): void
    {
        User::create([
            'nama' => 'Budi Santoso',
            'email' => 'admin@perkap.test',
            'username' => 'admin',
            'password' => Hash::make('password'),
            'no_hp' => '081234567890',
            'role' => 'petugas_perkap',
        ]);

        User::create([
            'nama' => 'Ahmad Fauzi',
            'email' => 'peminjam@perkap.test',
            'username' => 'peminjam1',
            'password' => Hash::make('password'),
            'no_hp' => '081234567891',
            'role' => 'peminjam',
        ]);

        User::create([
            'nama' => 'Siti Nurhaliza',
            'email' => 'siti@perkap.test',
            'username' => 'siti_n',
            'password' => Hash::make('password'),
            'no_hp' => '081234567892',
            'role' => 'peminjam',
        ]);

        User::create([
            'nama' => 'Rizky Ramadhan',
            'email' => 'rizky@perkap.test',
            'username' => 'rizky_r',
            'password' => Hash::make('password'),
            'no_hp' => '081234567893',
            'role' => 'peminjam',
        ]);
    }
}
