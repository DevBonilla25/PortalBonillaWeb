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
use Illuminate\Support\HtmlString;

class TicketsTable
{
    private const DISPLAY_TIMEZONE = 'America/Guayaquil';

    private const ADMIN_ROLES = ['super_admin', 'admin'];

    private const CASHIER_ROLES = ['cashier'];

    private const WAREHOUSE_ROLES = ['warehouse_operator', 'warehouse_assistant'];

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
                TextColumn::make('status')
                    ->label('Estado')
                    ->formatStateUsing(fn (TicketStatus $state): string => $state->label())
                    ->color(fn (TicketStatus $state): string => static::statusColor($state))
                    ->badge()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Últ. act.')
                    ->formatStateUsing(fn ($state): string => static::formatLastActivity($state))
                    ->dateTimeTooltip('d/m/Y H:i', timezone: self::DISPLAY_TIMEZONE)
                    ->sortable(),
                TextColumn::make('cashier.name')
                    ->label('Cajero')
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('currentDriver.user.name')
                    ->label('Chofer')
                    ->placeholder('-'),
                TextColumn::make('delivery_address')
                    ->label('Dirección')
                    ->limit(40)
                    ->tooltip(fn ($record): ?string => $record->delivery_address)
                    ->searchable(),
                TextColumn::make('zone.name')
                    ->label('Zona')
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('latestAssignment.warehouseUser.name')
                    ->label('Bodeguero')
                    ->placeholder('-'),
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
            ->description(function () use ($table): HtmlString {
                $livewire = $table->getLivewire();
                $total = method_exists($livewire, 'getFilteredTableQuery')
                    ? ($livewire->getFilteredTableQuery()?->count() ?? 0)
                    : 0;

                return static::tableDescription($total);
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

    private static function tableDescription(int $total): HtmlString
    {
        $ticketCount = $total === 1 ? "{$total} ticket" : "{$total} tickets";

        $badges = collect([
            ['label' => 'Pendientes', 'color' => 'warning'],
            ['label' => 'En ruta', 'color' => 'info'],
            ['label' => 'Entregados', 'color' => 'success'],
            ['label' => 'Cancelado / Novedad', 'color' => 'danger'],
        ])
            ->map(fn (array $badge): string => static::legendBadge($badge['label'], $badge['color']))
            ->implode(' ');

        return new HtmlString(<<<HTML
            <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.75rem;">
                <span>{$ticketCount}</span>
                <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem;">
                    <span style="font-size: 0.75rem; font-weight: 500; color: rgb(107 114 128);">Colores:</span>
                    {$badges}
                </div>
            </div>
        HTML);
    }

    private static function legendBadge(string $label, string $color): string
    {
        [$background, $text, $border] = match ($color) {
            'warning' => ['#fffbeb', '#b45309', '#fcd34d'],
            'info' => ['#eff6ff', '#1d4ed8', '#93c5fd'],
            'success' => ['#f0fdf4', '#15803d', '#86efac'],
            'danger' => ['#fef2f2', '#b91c1c', '#fca5a5'],
            default => ['#f9fafb', '#374151', '#d1d5db'],
        };

        return '<span style="display: inline-flex; align-items: center; border-radius: 0.375rem; border: 1px solid '.$border.'; background: '.$background.'; color: '.$text.'; padding: 0.125rem 0.5rem; font-size: 0.75rem; font-weight: 600; line-height: 1.25rem;">'.e($label).'</span>';
    }
}
