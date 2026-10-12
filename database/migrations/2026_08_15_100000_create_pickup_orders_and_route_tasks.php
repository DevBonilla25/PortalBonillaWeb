<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pickup_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('driver_id')->constrained('driver_profiles')->restrictOnDelete();
            $table->foreignId('vehicle_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('code');
            $table->string('status')->default('assigned');
            $table->string('priority')->default('normal');
            $table->string('pickup_name');
            $table->string('pickup_address');
            $table->string('pickup_reference')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_phone', 50)->nullable();
            $table->string('google_maps_url')->nullable();
            $table->text('item_description');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('en_route_at')->nullable();
            $table->timestamp('arrived_at')->nullable();
            $table->timestamp('loading_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('transporting_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'code']);
            $table->index(['driver_id', 'status']);
            $table->index(['vehicle_id', 'status']);
        });

        Schema::create('pickup_order_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pickup_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('performed_by')->constrained('users')->restrictOnDelete();
            $table->string('action');
            $table->string('from_status');
            $table->string('to_status');
            $table->text('notes')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('accuracy', 10, 2)->nullable();
            $table->string('location_source', 30)->nullable();
            $table->timestamp('occurred_at');
            $table->timestamp('received_at');
            $table->string('local_event_id', 120)->nullable();
            $table->timestamps();
            $table->unique(['pickup_order_id', 'local_event_id']);
        });

        Schema::create('delivery_route_tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('delivery_route_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sequence');
            $table->string('task_type');
            $table->foreignId('ticket_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('pickup_order_id')->nullable()->constrained()->restrictOnDelete();
            $table->timestamps();
            $table->unique(['delivery_route_id', 'sequence']);
            $table->unique(['delivery_route_id', 'ticket_id']);
            $table->unique(['delivery_route_id', 'pickup_order_id']);
        });

        DB::table('delivery_route_ticket')->orderBy('delivery_route_id')->orderBy('sequence')->each(function ($row): void {
            DB::table('delivery_route_tasks')->insert([
                'delivery_route_id' => $row->delivery_route_id,
                'sequence' => $row->sequence,
                'task_type' => 'delivery',
                'ticket_id' => $row->ticket_id,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        });

        Schema::table('driver_notifications', function (Blueprint $table): void {
            $table->foreignId('pickup_order_id')->nullable()->after('ticket_id')->constrained()->nullOnDelete();
            $table->index(['pickup_order_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::table('driver_notifications', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('pickup_order_id');
        });
        Schema::dropIfExists('delivery_route_tasks');
        Schema::dropIfExists('pickup_order_events');
        Schema::dropIfExists('pickup_orders');
    }
};
