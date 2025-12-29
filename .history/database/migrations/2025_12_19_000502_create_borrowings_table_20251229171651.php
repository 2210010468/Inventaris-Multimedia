<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('borrowings', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('borrower_id')->constrained('borrowers')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users'); // Admin yang melayani
            
            $table->date('borrow_date');
            $table->date('planned_return_date');
            
            // --- BAGIAN INI KITA LENGKAPI ---
            $table->date('actual_return_date')->nullable(); // Tanggal Realisasi
            $table->string('borrowing_status')->default('active'); // active, returned, overdue
            
            // Kolom baru untuk revisi:
            $table->string('return_condition')->nullable(); // Kondisi (Baik/Rusak/Lecet)
            $table->string('final_status')->nullable();     // Status Akhir (Selesai/Hilang/Diganti)
            
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('borrowings');
    }
};