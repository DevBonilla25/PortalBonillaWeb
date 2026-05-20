<?php

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('zone_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cashier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('current_driver_id')->nullable()->constrained('driver_profiles')->nullOnDelete();
            $table->foreignId('current_vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->string('ticket_code');
            $table->string('guide_number')->nullable();
            $table->string('source_image_path')->nullable();
            $table->string('customer_name');
            $table->string('customer_phone')->nullable();
            $table->text('delivery_address');
            $table->text('delivery_reference')->nullable();
            $table->string('priority')->default(TicketPriority::Normal->value);
            $table->string('status')->default(TicketStatus::Created->value);
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->text('observations')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'ticket_code']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'priority']);
            $table->index(['warehouse_id', 'status']);
            $table->index(['current_driver_id', 'status']);
            $table->index(['zone_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
