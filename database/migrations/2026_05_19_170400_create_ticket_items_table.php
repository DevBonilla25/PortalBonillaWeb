<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->string('product_code')->nullable();
            $table->string('product_name');
            $table->decimal('quantity', 12, 2)->default(1);
            $table->string('unit', 50)->nullable();
            $table->text('observations')->nullable();
            $table->timestamps();

            $table->index('product_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_items');
    }
};
