<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('warehouses', 'company_id')) {
            return;
        }

        Schema::table('warehouses', function (Blueprint $table) {
            $table->foreignId('company_id')->after('id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
            $table->string('code')->after('branch_id');
            $table->string('name')->after('code');
            $table->string('type')->after('name');
            $table->boolean('is_general')->default(false)->after('type');
            $table->text('address')->nullable()->after('is_general');
            $table->boolean('is_active')->default(true)->after('address');

            $table->unique(['company_id', 'code']);
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('warehouses', 'company_id')) {
            return;
        }

        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'code']);
            $table->dropConstrainedForeignId('branch_id');
            $table->dropConstrainedForeignId('company_id');
            $table->dropColumn([
                'code',
                'name',
                'type',
                'is_general',
                'address',
                'is_active',
            ]);
        });
    }
};
