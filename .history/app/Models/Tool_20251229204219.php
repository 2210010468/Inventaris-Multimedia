<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Purchase;


class Tool extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'tools'; // Pastikan ini sesuai nama tabel alat di DB

    protected $fillable = [
        'tool_code', 
        'tool_name', 
        'brand',           
        'purchase_date',
        'category_id', 
        'current_condition', 
        'availability_status',
        'disposal_reason',
        'purchase_item_id'
    ];

    public function category()
    {
        // Hubungkan ke model Category yang sudah kita perbaiki di atas
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    public function borrowingItems()
    {
        return $this->hasMany(\App\Models\BorrowingItem::class, 'tool_id', 'id');
    }

    // Relasi balik ke pembelian
    public function purchase()
    {
        // Artinya: Kolom 'purchase_item_id' di tabel tools 
        // milik id dari tabel purchases.
        return $this->belongsTo(Purchase::class, 'purchase_item_id', 'id');
    }
}