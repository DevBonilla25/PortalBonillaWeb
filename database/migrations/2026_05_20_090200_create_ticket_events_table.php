<?php

use App\Enums\TicketEventType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('driver_profiles')->nullOnDelete();
            $table->string('event_type')->default(TicketEventType::InternalNote->value);
            $table->string('previous_status')->nullable();
            $table->string('new_status')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('accuracy', 10, 2)->nullable();
            $table->timestamp('occurred_at');
            $table->timestamp('received_at')->nullable();
            $table->string('source')->default('web');
            $table->string('connection_status')->nullable();
            $table->string('local_event_id')->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['ticket_id', 'occurred_at']);
            $table->index(['event_type', 'occurred_at']);
            $table->index(['driver_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_events');
    }
};
