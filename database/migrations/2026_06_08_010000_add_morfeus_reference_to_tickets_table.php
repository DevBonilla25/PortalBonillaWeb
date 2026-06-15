<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('external_source')->nullable()->after('source_image_path');
            $table->string('external_source_type')->nullable()->after('external_source');
            $table->unsignedInteger('external_invoice_id')->nullable()->after('external_source_type');
            $table->string('external_document_number')->nullable()->after('external_invoice_id');
            $table->unsignedInteger('external_warehouse_id')->nullable()->after('external_document_number');
            $table->unsignedInteger('external_cashier_id')->nullable()->after('external_warehouse_id');
            $table->json('external_snapshot')->nullable()->after('external_cashier_id');

            $table->index(['external_source', 'external_invoice_id', 'external_warehouse_id'], 'tickets_external_invoice_idx');
            $table->unique(['company_id', 'external_source', 'external_invoice_id', 'external_warehouse_id'], 'tickets_company_external_invoice_unique');
        });

        Schema::table('ticket_items', function (Blueprint $table) {
            $table->unsignedInteger('external_line')->nullable()->after('product_code');
            $table->unsignedInteger('external_item_id')->nullable()->after('external_line');
            $table->unsignedInteger('external_unit_id')->nullable()->after('external_item_id');
            $table->json('external_snapshot')->nullable()->after('external_unit_id');

            $table->index(['external_item_id', 'external_line'], 'ticket_items_external_item_idx');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_items', function (Blueprint $table) {
            $table->dropIndex('ticket_items_external_item_idx');
            $table->dropColumn([
                'external_line',
                'external_item_id',
                'external_unit_id',
                'external_snapshot',
            ]);
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropUnique('tickets_company_external_invoice_unique');
            $table->dropIndex('tickets_external_invoice_idx');
            $table->dropColumn([
                'external_source',
                'external_source_type',
                'external_invoice_id',
                'external_document_number',
                'external_warehouse_id',
                'external_cashier_id',
                'external_snapshot',
            ]);
        });
    }
};
