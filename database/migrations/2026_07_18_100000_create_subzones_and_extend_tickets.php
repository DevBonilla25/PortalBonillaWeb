<?php

use App\Enums\DeliveryType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subzones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('zone_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['zone_id', 'name']);
        });

        Schema::table('tickets', function (Blueprint $table): void {
            $table->string('delivery_type')->default(DeliveryType::Internal->value)->after('zone_id');
            $table->foreignId('subzone_id')->nullable()->after('delivery_type')->constrained()->nullOnDelete();
            $table->text('google_maps_url')->nullable()->after('delivery_reference');
            $table->index(['company_id', 'delivery_type']);
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->dropIndex(['company_id', 'delivery_type']);
            $table->dropConstrainedForeignId('subzone_id');
            $table->dropColumn(['delivery_type', 'google_maps_url']);
        });
        Schema::dropIfExists('subzones');
    }
};
