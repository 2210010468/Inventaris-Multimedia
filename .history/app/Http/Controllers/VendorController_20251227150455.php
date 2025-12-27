<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class VendorController extends Controller
{
    // Menampilkan daftar vendor
    public function index()
    {
        $vendors = Vendor::latest()->get();
        return view('vendors.index', compact('vendors'));
    }

    // Menampilkan form tambah vendor
    public function create()
    {
        // Block kepala/head from creating vendors
        $user = Auth::user();
        if ($user && in_array($user->role, ['kepala','head'])) {
            return redirect()->route('vendors.index')->with('error', 'Akses ditolak. Anda tidak dapat menambahkan vendor.');
        }

        return view('vendors.create');
    }

    // Menyimpan data vendor baru
    public function store(Request $request)
    {
        // Block kepala/head from storing vendors
        $user = Auth::user();
        if ($user && in_array($user->role, ['kepala','head'])) {
            return redirect()->route('vendors.index')->with('error', 'Akses ditolak. Anda tidak dapat menambahkan vendor.');
        }
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
        ]);

        Vendor::create($request->all());

        return redirect()->route('vendors.index')
            ->with('success', 'Vendor berhasil ditambahkan.');
    }

    // Menampilkan form edit
    public function edit(Vendor $vendor)
    {
        // Block kepala/head from editing vendors
        $user = Auth::user();
        if ($user && in_array($user->role, ['kepala','head'])) {
            return redirect()->route('vendors.index')->with('error', 'Akses ditolak. Anda tidak dapat mengubah vendor.');
        }

        return view('vendors.edit', compact('vendor'));
    }

    // Update data vendor
    public function update(Request $request, Vendor $vendor)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
        ]);

        $vendor->update($request->all());

        return redirect()->route('vendors.index')
            ->with('success', 'Data vendor berhasil diperbarui.');
    }

    // Hapus vendor
    public function destroy(Vendor $vendor)
    {
        // Block kepala/head from deleting vendors
        $user = Auth::user();
        if ($user && in_array($user->role, ['kepala','head'])) {
            return redirect()->route('vendors.index')->with('error', 'Akses ditolak. Anda tidak dapat menghapus vendor.');
        }

        // Cek dulu apakah vendor ini punya riwayat transaksi
        if($vendor->purchases()->exists()) {
            return back()->with('error', 'Vendor tidak bisa dihapus karena memiliki riwayat pembelian.');
        }

        $vendor->delete();

        return redirect()->route('vendors.index')
            ->with('success', 'Vendor berhasil dihapus.');
    }
}