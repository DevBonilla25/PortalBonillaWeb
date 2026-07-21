<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->dropIndex(['status', 'rescheduled_for']);
            $table->dropColumn('rescheduled_for');
            $table->unsignedInteger('rescheduled_count')->default(0)->after('closed_at');
            $table->timestamp('last_rescheduled_at')->nullable()->after('rescheduled_count');
        });

        DB::table('tickets')->where('status', 'rescheduled')->update([
            'status' => 'sent_to_warehouse',
            'rescheduled_count' => 1,
            'last_rescheduled_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->dropColumn(['rescheduled_count', 'last_rescheduled_at']);
            $table->timestamp('rescheduled_for')->nullable()->after('closed_at');
            $table->index(['status', 'rescheduled_for']);
        });
    }
};
