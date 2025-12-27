<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;

    // Pastikan nama tabel benar (biasanya otomatis 'purchases', tapi aman didefine)
    protected $table = 'purchases';

    protected $fillable = [
        // 1. Identitas & FK
        'purchase_code', 
        'date',          // Sesuai request (tadi purchase_date)
        'vendor_id',
        'category_id',   // Baru
        'user_id',
        
        // 2. Detail Barang (Sekarang masuk sini semua)
        'tool_name',     // Baru
        'specification', // Baru
        'quantity',      // Baru
        'unit_price',    // Baru
        'subtotal',      // Baru
        
        // 3. Status & Eksekusi
        'status',                  // pending, approved, rejected
        'is_purchased',            // boolean
        'transaction_proof_photo', // path file gambar
        'rejection_note',          // Jaga-jaga kalau ada catatan penolakan
    ];

    // Casting agar tipe data otomatis dikonversi Laravel
    protected $casts = [
        'date' => 'date',
        'is_purchased' => 'boolean', // Penting: biar jadi true/false di JSON/Code
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    // --- RELATIONS ---

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi Baru ke Kategori
    public function category()
    {
        // Pastikan punya Model ToolCategory
        return $this->belongsTo(Category::class, 'category_id');
    }

    // PENTING: function items() DIHAPUS 
    // karena kita pakai Single Table (Cara 2), data barang sudah ada di tabel ini.
}