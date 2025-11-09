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
        Schema::create('sequence_counters', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // 'pengaduan', 'surat_tugas', 'ppid'
            $table->integer('year');
            $table->integer('month'); // 0 untuk reset tahunan, 1-12 untuk bulanan
            $table->unsignedBigInteger('count')->default(0);

            // Kunci utama agar setiap counter unik
            $table->unique(['name', 'year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sequence_counters');
    }
};
