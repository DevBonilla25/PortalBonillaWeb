<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_items', function (Blueprint $table) {
            $table->decimal('loaded_quantity', 12, 2)->nullable()->after('quantity');
            $table->boolean('is_loaded')->default(false)->after('loaded_quantity');
            $table->foreignId('load_reviewed_by')->nullable()->after('is_loaded')->constrained('users')->nullOnDelete();
            $table->timestamp('load_reviewed_at')->nullable()->after('load_reviewed_by');
            $table->text('load_observation')->nullable()->after('load_reviewed_at');

            $table->index(['ticket_id', 'is_loaded']);
            $table->index('load_reviewed_by');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_items', function (Blueprint $table) {
            $table->dropIndex(['ticket_id', 'is_loaded']);
            $table->dropIndex(['load_reviewed_by']);
            $table->dropConstrainedForeignId('load_reviewed_by');
            $table->dropColumn([
                'loaded_quantity',
                'is_loaded',
                'load_reviewed_at',
                'load_observation',
            ]);
        });
    }
};
