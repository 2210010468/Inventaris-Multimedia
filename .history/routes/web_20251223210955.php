<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ToolController;
use App\Http\Controllers\BorrowerController;
use App\Http\Controllers\BorrowingController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\PurchaseController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    // --- Profile Routes ---
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // --- Master Data (Kategori & Alat) ---
    // Di gambar kamu errornya ada di baris ini (ada tulisan 'Rout'), ini sudah saya hapus.
    Route::resource('categories', CategoryController::class);
    Route::get('/categories/{category}/next-code', [CategoryController::class, 'nextCode'])->name('categories.nextCode');
    Route::resource('tools', ToolController::class);

    // --- Peminjam & Peminjaman ---
    Route::resource('borrowers', BorrowerController::class);
    Route::resource('borrowings', BorrowingController::class);

    // --- Route Custom (Return & AJAX) ---
    // Saya rapikan pemanggilannya biar tidak panjang dan tidak error
    Route::put('/borrowings/{id}/return', [BorrowingController::class, 'returnItem'])->name('borrowings.return');
    Route::get('/get-tools/{categoryId}', [BorrowingController::class, 'getToolsByCategory'])->name('tools.getByCategory');

    // Route untuk Pembelian
    Route::resource('vendors', VendorController::class);
    Route::resource('purchases', PurchaseController::class);
    Route::get('/purchases/report', [PurchaseController::class, 'report'])->name('purchases.report');
    Route::post('/purchases/{purchase}/approve', [PurchaseController::class, 'approve'])->name('purchases.approve');
    Route::post('/purchases/{purchase}/reject', [PurchaseController::class, 'reject'])->name('purchases.reject');

    // --- Maintenance Routes ---
    Route::resource('maintenances', App\Http\Controllers\MaintenanceController::class);
    Route::resource('maintenance-types', App\Http\Controllers\MaintenanceTypeController::class);
});

require __DIR__.'/auth.php';