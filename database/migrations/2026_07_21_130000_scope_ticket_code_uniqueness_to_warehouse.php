<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'ticket_code']);
            $table->unique(
                ['company_id', 'ticket_code', 'warehouse_id'],
                'tickets_company_code_warehouse_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropUnique('tickets_company_code_warehouse_unique');
            $table->unique(['company_id', 'ticket_code']);
        });
    }
};
