<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Vendor;
use App\Models\Category; // <--- Import Category
use App\Models\Tool;     // <--- Import Tool
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str; // Untuk generate kode acak

class PurchaseController extends Controller
{
    public function index()
    {
        $purchases = Purchase::with(['vendor', 'user'])->latest()->get();
        return view('purchases.index', compact('purchases'));
    }

    public function create()
    {
        $vendors = Vendor::all();
        $categories = Category::all(); // <--- Ambil data kategori
        // Prevent kepala/head from creating purchases via UI/API
        $user = Auth::user();
        if ($user && in_array($user->role, ['kepala','head'])) {
            return redirect()->route('purchases.index')->with('error', 'Akses ditolak. Anda tidak dapat membuat transaksi.');
        }
        return view('purchases.create', compact('vendors', 'categories'));
    }

    public function store(Request $request)
    {
        // 1. VALIDASI
        $request->validate([
            'purchase_date' => 'required|date',
            'vendor_id'     => 'required|exists:vendors,id',
            // status is not accepted from form — always saved as 'pending'
            'items'         => 'required|array|min:1',
            'items.*.tool_name' => 'required|string',
            
            // PERHATIAN: Pastikan nama tabel kategori benar (biasanya 'categories' atau 'tool_categories')
            // Kita coba pakai 'categories' dulu sesuai standar Laravel, kalau error ganti 'tool_categories'
            'items.*.category_id' => 'required|exists:tool_categories,id', 
            
            'items.*.quantity'  => 'required|integer|min:1',
            'items.*.unit_price'=> 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            // 2. HITUNG TOTAL
            $grandTotal = 0;
            foreach ($request->items as $item) {
                $grandTotal += $item['quantity'] * $item['unit_price'];
            }

            // 3. SIMPAN HEADER: status default ke 'pending' (admin input hanya membuat permintaan)
            $purchase = Purchase::create([
                'purchase_code' => 'PO-' . date('Ymd') . '-' . rand(100, 999),
                'purchase_date' => $request->purchase_date,
                'vendor_id'     => $request->vendor_id,
                'status'        => 'pending',
                'total_amount'  => $grandTotal,
                'user_id'       => Auth::id(),
            ]);

            // 4. SIMPAN ITEMS & GENERATE TOOLS
            foreach ($request->items as $item) {
                
                // A. Simpan Item Pembelian
                $purchaseItem = PurchaseItem::create([
                    'purchase_id'   => $purchase->id,
                    'category_id'   => $item['category_id'],
                    'tool_name'     => $item['tool_name'],
                    'specification' => $item['specification'] ?? '-',
                    'quantity'      => $item['quantity'],
                    'unit_price'    => $item['unit_price'],
                    'subtotal'      => $item['quantity'] * $item['unit_price'],
                ]);

                // B. Tidak membuat Tool saat input pembelian — akan dibuat saat kepala menyetujui.
            }

            DB::commit();

            return redirect()->route('purchases.index')->with('success', 'Permintaan pembelian berhasil disimpan dan menunggu persetujuan kepala.');

        } catch (\Exception $e) {
            DB::rollBack();
            // Pesan error akan muncul di kotak merah di atas form
            return back()->withInput()->withErrors(['msg' => 'Gagal menyimpan: ' . $e->getMessage()]);
        }
    }

    public function show(Purchase $purchase)
    {
        $purchase->load(['vendor', 'user', 'items.category']);
        return view('purchases.show', compact('purchase'));
    }

    /**
     * Approve a pending purchase (role: kepala/head only)
     */
    public function approve(Purchase $purchase)
    {
        $user = Auth::user();
        if (!$user || !in_array($user->role, ['kepala','head'])) {
            return redirect()->route('purchases.index')->with('error', 'Anda tidak memiliki akses untuk menyetujui permintaan pembelian.');
        }

        if ($purchase->status !== 'pending') {
            return redirect()->back()->with('error', 'Permintaan ini sudah diproses.');
        }


        // Buat tools untuk setiap purchase item jika belum dibuat
        foreach ($purchase->items as $item) {
    // 1. Cek apakah tool untuk item ini sudah dibuat (Prevent Duplication)
    $existing = Tool::where('purchase_item_id', $item->id)->count();
    if ($existing >= $item->quantity) {
        continue; 
    }

    // 2. Ambil Prefix dari Kategori Item tersebut
    $category = Category::find($item->category_id);
    
    // Fallback jika kategori tidak punya kode, pakai 'UNK' (Unknown) atau 3 huruf nama alat
    $prefix = $category && $category->code ? $category->code : strtoupper(substr($item->tool_name, 0, 3));

    // 3. Cari Nomor Urut Terakhir (MAX) di Database untuk kategori ini
    // Logika ini dipindahkan ke luar loop quantity agar query tidak berat
    $codes = Tool::where('category_id', $item->category_id)
        ->where('tool_code', 'like', $prefix . '-%') // Cari yg depannya 'KODE-'
        ->pluck('tool_code')
        ->toArray();

    $max = 0;
    foreach ($codes as $c) {
        // Regex untuk ambil angka di belakang strip terakhir
        if (preg_match('/-(\d+)$/', $c, $m)) {
            $n = intval($m[1]);
            if ($n > $max) $max = $n;
        }
    }

    // 4. Mulai Loop Pembuatan Alat sesuai Quantity
    // Kita kurangi iterasi dengan jumlah yang existing (jika ada partial insert sebelumnya)
    for ($i = 1; $i <= ($item->quantity - $existing); $i++) {
        
        $max++; // Increment nomor urut (Logic: Last Max + 1)
        
        // Format kode: CONTOH-001, CONTOH-002
        $uniqueCode = sprintf('%s-%03d', $prefix, $max); 

        // Cek double safety (opsional): pastikan kode belum ada (karena race condition)
        // while (Tool::where('tool_code', $uniqueCode)->exists()) {
        //     $max++;
        //     $uniqueCode = sprintf('%s-%03d', $prefix, $max);
        // }

        Tool::create([
            'tool_code'           => $uniqueCode,
            'tool_name'           => $item->tool_name, // Tidak perlu pakai #1, #2 jika sudah ada kode unik
            'category_id'         => $item->category_id,
            'current_condition'   => 'Baik', // Default condition
            'availability_status' => 'available',
            'purchase_item_id'    => $item->id,
        ]);
    }
}
        $purchase->status = 'approved';
        $purchase->save();

        return redirect()->route('purchases.show', $purchase->id)->with('success', 'Permintaan pembelian disetujui.');
    }

    /**
     * Reject a pending purchase (role: kepala/head only)
     */
    public function reject(Purchase $purchase)
    {
        $user = Auth::user();
        if (!$user || !in_array($user->role, ['kepala','head'])) {
            return redirect()->route('purchases.index')->with('error', 'Anda tidak memiliki akses untuk menolak permintaan pembelian.');
        }

        if ($purchase->status !== 'pending') {
            return redirect()->back()->with('error', 'Permintaan ini sudah diproses.');
        }

        $purchase->status = 'rejected';
        $purchase->save();

        return redirect()->route('purchases.show', $purchase->id)->with('success', 'Permintaan pembelian ditolak.');
    }

    public function destroy(Purchase $purchase)
    {
        // Prevent kepala/head from deleting
        $user = Auth::user();
        if ($user && in_array($user->role, ['kepala','head'])) {
            return redirect()->route('purchases.index')->with('error', 'Akses ditolak. Anda tidak dapat menghapus transaksi.');
        }

        // Hati-hati, kalau dihapus tools yang sudah digenerate jadi yatim piatu (null)
        // Sesuai constraint nullOnDelete di migration
        $purchase->delete();
        return redirect()->route('purchases.index')->with('success', 'Data dihapus');
    }

    /**
     * Printable report for kepala (role kepala/head only)
     */
    public function report(Request $request)
    {
        $user = Auth::user();
        if (!$user || !in_array($user->role, ['kepala','head'])) {
            return redirect()->route('purchases.index')->with('error', 'Akses ditolak. Hanya kepala yang dapat melihat laporan.');
        }

        $query = Purchase::with(['vendor', 'user']);

        if ($request->start_date) {
            $query->whereDate('purchase_date', '>=', $request->start_date);
        }
        if ($request->end_date) {
            $query->whereDate('purchase_date', '<=', $request->end_date);
        }

        $purchases = $query->orderBy('purchase_date', 'desc')->get();

        // Support CSV download
        if ($request->query('format') === 'csv' || $request->query('download') === 'csv') {
            $filename = 'laporan_pembelian_' . now()->format('Ymd_His') . '.csv';
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];

            $columns = ['Tanggal', 'Kode', 'Vendor', 'Total', 'Status', 'Oleh', 'Items'];

            $callback = function() use ($purchases, $columns) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, $columns);

                foreach ($purchases as $p) {
                    $items = $p->items->map(function($it){
                        return $it->tool_name . ' x' . $it->quantity;
                    })->implode(' | ');

                    fputcsv($handle, [
                        $p->purchase_date,
                        $p->purchase_code,
                        $p->vendor->name ?? '-',
                        $p->total_amount,
                        $p->status,
                        $p->user->name ?? '-',
                        $items,
                    ]);
                }

                fclose($handle);
            };

            return response()->stream($callback, 200, $headers);
        }

        return view('purchases.report', compact('purchases'));
    }
}