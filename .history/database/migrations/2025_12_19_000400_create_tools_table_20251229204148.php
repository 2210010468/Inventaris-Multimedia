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
            
            // --- KOLOM BARU DITAMBAHKAN DI SINI ---
            $table->string('brand')->nullable();        // Merk / Tipe
            $table->date('purchase_date')->nullable();  // Tanggal Perolehan
            // --------------------------------------

            // Bagian Category (UnsignedBigInteger biasa)
            $table->unsignedBigInteger('category_id')->nullable(); 
            
            $table->string('current_condition')->default('Baik');
            $table->enum('availability_status', ['available', 'borrowed', 'maintenance', 'disposed'])->default('available');
            
            
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