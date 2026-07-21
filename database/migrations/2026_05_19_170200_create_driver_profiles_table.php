<?php

use App\Enums\DriverStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('default_vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->string('license_number')->nullable();
            $table->string('license_type')->nullable();
            $table->date('license_expires_at')->nullable();
            $table->string('status')->default(DriverStatus::Available->value);
            $table->decimal('last_latitude', 10, 7)->nullable();
            $table->decimal('last_longitude', 10, 7)->nullable();
            $table->timestamp('last_location_at')->nullable();
            $table->timestamp('last_connection_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('observations')->nullable();
            $table->timestamps();

            $table->index(['status', 'is_active']);
            $table->index('last_connection_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_profiles');
    }
};
