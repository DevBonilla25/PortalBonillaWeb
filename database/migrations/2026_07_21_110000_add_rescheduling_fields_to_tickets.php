<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->timestamp('rescheduled_for')->nullable()->after('closed_at');
            $table->text('reschedule_reason')->nullable()->after('rescheduled_for');
            $table->text('cancelled_reason')->nullable()->after('reschedule_reason');
            $table->index(['status', 'rescheduled_for']);
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->dropIndex(['status', 'rescheduled_for']);
            $table->dropColumn(['rescheduled_for', 'reschedule_reason', 'cancelled_reason']);
        });
    }
};
