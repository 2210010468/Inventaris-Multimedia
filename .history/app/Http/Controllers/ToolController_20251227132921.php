<?php

namespace App\Http\Controllers;

use App\Models\Tool;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf; 
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\Rule; // Tambahkan ini untuk validasi update

class ToolController extends Controller
{
    // ==========================
    // 1. READ DATA (INDEX & PDF)
    // ==========================

    public function index(Request $request)
    {
        $categories = Category::all();
        $query = $this->getFilteredQuery($request);
        $tools = $query->latest()->paginate(10)->withQueryString();

        return view('tools.index', compact('tools', 'categories'));
    }

    public function exportPdf(Request $request)
    {
        $query = $this->getFilteredQuery($request);
        $tools = $query->get();
        $pdf = Pdf::loadView('tools.pdf', compact('tools'));
        
        return $pdf->download('laporan-inventaris-alat-' . now()->format('Y-m-d') . '.pdf');
    }

    // ==========================
    // 2. SOFT DELETES (TRASH)
    // ==========================

    // Menampilkan halaman sampah (soft deleted) dengan Filter & Search
    public function trash(Request $request)
    {
        // 1. Query Dasar: Hanya ambil data yang sudah dihapus (onlyTrashed)
        $query = \App\Models\Tool::onlyTrashed()->with('category');

        // 2. Logika Search (Nama atau Kode)
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('tool_name', 'like', '%' . $search . '%')
                  ->orWhere('tool_code', 'like', '%' . $search . '%');
            });
        }

        // 3. Logika Filter Kategori
        if ($request->has('category_id') && $request->category_id != '' && $request->category_id != 'all') {
            $query->where('category_id', $request->category_id);
        }

        // 4. Ambil data (Pagination)
        $tools = $query->orderBy('deleted_at', 'desc')->paginate(10);

        // 5. Ambil semua kategori untuk isi dropdown filter
        $categories = \App\Models\Category::all();

        // 6. Return ke view 'tools.trash'
        return view('tools.trash', compact('tools', 'categories'));
    }

    public function restore($id)
    {
        $user = Auth::user();
        if ($user && in_array($user->role, ['kepala','head'])) {
             return redirect()->back()->with('error', 'Akses ditolak.');
        }

        $tool = Tool::onlyTrashed()->findOrFail($id);
        
        // Cek apakah kode alat bertabrakan dengan data aktif saat ini
        // Jika kode 'A-001' dihapus, lalu ada 'A-001' baru dibuat, restore akan error/duplicate.
        // Opsional: Tambahkan logika suffix jika duplikat, tapi di sini kita restore standar.
        
        $tool->availability_status = 'available';
        $tool->save();
        $tool->restore();
        
        return redirect()->route('tools.trash')->with('success', 'Alat berhasil dipulihkan ke daftar aktif.');
    }

    // ==========================
    // 3. CRUD (CREATE, UPDATE, DELETE)
    // ==========================

    public function create() {
        $user = Auth::user();
        if ($user && in_array($user->role, ['kepala','head'])) {
            return redirect()->route('tools.index')->with('error', 'Akses ditolak.');
        }

        $categories = Category::all();
        
        // (Opsional) Kita bisa kirim calon kode berikutnya ke view untuk ditampilkan
        $nextCode = $this->generateNextToolCode(); 

        return view('tools.create', compact('categories', 'nextCode'));
    }

    public function store(Request $request)
    {
        // 1. VALIDASI INPUT (SAYA SESUAIKAN DENGAN FILE BLADE ABANG)
        $request->validate([
            'tool_name'           => 'required|string|max:255',
            'category_id'         => 'required|exists:tool_categories,id',
            // Perbaikan: Pakai nama 'current_condition' sesuai form
            'current_condition'   => 'required', 
            // Perbaikan: Pakai nama 'availability_status' sesuai form
            'availability_status' => 'required', 
        ]);

        // ==========================================================
        // 2. GENERATOR KODE OTOMATIS
        // ==========================================================
        
        $category = Category::find($request->category_id);

        $prefix = 'TOOL'; 

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
        // 3. SIMPAN KE DATABASE
        // ==========================================================

        Tool::create([
            'tool_code'           => $generatedCode,
            'tool_name'           => $request->tool_name,
            'category_id'         => $request->category_id,
            // Perbaikan: Ambil dari input yang benar
            'current_condition'   => $request->current_condition, 
            'availability_status' => $request->availability_status,
            // 'purchase_item_id' => null, 
        ]);

        return redirect()->route('tools.index')->with('success', 'Berhasil! Alat manual ditambahkan dengan kode: ' . $generatedCode);
    }

    public function edit($id) {
        $user = Auth::user();
        if ($user && in_array($user->role, ['kepala','head'])) {
            return redirect()->route('tools.index')->with('error', 'Akses ditolak.');
        }
        $tool = Tool::findOrFail($id);
        $categories = Category::all();
        return view('tools.edit', compact('tool', 'categories'));
    }

    public function update(Request $request, $id) {
        $user = Auth::user();
        if ($user && in_array($user->role, ['kepala','head'])) {
            return redirect()->route('tools.index')->with('error', 'Akses ditolak.');
        }
        
        $request->validate([
            'tool_name'   => 'required|string|max:255',
            'category_id' => 'required|exists:tool_categories,id',
            'current_condition' => 'required|string',
            // Validasi: pastikan tidak mengubah kode menjadi kode yang sudah ada (termasuk di trash)
            // (Jika tool_code bisa diedit, tambahkan validasi unique di sini)
        ]);
        
        $tool = Tool::findOrFail($id);
        
        $tool->update([
            'tool_name'         => $request->tool_name,
            'category_id'       => $request->category_id,
            'current_condition' => $request->current_condition, 
            'availability_status' => $request->availability_status,
        ]);

        return redirect()->route('tools.index')->with('success', 'Data alat diperbarui.');
    }

    public function destroy($id)
    {
        $user = Auth::user();
        if ($user && in_array($user->role, ['kepala','head'])) {
            return redirect()->route('tools.index')->with('error', 'Akses ditolak.');
        }

        $tool = Tool::findOrFail($id);

        if ($tool->availability_status == 'borrowed') {
            return redirect()->back()->with('error', 'Gagal! Alat sedang dipinjam oleh seseorang.');
        }
        
        if ($tool->availability_status == 'maintenance') {
            return redirect()->back()->with('error', 'Gagal! Alat sedang dalam perbaikan.');
        }

        $tool->availability_status = 'disposed'; 
        $tool->save();
        $tool->delete(); 

        return redirect()->route('tools.index')->with('success', 'Alat berhasil dipindahkan ke Inventaris Tak Terpakai.');
    }

    // ==========================
    // 4. HELPER FUNCTION
    // ==========================

    /**
     * Membuat kode alat otomatis berdasarkan data terakhir (termasuk Trash).
     * Format contoh: TOOL-001, TOOL-002
     */
    private function generateNextToolCode()
    {
        // 1. Ambil tool terakhir berdasarkan ID, TERMASUK yang sudah dihapus (withTrashed)
        $lastTool = Tool::withTrashed()->orderBy('id', 'desc')->first();

        // 2. Jika belum ada data sama sekali, mulai dari 001
        if (!$lastTool) {
            return 'TOOL-001'; 
        }

        // 3. Ambil kode terakhir
        $lastCode = $lastTool->tool_code;

        // 4. Pecah string dan angka (Contoh: TOOL-005 menjadi "TOOL-" dan "005")
        // Regex: Ambil semua karakter sebelum angka terakhir, dan ambil angka terakhirnya
        if (preg_match('/^(.*?)(\d+)$/', $lastCode, $matches)) {
            $prefix = $matches[1]; // misal "TOOL-"
            $number = intval($matches[2]); // misal 5
            $length = strlen($matches[2]); // panjang digit (misal 3 digit)
            
            // Increment angka
            $newNumber = $number + 1;
            
            // Gabungkan kembali dengan padding nol di depan
            return $prefix . str_pad($newNumber, $length, '0', STR_PAD_LEFT);
        }

        // Fallback jika format kode sebelumnya tidak mengandung angka (misal "OBENG")
        return $lastCode . '-1';
    }

    private function getFilteredQuery(Request $request)
    {
        $query = Tool::with('category');

        if ($request->has('search') && $request->search != null) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('tool_name', 'like', '%'.$search.'%')
                  ->orWhere('tool_code', 'like', '%'.$search.'%');
            });
        }

        if ($request->has('status') && $request->status != null) {
            $query->where('availability_status', $request->status);
        }

        if ($request->has('category_id') && $request->category_id != null && $request->category_id != 'all') {
            $query->where('category_id', $request->category_id);
        }

        return $query;
    }

    
}