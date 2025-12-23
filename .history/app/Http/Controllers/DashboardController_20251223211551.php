<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Tool;      // Sesuaikan nama Model Barang/Alat Anda
use App\Models\Borrowing; // Sesuaikan nama Model Peminjaman Anda

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if ($user->isHead()) {
            // === DATA UNTUK KEPALA (Monitoring) ===
            $data = [
                // Hitung total peminjaman bulan ini
                'monthly_borrowings' => Borrowing::whereMonth('borrow_date', now()->month)
                                        ->whereYear('borrow_date', now()->year)
                                        ->count(),
                
                // Hitung barang yang sedang dipinjam (Active)
                'active_borrowings'  => Borrowing::where('borrowing_status', 'active')->count(),

                // Hitung barang yang telat kembali (Lewat tanggal rencana & status masih active)
                'overdue_items'      => Borrowing::where('borrowing_status', 'active')
                                        ->where('planned_return_date', '<', now())
                                        ->count(),
                
                // Ambil 5 riwayat terbaru untuk tabel ringkas
                'recent_activities'  => Borrowing::with('borrower', 'items.tool')
                                        ->latest()
                                        ->take(5)
                                        ->get(),
            ];
        } else {
            // === DATA UNTUK ADMIN (Operasional) ===
            $data = [
                'total_users'       => User::count(),
                'total_tools'       => Tool::count(), // Pastikan model Tool ada
                'active_borrowings' => Borrowing::where('borrowing_status', 'active')->count(),
                'returned_today'    => Borrowing::whereDate('actual_return_date', today())->count(),
            ];
        }

        return view('dashboard', compact('data'));
    }
}