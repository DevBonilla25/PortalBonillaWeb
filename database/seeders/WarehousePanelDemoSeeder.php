<?php

namespace Database\Seeders;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Ticket;
use App\Models\TicketItem;
use App\Models\Vehicle;
use App\Models\Warehouse;
use App\Models\Zone;
use Illuminate\Database\Seeder;

class WarehousePanelDemoSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::query()->where('ruc', '1791234567001')->firstOrFail();
        $warehouse = Warehouse::query()
            ->where('company_id', $company->id)
            ->where('code', 'BOD-GEN')
            ->firstOrFail();
        $branch = Branch::query()
            ->where('company_id', $company->id)
            ->where('code', 'MATRIZ')
            ->firstOrFail();

        $zones = [
            'VALLE' => Zone::query()->updateOrCreate(
                ['company_id' => $company->id, 'code' => 'VALLE'],
                ['name' => 'Valle', 'is_active' => true],
            ),
            'NORTE' => Zone::query()->where('company_id', $company->id)->where('code', 'NORTE')->firstOrFail(),
        ];

        $vehicle = Vehicle::query()
            ->where('company_id', $company->id)
            ->where('code', 'VEH-001')
            ->first();

        $demos = [
            [
                'ticket_code' => 'TCK-000125',
                'customer_name' => 'Ferretería San Luis',
                'status' => TicketStatus::Picking,
                'priority' => TicketPriority::Normal,
                'zone' => $zones['VALLE'],
                'items' => [
                    ['product_name' => 'Galones de pintura blanca', 'quantity' => 10],
                    ['product_name' => 'Rodillos', 'quantity' => 5],
                    ['product_name' => 'Brochas 4 pulgadas', 'quantity' => 12],
                ],
            ],
            [
                'ticket_code' => 'TCK-000126',
                'customer_name' => 'Maestro Juan Paredes',
                'status' => TicketStatus::Loading,
                'priority' => TicketPriority::High,
                'zone' => $zones['NORTE'],
                'items' => [
                    ['product_name' => 'Tubos PVC', 'quantity' => 15],
                    ['product_name' => 'Codos PVC', 'quantity' => 6],
                    ['product_name' => 'Pegamento PVC', 'quantity' => 4],
                ],
            ],
            [
                'ticket_code' => 'TCK-000124',
                'customer_name' => 'Constructora El Progreso',
                'status' => TicketStatus::Loading,
                'priority' => TicketPriority::High,
                'zone' => $zones['NORTE'],
                'items' => [
                    ['product_name' => 'Sacos de cemento', 'quantity' => 20],
                    ['product_name' => 'Varillas de hierro', 'quantity' => 12],
                    ['product_name' => 'Alambre de amarre', 'quantity' => 8],
                ],
            ],
            [
                'ticket_code' => 'TCK-000127',
                'customer_name' => 'Distribuidora Centro',
                'status' => TicketStatus::SentToWarehouse,
                'priority' => TicketPriority::Normal,
                'zone' => $zones['NORTE'],
                'items' => [
                    ['product_name' => 'Clavos 2 pulgadas', 'quantity' => 30],
                    ['product_name' => 'Martillos', 'quantity' => 6],
                ],
            ],
            [
                'ticket_code' => 'TCK-000123',
                'customer_name' => 'Ferretería La Esquina',
                'status' => TicketStatus::Dispatched,
                'priority' => TicketPriority::High,
                'zone' => $zones['VALLE'],
                'items' => [
                    ['product_name' => 'Bloques de hormigón', 'quantity' => 50],
                    ['product_name' => 'Arena fina', 'quantity' => 10],
                    ['product_name' => 'Grava', 'quantity' => 10],
                ],
            ],
        ];

        foreach ($demos as $demo) {
            $ticket = Ticket::query()->updateOrCreate(
                [
                    'company_id' => $company->id,
                    'ticket_code' => $demo['ticket_code'],
                ],
                [
                    'branch_id' => $branch->id,
                    'warehouse_id' => $warehouse->id,
                    'zone_id' => $demo['zone']->id,
                    'customer_name' => $demo['customer_name'],
                    'customer_phone' => '0999999999',
                    'delivery_address' => 'Av. Principal y calle secundaria',
                    'priority' => $demo['priority'],
                    'status' => $demo['status'],
                    'current_vehicle_id' => $vehicle?->id,
                    'assigned_at' => now()->subHours(2),
                ],
            );

            $ticket->items()->delete();

            foreach ($demo['items'] as $item) {
                TicketItem::query()->create([
                    'ticket_id' => $ticket->id,
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'unit' => 'und',
                ]);
            }
        }
    }
}
