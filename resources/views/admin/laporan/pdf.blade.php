<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #111111; }
        h1 { font-size: 16px; margin-bottom: 2px; }
        p.sub { color: #555; margin-top: 0; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; }
        th { background: #F3F4F6; font-weight: bold; }
        .status-pending { color: #F59E0B; }
        .status-approved { color: #10B981; }
        .status-returned { color: #8B5CF6; }
        .status-rejected { color: #EF4444; }
        .footer { margin-top: 16px; font-size: 9px; color: #999; }
    </style>
</head>
<body>
    <h1>Laporan Peminjaman Perkap Pramuka</h1>
    <p class="sub">SMK Negeri 5 Surakarta &mdash; Periode {{ \Carbon\Carbon::parse($dari)->format('d M Y') }} s/d {{ \Carbon\Carbon::parse($sampai)->format('d M Y') }}</p>

    <table>
        <thead>
            <tr>
                <th>Kode</th>
                <th>Peminjam</th>
                <th>Tgl Pinjam</th>
                <th>Tgl Kembali</th>
                <th>Alat</th>
                <th>Total Biaya</th>
                <th>Status</th>
                <th>Status Akhir</th>
            </tr>
        </thead>
        <tbody>
            @forelse($pengajuan as $p)
                <tr>
                    <td>{{ $p->kode_pengajuan }}</td>
                    <td>{{ $p->user->nama }}</td>
                    <td>{{ $p->tanggal_pinjam->format('d/m/Y') }}</td>
                    <td>{{ $p->tanggal_kembali->format('d/m/Y') }}</td>
                    <td>{{ $p->details->map(fn($d) => "{$d->alat->nama_alat} ({$d->jumlah})")->implode(', ') }}</td>
                    <td>{{ $p->total_biaya_format }}</td>
                    <td class="status-{{ $p->status_pengajuan }}">{{ $p->label_status }}</td>
                    <td>{{ $p->status_akhir ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="8">Tidak ada data pada rentang tanggal ini.</td></tr>
            @endforelse
        </tbody>
    </table>

    <p class="footer">Dicetak otomatis oleh Sistem Perkap Pramuka pada {{ now()->translatedFormat('d M Y, H:i') }}.</p>
</body>
</html>
