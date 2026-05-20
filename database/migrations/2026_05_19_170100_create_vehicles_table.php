<?php

use App\Enums\VehicleStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('plate');
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('type')->nullable();
            $table->decimal('capacity_kg', 10, 2)->nullable();
            $table->decimal('volume_m3', 10, 2)->nullable();
            $table->string('status')->default(VehicleStatus::Available->value);
            $table->boolean('is_active')->default(true);
            $table->text('observations')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'code']);
            $table->unique(['company_id', 'plate']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
