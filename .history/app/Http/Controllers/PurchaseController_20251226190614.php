<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\Vendor;
use App\Models\Category; // Pastikan Model ini ada (sesuai migrasi tool_categories)
use App\Models\Tool;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PurchaseController extends Controller
{
    // ----------------------------------------------------------------------
    // HALAMAN 1: DAFTAR REQUEST (Untuk Kepala melihat & Approve)
    // ----------------------------------------------------------------------
    public function indexRequests()
    {
        // Menampilkan yang belum Approved (Pending/Rejected)
        // Atau semua history pengajuan
        $purchases = Purchase::with(['vendor', 'user', 'category'])
            ->where('is_purchased', false) // Belum dibeli real
            ->orderBy('created_at', 'desc')
            ->get();

        return view('purchases.requests', compact('purchases'));
    }

    // ----------------------------------------------------------------------
    // HALAMAN 2: DAFTAR BELANJA (Untuk Admin Upload Bukti)
    // ----------------------------------------------------------------------
    public function indexPurchases()
    {
        // Menampilkan yang SUDAH Approved TAPI belum dibeli (Todo List Admin)
        $purchases = Purchase::with(['vendor', 'user', 'category'])
            ->where('status', 'approved')
            ->where('is_purchased', false)
            ->orderBy('date', 'asc')
            ->get();

        return view('purchases.todos', compact('purchases'));
    }

    // Form Pengajuan Baru
    public function create()
    {
        $vendors = Vendor::all();
        $categories = Category::all(); 
        
        // Prevent kepala from creating (sesuai logic mas)
        $user = Auth::user();
        if ($user && in_array($user->role, ['kepala','head'])) {
            return redirect()->back()->with('error', 'Akses ditolak. Kepala tidak membuat pengajuan.');
        }
        
        return view('purchases.create', compact('vendors', 'categories'));
    }

    // ----------------------------------------------------------------------
    // LOGIC 1: SIMPAN PENGAJUAN (Status: Pending)
    // ----------------------------------------------------------------------
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
                
                // Detail Item Langsung di sini
                'tool_name'     => $request->tool_name,
                'specification' => $request->specification ?? '-',
                'quantity'      => $request->quantity,
                'unit_price'    => $request->unit_price,
                'subtotal'      => $subtotal,
                
                'status'        => 'pending',
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
    // LOGIC 2: APPROVAL KEPALA (Hanya Ganti Status)
    // ----------------------------------------------------------------------
    public function approve($id)
    {
        $user = Auth::user();
        if (!$user || !in_array($user->role, ['kepala','head'])) {
            return abort(403, 'Unauthorized');
        }

        $purchase = Purchase::findOrFail($id);
        
        // Cukup update status, JANGAN generate tool dulu
        $purchase->update([
            'status' => 'approved'
        ]);

        return redirect()->back()->with('success', 'Pengajuan disetujui. Data masuk ke daftar belanja Admin.');
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
    // LOGIC 3: EKSEKUSI BELANJA (Admin Upload Bukti -> Generate Alat)
    // ----------------------------------------------------------------------
    public function storePurchaseEvidence(Request $request, $id)
    {
        $request->validate([
            'proof_photo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            // Opsional: Admin bisa revisi harga real jika beda dengan pengajuan
            'real_price'  => 'nullable|numeric', 
        ]);

        try {
            DB::beginTransaction();

            $purchase = Purchase::findOrFail($id);

            // 1. Upload Foto
            if ($request->hasFile('proof_photo')) {
                $path = $request->file('proof_photo')->store('bukti_pembelian', 'public');
                $purchase->transaction_proof_photo = $path;
            }

            // 2. Update Data jika ada perubahan harga realisasi
            if($request->filled('real_price')) {
                $purchase->unit_price = $request->real_price;
                $purchase->subtotal = $request->real_price * $purchase->quantity;
            }

            // 3. Update Status Akhir
            $purchase->is_purchased = true;
            $purchase->save();

            // 4. GENERATE TOOLS (Pindah kesini)
            // Logic: Generate kode unik & Masukkan ke tabel Tool
            
            $category = Category::find($purchase->category_id);
            // Prefix: Kalau kategori punya kode pakai itu, kalau gak pakai 3 huruf nama alat
            $prefix = $category && $category->code ? $category->code : strtoupper(substr($purchase->tool_name, 0, 3));

            // Cari urutan terakhir untuk prefix ini
            // Ambil yg kodenya mirip "PREFIX-%"
            $lastTool = Tool::where('tool_code', 'like', "$prefix-%")
                            ->orderByRaw('LENGTH(tool_code) desc') // Biar urutan 10 gak kalah sama 9
                            ->orderBy('tool_code', 'desc')
                            ->first();

            $lastNumber = 0;
            if ($lastTool) {
                // Pecah string "ABC-005" ambil angka belakangnya
                $parts = explode('-', $lastTool->tool_code);
                $lastNumber = intval(end($parts));
            }

            // Loop sesuai quantity
            for ($i = 1; $i <= $purchase->quantity; $i++) {
                $lastNumber++;
                $newCode = sprintf('%s-%03d', $prefix, $lastNumber);

                Tool::create([
                    'tool_code'           => $newCode,
                    'tool_name'           => $purchase->tool_name,
                    'category_id'         => $purchase->category_id,
                    'current_condition'   => 'Baik',
                    'availability_status' => 'available',
                    'source'              => 'Pembelian PO: ' . $purchase->purchase_code,
                    // Opsional: link ke ID pembelian kalau mau tracking
                    // 'purchase_id'      => $purchase->id 
                ]);
            }

            DB::commit();
            return redirect()->route('purchases.todos')->with('success', 'Bukti terupload & Barang masuk inventaris!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal memproses: ' . $e->getMessage());
        }
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