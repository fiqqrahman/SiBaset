<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name');
            $table->string('scheme', 12)->default('KIB')->index();
            $table->timestamps();
        });

        Schema::create('asset_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_category_id')->constrained('asset_categories')->restrictOnDelete();
            $table->string('name');
            $table->string('classification_code', 100)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['asset_category_id', 'name']);
            $table->unique(['asset_category_id', 'classification_code']);
        });

        Schema::create('asset_acquisitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('funding_source', 32)->index();
            $table->string('acquisition_method', 32);
            $table->string('owner_entity', 40)->default('belum_ditentukan');
            $table->string('provider_name')->nullable();
            $table->string('document_reference')->nullable();
            $table->date('acquired_at')->nullable();
            $table->decimal('total_value', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->string('ownership_status', 32)->default('pending_verification')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();
            $table->index(['school_id', 'ownership_status']);
        });

        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $table->foreignId('asset_acquisition_id')->constrained('asset_acquisitions')->restrictOnDelete();
            $table->foreignId('asset_type_id')->constrained('asset_types')->restrictOnDelete();
            $table->foreignId('submitted_by')->constrained('users')->restrictOnDelete();
            $table->string('asset_code', 80);
            $table->string('name');
            $table->string('brand_model')->nullable();
            $table->string('serial_number', 160)->nullable();
            $table->string('location')->nullable();
            $table->string('condition', 24)->default('tidak_diketahui');
            $table->unsignedSmallInteger('year_acquired')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 32)->default('pending_verification')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();
            $table->unique(['school_id', 'asset_code']);
            $table->unique(['school_id', 'serial_number']);
            $table->index(['school_id', 'status']);
        });

        Schema::create('asset_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_acquisition_id')->constrained('asset_acquisitions')->cascadeOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained('assets')->nullOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('document_kind', 40);
            $table->string('original_name');
            $table->string('storage_path');
            $table->string('mime_type', 160)->nullable();
            $table->unsignedBigInteger('size_bytes');
            $table->timestamps();
            $table->index(['asset_acquisition_id', 'document_kind']);
        });

        Schema::create('asset_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->foreignId('submitted_by')->constrained('users')->restrictOnDelete();
            $table->json('proposed_changes');
            $table->text('request_note')->nullable();
            $table->string('status', 24)->default('pending')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();
            $table->index(['asset_id', 'status']);
        });

        Schema::create('asset_acquisition_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_acquisition_id')->constrained('asset_acquisitions')->cascadeOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('decision', 32);
            $table->string('owner_entity_before', 40)->nullable();
            $table->string('owner_entity_after', 40)->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();
        });

        $now = now();
        DB::table('asset_categories')->insert([
            ['code' => 'A', 'name' => 'Tanah', 'scheme' => 'KIB', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'B', 'name' => 'Peralatan dan Mesin', 'scheme' => 'KIB', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'C', 'name' => 'Gedung dan Bangunan', 'scheme' => 'KIB', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'D', 'name' => 'Jalan, Irigasi dan Jaringan', 'scheme' => 'KIB', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'E', 'name' => 'Aset Tetap Lainnya', 'scheme' => 'KIB', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'F', 'name' => 'Konstruksi dalam Pengerjaan', 'scheme' => 'KIB', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'KIBAR-KEMITRAAN', 'name' => 'Kemitraan dengan Pihak Ketiga', 'scheme' => 'KIBAR', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'KIBAR-ASET-TAK-BERWUJUD', 'name' => 'Aset Tak Berwujud', 'scheme' => 'KIBAR', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'KIBAR-ASET-LAINNYA', 'name' => 'Aset Lain-lain', 'scheme' => 'KIBAR', 'created_at' => $now, 'updated_at' => $now],
        ]);

        $categoryIds = DB::table('asset_categories')->pluck('id', 'code');
        $seedTypes = [
            ['A', 'Tanah', 'Tanah yang dicatat sebagai aset tetap.'],
            ['B', 'Komputer', 'Komputer desktop dan unit kerja.'],
            ['B', 'Laptop', 'Laptop atau komputer portabel.'],
            ['B', 'Interactive Flat Panel (IFP)', 'Panel interaktif pembelajaran.'],
            ['B', 'Printer', 'Printer dan perangkat cetak.'],
            ['B', 'Proyektor', 'Perangkat proyeksi visual.'],
            ['B', 'Meja', 'Meja sekolah dan perkantoran.'],
            ['B', 'Kursi', 'Kursi sekolah dan perkantoran.'],
            ['B', 'Peralatan Laboratorium', 'Peralatan laboratorium; klasifikasi detail perlu disesuaikan dengan kode barang resmi.'],
            ['B', 'Perangkat Jaringan', 'Router, switch, access point, dan perangkat jaringan.'],
            ['C', 'Gedung Sekolah', 'Gedung yang dicatat sebagai aset tetap.'],
            ['C', 'Bangunan Penunjang', 'Bangunan penunjang sekolah.'],
            ['D', 'Jaringan Internet Permanen', 'Infrastruktur jaringan yang memenuhi kriteria aset tetap; biaya langganan bukan aset tetap.'],
            ['D', 'Jaringan Utilitas', 'Jaringan utilitas yang memenuhi klasifikasi ini.'],
            ['E', 'Buku Perpustakaan', 'Koleksi buku yang memenuhi kriteria aset tetap.'],
            ['E', 'Alat Musik', 'Alat musik yang memenuhi kriteria aset tetap.'],
            ['KIBAR-ASET-TAK-BERWUJUD', 'Lisensi Perangkat Lunak', 'Lisensi atau hak perangkat lunak yang memenuhi kriteria aset tak berwujud.'],
            ['KIBAR-ASET-LAINNYA', 'Aset Lain-lain', 'Aset yang diklasifikasikan ke kelompok aset lain-lain sesuai ketentuan.'],
            ['KIBAR-KEMITRAAN', 'Aset Kemitraan', 'Aset atau kerja sama yang masuk kelompok kemitraan sesuai ketentuan.'],
        ];

        foreach ($seedTypes as [$categoryCode, $name, $description]) {
            DB::table('asset_types')->insert([
                'asset_category_id' => $categoryIds[$categoryCode],
                'name' => $name,
                'description' => $description,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_acquisition_reviews');
        Schema::dropIfExists('asset_change_requests');
        Schema::dropIfExists('asset_documents');
        Schema::dropIfExists('assets');
        Schema::dropIfExists('asset_acquisitions');
        Schema::dropIfExists('asset_types');
        Schema::dropIfExists('asset_categories');
    }
};