<?php

use App\Support\Identitas;
use App\Support\KodeAnggota;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PENGAMAN = 'users_kode_anggota_permanen';

    /**
     * IDENTITAS UNIK, STATUS AKUN, dan jejak pendaftaran.
     *
     *  - kode_anggota   : ID Anggota unik & permanen (UNIQUE + pengaman agar tak bisa diubah)
     *  - no_hp_normal   : nomor HP bentuk baku (UNIQUE) → "0812…", "+62812…" dianggap sama
     *  - email_kanonik  : email bentuk baku (UNIQUE) → trik titik/+label Gmail dianggap sama
     *  - status_akun    : aktif | diblokir
     *  - ip_daftar      : kunci IP saat mendaftar (untuk batas pendaftaran per IP)
     *  - syarat_disetujui_pada : waktu pengguna menyetujui tata tertib saat mendaftar
     *  - diblokir_pada / alasan_blokir / diblokir_oleh : jejak pemblokiran oleh petugas
     *
     * Migrasi ini IDEMPOTEN dan AMAN untuk database yang sudah berisi data: pengguna lama
     * otomatis diberi ID Anggota, dan nilai baku HP/email hanya diisi bila tidak bentrok
     * (data lama yang kebetulan kembar tidak membuat migrasi gagal).
     */
    public function up(): void
    {
        // 1) Kolom — dicek satu per satu supaya aman dijalankan ulang pada database setengah jadi
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'kode_anggota')) {
                $table->string('kode_anggota', 24)->nullable()->after('id');
            }
            if (! Schema::hasColumn('users', 'no_hp_normal')) {
                $table->string('no_hp_normal', 20)->nullable()->after('no_hp');
            }
            if (! Schema::hasColumn('users', 'email_kanonik')) {
                $table->string('email_kanonik', 191)->nullable()->after('email');
            }
            if (! Schema::hasColumn('users', 'status_akun')) {
                $table->string('status_akun', 12)->default('aktif')->after('role');
            }
            if (! Schema::hasColumn('users', 'ip_daftar')) {
                $table->string('ip_daftar', 45)->nullable();
            }
            if (! Schema::hasColumn('users', 'syarat_disetujui_pada')) {
                $table->timestamp('syarat_disetujui_pada')->nullable();
            }
            if (! Schema::hasColumn('users', 'diblokir_pada')) {
                $table->timestamp('diblokir_pada')->nullable();
            }
            if (! Schema::hasColumn('users', 'alasan_blokir')) {
                $table->text('alasan_blokir')->nullable();
            }
            if (! Schema::hasColumn('users', 'diblokir_oleh')) {
                $table->unsignedBigInteger('diblokir_oleh')->nullable();
            }
        });

        // 2) Isi data untuk pengguna yang sudah ada (sebelum indeks UNIQUE dipasang)
        $this->isiKodeAnggota();
        $this->isiNilaiBaku();
        DB::table('users')->whereNull('status_akun')->update(['status_akun' => 'aktif']);

        // 3) Indeks — UNIQUE mengizinkan banyak NULL, jadi baris yang belum punya nilai tidak bentrok
        $ada = collect(Schema::getIndexes('users'))->pluck('name')->all();
        Schema::table('users', function (Blueprint $table) use ($ada) {
            if (! in_array('users_kode_anggota_unique', $ada, true)) {
                $table->unique('kode_anggota');
            }
            if (! in_array('users_no_hp_normal_unique', $ada, true)) {
                $table->unique('no_hp_normal');
            }
            if (! in_array('users_email_kanonik_unique', $ada, true)) {
                $table->unique('email_kanonik');
            }
            if (! in_array('users_status_akun_index', $ada, true)) {
                $table->index('status_akun');
            }
            if (! in_array('users_ip_daftar_created_at_index', $ada, true)) {
                $table->index(['ip_daftar', 'created_at']);
            }
        });

        // 4) Pengaman tingkat database: ID Anggota tidak bisa diubah, bahkan lewat SQL langsung
        $this->pasangPengaman();
    }

    public function down(): void
    {
        $this->lepasPengaman();

        $indeks = collect(Schema::getIndexes('users'))->pluck('name')->all();
        Schema::table('users', function (Blueprint $table) use ($indeks) {
            foreach ([
                'users_kode_anggota_unique' => fn () => $table->dropUnique('users_kode_anggota_unique'),
                'users_no_hp_normal_unique' => fn () => $table->dropUnique('users_no_hp_normal_unique'),
                'users_email_kanonik_unique' => fn () => $table->dropUnique('users_email_kanonik_unique'),
                'users_status_akun_index' => fn () => $table->dropIndex('users_status_akun_index'),
                'users_ip_daftar_created_at_index' => fn () => $table->dropIndex('users_ip_daftar_created_at_index'),
            ] as $nama => $hapus) {
                if (in_array($nama, $indeks, true)) {
                    $hapus();
                }
            }
        });

        $kolom = array_values(array_filter([
            'kode_anggota', 'no_hp_normal', 'email_kanonik', 'status_akun', 'ip_daftar',
            'syarat_disetujui_pada', 'diblokir_pada', 'alasan_blokir', 'diblokir_oleh',
        ], fn ($k) => Schema::hasColumn('users', $k)));

        if ($kolom) {
            Schema::table('users', fn (Blueprint $table) => $table->dropColumn($kolom));
        }
    }

    /** Beri ID Anggota ke setiap pengguna lama; tahun ID mengikuti tahun akun dibuat. */
    private function isiKodeAnggota(): void
    {
        $terpakai = array_flip(DB::table('users')->whereNotNull('kode_anggota')->pluck('kode_anggota')->all());

        // chunkById (bukan chunk/each): baris yang sudah diisi tidak boleh membuat halaman bergeser
        DB::table('users')->whereNull('kode_anggota')->chunkById(200, function ($pengguna) use (&$terpakai) {
            foreach ($pengguna as $u) {
                $waktu = $u->created_at ? Carbon::parse($u->created_at) : now();
                do {
                    $kode = KodeAnggota::buat($waktu);
                } while (isset($terpakai[$kode]));

                $terpakai[$kode] = true;
                DB::table('users')->where('id', $u->id)->update(['kode_anggota' => $kode]);
            }
        });
    }

    /**
     * Isi nomor HP & email baku. Bila dua pengguna lama ternyata kembar setelah dibakukan,
     * yang ber-id lebih kecil dipertahankan dan yang lain dibiarkan NULL (tidak menggagalkan migrasi).
     */
    private function isiNilaiBaku(): void
    {
        $hp = array_flip(DB::table('users')->whereNotNull('no_hp_normal')->pluck('no_hp_normal')->all());
        $email = array_flip(DB::table('users')->whereNotNull('email_kanonik')->pluck('email_kanonik')->all());

        DB::table('users')
            ->where(fn ($q) => $q->whereNull('no_hp_normal')->orWhereNull('email_kanonik'))
            ->chunkById(200, function ($pengguna) use (&$hp, &$email) {
                foreach ($pengguna as $u) {
                    $ubah = [];

                    if ($u->no_hp_normal === null && ($nilai = Identitas::normalisasiNoHp($u->no_hp)) !== null && ! isset($hp[$nilai])) {
                        $hp[$nilai] = true;
                        $ubah['no_hp_normal'] = $nilai;
                    }

                    if ($u->email_kanonik === null && ($nilai = Identitas::kanonikEmail($u->email)) !== null && ! isset($email[$nilai])) {
                        $email[$nilai] = true;
                        $ubah['email_kanonik'] = $nilai;
                    }

                    if ($ubah) {
                        DB::table('users')->where('id', $u->id)->update($ubah);
                    }
                }
            });
    }

    /**
     * TRIGGER: menolak UPDATE yang mengubah kode_anggota yang sudah terisi.
     * Lapisan lain ada di model User (event `updating`) dan form (field tidak pernah diterima).
     * Bila hosting tidak mengizinkan pembuatan trigger (hak TRIGGER / SUPER tidak tersedia),
     * migrasi TETAP berhasil — perlindungan di tingkat aplikasi tetap aktif.
     */
    private function pasangPengaman(): void
    {
        $driver = DB::getDriverName();
        $pesan = 'ID Anggota bersifat permanen dan tidak dapat diubah.';

        try {
            DB::unprepared('DROP TRIGGER IF EXISTS '.self::PENGAMAN);

            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                DB::unprepared(
                    'CREATE TRIGGER '.self::PENGAMAN.' BEFORE UPDATE ON users FOR EACH ROW BEGIN '
                    .'IF OLD.kode_anggota IS NOT NULL AND NOT (NEW.kode_anggota <=> OLD.kode_anggota) THEN '
                    ."SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = '{$pesan}'; "
                    .'END IF; END'
                );
            } elseif ($driver === 'sqlite') {
                DB::unprepared(
                    'CREATE TRIGGER '.self::PENGAMAN.' BEFORE UPDATE OF kode_anggota ON users FOR EACH ROW '
                    .'WHEN OLD.kode_anggota IS NOT NULL AND NEW.kode_anggota IS NOT OLD.kode_anggota '
                    ."BEGIN SELECT RAISE(ABORT, '{$pesan}'); END"
                );
            }
        } catch (Throwable $e) {
            Log::warning('Trigger pengaman ID Anggota tidak dapat dipasang (dilewati): '.$e->getMessage());
        }
    }

    private function lepasPengaman(): void
    {
        try {
            DB::unprepared('DROP TRIGGER IF EXISTS '.self::PENGAMAN);
        } catch (Throwable $e) {
            // abaikan
        }
    }
};
