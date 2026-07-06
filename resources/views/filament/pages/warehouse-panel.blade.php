<x-filament-panels::page>
    @once
        <style>
            .wh-panel-filters {
                display: grid;
                grid-template-columns: minmax(14rem, 3fr) minmax(10rem, 2fr) minmax(18rem, 7fr);
                gap: 0.75rem;
                align-items: end;
                margin-bottom: 1.5rem;
            }

            @media (max-width: 768px) {
                .wh-panel-filters {
                    grid-template-columns: minmax(0, 1fr);
                }
            }

            .wh-panel-field {
                display: flex;
                flex-direction: column;
                gap: 0.375rem;
                min-width: 0;
            }

            .wh-panel-label {
                font-size: 0.75rem;
                font-weight: 600;
                color: rgb(75 85 99);
            }

            .dark .wh-panel-label {
                color: rgb(209 213 219);
            }

            .wh-zone-legend {
                display: flex;
                flex-wrap: wrap;
                gap: 0.5rem;
                align-items: center;
                min-height: 2.625rem;
            }

            .wh-zone-chip {
                display: inline-flex;
                align-items: center;
                gap: 0.375rem;
                border-radius: 9999px;
                padding: 0.25rem 0.625rem;
                color: #fff;
                font-size: 0.75rem;
                font-weight: 700;
                line-height: 1rem;
                white-space: nowrap;
            }

            .wh-zone-chip-dot {
                width: 0.5rem;
                height: 0.5rem;
                border-radius: 9999px;
                background: rgb(255 255 255 / 0.85);
                flex-shrink: 0;
            }

            .wh-kanban {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 1rem;
                align-items: start;
            }

            @media (max-width: 1280px) {
                .wh-kanban {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }
            }

            @media (max-width: 640px) {
                .wh-kanban {
                    grid-template-columns: minmax(0, 1fr);
                }
            }

            .wh-kanban-column {
                display: flex;
                flex-direction: column;
                gap: 0.75rem;
                min-width: 0;
            }

            .wh-kanban-column-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.5rem;
                border-radius: 0.75rem;
                background: #fff;
                padding: 0.875rem 1rem;
                box-shadow: 0 1px 2px rgb(0 0 0 / 0.05);
                border: 1px solid rgb(0 0 0 / 0.05);
            }

            .dark .wh-kanban-column-header {
                background: rgb(17 24 39);
            }

            .wh-kanban-column-title {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                font-size: 0.875rem;
                font-weight: 600;
                color: rgb(3 7 18);
            }

            .dark .wh-kanban-column-title {
                color: #fff;
            }

            .wh-kanban-dot {
                width: 0.625rem;
                height: 0.625rem;
                border-radius: 9999px;
                flex-shrink: 0;
            }

            .wh-kanban-dot--info {
                background: rgb(59 130 246);
            }

            .wh-kanban-dot--warning {
                background: rgb(245 158 11);
            }

            .wh-kanban-dot--primary {
                background: rgb(217 119 6);
            }

            .wh-kanban-cards {
                display: flex;
                flex-direction: column;
                gap: 0.75rem;
                min-height: 12rem;
            }

            .wh-kanban-card {
                --zone-color: rgb(217 119 6);
                --zone-rgb: 217 119 6;
                display: flex;
                flex-direction: column;
                gap: 0.75rem;
                border-radius: 0.75rem;
                background: #fff;
                padding: 1rem;
                box-shadow: 0 1px 2px rgb(0 0 0 / 0.05);
                border: 1px solid rgb(0 0 0 / 0.05);
            }

            .wh-kanban-card--zoned {
                border-color: rgb(var(--zone-rgb) / 0.38);
                border-left: 5px solid var(--zone-color);
                background:
                    linear-gradient(0deg, rgb(var(--zone-rgb) / 0.08), rgb(var(--zone-rgb) / 0.08)),
                    #fff;
            }

            .dark .wh-kanban-card {
                background: rgb(17 24 39);
                border-color: rgb(255 255 255 / 0.1);
            }

            .dark .wh-kanban-card--zoned {
                border-color: rgb(var(--zone-rgb) / 0.55);
                border-left-color: var(--zone-color);
                background:
                    linear-gradient(0deg, rgb(var(--zone-rgb) / 0.16), rgb(var(--zone-rgb) / 0.16)),
                    rgb(17 24 39);
            }

            .wh-kanban-card-header {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 0.5rem;
            }

            .wh-kanban-card-code {
                font-size: 0.875rem;
                font-weight: 700;
                color: rgb(217 119 6);
                text-decoration: none;
            }

            .wh-kanban-card-code:hover {
                text-decoration: underline;
            }

            .wh-kanban-card-customer {
                font-size: 0.875rem;
                font-weight: 600;
                color: rgb(3 7 18);
            }

            .dark .wh-kanban-card-customer {
                color: #fff;
            }

            .wh-kanban-card-meta {
                font-size: 0.75rem;
                color: rgb(107 114 128);
            }

            .wh-kanban-card-items {
                margin: 0;
                padding: 0;
                list-style: none;
                font-size: 0.75rem;
                color: rgb(75 85 99);
            }

            .wh-kanban-card-item {
                display: flex;
                align-items: baseline;
                justify-content: space-between;
                gap: 0.75rem;
            }

            .wh-kanban-card-item + .wh-kanban-card-item,
            .wh-kanban-card-item + .wh-kanban-card-more {
                margin-top: 0.25rem;
            }

            .wh-kanban-card-item-name {
                flex: 1;
                min-width: 0;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .wh-kanban-card-item-qty {
                flex-shrink: 0;
                font-weight: 500;
                color: rgb(107 114 128);
            }

            .wh-kanban-card-more {
                color: rgb(156 163 175);
            }

            .wh-kanban-card-actions {
                display: flex;
                flex-wrap: wrap;
                gap: 0.5rem;
                padding-top: 0.75rem;
                border-top: 1px solid rgb(243 244 246);
            }

            .dark .wh-kanban-card-actions {
                border-top-color: rgb(255 255 255 / 0.1);
            }

            .wh-kanban-card--zoned .wh-kanban-card-actions {
                border-top-color: rgb(var(--zone-rgb) / 0.22);
            }

            .dark .wh-kanban-card--zoned .wh-kanban-card-actions {
                border-top-color: rgb(var(--zone-rgb) / 0.35);
            }

            .wh-kanban-empty {
                display: flex;
                flex: 1;
                align-items: center;
                justify-content: center;
                border-radius: 0.75rem;
                border: 1px dashed rgb(229 231 235);
                padding: 2.5rem 1rem;
                font-size: 0.875rem;
                color: rgb(156 163 175);
            }

            .dark .wh-kanban-empty {
                border-color: rgb(255 255 255 / 0.1);
            }
        </style>
    @endonce

    @if ($this->requiresWarehouseAssignment())
        <x-filament::section>
            <x-filament::badge color="warning">Bodega no configurada</x-filament::badge>
            <p class="mt-3 text-sm text-gray-600 dark:text-gray-300">
                Este usuario bodeguero debe tener un empleado asociado con sucursal y bodega asignadas en Laravel.
            </p>
        </x-filament::section>
    @else
    <div class="wh-panel-filters">
        <div class="wh-panel-field">
            <label class="wh-panel-label">Bodega</label>
            <x-filament::input.wrapper>
                <x-filament::input.select
                    wire:model.live="warehouseId"
                    :disabled="$this->isWarehouseSelectorLocked()"
                >
                    @foreach ($this->warehouseOptions as $warehouseId => $warehouseName)
                        <option value="{{ $warehouseId }}">{{ $warehouseName }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </div>

        <div class="wh-panel-field">
            <label class="wh-panel-label">Buscar</label>
            <x-filament::input.wrapper>
                <x-filament::input
                    type="search"
                    wire:model.live.debounce.400ms="search"
                    placeholder="Buscar ticket, cliente, chofer..."
                />
            </x-filament::input.wrapper>
        </div>

        <div class="wh-panel-field">
            <label class="wh-panel-label">Zonas</label>
            <div class="wh-zone-legend" aria-label="Zonas visibles">
                @foreach ($this->visibleZones as $zone)
                    <span class="wh-zone-chip" style="background-color: {{ $zone->color ?? '#6B7280' }};">
                        <span class="wh-zone-chip-dot"></span>
                        {{ $zone->name }}
                    </span>
                @endforeach
            </div>
        </div>
    </div>

    <div class="wh-kanban">
        @foreach ($this->getColumns() as $column)
            @php
                $tickets = $this->ticketsByColumn->get($column->key, collect());
            @endphp

            <div class="wh-kanban-column" wire:key="column-{{ $column->key }}">
                <div class="wh-kanban-column-header">
                    <div class="wh-kanban-column-title">
                        <span @class([
                            'wh-kanban-dot',
                            'wh-kanban-dot--info' => $column->dotColor === 'info',
                            'wh-kanban-dot--warning' => $column->dotColor === 'warning',
                            'wh-kanban-dot--primary' => $column->dotColor === 'primary',
                        ])></span>
                        <span>{{ $column->label }}</span>
                    </div>
                    <x-filament::badge color="gray" size="sm">
                        {{ $tickets->count() }}
                    </x-filament::badge>
                </div>

                <div class="wh-kanban-cards">
                    @forelse ($tickets as $ticket)
                        @php
                            $zoneColor = $ticket->zone?->color;
                            $zoneRgb = null;

                            if (is_string($zoneColor) && preg_match('/^#?([A-Fa-f0-9]{6})$/', $zoneColor, $matches)) {
                                $hex = $matches[1];
                                $zoneRgb = hexdec(substr($hex, 0, 2)) . ' ' . hexdec(substr($hex, 2, 2)) . ' ' . hexdec(substr($hex, 4, 2));
                                $zoneColor = '#' . strtoupper($hex);
                            }
                        @endphp
                        <div
                            @class([
                                'wh-kanban-card',
                                'wh-kanban-card--zoned' => filled($zoneRgb),
                            ])
                            @style([
                                '--zone-color: ' . $zoneColor => filled($zoneRgb),
                                '--zone-rgb: ' . $zoneRgb => filled($zoneRgb),
                            ])
                            wire:key="ticket-{{ $ticket->id }}"
                        >
                            <div class="wh-kanban-card-header">
                                <a href="{{ $this->ticketViewUrl($ticket) }}" class="wh-kanban-card-code">
                                    {{ $ticket->ticket_code }}
                                </a>
                                @if ($ticket->zone)
                                    <span class="wh-zone-chip" style="background-color: {{ $ticket->zone->color ?? '#6B7280' }};">
                                        <span class="wh-zone-chip-dot"></span>
                                        {{ $ticket->zone->name }}
                                    </span>
                                @endif
                            </div>

                            <div>
                                <p class="wh-kanban-card-customer">{{ $ticket->customer_name }}</p>
                                <p class="wh-kanban-card-meta">
                                    Sucursal: {{ $ticket->warehouse?->name ?? 'Sin bodega' }}
                                </p>
                                <p class="wh-kanban-card-meta">
                                    Chofer: {{ $ticket->currentDriver?->user?->name ?? 'Sin asignar' }}
                                    ·
                                    {{ $ticket->items_count ?? $ticket->items->count() }}
                                    {{ ($ticket->items_count ?? $ticket->items->count()) === 1 ? 'producto' : 'productos' }}
                                </p>
                                @if ($ticket->latestAssignment?->assistants?->isNotEmpty())
                                    <p class="wh-kanban-card-meta">
                                        Auxiliar: {{ $ticket->latestAssignment->assistants->pluck('name')->join(', ') }}
                                    </p>
                                @endif
                            </div>

                            @if ($ticket->items->isNotEmpty())
                                <ul class="wh-kanban-card-items">
                                    @foreach ($ticket->items->take(2) as $item)
                                        <li class="wh-kanban-card-item">
                                            <span class="wh-kanban-card-item-name">{{ $item->product_name }}</span>
                                            <span class="wh-kanban-card-item-qty">x{{ rtrim(rtrim((string) $item->quantity, '0'), '.') }}</span>
                                        </li>
                                    @endforeach
                                    @if ($remaining = $ticket->remainingItemsCount())
                                        <li class="wh-kanban-card-more">+{{ $remaining }} más</li>
                                    @endif
                                </ul>
                            @endif

                            <div class="wh-kanban-card-actions">
                                @if ($this->canAssignTicket($ticket))
                                    {!! $this->assignTicketButtonHtml($ticket->id) !!}
                                @endif

                                @if ($this->canAdvanceTicket($ticket))
                                    {!! $this->advanceTicketButtonHtml($ticket->id) !!}
                                @endif

                                @if ($this->canMarkLoaded($ticket))
                                    {!! $this->markLoadedButtonHtml($ticket->id) !!}
                                @endif

                                @if ($this->canReviewLoadingChecklist($ticket))
                                    {!! $this->reviewLoadingChecklistButtonHtml($ticket->id) !!}
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="wh-kanban-empty">Sin tickets</div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>

    @endif

    <x-filament-actions::modals />
</x-filament-panels::page>
