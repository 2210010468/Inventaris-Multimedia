<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tools', function (Blueprint $table) {
            $table->id();
            $table->string('tool_code')->unique()->nullable(); // Saya buat nullable jaga-jaga
            $table->string('tool_name');
            
            // --- PERBAIKAN DISINI ---
            // Kita pakai unsignedBigInteger dulu biar tidak error kalau tabel kategorinya belum ada
            $table->unsignedBigInteger('category_id')->nullable(); 
            
            $table->string('current_condition')->default('Baik'); // Kasih default biar aman
            $table->enum('availability_status', ['available', 'borrowed', 'maintenance', 'disposed'])->default('available');
            
            // --- PERBAIKAN DISINI ---
            // Sama, kita longgarkan dulu kuncinya
            $table->unsignedBigInteger('purchase_item_id')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tools');
    }
};