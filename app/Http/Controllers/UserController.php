<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules;

class UserController extends Controller
{
    // Cek apakah user adalah HEAD
    private function checkHead()
    {
        if (Auth::user()->role !== 'head') {
            abort(403, 'Unauthorized action. Only Head can perform this.');
        }
    }

    // 1. READ (Bisa diakses Head & Admin)
    public function index()
    {
        // Ambil semua data user, urutkan dari yang terbaru
        $users = User::orderBy('created_at', 'desc')->paginate(10);
        return view('users.index', compact('users'));
    }

    // 2. CREATE FORM (Hanya Head)
    public function create()
    {
        $this->checkHead(); // Security Check
        return view('users.create');
    }

    // 3. STORE DATABASE (Hanya Head)
    public function store(Request $request)
    {
        $this->checkHead(); // Security Check

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'role' => ['required', 'in:admin,head'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('users.index')->with('success', 'User berhasil ditambahkan!');
    }

    // 4. EDIT FORM (Hanya Head)
    public function edit(User $user)
    {
        $this->checkHead(); // Security Check
        return view('users.edit', compact('user'));
    }

    // 5. UPDATE DATABASE (Hanya Head)
    public function update(Request $request, User $user)
    {
        $this->checkHead(); // Security Check

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'role' => ['required', 'in:admin,head'],
        ]);

        // Update data dasar
        $user->name = $request->name;
        $user->email = $request->email;
        $user->role = $request->role;

        // Update password HANYA JIKA diisi (biar tidak wajib ganti password saat edit profil)
        if ($request->filled('password')) {
            $request->validate([
                'password' => ['confirmed', Rules\Password::defaults()],
            ]);
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return redirect()->route('users.index')->with('success', 'Data user berhasil diperbarui!');
    }

    // 6. DELETE (Hanya Head)
    public function destroy(User $user)
    {
        $this->checkHead(); // Security Check

        // Mencegah Head menghapus dirinya sendiri
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri saat sedang login.');
        }

        $user->delete();
        return redirect()->route('users.index')->with('success', 'User berhasil dihapus!');
    }
}