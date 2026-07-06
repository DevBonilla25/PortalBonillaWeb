<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_novelties', function (Blueprint $table) {
            $table->foreignId('novelty_reason_id')
                ->nullable()
                ->after('driver_id')
                ->constrained('novelty_reasons')
                ->nullOnDelete();
            $table->string('photo_path')->nullable()->after('description');

            $table->index(['novelty_reason_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('ticket_novelties', function (Blueprint $table) {
            $table->dropIndex(['novelty_reason_id', 'created_at']);
            $table->dropConstrainedForeignId('novelty_reason_id');
            $table->dropColumn('photo_path');
        });
    }
};
