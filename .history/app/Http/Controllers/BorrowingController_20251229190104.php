<?php

namespace App\Http\Controllers;

use App\Models\Borrowing;
use App\Models\Tool;
use App\Models\Borrower;
use App\Models\Maintenance;
use App\Models\MaintenanceType;
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
        $query = $this->getFilteredQuery($request);

        $borrowings = $query->with(['borrower', 'items.tool', 'user'])
                            ->latest()
                            ->paginate(5);        

        // [BARU] Ambil jenis maintenance buat dropdown
        $maintenanceTypes = MaintenanceType::all(); 

        // [UBAH] Tambahkan compact 'maintenanceTypes'
        return view('borrowings.index', compact('borrowings', 'maintenanceTypes'));
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
        // [UBAH] Validasi dinamis
        $rules = [
            'return_condition' => 'required|string',
            'final_status' => 'required|string',
        ];

        // Kalau kondisi Rusak, WAJIB pilih jenis maintenance
        if (in_array($request->return_condition, ['Rusak Berat', 'Rusak Ringan'])) {
            $rules['maintenance_type_id'] = 'required|exists:maintenance_types,id';
        }

        $request->validate($rules);

        DB::transaction(function () use ($request, $id) {
            $borrowing = Borrowing::with('items.tool')->findOrFail($id);
            
            $borrowing->update([
                'borrowing_status' => 'returned', 
                'actual_return_date' => now(), 
                'return_condition' => $request->return_condition,
                'final_status' => $request->final_status,
            ]);

            $newToolStatus = 'available'; 
            $needsMaintenance = false;

            if ($request->final_status == 'Hilang') {
                $newToolStatus = 'disposed'; 
            } elseif (in_array($request->return_condition, ['Rusak Berat', 'Rusak Ringan'])) {
                $newToolStatus = 'maintenance'; 
                $needsMaintenance = true;
            } elseif ($request->final_status == 'Diganti') {
                $newToolStatus = 'available'; 
            }

            foreach ($borrowing->items as $item) {
                if($item->tool) {
                    $item->tool->update([
                        'availability_status' => $newToolStatus,
                        'current_condition' => $request->return_condition
                    ]);

                    // [UBAH] Logic Maintenance pakai inputan User
                    if ($needsMaintenance) {
                        Maintenance::create([
                            'tool_id'       => $item->tool_id,
                            'user_id'       => Auth::id(),
                            'start_date'    => now(),
                            'note'          => $request->return_condition . ' - Eks Peminjaman #' . $borrowing->id,
                            
                            // [PENTING] Ambil dari dropdown form
                            'maintenance_type_id' => $request->maintenance_type_id, 
                            
                            'status'        => 'in_progress', 
                            'cost'          => 0,
                            'action_taken'  => null,
                        ]);
                    }
                }
            }
        });

        return redirect()->route('borrowings.index')->with('success', 'Barang dikembalikan & status diperbarui.');
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