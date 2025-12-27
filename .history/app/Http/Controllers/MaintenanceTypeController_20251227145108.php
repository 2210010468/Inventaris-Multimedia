<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceType;
use Illuminate\Http\Request;

class MaintenanceTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = MaintenanceType::query();

        // Cek jika ada input pencarian
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        // Gunakan paginate() agar cocok dengan "$maintenanceTypes->links()" di View
        // withQueryString() agar saat pindah halaman, filter pencarian tidak hilang
        $maintenanceTypes = $query->latest()->paginate(10)->withQueryString();

        // Pastikan nama view ini sesuai dengan nama folder/file kamu (maintenance_types atau maintenance-types)
        return view('maintenance_types.index', compact('maintenanceTypes'));
    }

    public function create()
    {
        return view('maintenance_types.create');
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);
        MaintenanceType::create($request->all());
        return redirect()->route('maintenance-types.index')->with('success', 'Jenis maintenance berhasil dibuat.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string', // Pastikan ini ada
        ]);

        $type = \App\Models\MaintenanceType::findOrFail($id);
        
        $type->update([
            'name' => $request->name,
            'description' => $request->description, // Simpan deskripsi
        ]);

        // Redirect kembali ke halaman Index (supaya popup tertutup dan data ter-refresh)
        return redirect()->route('maintenance-types.index')->with('success', 'Berhasil diupdate');
    }

    public function destroy(MaintenanceType $maintenanceType)
    {
        $maintenanceType->delete();
        return redirect()->route('maintenance-types.index')->with('success', 'Data dihapus.');
    }
}