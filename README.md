# Sistem Peminjaman Perkap Pramuka — SMK Negeri 5 Surakarta

Aplikasi web manajemen peminjaman & inventaris perlengkapan Pramuka.
Dibangun dengan **Laravel 11**, **Tailwind CSS**, dan **Alpine.js**.

Proyek Akhir Semester Gasal — Pemrograman Teks, Grafik, dan Multimedia
(Kurikulum Merdeka, Kelas XII RPL).

---

## 🆕 Update Terbaru — Identitas Unik, Batas Pendaftaran per IP, Blokir & Hapus Akun

Tiga permintaan terakhir sudah dikerjakan sebagai satu paket:

| Permintaan | Yang dibuat | Di mana melihatnya |
|---|---|---|
| Identitas unik & verifikasi saat membuat akun; tidak bisa diganti dan tidak boleh ada yang sama | **ID Anggota** `PRK-26-K7M2QH-9` + **Kartu Anggota** + halaman **Verifikasi** publik | Setelah daftar → Kartu Anggota (menu peminjam); `/verifikasi` |
| Batasi pendaftaran dari satu IP (mis. maksimal 3 per hari) | Kuota **3 akun / 24 jam per IP** (bisa diubah di `.env`) | Halaman **Daftar** |
| Admin dapat memblokir & menghapus akun bermasalah (mis. 3× penipuan) | Menu **Pengguna** untuk petugas: catat pelanggaran, blokir, hapus, pulihkan | `/admin/pengguna` |

### ⚙️ Cara memasang update ini

