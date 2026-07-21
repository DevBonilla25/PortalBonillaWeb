<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logistic_operation_stops', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('logistic_operation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('started_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('finished_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason');
            $table->text('notes')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            $table->index(['logistic_operation_id', 'finished_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logistic_operation_stops');
    }
};
