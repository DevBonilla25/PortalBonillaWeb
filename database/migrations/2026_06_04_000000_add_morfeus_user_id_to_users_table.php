<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'morfeus_user_id')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('morfeus_user_id')->nullable()->after('employee_id')->index();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'morfeus_user_id')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['morfeus_user_id']);
            $table->dropColumn('morfeus_user_id');
        });
    }
};
