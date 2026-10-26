<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->nullable()->constrained('assets')->nullOnDelete();
            $table->foreignId('asset_acquisition_id')->nullable()->constrained('asset_acquisitions')->nullOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type', 40)->index();
            $table->json('details')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['asset_acquisition_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_events');
    }
};