<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_evidence_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_evidence_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_item_id')->constrained()->cascadeOnDelete();
            $table->decimal('delivered_quantity', 12, 2);
            $table->timestamps();
            $table->unique(['delivery_evidence_id', 'ticket_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_evidence_items');
    }
};
