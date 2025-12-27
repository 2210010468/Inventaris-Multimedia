<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();

            // --- 1. Relasi (Foreign Keys) ---
            $table->foreignId('user_id')->constrained('users'); // Yang mengajukan
            $table->foreignId('vendor_id')->constrained('vendors'); // Vendor tujuan
            // Pastikan tabel 'tool_categories' sudah ada sebelumnya
            $table->foreignId('category_id')->constrained('tool_categories'); 

            // --- 2. Detail Barang ---
            $table->string('tool_name');
            $table->text('specification')->nullable(); // Pakai text biar muat panjang
            $table->integer('quantity');
            $table->decimal('unit_price', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->date('date'); // Tanggal pengajuan

            // --- 3. Status & Logic Approval ---
            // Enum untuk membatasi pilihan status
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('rejection_note')->nullable(); // Tambahan: Alasan jika ditolak

            // --- 4. Eksekusi Pembelian (Logic Baru) ---
            // Default false, akan jadi true saat admin upload bukti
            $table->boolean('is_purchased')->default(false); 
            // Nullable, karena saat request dibuat, foto belum ada
            $table->string('transaction_proof_photo')->nullable(); 

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};