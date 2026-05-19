<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('branches', 'company_id')) {
            return;
        }

        Schema::table('branches', function (Blueprint $table) {
            $table->foreignId('company_id')->after('id')->constrained()->cascadeOnDelete();
            $table->string('code')->after('company_id');
            $table->string('name')->after('code');
            $table->text('address')->nullable()->after('name');
            $table->string('city')->nullable()->after('address');
            $table->string('phone')->nullable()->after('city');
            $table->boolean('is_main')->default(false)->after('phone');
            $table->boolean('is_active')->default(true)->after('is_main');

            $table->unique(['company_id', 'code']);
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('branches', 'company_id')) {
            return;
        }

        Schema::table('branches', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'code']);
            $table->dropConstrainedForeignId('company_id');
            $table->dropColumn([
                'code',
                'name',
                'address',
                'city',
                'phone',
                'is_main',
                'is_active',
            ]);
        });
    }
};
