<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_external_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->string('external_system', 50)->default('morfeus');
            $table->unsignedInteger('external_warehouse_id');
            $table->string('external_name')->nullable();
            $table->string('external_type', 50)->default('physical');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['external_system', 'external_warehouse_id', 'external_type'], 'warehouse_external_unique');
            $table->index(['warehouse_id', 'external_system']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_external_mappings');
    }
};