**Proyek baru** → ikuti bagian [Instalasi](#-instalasi-langkah-demi-langkah) di bawah.

**Proyek lama yang sudah berjalan** (data jangan sampai hilang):

```bash
# 1. Salin file dari ZIP ke folder proyek.
#    JANGAN menimpa file .env milikmu (isinya kredensial database).
#    Pengaturan baru bersifat opsional; daftarnya ada di .env.example.
composer dump-autoload
php artisan migrate          # menambah kolom & tabel baru; data lama TIDAK dihapus
npm run build                # wajib, supaya tampilan baru ikut ter-compile
php artisan optimize:clear   # membersihkan cache konfigurasi/tampilan lama
php artisan perkap:periksa   # diagnosis: semua baris harus bertanda ✔

# (opsional) data contoh untuk halaman Pengguna:
php artisan db:seed --class=DemoPenggunaSeeder
```

`php artisan migrate` otomatis memberi **ID Anggota** kepada semua akun lama.
Lalu hard refresh browser (`Ctrl+Shift+R`).

---

### 1. ID Anggota — identitas unik & permanen

Setiap akun mendapat satu ID yang dibuat otomatis saat mendaftar, misalnya:

```
PRK-26-K7M2QH-9
 │   │    │     └─ karakter pemeriksa (Luhn mod 32): salah ketik langsung ketahuan
 │   │    └────── 6 karakter acak (tanpa I, L, O, U agar tidak tertukar dengan 1, 0, V)
 │   └─────────── 2 digit tahun akun dibuat (angkatan)
 └─────────────── awalan (bisa diganti lewat PERKAP_PREFIX_ID)
```

**Kenapa acak, bukan nomor urut?** Nomor urut mudah ditebak: siapa pun bisa
"mengarang" ID anggota yang tampak sah atau menebak jumlah anggota. ID acak
dengan karakter pemeriksa tidak bisa dikarang, dan ada ±1 miliar kombinasi per tahun.

**Dijamin tidak ada yang sama** (berlapis):
1. Indeks `UNIQUE` di database — penjaga terakhir yang tidak bisa dilewati.
2. Pembuat ID memeriksa dulu ke database (termasuk akun yang sudah dihapus) dan mengulang bila bentrok.
3. Bila ID acak kebetulan bentrok saat disimpan (peluangnya sangat kecil), ID dibuat ulang otomatis.
   Bentrok pada email/HP/username (mis. dua orang memakai data yang sama di detik yang sama) ditampilkan sebagai galat validasi biasa, bukan halaman error.

**Dijamin tidak bisa diganti** (3 lapis):
1. Aplikasi: kolom `kode_anggota` tidak masuk `$fillable` dan tidak pernah dibaca dari formulir apa pun.
2. Model `User`: upaya mengubahnya melempar `IdentitasTidakBolehDiubah`.
3. Database: trigger `users_kode_anggota_permanen` menolak `UPDATE` pada ID yang sudah terisi, **bahkan lewat SQL langsung**.
   *(Sebagian hosting murah tidak memberi hak `TRIGGER`; bila begitu, migrasi tetap sukses dan
   lapis 1–2 tetap aktif — catatan "Trigger pengaman … dilewati" muncul di `storage/logs/laravel.log`.)*

**Identitas lain juga unik — dengan pembakuan**, supaya tidak bisa disiasati:

| Data | Contoh yang dianggap SAMA |
|---|---|
| No. WhatsApp | `0812-3456-7890`, `+62 812 3456 7890`, `6281234567890` |
| Email | `budi.santoso@gmail.com`, `budisantoso@gmail.com`, `budisantoso+tugas@gmail.com`, `budisantoso@googlemail.com` |
| Username | `Budi`, `budi` (huruf besar-kecil tidak dibedakan) |

Identitas akun yang **dihapus** admin tetap **terpesan**: ID, email, nomor HP, dan username-nya
tidak bisa dipakai mendaftar ulang.

**Kartu Anggota** (menu peminjam → *Kartu Anggota*) bergaya lencana Pramuka: tepi jahitan putus-putus,
ID dengan keterangan tiap bagiannya, tombol salin, dan **Sandi Morse** dari bagian acak ID — jadi
setiap kartu tampak berbeda karena isinya memang berbeda. Kartu otomatis tampil sesaat setelah mendaftar.

**Verifikasi** (halaman publik `/verifikasi`, tanpa login — mis. dibuka guru/petugas saat peminjam menunjukkan kartunya):
- Mengetik ID boleh huruf kecil, tanpa tanda hubung, atau berspasi.
- Hasilnya dibedakan: **sah** · **salah ketik** (karakter pemeriksa tidak cocok) · **tidak terdaftar** · **tidak aktif**.
- Yang tampil hanya nama yang disamarkan (`Ahmad F****`), peran, dan bulan bergabung.
  Akun diblokir/dihapus cukup disebut "tidak aktif" **tanpa alasan** — data sensitif tidak bocor ke pemegang sebuah ID.
- Dibatasi 30 permintaan/menit per IP agar ID tidak bisa ditebak massal.

> **Catatan tafsir:** "Verifikasi" saya wujudkan sebagai ID yang bisa diperiksa keasliannya
> (karakter pemeriksa + halaman publik), ditambah kewajiban menyetujui tata tertib saat mendaftar.
> Verifikasi dengan kode OTP ke email/WhatsApp **belum** ada karena butuh layanan SMTP / gateway WhatsApp
> berbayar; arsitekturnya siap bila nanti dibutuhkan.

### 2. Batas pendaftaran per IP

- Bawaan: **maksimal 3 akun per 24 jam dari IP yang sama**, dengan jendela waktu *bergulir*
  (bukan reset tengah malam): slot kosong lagi tepat 24 jam setelah pendaftaran terlama.
- Yang dihitung adalah akun yang **berhasil dibuat**, termasuk yang kemudian dihapus admin
  (kalau tidak, pelaku bisa daftar → dihapus → daftar lagi tanpa batas).
- Halaman Daftar memberi tahu **sebelum** formulir diisi bila kuota habis (tombol nonaktif + kapan boleh mencoba lagi),
  dan server memeriksa ulang di dalam kunci (`Cache::lock`) sehingga dua pendaftaran serentak tidak bisa menembus batas.
- Lapisan tambahan anti-bot: batas 20 percobaan kirim formulir per IP per jam (hasilnya halaman 429 berbahasa Indonesia)
  dan kolom jebakan (*honeypot*) tersembunyi.
- IPv6 dihitung per blok `/64` (satu pelanggan rumahan menerima seluruh blok itu), dan bentuk IPv4-mapped (`::ffff:1.2.3.4`)
  disamakan dengan IPv4.

**Pengaturan** (semua opsional, di `.env`; setelah mengubah jalankan `php artisan config:clear`):

| Variabel | Bawaan | Fungsi |
|---|---|---|
| `PERKAP_BATAS_DAFTAR_PER_IP` | `3` | Maksimal akun baru per IP dalam satu jendela |
| `PERKAP_JENDELA_DAFTAR_JAM` | `24` | Panjang jendela (jam) |
| `PERKAP_IP_DIKECUALIKAN` | *(kosong)* | IP / blok CIDR yang bebas batas, pisahkan koma. Mis. `203.0.113.10,198.51.100.0/24` |
| `PERKAP_BATAS_PERCOBAAN_DAFTAR` | `20` | Batas percobaan kirim formulir daftar per IP per jam |
| `PERKAP_BATAS_PENIPUAN` | `3` | Jumlah catatan "penipuan" yang menandai akun *perlu ditinjau* |
| `PERKAP_PREFIX_ID` | `PRK` | Awalan ID Anggota (2–6 huruf). Jangan diubah setelah ada anggota |
| `PERKAP_WA` | `085877708909` | Kontak WhatsApp petugas pada pesan batas/blokir |
| `PERKAP_PERIKSA_MIGRASI` | `true` | Tampilkan petunjuk `php artisan migrate` bila ada migrasi tertunda |
| `TRUSTED_PROXIES` | *(kosong)* | Hanya bila di belakang proxy/CDN — lihat di bawah |

**⚠️ Dua hal yang perlu diketahui:**

1. **Wi-Fi sekolah = satu IP publik.** Semua siswa yang memakai jaringan sekolah yang sama tampak
   berasal dari *satu* IP, jadi kuota 3 akun akan cepat habis saat pendaftaran massal. Solusinya:
   naikkan `PERKAP_BATAS_DAFTAR_PER_IP` sementara, atau daftarkan IP publik sekolah di `PERKAP_IP_DIKECUALIKAN`.
   Pesan yang tampil kepada siswa sudah memuat kontak petugas.
2. **Menguji di komputer sendiri.** Dengan `php artisan serve` semua pendaftaran berasal dari `127.0.0.1`,
   sehingga pendaftaran ke-4 akan ditolak — itu justru bukti batasnya bekerja, **bukan error**. Untuk menguji
   tanpa batas, isi `PERKAP_IP_DIKECUALIKAN=127.0.0.1,::1` di `.env` lalu `php artisan config:clear`
   (hapus lagi bila ingin mendemokan batasnya). Saat `APP_ENV=local`, pesan penolakan sudah menyertakan petunjuk ini.

**Di belakang proxy/CDN?** (Cloudflare, Nginx reverse proxy, dll.) Semua pengunjung tampak berasal dari IP proxy.
Isi `TRUSTED_PROXIES` dengan IP proxy (atau `*` hanya bila server tidak bisa diakses langsung) agar IP asli
dibaca dari `X-Forwarded-For`. **Tanpa itu, header `X-Forwarded-For` palsu diabaikan** — jadi batas tidak bisa
disiasati dengan memalsukan header (sudah diuji di browser untuk kedua mode).

### 3. Admin: blokir & hapus akun bermasalah (menu **Pengguna**)

**Daftar pengguna**: tab status lengkap dengan jumlahnya (Semua / Aktif / Perlu ditinjau / Diblokir / Dihapus),
banner peringatan bila ada akun yang layak ditinjau, dan pencarian berdasarkan nama, username, **ID Anggota**,
email, atau nomor HP (nomor boleh ditulis `0812…` maupun `+62812…`). Di HP tampil sebagai kartu, di desktop sebagai tabel.

**Detail pengguna**: Kartu Anggota, data akun, riwayat peminjaman, catatan pelanggaran, serta petunjuk
bila ada **akun lain dari IP pendaftaran yang sama**.

**Mencatat pelanggaran** (dari halaman detail): *Penipuan peminjaman · Barang hilang · Barang rusak ·
Terlambat mengembalikan · Lainnya*, lengkap dengan keterangan (dan boleh dikaitkan ke satu pengajuan).
Peminjam otomatis mendapat notifikasi atas catatan tersebut.

**Aturan "3× penipuan"**: saat catatan *Penipuan peminjaman* sebuah akun mencapai batas
(`PERKAP_BATAS_PENIPUAN`, bawaan 3), akun masuk tab **Perlu ditinjau** (ada lencana jumlah di menu Pengguna), dan
**semua petugas menerima notifikasi** (sekali saja). **Pemblokiran sengaja tetap keputusan petugas**, tidak
otomatis — supaya tidak ada akun yang terblokir karena catatan yang keliru. Jendela Blokir menyiapkan alasan
saran otomatis yang tinggal dikonfirmasi atau diubah petugas.

**Yang terjadi saat akun diblokir:**
- Status berubah, tercatat siapa/kapan/alasan; cookie "ingat saya" dicabut.
- **Pengajuan yang masih menunggu otomatis ditolak** (stok tidak tersentuh, pengajuan yang sudah disetujui tidak diubah).
- Sesi yang sedang aktif diputus seketika (driver session `database`, bawaan paket ini); pada driver lain pengguna
  dikeluarkan pada permintaan berikutnya oleh middleware `PastikanAkunAktif`.
- Saat mencoba login, alasan blokir **hanya ditampilkan bila password benar** (orang lain tidak bisa mengintip alasannya).
- Pengajuan miliknya yang masih tersisa tidak bisa lagi disetujui petugas.
- *Buka blokir* tersedia kapan saja; jejak blokir terakhir tetap tersimpan, dan pengguna diberi notifikasi.

**Menghapus akun** (*soft delete*): akun hilang dari daftar dan tidak bisa masuk, tetapi riwayat peminjaman tetap utuh
untuk laporan, dan identitasnya tetap terpesan (lihat bagian 1). **Ditolak selama masih ada peminjaman yang barangnya
belum dikembalikan** — proses pengembaliannya dulu supaya stok tidak ikut hilang (alasannya ditampilkan jelas di halaman).
Akun yang terhapus dapat **dipulihkan** dari tab *Dihapus*.

**Pengamanan:** hanya akun peminjam yang bisa diblokir/dihapus; akun petugas dan akun sendiri tidak bisa dikelola.

### 4. Akun demo tambahan (dari `DemoPenggunaSeeder`)

Semua memakai password `password`. Dibuat agar halaman Pengguna tidak kosong saat pertama dibuka
(aman dihapus panggilannya di `DatabaseSeeder`).

| Akun | Username | Keadaan |
|---|---|---|
| Dimas Prakoso | `dimas_p` | 3× penipuan, masih aktif → muncul di tab **Perlu ditinjau** |
| Putri Maharani | `putri_m` | 1× penipuan + 1× terlambat (peringatan dini) |
| Bayu Setiawan | `bayu_s` | **Diblokir** — coba login dengan password benar untuk melihat alasannya |
| Rendra Aditya | `rendra_a` | **Dihapus** — identitasnya tetap terpesan |

*(Dimas & Bayu sengaja diberi IP daftar yang sama agar petunjuk "akun lain dari IP yang sama" terlihat.)*

---

## 🩹 Koreksi: masalah "Ajukan Peminjaman" & "Daftar" yang tidak berfungsi

Pada update pop-up sebelumnya, README ini menulis bahwa akar masalahnya **"akhirnya ditemukan"**: kutip ganda
dari `@js()` yang memotong atribut `@click`. **Penjelasan itu keliru**, dan perubahan yang saya lakukan saat itu
(membungkus `@click` dengan kutip tunggal) malah menimbulkan masalah baru. Hasil penyelidikan ulang, yang
direproduksi di browser sungguhan:

1. **Penyebab pada rekaman layar:** database yang dipakai masih **berskema lama** — tidak punya kolom `harga_sewa`.
   Blade lalu menulis `harga_sewa: ` *kosong* ke dalam atribut Alpine, sehingga ekspresi JavaScript-nya menjadi
   `{ …, harga_sewa: }` → `SyntaxError`. Seluruh komponen kartu alat pun mati, dan tombol "+ Ajukan" terlihat bisa diklik
   padahal tidak melakukan apa-apa.
2. **Regresi buatan saya:** atribut `@click='…'` berkutip tunggal (lihat atas) membuat ZIP versi itu rusak.
   Bentuk yang benar — atribut berkutip ganda berisi `@js()`, yang di Laravel 11 sudah meng-escape `"` menjadi
   `"` — sudah dikembalikan.

**Perbaikan yang kini aktif:**
- Migrasi `pastikan_kolom_harga_tersedia` (idempoten) membuat kolom harga bila belum ada dan mengganti nilai NULL
  menjadi 0; data yang sudah terisi tidak pernah ditimpa.
- **Penjaga migrasi**: bila ada migrasi yang belum dijalankan, aplikasi menampilkan petunjuk
  *"jalankan `php artisan migrate`"* — bukan fitur yang mati diam-diam. (Matikan dengan `PERKAP_PERIKSA_MIGRASI=false`.)
- Perintah **`php artisan perkap:periksa`**: memeriksa APP_KEY, koneksi database, migrasi, kelengkapan kolom setiap tabel,
  tabel sessions, akun petugas, data perlengkapan, izin tulis folder storage, `storage:link`, dan hasil `npm run build` —
  tiap yang gagal disertai langkah perbaikannya.
- Data ke Alpine kini dikirim dengan tipe yang dipastikan (angka tidak pernah kosong), dan keranjang tahan terhadap
  data rusak di `localStorage`.
- Pendaftaran: pesan galat berbahasa Indonesia yang jelas per kolom.

Kalau setelah memasang ZIP ini masih ada yang tidak berfungsi: jalankan `php artisan perkap:periksa`, lalu kirim
hasilnya (atau screenshot Console browser — tekan F12) supaya penyebabnya bisa dilacak tepat.

---

## ✅ Pengujian

```bash
php artisan test           # ±138 tes otomatis (butuh ekstensi PHP pdo_sqlite; memakai SQLite di memori)
php artisan perkap:periksa # diagnosis kesiapan sistem
```

Yang sudah dilakukan: tes unit & fitur (pendaftaran, ID Anggota, pembakuan identitas, kuota IP, proxy, verifikasi,
blokir/hapus/pulihkan, pelanggaran, kartu anggota, migrasi data lama); **uji mutasi** (27 kerusakan sengaja pada kode —
semuanya tertangkap tes); dan uji menyeluruh di Chromium untuk alur daftar → kartu → verifikasi → admin blokir/hapus,
batas IP (dengan dan tanpa proxy), serta 100 halaman pada ukuran HP/tablet/desktop dan mode gelap.

Yang **belum bisa** saya uji di lingkungan saya, jadi mohon dicek di komputermu: MySQL 8 sungguhan (trigger pengaman
diuji di SQLite; versi MySQL ditulis menurut sintaks standar), build Tailwind 3.4 asli lewat `npm run build`, dan grafik
Chart.js (dimuat dari CDN sehingga butuh internet).

---

## 🕓 Riwayat pembaruan sebelumnya

### Pop-up detail barang
Klik gambar atau nama alat di katalog (bukan tombol "+ Ajukan") untuk membuka pop-up berisi foto/ilustrasi besar,
kategori, harga, ketersediaan, dan **deskripsi singkat**. Admin mengisi/mengubah deskripsi lewat
**Perlengkapan → Edit → "Deskripsi Singkat"**. Bila belum diisi, tampil pesan default yang sopan.

### Perbaikan bug & redesain besar
- Rule CSS `[x-cloak]` yang hilang ditambahkan (dipakai di 13+ tempat; tanpa itu elemen "berkedip" sebelum Alpine siap).
- Halaman login & daftar kini menampilkan ringkasan galat di atas formulir.
- Seluruh ikon dirombak (27 ikon), termasuk ikon WhatsApp, Instagram, TikTok yang mirip aslinya, dengan akun
  WA `085877708909`, Instagram `pramuka.smkn5surakarta`, TikTok `Prajamudalimasala`.
- Warna lebih berani: hero gradasi di dashboard, kartu statistik bergaris warna, doughnut status pengajuan, dan bar chart
  stok per kategori di dashboard admin. Animasi konfeti saat pengajuan berhasil dikirim.

### Tampilan lebih rapat, harga sewa, peringatan
1. **Tombol diperkecil** terpusat di `resources/css/app.css`; tombol utama bergradasi ungu; warna `info` (biru) & `rose` (pink) ditambahkan.
2. **Halaman Katalog dihapus** — Dashboard peminjam kini sekaligus katalog (cari + filter + grid).
3. **Harga sewa per alat** (`alats.harga_sewa`), bisa diedit admin (Perlengkapan → Edit); tampil di katalog, form pengajuan
   (dengan total otomatis), riwayat, dan detail. Harga **di-snapshot** ke `pengajuan_detail.harga_saat_pinjam` saat pengajuan
   dibuat, sehingga mengubah harga tidak mengubah riwayat lama. *(Murni pencatatan; belum ada pembayaran online — sesuai brief.)*
4. **Kirim notifikasi peringatan dari halaman Laporan** — tombol "Peringatan" di tiap baris, langsung terkirim ke peminjam.
5. Dua bug nyata diperbaiki: link "Jelajahi Katalog" yang menunjuk route terhapus, dan warna badge yang dibangun dari
   variabel PHP (`bg-{{ $x }}`) sehingga tidak ikut ter-compile Tailwind — kini memakai daftar class literal.

### Logo, ilustrasi barang, interaktivitas
- Logo diganti lambang Gugus Depan (Wisnu Kencana Putra & Dewi Satya Warma) di navbar, sidebar, login/daftar, footer, dan favicon.
- **Ilustrasi vektor (SVG) buatan sendiri untuk 52 barang** (27 desain), dipetakan lewat `Alat::getIkonAttribute()`.
  Foto asli yang diunggah admin otomatis menggantikannya.
- Angka statistik menghitung naik, tombol submit penting menampilkan spinner (mencegah submit ganda),
  kartu muncul bertahap, dan ilustrasi membesar halus saat disorot.

---

## 📦 Isi Paket Ini

Paket ini adalah **proyek Laravel 11 yang utuh & siap pakai** — semua file skeleton wajib (config/, bootstrap/,
storage/, artisan, composer.json, .env, package.json, dst) sudah dibuat. Yang **tidak** ikut disertakan cuma dua:

- **`vendor/`** — kode Laravel Framework + library pihak ketiga; dihasilkan oleh `composer install`.
- **`node_modules/`** — dihasilkan oleh `npm install` berdasarkan `package.json`.

Setelah dua perintah itu dijalankan, proyek langsung lengkap. Tidak perlu `composer create-project` atau `laravel/breeze` —
auth, Tailwind, Alpine, dan Vite semuanya sudah ditulis langsung supaya persis sesuai brief (login username/email, register lengkap, dst).

```
app/            → Models, Controllers, Middleware, Requests, Services, Support, Exports
bootstrap/      → app.php (routing + middleware) & providers.php
config/         → konfigurasi inti + perkap.php (pengaturan aplikasi) + trustedproxy.php
database/       → migrations, seeders, factories
lang/id/        → terjemahan validasi & pesan berbahasa Indonesia
public/         → index.php (front controller), logo & gambar
resources/      → views (Blade), css (Tailwind), js (Alpine)
routes/         → web.php & console.php
storage/        → folder cache/logs/upload (writable)
tests/          → PHPUnit (unit + feature)
artisan, composer.json, package.json, .env  → siap pakai
```

---

## 🚀 Instalasi (Langkah demi Langkah)

### 1. Ekstrak ZIP ini, lalu masuk ke foldernya
```bash
cd perkap-pramuka
```

### 2. Install dependency PHP & JS
```bash
composer install
npm install
```

### 3. Siapkan database
`.env` sudah terisi nilai default (termasuk `APP_KEY`); cukup pastikan kredensial database cocok dengan MySQL di komputermu:
```env
DB_DATABASE=perkap_pramuka
DB_USERNAME=root
DB_PASSWORD=
```
Buat database kosong bernama `perkap_pramuka` (via phpMyAdmin/HeidiSQL/CLI/Laragon).

> Untuk server sungguhan: ubah `APP_ENV=production` dan `APP_DEBUG=false`, dan baca bagian
> [Batas pendaftaran per IP](#2-batas-pendaftaran-per-ip) bila di belakang proxy/CDN.

### 4. Migrasi & isi data awal
```bash
php artisan storage:link
php artisan migrate:fresh --seed
```
Membuat semua tabel + mengisi **52 barang**, **4 akun demo**, **contoh pengajuan**, dan **4 akun demo pengguna**
(contoh pelanggaran/blokir/hapus) supaya semua dashboard tidak kosong saat pertama dibuka.

### 5. Build asset frontend
```bash
npm run dev      # mode development (auto-reload) — biarkan tetap berjalan
# atau
npm run build    # build sekali untuk production
```

### 6. Jalankan server
```bash
php artisan serve
```
Buka **http://localhost:8000** di browser. 🎉

Ada yang terasa tidak beres? Jalankan `php artisan perkap:periksa` (setelah langkah 5) — semua baris harus bertanda ✔,
dan setiap yang gagal disertai langkah perbaikannya.

---

## 🔑 Akun Demo

| Peran | Username / Email | Password |
|---|---|---|
| Petugas Perkap (Admin) | `admin` atau `admin@perkap.test` | `password` |
| Peminjam | `peminjam1` atau `peminjam@perkap.test` | `password` |
| Peminjam (data contoh) | `siti_n`, `rizky_r` | `password` |
| Peminjam (contoh pelanggaran/blokir) | `dimas_p`, `putri_m`, `bayu_s`, `rendra_a` | `password` |

Login menerima **username maupun email** di satu kolom yang sama.

---

## 🔧 Perbaikan yang Dilakukan dari Brief Asli

Brief sudah sangat lengkap, tapi ada beberapa hal kecil yang tidak konsisten antar-bagian. Berikut yang diperbaiki
(supaya kamu paham & bisa menjelaskannya saat presentasi LKPD):

1. **Kolom `satuan` ditambahkan ke tabel `alats`.** Data di `AlatSeeder` memakai `satuan` (Pcs/Biji/Pasang), tapi kolom ini tidak ada di skema brief.
2. **Form Register ditambah `Nama Lengkap` & `Email`.** Brief hanya meminta Username/Password/No.WA, padahal tabel `users` mewajibkan `nama` dan `email` (unik).
3. **Login pakai satu kolom "Username/Email".** Breeze bawaan hanya mendukung email; controller dibuat custom untuk mendeteksi otomatis.
4. **Middleware `role:admin` → `role:petugas_perkap`.** Brief menyebut `role:admin`, padahal nilai enum di database adalah `petugas_perkap`.
5. **Waktu pengurangan stok diperjelas.** Stok dicek saat pengajuan dibuat, tetapi **baru dikurangi saat admin klik "Setujui"** (sesuai Activity Diagram), dengan pengecekan ulang saat approve.
6. **Alat rusak/hilang mengurangi `stok_total`, bukan cuma `stok_tersedia`**, karena satu baris `alats` mewakili banyak unit fisik tanpa nomor seri.

---

## 💡 Ide Tambahan yang Disertakan

- **Keranjang peminjaman** (Alpine.js + localStorage): satu pengajuan multi-item, persisten lintas halaman.
- **PengajuanSeeder**: 5 contoh pengajuan berbagai status (pending, approved, rejected, returned baik, returned rusak).
- **Pembatalan pengajuan** oleh peminjam selama masih *pending*.
- **Dark mode** (toggle bulan/matahari, tersimpan di `localStorage`) di layout admin & peminjam.
- **Auto-generate `kode_alat` & `kode_pengajuan`** (`TDA-001`, `PJM-20260910-001`, dst).
- **Tombol "Hubungi via WhatsApp"** di detail pengajuan admin.
- **Empty state** yang jelas di setiap list kosong.
- **Soft delete** pada `users`, `alats`, dan `pengajuan_peminjaman` supaya riwayat tidak hilang.
- **Halaman galat** (403/404/419/429/500/503) berbahasa Indonesia dengan tampilan senada.

### Belum diimplementasi (opsional)
- **Verifikasi OTP** lewat email/WhatsApp saat daftar (butuh SMTP atau gateway WhatsApp).
- **Activity log** — pasang `spatie/laravel-activitylog` dan tambahkan trait `LogsActivity` di model `Alat` & `PengajuanPeminjaman`.
- **Cetak Kartu Anggota ke PDF**.

---

## 🗂️ Ringkasan Struktur Halaman

| Role | Route awal | Menu |
|---|---|---|
| Peminjam | `/user/dashboard` | Dashboard (sekaligus katalog), Pengajuan, **Kartu Anggota**, Profile |
| Petugas Perkap | `/admin/dashboard` | Dashboard, Perlengkapan, Peminjaman, **Pengguna**, Laporan |
| Publik (tanpa login) | `/verifikasi` | Verifikasi ID Anggota |

Semua route peminjam diproteksi `middleware(['auth','role:peminjam'])`, semua route admin `middleware(['auth','role:petugas_perkap'])`.
Akun yang diblokir dikeluarkan oleh middleware `PastikanAkunAktif` di setiap permintaan.

---

## 🛠️ Troubleshooting Singkat

- **Ada yang tidak berfungsi setelah update** → jalankan `php artisan migrate`, `npm run build`, `php artisan optimize:clear`,
  lalu `php artisan perkap:periksa`, dan hard refresh browser (`Ctrl+Shift+R`).
- **"Batas pendaftaran dari jaringan ini sudah tercapai" saat menguji sendiri** → bukan error; itu kuota 3 akun/24 jam per IP.
  Lihat bagian [Batas pendaftaran per IP](#2-batas-pendaftaran-per-ip) untuk cara menonaktifkannya di komputer lokal.
- **Semua pengunjung tercatat dengan IP yang sama (mis. IP proxy)** → isi `TRUSTED_PROXIES` di `.env`.
- **`storage/logs/laravel.log` berisi "Trigger pengaman ID Anggota tidak dapat dipasang"** → hosting tidak memberi hak
  `TRIGGER`. Tidak wajib diperbaiki: ID tetap terlindung oleh lapisan aplikasi. Bila nanti hak itu aktif dan ingin
  lapisan database juga, jalankan SQL trigger yang ada di method `pasangPengaman()` pada file migrasi
  `2026_10_23_000001_add_identitas_dan_status_to_users_table.php` secara manual. *(Jangan memakai `migrate:rollback`
  untuk ini — itu menghapus kolom ID Anggota.)*
- **`composer install` gagal / lambat** → butuh koneksi internet dan PHP ≥ 8.2 dengan ekstensi `pdo_mysql`, `mbstring`,
  `openssl`, `tokenizer`, `xml`, `ctype`, `json` (cek `php -m`).
- **Error `could not find driver` saat migrate** → aktifkan ekstensi `pdo_mysql` di `php.ini`.
- **Gambar alat/avatar tidak muncul setelah upload** → jalankan `php artisan storage:link`.
- **Style Tailwind tidak berubah** → pastikan `npm run dev` berjalan, atau jalankan ulang `npm run build`.
- **Grafik dashboard admin tidak tampil** → Chart.js dimuat dari CDN; periksa koneksi internet.
- **419 Page Expired saat submit form** → `php artisan config:clear` dan pastikan `APP_URL` sesuai alamat di browser.
- **`SQLSTATE[HY000] [1049] Unknown database`** → buat dulu database `perkap_pramuka` (kosong; tabel dibuat oleh migration).

---

Selamat mengerjakan LKPD! 🎒
