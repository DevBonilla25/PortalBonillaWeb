<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logistic_operations', function (Blueprint $table): void {
            $table->timestamp('scheduled_arrival_at')->nullable()->after('scheduled_start_at');
        });
    }

    public function down(): void
    {
        Schema::table('logistic_operations', function (Blueprint $table): void {
            $table->dropColumn('scheduled_arrival_at');
        });
    }
};
