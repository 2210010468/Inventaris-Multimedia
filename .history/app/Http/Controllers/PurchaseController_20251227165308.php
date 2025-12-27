<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\Vendor;
use App\Models\Category;
use App\Models\Tool;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PurchaseController extends Controller
{
    // ----------------------------------------------------------------------
    // 1. HALAMAN "PERMOHONAN PEMBELIAN" (indexRequests)
    // Aturan: Menampilkan data ketika status != 'approved'
    // ----------------------------------------------------------------------
    public function indexRequests(Request $request)
    {
        // Mulai Query
        $query = Purchase::with(['vendor', 'user', 'category'])
            // Hanya tampilkan yang BELUM disetujui (Pending & Rejected)
            // Karena yang Approved pindah ke menu Transaksi
            ->where('status', '!=', 'approved');

        // --- FILTER 1: SEARCH (Kode / Nama Barang) ---
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('purchase_code', 'LIKE', "%{$search}%")
                  ->orWhere('tool_name', 'LIKE', "%{$search}%");
            });
        }

        // --- FILTER 2: STATUS (Pending / Rejected) ---
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // --- FILTER 3: TANGGAL ---
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        // Eksekusi dengan Pagination
        $purchases = $query->orderBy('created_at', 'desc')
                           ->paginate(10)
                           ->withQueryString();

        return view('purchases.requests', compact('purchases'));
    }

    // ----------------------------------------------------------------------
    // 2. HALAMAN "PEMBELIAN BARANG" (indexTransaction)
    // Aturan: Menampilkan data ketika status == 'approved'
    // ----------------------------------------------------------------------
    public function indexTransaction(Request $request)
    
    {
        // Query: Approved TAPI Belum Dibeli
        $query = Purchase::with(['vendor', 'user', 'category'])
            ->where('status', 'approved')
            ->where('is_purchased', false);

        // --- FILTER 1: SEARCH ---
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('purchase_code', 'LIKE', "%{$search}%")
                  ->orWhere('tool_name', 'LIKE', "%{$search}%")
                  ->orWhereHas('vendor', function($v) use ($search) {
                      $v->where('name', 'LIKE', "%{$search}%");
                  });
            });
        }

        // --- FILTER 2: BULAN ---
        if ($request->filled('month')) {
            $query->whereMonth('date', $request->month);
        }

        // --- FILTER 3: TAHUN ---
        if ($request->filled('year')) {
            $query->whereYear('date', $request->year);
        }

        $purchases = $query->orderBy('date', 'asc')
                           ->paginate(10)
                           ->withQueryString();

        return view('purchases.transaction', compact('purchases'));
    }

    // ----------------------------------------------------------------------
    // 3. HALAMAN RIWAYAT (indexHistory)
    // Menampilkan yang sudah selesai (is_purchased = true) atau History Rejected
    // ----------------------------------------------------------------------
    public function indexHistory()
    {
        $history = Purchase::with(['vendor', 'user', 'category'])
                    ->where('is_purchased', true) // Sudah jadi barang
                    ->orWhere('status', 'rejected') // Atau ditolak (opsional jika ingin melihat rejected di history juga)
                    ->orderBy('updated_at', 'desc')
                    ->get();

        return view('purchases.history', compact('history'));
    }

    // ----------------------------------------------------------------------
    // CREATE & STORE (Pengajuan Baru)
    // ----------------------------------------------------------------------
    public function create()
    {
        $vendors = Vendor::all();
        $categories = Category::all(); 
        
        $user = Auth::user();
        if ($user && in_array($user->role, ['kepala','head'])) {
            return redirect()->back()->with('error', 'Akses ditolak. Kepala tidak membuat pengajuan.');
        }
        
        return view('purchases.create', compact('vendors', 'categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'date'          => 'required|date',
            'vendor_id'     => 'required|exists:vendors,id',
            'category_id'   => 'required|exists:tool_categories,id',
            'tool_name'     => 'required|string',
            'quantity'      => 'required|integer|min:1',
            'unit_price'    => 'required|numeric|min:0',
            'specification' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $subtotal = $request->quantity * $request->unit_price;

            Purchase::create([
                'purchase_code' => 'REQ-' . date('ymd') . '-' . rand(1000, 9999),
                'date'          => $request->date,
                'vendor_id'     => $request->vendor_id,
                'category_id'   => $request->category_id,
                'user_id'       => Auth::id(),
                'tool_name'     => $request->tool_name,
                'specification' => $request->specification ?? '-',
                'quantity'      => $request->quantity,
                'unit_price'    => $request->unit_price,
                'subtotal'      => $subtotal,
                'status'        => 'pending', // Default Pending
                'is_purchased'  => false,
            ]);

            DB::commit();
            return redirect()->route('purchases.request')->with('success', 'Pengajuan berhasil dibuat.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['msg' => 'Error: ' . $e->getMessage()]);
        }
    }

    // ----------------------------------------------------------------------
    // ACTION: APPROVE & REJECT
    // ----------------------------------------------------------------------
    public function approve($id)
    {
        $user = Auth::user();
        if (!$user || !in_array($user->role, ['kepala','head'])) {
            return abort(403, 'Unauthorized');
        }

        $purchase = Purchase::findOrFail($id);
        
        // Hanya update status jadi Approved. 
        // Data akan pindah dari halaman Request -> Halaman Transaction
        $purchase->update([
            'status' => 'approved'
        ]);

        return redirect()->back()->with('success', 'Pengajuan disetujui. Silakan cek menu Pembelian Barang.');
    }

    public function reject(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user || !in_array($user->role, ['kepala','head'])) {
            return abort(403);
        }

        $purchase = Purchase::findOrFail($id);
        $purchase->update([
            'status' => 'rejected',
            'rejection_note' => $request->input('note', '-')
        ]);

        return redirect()->back()->with('success', 'Pengajuan ditolak.');
    }

    // ----------------------------------------------------------------------
    // 4. ACTION: UPLOAD BUKTI (Eksekusi Akhir)
    // Aturan: Input 'transaction_proof_photo' -> 'is_purchased' = true -> Masuk Data Barang
    // ----------------------------------------------------------------------
    public function storePurchaseEvidence(Request $request, $id)
    {
        // 1. VALIDASI INPUT (Sesuai name="proof_photo" di View)
        $request->validate([
            'proof_photo' => 'required|image|max:2048', // <--- SUDAH SAYA GANTI JADI proof_photo
            'real_price'  => 'required|numeric',       // <--- Tambahan biar harga tersimpan
        ]);

        $purchase = Purchase::findOrFail($id);

        // 2. SIMPAN HARGA REALISASI & BUKTI
        // Update harga kalau admin ubah di modal
        if ($request->has('real_price')) {
            $purchase->unit_price = $request->real_price; 
            // Opsional: Update subtotal juga kalau mau
            $purchase->subtotal = $request->real_price * $purchase->quantity;
        }

        // Upload Foto
        if ($request->hasFile('proof_photo')) {
            $path = $request->file('proof_photo')->store('proofs', 'public');
            $purchase->transaction_proof_photo = $path;
        }

        // Update Status jadi Selesai
        $purchase->status = 'completed';
        $purchase->is_purchased = true;
        $purchase->save();

        // ==========================================================
        // 3. GENERATOR KODE ASET (Sesuai Category.php Abang)
        // ==========================================================
        
        $category = Category::find($purchase->category_id);
        
        // Default Prefix
        $prefix = 'GEN'; 

        // Ambil 3 huruf depan dari category_name
        if ($category && !empty($category->category_name)) {
            $prefix = strtoupper(substr($category->category_name, 0, 3));
        }

        // Cari nomor urut terakhir
        $lastTool = Tool::where('tool_code', 'like', $prefix . '-%')
                        ->orderBy('id', 'desc')
                        ->first();

        $nextNumber = 1;
        if ($lastTool) {
            $parts = explode('-', $lastTool->tool_code);
            if (count($parts) >= 2) {
                $nextNumber = intval(end($parts)) + 1;
            }
        }

        $generatedCode = $prefix . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        
        // ==========================================================
        // 4. MASUKKAN KE DAFTAR ALAT (INVENTORY)
        // ==========================================================

        Tool::create([
            'tool_code'         => $generatedCode,
            'tool_name'         => $purchase->tool_name,
            'category_id'       => $purchase->category_id,
            'purchase_item_id'  => $purchase->id,
            'current_condition' => 'Baik',
            'availability_status' => 'available',
        ]);

        // 5. PINDAH KE HALAMAN RIWAYAT
        return redirect()->route('purchases.history')->with('success', 'Transaksi Selesai! Barang masuk inventaris: ' . $generatedCode);
    }

    public function show($id)
    {
        $purchase = Purchase::with(['vendor', 'user', 'category'])->findOrFail($id);
        return view('purchases.show', compact('purchase'));
    }

    public function destroy($id)
    {
        $purchase = Purchase::findOrFail($id);
        
        if ($purchase->status == 'approved' || $purchase->is_purchased) {
             return back()->with('error', 'Tidak bisa menghapus data yang sudah disetujui/dibeli.');
        }

        $purchase->delete();
        return back()->with('success', 'Data dihapus.');
    }
}