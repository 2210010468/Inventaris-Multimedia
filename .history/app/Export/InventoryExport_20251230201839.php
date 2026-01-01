<?php
namespace App\Exports;

use App\Models\Tool;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class InventoryExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        // Ambil semua data tool dengan relasi kategorinya
        return Tool::with('category')->get();
    }

    // Header Judul di Excel
    public function headings(): array
    {
        return [
            'Kode Aset',
            'Nama Barang',
            'Kategori',
            'Merk/Brand',
            'Kondisi',
            'Tahun Perolehan',
            'Nilai Aset (IDR)', // Sesuai saranmu di poin 1
        ];
    }

    // Mapping data per baris
    public function map($tool): array
    {
        return [
            $tool->tool_code,
            $tool->name,
            $tool->category->name ?? '-', // Handle jika kategori dihapus
            $tool->brand,
            $tool->condition, // Baik/Rusak/Perbaikan
            $tool->purchase_year,
            number_format($tool->price, 0, ',', '.'), // Format mata uang
        ];
    }
}