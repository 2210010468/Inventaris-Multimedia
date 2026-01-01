<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    // Update Tabel Purchases
    Schema::table('purchases', function (Blueprint $table) {
        // Menambah harga rencana, nullable jaga-jaga data lama belum ada budgetnya
        $table->decimal('budgeted_price', 15, 2)->after('vendor_id')->nullable(); 
    });

    // Update Tabel Tools
    Schema::table('tools', function (Blueprint $table) {
        // Menambah alasan dan tanggal pemusnahan
        $table->text('disposal_reason')->nullable()->after('status');
        $table->date('disposal_date')->nullable()->after('disposal_reason');
        
        // Pastikan enum 'status' kamu sudah support 'disposed'
        // Jika pakai string biasa, aman. Jika pakai Enum type DB, perlu alter table.
    });
}

public function down()
{
    Schema::table('purchases', function (Blueprint $table) {
        $table->dropColumn('budgeted_price');
    });

    Schema::table('tools', function (Blueprint $table) {
        $table->dropColumn(['disposal_reason', 'disposal_date']);
    });
}
};
