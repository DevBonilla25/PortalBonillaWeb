<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->timestamp('arrived_destination_at')->nullable()->after('dispatched_at');
            $table->timestamp('unloading_at')->nullable()->after('arrived_destination_at');
            $table->timestamp('warehouse_received_at')->nullable()->after('returned_at');
        });

        Schema::create('delivery_attempts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_assignment_id')->nullable()->constrained('ticket_assignments')->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('driver_profiles')->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->foreignId('failure_novelty_id')->nullable()->constrained('ticket_novelties')->nullOnDelete();
            $table->unsignedInteger('attempt_number');
            $table->string('status')->default('in_progress');
            $table->json('return_items')->nullable();
            $table->boolean('goods_remain_on_vehicle')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('warehouse_received_at')->nullable();
            $table->foreignId('warehouse_received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('warehouse_observation')->nullable();
            $table->timestamps();

            $table->unique(['ticket_id', 'attempt_number']);
            $table->index(['ticket_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_attempts');

        Schema::table('tickets', function (Blueprint $table): void {
            $table->dropColumn(['arrived_destination_at', 'unloading_at', 'warehouse_received_at']);
        });
    }
};
