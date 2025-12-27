<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\Vendor;
use App\Models\Category;
use App\Models\Tool;
use App\Models\ToolCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class PurchaseController extends Controller
{
    // ----------------------------------------------------------------------
    // 1. HALAMAN "PERMOHONAN PEMBELIAN" (indexRequests)
    // Aturan: Menampilkan data ketika status != 'approved'
    // ----------------------------------------------------------------------
    public function indexRequests()
    {
        $purchases = Purchase::with(['vendor', 'user', 'category'])
            // MENAMPILKAN: Pending (Menunggu) & Rejected (Ditolak)
            ->where('status', '!=', 'approved')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('purchases.requests', compact('purchases'));
    }

    // ----------------------------------------------------------------------
    // 2. HALAMAN "PEMBELIAN BARANG" (indexTransaction)
    // Aturan: Menampilkan data ketika status == 'approved'
    // ----------------------------------------------------------------------
    public function indexTransaction()
    {
        $purchases = Purchase::with(['vendor', 'user', 'category'])
            // MENAMPILKAN: Approved (Disetujui) TAPI Belum Dibeli (is_purchased = false)
            ->where('status', 'approved')
            ->where('is_purchased', false) 
            ->orderBy('date', 'asc')
            ->get();

        // View diarahkan ke 'purchases.transaction' sesuai permintaan sebelumnya
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
        $request->validate([
            'proof_photo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'real_price'  => 'nullable|numeric', 
        ]);

        try {
            DB::beginTransaction();

            $purchase = Purchase::findOrFail($id);

            // A. Upload Foto Bukti
            if ($request->hasFile('proof_photo')) {
                $path = $request->file('proof_photo')->store('bukti_pembelian', 'public');
                $purchase->transaction_proof_photo = $path;
            }

            // B. Update Harga Real (Jika ada)
            if($request->filled('real_price')) {
                $purchase->unit_price = $request->real_price;
                $purchase->subtotal = $request->real_price * $purchase->quantity;
            }

            // C. Update Logic Utama: IS_PURCHASED = TRUE
            $purchase->is_purchased = true;
            $purchase->save();

            // D. GENERATE TOOLS (Masuk ke Data Barang)
            $category = Category::find($purchase->category_id);
            // Buat prefix kode: ambil dari kategori atau 3 huruf nama alat
            $prefix = $category && $category->code ? $category->code : strtoupper(substr($purchase->tool_name, 0, 3));

            // Cari nomor urut terakhir di DB Tool
            $lastTool = Tool::where('tool_code', 'like', "$prefix-%")
                            ->orderByRaw('LENGTH(tool_code) desc')
                            ->orderBy('tool_code', 'desc')
                            ->first();

            $lastNumber = 0;
            if ($lastTool) {
                $parts = explode('-', $lastTool->tool_code);
                $lastNumber = intval(end($parts));
            }

            // Loop sebanyak Quantity untuk insert ke tabel Tools
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
                ]);
            }

            DB::commit();
            // Redirect ke halaman transaction (karena data akan hilang dari list ini dan masuk history)
            return redirect()->route('purchases.transaction')->with('success', 'Transaksi selesai! Barang telah masuk ke inventaris.');

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