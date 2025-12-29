<?php

namespace App\Http\Controllers;

use App\Models\Borrowing;
use App\Models\Tool;
use App\Models\Borrower;
use App\Models\Maintenance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf; // <--- TAMBAHAN PENTING

class BorrowingController extends Controller
{
    /**
     * Menampilkan daftar peminjaman dengan fitur Filter & Search
     */
    public function index(Request $request)
    {
        // Panggil fungsi private untuk query agar tidak menulis ulang logika yang sama
        $query = $this->getFilteredQuery($request);

        // Ambil data dengan Pagination (6 per halaman)
        $borrowings = $query->with(['borrower', 'items.tool', 'user']) // <--- Tambahkan 'user'
                        ->latest()
                        ->paginate(5);        

        return view('borrowings.index', compact('borrowings'));
    }

    /**
     * [BARU] Export Laporan ke PDF
     */
    public function exportPdf(Request $request)
    {
        // 1. Gunakan logika filter yang sama
        $query = $this->getFilteredQuery($request);

        // 2. Load Relationship 'borrower' dan 'items.tool'
        // PENTING: Tanpa 'with', PDF tidak bisa membaca data relasi (sering kosong)
        $borrowings = $query->with(['borrower', 'items.tool']) 
                            ->latest()
                            ->get();

        // 3. Load View PDF
        $pdf = Pdf::loadView('borrowings.pdf', compact('borrowings'));
        
        // 4. Download file
        return $pdf->download('laporan-peminjaman-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * [HELPER] Fungsi Logika Filter (Dipakai oleh Index & ExportPdf)
     * Ini biar kodingan rapi dan tidak duplikat.
     */
    private function getFilteredQuery(Request $request)
    {
        $query = Borrowing::with(['borrower', 'user', 'items.tool']);

        // Logika Search
        if ($request->has('search') && $request->search != null) {
            $search = $request->search;
            $query->whereHas('borrower', function($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                  ->orWhere('code', 'like', '%'.$search.'%');
            });
        }

        // Logika Filter Status
        if ($request->has('status') && $request->status != null) {
            if ($request->status == 'active') {
                $query->where('borrowing_status', 'active');
            } elseif ($request->status == 'returned') {
                $query->where('borrowing_status', 'returned');
            }
        }

        // Filter periode
        if ($request->filled('period') && $request->period !== 'all') {
            if ($request->period == 'week') {
                $query->where('borrow_date', '>=', now()->subWeek());
            } elseif ($request->period == 'month') {
                $query->where('borrow_date', '>=', now()->subMonth());
            }
        }

        return $query;
    }

    // =================================================================
    // FUNGSI DI BAWAH INI TIDAK BERUBAH DARI YANG KAMU KIRIM SEBELUMNYA
    // =================================================================

    public function create()
    {
        $user = Auth::user();
        if ($user && in_array($user->role, ['kepala','head'])) {
            return redirect()->route('borrowings.index')->with('error', 'Akses ditolak.');
        }

        $borrowers = \App\Models\Borrower::all();
        $categories = \App\Models\Category::all(); 

        return view('borrowings.create', compact('borrowers', 'categories'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if ($user && in_array($user->role, ['kepala','head'])) {
            return redirect()->route('borrowings.index')->with('error', 'Akses ditolak.');
        }
        
        $request->validate([
            'borrower_id' => 'required|exists:borrowers,id',
            'tool_ids' => 'required|array',
            'tool_ids.*' => 'exists:tools,id',
            'borrow_date' => 'required|date',
            'planned_return_date' => 'required|date|after_or_equal:borrow_date',
            'notes' => 'nullable|string|max:255',
        ]);

        // Cek apakah ada SATU SAJA alat yang statusnya TIDAK 'available' (misal: maintenance atau borrowed)
        $unavailableTools = Tool::whereIn('id', $request->tool_ids)
                                ->where('availability_status', '!=', 'available')
                                ->get();

        if ($unavailableTools->count() > 0) {
            // Ambil nama alat untuk pesan error
            $names = $unavailableTools->pluck('tool_name')->join(', ');
            return redirect()->back()
                ->withInput()
                ->with('error', "Gagal! Alat berikut sedang tidak tersedia (Maintenance/Dipinjam): $names");
        }

        DB::transaction(function () use ($request) {
            $borrowing = Borrowing::create([
                'user_id' => Auth::id(),
                'borrower_id' => $request->borrower_id,
                'borrow_date' => $request->borrow_date,
                'planned_return_date' => $request->planned_return_date,
                'borrowing_status' => 'active',
                'notes' => $request->notes,
            ]);

            foreach ($request->tool_ids as $toolId) {
                $tool = Tool::find($toolId);
                $borrowing->items()->create([
                    'tool_id' => $toolId,
                    'tool_condition_before' => $tool->current_condition ?? 'Baik', 
                ]);
                $tool->update(['availability_status' => 'borrowed']);
            }
        });

        return redirect()->route('borrowings.index')->with('success', 'Peminjaman berhasil dicatat!');
    }

    public function returnItem(Request $request, $id)
    {
        // 1. Validasi (Tanggal kita hapus karena otomatis)
        $request->validate([
            'return_condition' => 'required|string',
            'final_status' => 'required|string',
        ]);

        DB::transaction(function () use ($request, $id) {
            $borrowing = Borrowing::with('items.tool')->findOrFail($id);
            
            // 2. Update Data Peminjaman (Tanggal Otomatis NOW)
            $borrowing->update([
                'borrowing_status' => 'returned', 
                'actual_return_date' => now(), 
                'return_condition' => $request->return_condition,
                'final_status' => $request->final_status,
            ]);

            // 3. Logika Update Status Alat & Buat Maintenance
            $newToolStatus = 'available'; 

            if ($request->final_status == 'Hilang') {
                $newToolStatus = 'disposed'; 
            } elseif ($request->return_condition == 'Rusak Berat','Rusak ') {
                $newToolStatus = 'maintenance'; 
            } elseif ($request->final_status == 'Diganti') {
                $newToolStatus = 'available'; 
            }

            foreach ($borrowing->items as $item) {
                if($item->tool) {
                    // Update Status Alat di Master Data
                    $item->tool->update([
                        'availability_status' => $newToolStatus,
                        'current_condition' => $request->return_condition
                    ]);

                    // ========================================================
                    // FITUR OTOMATIS: JIKA RUSAK BERAT -> MASUK MAINTENANCE
                    // ========================================================
                    if ($request->return_condition == 'Rusak Berat') {
                        Maintenance::create([
                            'tool_id'       => $item->tool_id,
                            'user_id'       => Auth::id(), // Admin yang memproses
                            'start_date'    => now(),      // Tanggal masuk maintenance hari ini
                            
                            // Isi Note dengan info peminjaman agar jelas asal kerusakannya
                            'note'          => 'Rusak Berat - Eks Peminjaman #' . $borrowing->id,
                            
                            // Data ini KOSONG (NULL) dulu, nanti diisi di menu Maintenance
                            'maintenance_type_id' => null, 
                            'vendor_name'   => null,
                            'action_taken'  => null,
                            'cost'          => 0,
                            'status'        => 'pending' // Status menunggu perbaikan
                        ]);
                    }
                    // ========================================================
                }
            }
        });

        return redirect()->route('borrowings.index')->with('success', 'Barang dikembalikan. Jika Rusak Berat, data Maintenance telah dibuat otomatis.');
    }

    public function edit($id)
    {
        $user = Auth::user();
        if ($user && in_array($user->role, ['kepala','head'])) {
            return redirect()->route('borrowings.index')->with('error', 'Akses ditolak.');
        }

        $borrowing = Borrowing::findOrFail($id);
        $borrowers = \App\Models\Borrower::all();
        
        return view('borrowings.edit', compact('borrowing', 'borrowers'));
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        if ($user && in_array($user->role, ['kepala','head'])) {
            return redirect()->route('borrowings.index')->with('error', 'Akses ditolak.');
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

    // --- TAMBAHAN UNTUK DROPDOWN ALAT ---
    public function getToolsByCategory($categoryId)
    {
        // Kita ambil alat berdasarkan Kategori
        // DAN statusnya harus 'available' (biar yang sedang dipinjam tidak muncul)
        // Saya sesuaikan nama kolomnya dengan fungsi store() kamu: 'availability_status'
        
        $tools = Tool::where('category_id', $categoryId)
                     ->where('availability_status', 'available') 
                     ->get();

        return response()->json($tools);
    }
}