<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LaporanPeminjamanExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function __construct(protected Collection $data)
    {
    }

    public function collection(): Collection
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'Kode Pengajuan', 'Peminjam', 'Tanggal Pinjam', 'Tanggal Kembali',
            'Keperluan', 'Daftar Alat', 'Total Biaya', 'Status', 'Status Akhir', 'Petugas',
        ];
    }

    public function map($pengajuan): array
    {
        $daftarAlat = $pengajuan->details->map(
            fn ($d) => "{$d->alat->nama_alat} ({$d->jumlah})"
        )->implode(', ');

        return [
            $pengajuan->kode_pengajuan,
            $pengajuan->user->nama,
            $pengajuan->tanggal_pinjam->format('d-m-Y'),
            $pengajuan->tanggal_kembali->format('d-m-Y'),
            $pengajuan->keperluan,
            $daftarAlat,
            $pengajuan->total_biaya_format,
            $pengajuan->label_status,
            $pengajuan->status_akhir ?? '-',
            $pengajuan->petugas->nama ?? '-',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
