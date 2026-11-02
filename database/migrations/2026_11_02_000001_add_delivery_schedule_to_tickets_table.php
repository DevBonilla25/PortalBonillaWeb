<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->date('scheduled_delivery_date')->nullable()->after('delivery_type')->index();
            $table->text('delivery_schedule_reason')->nullable()->after('scheduled_delivery_date');
            $table->timestamp('delivery_date_changed_at')->nullable()->after('delivery_schedule_reason');
            $table->foreignId('delivery_date_changed_by')->nullable()->after('delivery_date_changed_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('delivery_date_changed_by');
            $table->dropColumn(['scheduled_delivery_date', 'delivery_schedule_reason', 'delivery_date_changed_at']);
        });
    }
};
