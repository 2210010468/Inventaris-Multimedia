<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ToolController;
use App\Http\Controllers\BorrowerController;
use App\Http\Controllers\BorrowingController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\MaintenanceTypeController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    
    // --- Profile Routes ---
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // --- Manajemen Alat (Tools) ---
    // 1. Trash & Restore (WAJIB DI ATAS Resource)
    Route::get('/tools/trash', [ToolController::class, 'trash'])->name('tools.trash');
    Route::put('/tools/{id}/restore', [ToolController::class, 'restore'])->name('tools.restore');
    
    // 2. Export & Custom
    Route::get('tools/export-pdf', [ToolController::class, 'exportPdf'])->name('tools.exportPdf');
    
    // 3. Resource & Kategori
    Route::resource('categories', CategoryController::class);
    Route::get('/categories/{category}/next-code', [CategoryController::class, 'nextCode'])->name('categories.nextCode');
    Route::resource('tools', ToolController::class);


    // --- Peminjam & Peminjaman ---
    Route::resource('borrowers', BorrowerController::class);
    
    // Custom Routes Peminjaman
    Route::get('/borrowings/export-pdf', [BorrowingController::class, 'exportPdf'])->name('borrowings.exportPdf');
    Route::put('/borrowings/{id}/return', [BorrowingController::class, 'returnItem'])->name('borrowings.return');
    Route::get('/get-tools/{categoryId}', [BorrowingController::class, 'getToolsByCategory'])->name('tools.getByCategory');
    
    Route::resource('borrowings', BorrowingController::class);


    // --- Manajemen Pembelian (UPDATED LOGIC) ---
    Route::resource('vendors', VendorController::class);

    // 1. Halaman List / Tampilan
    // Halaman Approval (Status Pending)
    Route::get('/purchases/requests', [PurchaseController::class, 'indexRequests'])->name('purchases.request');
    
    // Halaman Transaction/Todos (Status Approved -> Menunggu Upload Bukti)
    Route::get('/purchases/todos', [PurchaseController::class, 'indexPurchases'])->name('purchases.todos');
    
    // Halaman Riwayat (Status Rejected & Completed/Purchased)
    Route::get('/purchases/history', [PurchaseController::class, 'indexHistory'])->name('purchases.history');

    // 2. Action Routes (Tombol Aksi)
    // Approve & Reject (Kepala)
    Route::patch('/purchases/{id}/approve', [PurchaseController::class, 'approve'])->name('purchases.approve');
    Route::patch('/purchases/{id}/reject', [PurchaseController::class, 'reject'])->name('purchases.reject');
    
    // Upload Bukti / Eksekusi Belanja (Admin)
    Route::post('/purchases/{id}/evidence', [PurchaseController::class, 'storePurchaseEvidence'])->name('purchases.evidence');
    
    // 3. Resource Purchases (CRUD Dasar)
    // Menggunakan except 'index' karena kita sudah buat custom index di atas (requests, todos, history)
    Route::resource('purchases', PurchaseController::class)->except(['index', 'update', 'edit']);


    // --- Maintenance Routes ---
    Route::resource('maintenances', MaintenanceController::class);
    Route::resource('maintenance-types', MaintenanceTypeController::class);
});

require __DIR__.'/auth.php';