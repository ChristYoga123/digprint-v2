<?php

namespace App\Exports;

use App\Models\StokOpname;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StokOpnameSummaryExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize
{
    public function collection()
    {
        return StokOpname::with(['createdBy', 'approvedBy'])
            ->withCount('items')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'No',
            'Kode',
            'Nama/Deskripsi',
            'Tanggal',
            'Status',
            'Total Item',
            'Selisih (+)',
            'Selisih (-)',
            'Selisih Bersih',
            'Dibuat Oleh',
            'Diapprove Oleh',
            'Tgl Approval',
        ];
    }

    public function map($stokOpname): array
    {
        static $no = 0;
        $no++;

        return [
            $no,
            $stokOpname->kode,
            $stokOpname->nama ?? '-',
            $stokOpname->created_at->format('d/m/Y'),
            $stokOpname->status->getLabel(),
            $stokOpname->items_count,
            number_format($stokOpname->total_positive_difference, 2),
            number_format($stokOpname->total_negative_difference, 2),
            number_format($stokOpname->net_difference, 2),
            $stokOpname->createdBy->name ?? '-',
            $stokOpname->approvedBy->name ?? '-',
            $stokOpname->approved_at ? $stokOpname->approved_at->format('d/m/Y H:i') : '-',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return 'Ringkasan Stok Opname';
    }
}
