<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class Vendor extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'address', 'phone', 'email'];

    public function index(Request $request)
    {
        $query = Vendor::query();

        // Logika Filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('phone', 'LIKE', "%{$search}%")
                  ->orWhere('address', 'LIKE', "%{$search}%");
            });
        }

        // --- BAGIAN INI YANG PENTING ---
        // JANGAN pakai ->get();
        // GANTI dengan ->paginate(10)->withQueryString();
        
        $vendors = $query->latest()->paginate(10)->withQueryString();

        return view('vendors.index', compact('vendors'));
    }

    // Relasi: Satu vendor bisa punya banyak transaksi pembelian
    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }
}