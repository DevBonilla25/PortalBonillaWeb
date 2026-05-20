<?php

use App\Enums\TicketStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('tickets')
            ->where('status', TicketStatus::AssignedToWarehouse->value)
            ->update(['status' => TicketStatus::Picking->value]);

        DB::table('tickets')
            ->where('status', TicketStatus::Loaded->value)
            ->update(['status' => TicketStatus::Loading->value]);
    }

    public function down(): void
    {
        // No reversible: los estados intermedios ya no forman parte del flujo de bodega.
    }
};
