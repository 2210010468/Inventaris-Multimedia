<?php

namespace App\Http\Controllers;

use App\Models\Tool;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ToolController extends Controller
{
    public function index(Request $request)
    {
        // Eager load category biar ringan
        $query = Tool::with('category');

        // 1. Search Nama Alat
        if ($request->has('search') && $request->search != null) {
            $query->where('tool_name', 'like', '%' . $request->search . '%');
        }

        // 2. Filter Kategori
        if ($request->has('category_id') && $request->category_id != null) {
            $query->where('category_id', $request->category_id);
        }
        
        // 3. Filter Kondisi (support legacy 'condition' and new 'current_condition')
        $cond = $request->input('current_condition', $request->input('condition'));
        if ($cond != null) {
            $query->where('current_condition', $cond);
        }

        $tools = $query->latest()->paginate(10)->withQueryString();
        $categories = Category::all(); // Untuk dropdown filter

        return view('tools.index', compact('tools', 'categories'));
    }

    public function create() {
        // Block kepala/head from creating tools
        $user = Auth::user();
        if ($user && in_array($user->role, ['kepala','head'])) {
            return redirect()->route('tools.index')->with('error', 'Akses ditolak. Anda tidak dapat menambahkan alat.');
        }

        $categories = Category::all();
        return view('tools.create', compact('categories'));
    }

    public function store(Request $request) {
        // Block kepala/head from storing tools
        $user = Auth::user();
        if ($user && in_array($user->role, ['kepala','head'])) {
            return redirect()->route('tools.index')->with('error', 'Akses ditolak. Anda tidak dapat menambahkan alat.');
        }
        $request->validate([
            'tool_code' => 'required|string|unique:tools,tool_code',
            'tool_name' => 'required|string',
            'category_id' => 'required|exists:tool_categories,id',
            'current_condition' => 'required|string',
            'availability_status' => 'nullable|in:available,borrowed,maintenance,lost',
        ]);

        $data = $request->only(['tool_code', 'tool_name', 'category_id', 'current_condition', 'availability_status']);
        $data['availability_status'] = $data['availability_status'] ?? 'available';

        Tool::create($data);
        return redirect()->route('tools.index')->with('success', 'Alat berhasil ditambahkan.');
    }

    public function edit($id) {
        // Block kepala/head from editing tools
        $user = Auth::user();
        if ($user && in_array($user->role, ['kepala','head'])) {
            return redirect()->route('tools.index')->with('error', 'Akses ditolak. Anda tidak dapat mengubah data alat.');
        }
        $tool = Tool::findOrFail($id);
        $categories = Category::all();
        return view('tools.edit', compact('tool', 'categories'));
    }

    public function update(Request $request, $id) {
        // Block kepala/head from updating tools
        $user = Auth::user();
        if ($user && in_array($user->role, ['kepala','head'])) {
            return redirect()->route('tools.index')->with('error', 'Akses ditolak. Anda tidak dapat mengubah data alat.');
        }
        $request->validate([
            'tool_name'   => 'required|string|max:255',
            'category_id' => 'required|exists:tool_categories,id',
            'current_condition' => 'required|string',
        ]);
        
        $tool = Tool::findOrFail($id);
        
        $tool->update([
            'tool_name'           => $request->tool_name,
            'category_id'         => $request->category_id,
            'current_condition'   => $request->current_condition, // <--- Ganti jadi current_condition
            'availability_status' => $request->availability_status,
        ]);

        return redirect()->route('tools.index')->with('success', 'Data alat diperbarui.');
    }

    /**
     * Remove the specified tool from storage.
     * Prevent deletion when there is borrowing history.
     */
    public function destroy($id)
    {
        // Block kepala/head from deleting tools
        $user = Auth::user();
        if ($user && in_array($user->role, ['kepala','head'])) {
            return redirect()->route('tools.index')->with('error', 'Akses ditolak. Anda tidak dapat menghapus alat.');
        }

        $tool = Tool::findOrFail($id);

        // Soft-delete saja: biarkan riwayat peminjaman tetap ada.
        $tool->delete();

        return redirect()->route('tools.index')->with('success', 'Alat berhasil dinonaktifkan (soft-deleted). Riwayat peminjaman tetap tersimpan.');
    }
}