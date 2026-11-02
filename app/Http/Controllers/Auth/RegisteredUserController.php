<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\DaftarRequest;
use App\Models\User;
use App\Support\BatasPendaftaran;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegisteredUserController extends Controller
{
    public function create(Request $request)
    {
        // Kuota ditampilkan di formulir supaya pengguna tahu SEBELUM mengisi bila jaringannya sudah mentok.
        return view('auth.register', ['kuota' => BatasPendaftaran::untuk($request->ip())]);
    }

    /**
     * Alur pendaftaran:
     *  1. Validasi (form request) — termasuk keunikan email/HP/username dalam bentuk baku.
     *  2. Ambil kunci per-IP lalu periksa kuota 3 akun/24 jam, DAN buat akun, di dalam kunci yang sama.
     *     Tanpa kunci, tiga permintaan serentak dari IP yang sama bisa lolos semua (race condition).
     *  3. Akun baru otomatis mendapat ID Anggota unik (lihat event model User) lalu masuk ke Kartu Anggota.
     */
    public function store(DaftarRequest $request): RedirectResponse
    {
        $masukan = $request->safe()->except(['password', 'password_confirmation']);

        $kuotaAwal = BatasPendaftaran::untuk($request->ip());
        $kunci = Cache::lock('daftar-ip:'.$kuotaAwal['kunci'], 15);

        try {
            $kunci->block(5);
        } catch (LockTimeoutException) {
            return back()->withInput($masukan)->withErrors([
                'email' => 'Server sedang memproses pendaftaran lain dari jaringan Anda. Coba lagi beberapa detik lagi.',
            ]);
        }

        try {
            // Periksa ulang DI DALAM kunci: pendaftaran lain mungkin baru saja selesai.
            $kuota = BatasPendaftaran::untuk($request->ip());
            if ($kuota['tercapai']) {
                return back()->withInput($masukan)->withErrors(['batas_ip' => BatasPendaftaran::pesan($kuota)]);
            }

            $user = $this->buatAkun($request->validated(), $kuota['kunci']);
        } finally {
            $kunci->release();
        }

        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate(); // cegah session fixation

        return redirect()->route('user.kartu')
            ->with('baru_daftar', true)
            ->with('sukses', "Akun berhasil dibuat. Selamat datang, {$user->nama}!");
    }

    /**
     * Simpan akun baru. Jaminan keunikan sebenarnya ada di indeks UNIQUE database; bila ID Anggota
     * (acak) kebetulan bentrok — peluangnya sangat kecil — dibuat ulang otomatis. Bentrok pada
     * email/HP/username (mis. dua orang mendaftar dengan data sama pada detik yang sama) dilaporkan
     * sebagai galat validasi biasa, bukan halaman error.
     */
    private function buatAkun(array $data, string $kunciIp): User
    {
        for ($percobaan = 1; ; $percobaan++) {
            try {
                return DB::transaction(function () use ($data, $kunciIp) {
                    $user = new User([
                        'nama' => $data['nama'],
                        'username' => $data['username'],
                        'email' => $data['email'],
                        'no_hp' => $data['no_hp'],
                        'password' => $data['password'], // di-hash otomatis oleh cast 'hashed'
                        'role' => 'peminjam',             // pendaftar baru selalu peminjam
                    ]);

                    // Kolom milik sistem: tidak pernah lewat mass-assignment
                    $user->forceFill(['ip_daftar' => $kunciIp, 'syarat_disetujui_pada' => now()]);
                    $user->save();

                    return $user;
                });
            } catch (UniqueConstraintViolationException $e) {
                $pesan = $e->getMessage();

                if (str_contains($pesan, 'kode_anggota') && $percobaan < 5) {
                    continue; // ID Anggota bentrok → buat ID lain
                }

                throw ValidationException::withMessages([
                    match (true) {
                        str_contains($pesan, 'username') => 'username',
                        str_contains($pesan, 'no_hp') => 'no_hp',
                        default => 'email',
                    } => 'Data ini baru saja dipakai akun lain. Periksa kembali atau gunakan data yang berbeda.',
                ]);
            }
        }
    }
}
