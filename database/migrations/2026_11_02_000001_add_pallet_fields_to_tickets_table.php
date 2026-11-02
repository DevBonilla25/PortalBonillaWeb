<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->boolean('uses_pallets')->nullable()->after('fulfillment_status');
            $table->unsignedSmallInteger('pallet_count')->nullable()->after('uses_pallets');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->dropColumn(['uses_pallets', 'pallet_count']);
        });
    }
};
