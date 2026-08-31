<?php

use App\Enums\LogisticOperationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logistic_operations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('driver_profiles')->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('last_transition_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type')->default('cement_supply');
            $table->string('status')->default(LogisticOperationStatus::Planned->value);
            $table->string('origin');
            $table->string('destination');
            $table->string('plant_name')->nullable();
            $table->timestamp('scheduled_start_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('arrived_plant_at')->nullable();
            $table->timestamp('queue_started_at')->nullable();
            $table->timestamp('plant_entry_at')->nullable();
            $table->timestamp('loading_started_at')->nullable();
            $table->timestamp('loading_finished_at')->nullable();
            $table->timestamp('left_plant_at')->nullable();
            $table->timestamp('arrived_origin_at')->nullable();
            $table->timestamp('unloading_started_at')->nullable();
            $table->timestamp('unloading_finished_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'status']);
            $table->index(['driver_id', 'status']);
        });

        Schema::create('operation_incidents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('logistic_operation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reported_by')->constrained('users')->restrictOnDelete();
            $table->string('type');
            $table->text('description');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();
        });

        Schema::create('logistic_operation_transitions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('logistic_operation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('performed_by')->constrained('users')->restrictOnDelete();
            $table->string('action');
            $table->string('from_status');
            $table->string('to_status');
            $table->text('notes')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logistic_operation_transitions');
        Schema::dropIfExists('operation_incidents');
        Schema::dropIfExists('logistic_operations');
    }
};
