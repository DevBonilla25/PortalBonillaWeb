<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('parent_ticket_id')->nullable()->after('id')->constrained('tickets')->nullOnDelete();
            $table->unsignedSmallInteger('partial_sequence')->nullable()->after('parent_ticket_id');
            $table->string('fulfillment_status')->default('complete')->after('status');
            $table->unique(['parent_ticket_id', 'partial_sequence']);
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropUnique(['parent_ticket_id', 'partial_sequence']);
            $table->dropConstrainedForeignId('parent_ticket_id');
            $table->dropColumn(['partial_sequence', 'fulfillment_status']);
        });
    }
};
