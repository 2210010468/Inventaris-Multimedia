<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenances', function (Blueprint $table) {
            $table->id();
            
            // Relasi ke Alat & User (Pelapor)
            $table->foreignId('tool_id')->constrained('tools')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // Pastikan tabel 'maintenance_types' migration-nya dijalankan SEBELUM file ini
            $table->foreignId('maintenance_type_id')->constrained('maintenance_types');
            
            $table->date('start_date'); // Tanggal mulai perbaikan
            $table->date('end_date')->nullable(); // Tanggal selesai
            
            $table->text('note'); // Deskripsi kerusakan
            
            $table->string('vendor_name')->nullable(); // Nama Vendor (Diisi nanti)
            $table->text('action_taken')->nullable();  // Tindakan (Diisi nanti)
            
            $table->string('status')->default('in_progress'); // in_progress, completed
            
            // Opsional: Biaya perbaikan
            $table->decimal('cost', 15, 2)->default(0); 

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenances');
    }
};