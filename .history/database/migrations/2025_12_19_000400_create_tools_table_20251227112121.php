<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tools', function (Blueprint $table) {
            $table->id();
            $table->string('tool_code')->unique()->nullable(); 
            $table->string('tool_name');
            
            // --- BAGIAN INI SAYA UBAH BIAR TIDAK ERROR ---
            // Saya pakai unsignedBigInteger biasa, tanpa 'constrained'
            // Jadi dia tidak akan ngecek tabel lain dulu.
            $table->unsignedBigInteger('category_id')->nullable(); 
            
            $table->string('current_condition')->default('Baik');
            $table->enum('availability_status', ['available', 'borrowed', 'maintenance', 'disposed'])->default('available');
            
            // --- INI JUGA DIUBAH ---
            $table->unsignedBigInteger('purchase_item_id')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tools');
    }
};