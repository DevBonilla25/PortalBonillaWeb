<?php

namespace App\Filament\Resources\Tickets\Tables;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Filament\Resources\Tickets\TicketResource;
use App\Models\DriverProfile;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class TicketsTable
{
    private const DISPLAY_TIMEZONE = 'America/Guayaquil';

    private const ADMIN_ROLES = ['super_admin', 'admin'];

    private const CASHIER_ROLES = ['cashier', 'vendedor'];

    private const WAREHOUSE_ROLES = ['warehouse_operator', 'jefe_bodega', 'auxiliar_bodega'];

    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query): Builder {
                $user = Auth::user();

                return $query
                    ->with([
                        'zone',
                        'cashier',
                        'currentDriver.user',
                        'latestAssignment.warehouseUser',
                    ])
                    ->when(
                        $user
                            && $user->hasAnyRole(self::CASHIER_ROLES)
                            && ! $user->hasAnyRole([...self::ADMIN_ROLES, ...self::WAREHOUSE_ROLES]),
                        fn (Builder $query): Builder => $query->where('cashier_id', $user->id),
                    );
            })
            ->columns([
                TextColumn::make('ticket_code')
                    ->label('N° Documento')
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record): string => TicketResource::getUrl('view', ['record' => $record]))
                    ->color(fn ($record): string => static::statusColor($record->status))
                    ->weight('semibold'),
                TextColumn::make('customer_name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('delivery_address')
                    ->label('Dirección')
                    ->limit(40)
                    ->tooltip(fn ($record): ?string => $record->delivery_address)
                    ->searchable(),
                TextColumn::make('zone.name')
                    ->label('Zona')
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('currentDriver.user.name')
                    ->label('Chofer')
                    ->placeholder('-'),
                TextColumn::make('status')
                    ->label('Estado')
                    ->formatStateUsing(fn (TicketStatus $state): string => $state->label())
                    ->color(fn (TicketStatus $state): string => static::statusColor($state))
                    ->badge()
                    ->sortable(),
                TextColumn::make('latestAssignment.warehouseUser.name')
                    ->label('Bodeguero')
                    ->placeholder('-'),
                TextColumn::make('cashier.name')
                    ->label('Cajero')
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('priority')
                    ->label('Prioridad')
                    ->formatStateUsing(fn (TicketPriority $state): string => match ($state) {
                        TicketPriority::Normal => 'Media',
                        default => $state->label(),
                    })
                    ->color(fn (TicketPriority $state): string => match ($state) {
                        TicketPriority::High, TicketPriority::Urgent => 'danger',
                        TicketPriority::Normal => 'warning',
                        TicketPriority::Low => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Últ. act.')
                    ->formatStateUsing(fn ($state): string => static::formatLastActivity($state))
                    ->dateTimeTooltip('d/m/Y H:i', timezone: self::DISPLAY_TIMEZONE)
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                SelectFilter::make('status_group')
                    ->label('Estado')
                    ->options(static::statusGroupOptions())
                    ->default('sent_to_warehouse')
                    ->selectablePlaceholder(false)
                    ->query(fn (Builder $query, array $data): Builder => static::applyStatusGroupFilter($query, $data['value'] ?? 'sent_to_warehouse')),
                SelectFilter::make('zone_id')
                    ->label('Zona')
                    ->relationship('zone', 'name')
                    ->placeholder('Todos')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('current_driver_id')
                    ->label('Chofer')
                    ->options(fn (): array => DriverProfile::query()
                        ->with('user')
                        ->where('is_active', true)
                        ->get()
                        ->mapWithKeys(fn (DriverProfile $driver): array => [
                            $driver->id => $driver->user?->name ?? "Chofer #{$driver->id}",
                        ])
                        ->all())
                    ->placeholder('Todos')
                    ->searchable(),
                SelectFilter::make('priority')
                    ->label('Prioridad')
                    ->options(TicketPriority::class)
                    ->placeholder('Todos'),
                Filter::make('date')
                    ->label('Fecha')
                    ->schema([
                        DatePicker::make('value')
                            ->label('Fecha')
                            ->displayFormat('d/m/Y'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'] ?? null,
                            fn (Builder $query, string $date): Builder => $query->whereDate('created_at', $date),
                        );
                    })
                    ->indicateUsing(function (array $data): array {
                        if (blank($data['value'] ?? null)) {
                            return [];
                        }

                        return [
                            Indicator::make('Fecha: '.Carbon::parse($data['value'])->format('d/m/Y')),
                        ];
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns([
                'default' => 1,
                'sm' => 2,
                'lg' => 3,
                'xl' => 5,
            ])
            ->deferFilters(false)
            ->description(function () use ($table): string {
                $livewire = $table->getLivewire();
                $total = method_exists($livewire, 'getFilteredTableQuery')
                    ? ($livewire->getFilteredTableQuery()?->count() ?? 0)
                    : 0;

                return $total === 1 ? "{$total} ticket" : "{$total} tickets";
            })
            ->recordActions([
                ViewAction::make()
                    ->label('Ver')
                    ->icon(Heroicon::OutlinedEye),
            ])
            ->striped();
    }

    public static function formatLastActivity(mixed $state): string
    {
        if (blank($state)) {
            return '-';
        }

        $date = Carbon::parse($state)->timezone(self::DISPLAY_TIMEZONE);

        if ($date->isToday()) {
            return $date->format('H:i');
        }

        if ($date->isYesterday()) {
            return 'Ayer '.$date->format('H:i');
        }

        return $date->format('d/m/Y H:i');
    }

    /**
     * @return array<string, string>
     */
    private static function statusGroupOptions(): array
    {
        return [
            'sent_to_warehouse' => 'Enviado a bodega',
            'in_route' => 'En ruta',
            'delivered' => 'Entregados',
            'all' => 'Todos',
        ];
    }

    private static function applyStatusGroupFilter(Builder $query, ?string $group): Builder
    {
        return match ($group) {
            'in_route' => $query->whereIn('status', static::statusValues([
                TicketStatus::Dispatched,
                TicketStatus::InRoute,
            ])),
            'delivered' => $query->whereIn('status', static::statusValues([
                TicketStatus::Delivered,
                TicketStatus::ArrivedBack,
            ])),
            'all' => $query,
            default => $query->whereIn('status', static::statusValues(static::pendingStatuses())),
        };
    }

    /**
     * @return list<TicketStatus>
     */
    private static function pendingStatuses(): array
    {
        return [
            TicketStatus::Created,
            TicketStatus::SentToWarehouse,
            TicketStatus::AssignedToWarehouse,
            TicketStatus::Picking,
            TicketStatus::Loading,
            TicketStatus::Loaded,
        ];
    }

    /**
     * @param  list<TicketStatus>  $statuses
     * @return list<string>
     */
    private static function statusValues(array $statuses): array
    {
        return array_map(fn (TicketStatus $status): string => $status->value, $statuses);
    }

    private static function statusColor(TicketStatus $status): string
    {
        return match ($status) {
            TicketStatus::Created,
            TicketStatus::SentToWarehouse,
            TicketStatus::AssignedToWarehouse,
            TicketStatus::Picking,
            TicketStatus::Loading,
            TicketStatus::Loaded => 'warning',

            TicketStatus::Dispatched,
            TicketStatus::InRoute,
            TicketStatus::Returning => 'info',

            TicketStatus::Delivered,
            TicketStatus::ArrivedBack => 'success',

            TicketStatus::DeliveryFailed,
            TicketStatus::Cancelled => 'danger',
        };
    }
}
