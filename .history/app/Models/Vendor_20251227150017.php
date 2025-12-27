<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class Vendor extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'address', 'phone', 'email'];

    
    // Relasi: Satu vendor bisa punya banyak transaksi pembelian
    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }
}