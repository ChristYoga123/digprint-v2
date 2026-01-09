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

class StokOpnameExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize
{
    protected StokOpname $stokOpname;

    public function __construct(StokOpname $stokOpname)
    {
        $this->stokOpname = $stokOpname->load(['items.bahan.satuanTerkecil', 'items.approvedBy']);
    }

    public function collection()
    {
        return $this->stokOpname->items;
    }

    public function headings(): array
    {
        return [
            'No',
            'Kode Bahan',
            'Nama Bahan',
            'Satuan',
            'Stok Sistem',
            'Stok Fisik',
            'Selisih',
            'Status',
            'Catatan',
            'Diapprove Oleh',
            'Tgl Approval',
        ];
    }

    public function map($item): array
    {
        static $no = 0;
        $no++;

        return [
            $no,
            $item->bahan->kode ?? '',
            $item->bahan->nama ?? '',
            $item->bahan->satuanTerkecil->nama ?? '',
            number_format($item->stock_system, 2),
            $item->stock_physical !== null ? number_format($item->stock_physical, 2) : '-',
            $item->difference !== null ? number_format($item->difference, 2) : '-',
            $item->status->getLabel(),
            $item->catatan ?? '',
            $item->approvedBy->name ?? '-',
            $item->approved_at ? $item->approved_at->format('d/m/Y H:i') : '-',
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
        return 'Stok Opname - ' . $this->stokOpname->kode;
    }
}
