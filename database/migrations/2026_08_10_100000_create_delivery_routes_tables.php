<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_routes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('driver_id')->constrained('driver_profiles')->restrictOnDelete();
            $table->foreignId('vehicle_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('status')->default('planned');
            $table->timestamp('scheduled_start_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('returning_at')->nullable();
            $table->timestamp('arrived_warehouse_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'status']);
            $table->index(['driver_id', 'status']);
            $table->index(['vehicle_id', 'status']);
        });

        Schema::create('delivery_route_ticket', function (Blueprint $table): void {
            $table->foreignId('delivery_route_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('sequence');
            $table->timestamps();
            $table->primary(['delivery_route_id', 'ticket_id']);
            $table->unique(['delivery_route_id', 'sequence']);
        });

        Schema::create('delivery_route_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('delivery_route_id')->constrained()->cascadeOnDelete();
            $table->foreignId('performed_by')->constrained('users')->restrictOnDelete();
            $table->string('type');
            $table->string('from_status');
            $table->string('to_status');
            $table->text('notes')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('accuracy', 10, 2)->nullable();
            $table->string('location_source', 30)->nullable();
            $table->timestamp('recorded_at');
            $table->timestamp('received_at');
            $table->string('local_event_id', 120)->nullable();
            $table->timestamps();
            $table->unique(['delivery_route_id', 'local_event_id']);
        });

        Schema::create('delivery_route_novelties', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('delivery_route_id')->constrained()->cascadeOnDelete();
            $table->foreignId('novelty_reason_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reported_by')->constrained('users')->restrictOnDelete();
            $table->text('description');
            $table->string('status')->default('open');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('accuracy', 10, 2)->nullable();
            $table->string('location_source', 30)->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_route_novelties');
        Schema::dropIfExists('delivery_route_events');
        Schema::dropIfExists('delivery_route_ticket');
        Schema::dropIfExists('delivery_routes');
    }
};
