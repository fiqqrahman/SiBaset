<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('sekolah_id')->nullable()->index();
            $table->string('npsn', 8)->unique();
            $table->string('nama');
            $table->string('bentuk_pendidikan', 20);
            $table->string('status_sekolah', 20);
            $table->text('alamat_jalan')->nullable();
            $table->string('nama_dusun')->nullable();
            $table->string('kecamatan')->nullable()->index();
            $table->string('kabupaten')->nullable();
            $table->string('provinsi')->nullable();
            $table->string('nomor_telepon')->nullable();
            $table->string('akreditasi', 20)->nullable();
            $table->decimal('lintang', 10, 7)->nullable();
            $table->decimal('bujur', 10, 7)->nullable();
            $table->string('coordinate_status', 24)->default('missing')->index();
            $table->timestamp('coordinate_verified_at')->nullable();
            $table->foreignId('coordinate_verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schools');
    }
};
