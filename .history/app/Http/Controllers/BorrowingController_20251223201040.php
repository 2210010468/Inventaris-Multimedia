<?php

namespace App\Http\Controllers;

use App\Models\Borrowing;
use App\Models\Tool;
use App\Models\Borrower;
use App\Models\BorrowingItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // WAJIB: Untuk Database Transaction
use Illuminate\Support\Facades\Auth;

class BorrowingController extends Controller
{
    /**
     * Menampilkan daftar peminjaman dengan fitur Filter & Search
     */
    public function index(Request $request)
    {
        // 1. Siapkan Query Dasar
        $query = Borrowing::with(['borrower', 'user', 'items.tool']);

        // 2. Logika Search (Mencari Nama Peminjam atau NIS/NIP)
        if ($request->has('search') && $request->search != null) {
            $search = $request->search;
            $query->whereHas('borrower', function($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                  ->orWhere('code', 'like', '%'.$search.'%');
            });
        }

        // 3. Logika Filter Status (Dipinjam / Kembali)
        if ($request->has('status') && $request->status != null) {
            if ($request->status == 'active') {
                $query->where('borrowing_status', 'active');
            } elseif ($request->status == 'returned') {
                $query->where('borrowing_status', 'returned');
            }
        }

        // 4. Filter periode (all | week | month)
        if ($request->filled('period') && $request->period !== 'all') {
            if ($request->period == 'week') {
                $query->where('borrow_date', '>=', now()->subWeek());
            } elseif ($request->period == 'month') {
                $query->where('borrow_date', '>=', now()->subMonth());
            }
        }

        // 5. Ambil data dengan Pagination (6 per halaman)
        // ->withQueryString() penting agar saat pindah halaman, filter tidak reset
        $borrowings = $query->latest()->paginate(6)->withQueryString();
        
        return view('borrowings.index', compact('borrowings'));
    }

    /**
     * Menampilkan form tambah peminjaman
     */
    public function create()
    {
        // Block kepala/head from creating borrowings
        $user = Auth::user();
        if ($user && in_array($user->role, ['kepala','head'])) {
            return redirect()->route('borrowings.index')->with('error', 'Akses ditolak. Anda tidak dapat mencatat peminjaman.');
        }

        $borrowers = \App\Models\Borrower::all();
        $categories = \App\Models\Category::all(); 

        return view('borrowings.create', compact('borrowers', 'categories'));
    }

    /**
     * Menyimpan data peminjaman ke database
     */
    public function store(Request $request)
    {
        // Block kepala/head from storing borrowings
        $user = Auth::user();
        if ($user && in_array($user->role, ['kepala','head'])) {
            return redirect()->route('borrowings.index')->with('error', 'Akses ditolak. Anda tidak dapat mencatat peminjaman.');
        }
        // 1. Validasi input
        $request->validate([
            'borrower_id' => 'required|exists:borrowers,id',
            'tool_ids' => 'required|array',
            'tool_ids.*' => 'exists:tools,id',
            'borrow_date' => 'required|date',
            'planned_return_date' => 'required|date|after_or_equal:borrow_date',
            'notes' => 'nullable|string|max:255',
        ]);

        // 2. Simpan Data dengan Transaksi Database
        // Ini mencegah data tersimpan sebagian jika terjadi error
        DB::transaction(function () use ($request) {
            // A. Simpan Header Peminjaman
            $borrowing = Borrowing::create([
                'user_id' => Auth::id(),
                'borrower_id' => $request->borrower_id,
                'borrow_date' => $request->borrow_date,
                'planned_return_date' => $request->planned_return_date,
                'borrowing_status' => 'active',
                'notes' => $request->notes,
            ]);

            // B. Simpan Detail Item (Looping alat yang dipilih)
            foreach ($request->tool_ids as $toolId) {
                // Ambil data alat untuk tahu kondisi saat ini
                $tool = Tool::find($toolId);

                $borrowing->items()->create([
                    'tool_id' => $toolId,
                    // PENTING: Mengisi kondisi awal alat agar tidak error database
                    'tool_condition_before' => $tool->current_condition ?? 'Baik', 
                ]);
                
                // C. Update status alat menjadi "dipinjam"
                // Agar alat tidak muncul di list peminjaman orang lain
                $tool->update(['availability_status' => 'borrowed']);
            }
        });

        return redirect()->route('borrowings.index')->with('success', 'Peminjaman berhasil dicatat!');
    }

    /**
     * Memproses pengembalian barang
     */
    public function returnItem($id)
    {
        // 1. Cari data peminjaman beserta itemnya
        $borrowing = Borrowing::with('items.tool')->findOrFail($id);

        // 2. Update status peminjaman jadi 'returned'
        $borrowing->update([
            'borrowing_status' => 'returned', 
            'actual_return_date' => now(),
        ]);

        // 3. Kembalikan Status Alat menjadi 'Available'
        foreach ($borrowing->items as $item) {
            $tool = $item->tool;
            if($tool) {
                $tool->update(['availability_status' => 'available']);
            }
        }

        return redirect()->route('borrowings.index')->with('success', 'Barang berhasil dikembalikan! Stok alat tersedia kembali.');
    }

    /**
     * Menampilkan form edit (Hanya tanggal & notes)
     */
    public function edit($id)
    {
        // Block kepala/head from editing borrowings
        $user = Auth::user();
        if ($user && in_array($user->role, ['kepala','head'])) {
            return redirect()->route('borrowings.index')->with('error', 'Akses ditolak. Anda tidak dapat mengubah data peminjaman.');
        }

        $borrowing = Borrowing::findOrFail($id);
        $borrowers = \App\Models\Borrower::all();
        
        return view('borrowings.edit', compact('borrowing', 'borrowers'));
    }

    /**
     * Update data edit
     */
    public function update(Request $request, $id)
    {
        // Block kepala/head from updating borrowings
        $user = Auth::user();
        if ($user && in_array($user->role, ['kepala','head'])) {
            return redirect()->route('borrowings.index')->with('error', 'Akses ditolak. Anda tidak dapat mengubah data peminjaman.');
        }
        $request->validate([
            'planned_return_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $borrowing = Borrowing::findOrFail($id);
        
        $borrowing->update([
            'planned_return_date' => $request->planned_return_date,
            'notes' => $request->notes,
        ]);

        return redirect()->route('borrowings.index')->with('success', 'Data peminjaman berhasil diperbarui.');
    }

    /**
     * Hapus riwayat peminjaman
     */
    public function destroy($id)
    {
        // Block kepala/head from deleting borrowings
        $user = Auth::user();
        if ($user && in_array($user->role, ['kepala','head'])) {
            return redirect()->route('borrowings.index')->with('error', 'Akses ditolak. Anda tidak dapat menghapus riwayat peminjaman.');
        }
        $borrowing = Borrowing::findOrFail($id);
        $borrowing->delete();
        
        return redirect()->route('borrowings.index')->with('success', 'Riwayat berhasil dihapus.');
    }

    /**
     * API untuk mengambil alat berdasarkan kategori (AJAX)
     */
    public function getToolsByCategory($categoryId)
    {
        $tools = \App\Models\Tool::where('category_id', $categoryId)
                    ->where('availability_status', 'available') // Hanya ambil yang tersedia
                    ->get();
                    
        return response()->json($tools);
    }
}