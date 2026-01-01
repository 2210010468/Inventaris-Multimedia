<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            
            // Kolom Identitas
            $table->string('purchase_code'); // <--- INI YANG DICARI DATABASE
            $table->date('date');            // <--- INI JUGA
            
            // Kolom Foreign Key
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // PENTING: Foreign Key ke Kategori (Pastikan tabel 'tool_categories' sudah ada)
            // Kalau tabel kategori dibuat SETELAH ini, hapus 'constrained' jadi 'unsignedBigInteger'
            $table->unsignedBigInteger('category_id')->nullable(); 
            
            // Kolom Detail Barang (Single Table)
            $table->string('tool_name');
            $table->text('specification')->nullable();
            $table->integer('quantity')->default(1);
            $table->string('brand')->nu(1);
            $table->decimal('unit_price', 15, 2)->default(0); // Harga Rencana (Budget)
            $table->decimal('actual_unit_price', 15, 2)->nullable(); // Harga Asli (Realisasi)
            $table->decimal('subtotal', 15, 2)->default(0);
            
            // Kolom Status
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->boolean('is_purchased')->default(false); // true kalau sudah dibeli admin
            
            // Kolom Bukti & Catatan
            $table->string('transaction_proof_photo')->nullable();
            $table->text('rejection_note')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('purchases');
    }
};