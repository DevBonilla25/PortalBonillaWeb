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

class TicketsTable
{
    private const DISPLAY_TIMEZONE = 'America/Guayaquil';

    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with([
                'zone',
                'cashier',
                'currentDriver.user',
                'latestAssignment.warehouseUser',
            ]))
            ->columns([
                TextColumn::make('ticket_code')
                    ->label('Código')
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record): string => TicketResource::getUrl('view', ['record' => $record]))
                    ->color('primary')
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
                TextColumn::make('cashier.name')
                    ->label('Cajero')
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('latestAssignment.warehouseUser.name')
                    ->label('Bodeguero')
                    ->placeholder('-'),
                TextColumn::make('currentDriver.user.name')
                    ->label('Chofer')
                    ->placeholder('-'),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
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
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(TicketStatus::class)
                    ->placeholder('Todos'),
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
}
