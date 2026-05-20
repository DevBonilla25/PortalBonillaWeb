<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_novelties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('driver_profiles')->nullOnDelete();
            $table->string('novelty_type');
            $table->text('description');
            $table->string('status')->default('open');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('accuracy', 10, 2)->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['ticket_id', 'created_at']);
            $table->index(['driver_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_novelties');
    }
};
