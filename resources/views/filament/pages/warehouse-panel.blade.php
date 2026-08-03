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
                grid-auto-flow: column;
                grid-auto-columns: minmax(18rem, 22rem);
                grid-template-columns: none;
                gap: 1rem;
                align-items: start;
                overflow-x: auto;
                overflow-y: hidden;
                padding-bottom: 0.75rem;
                scroll-snap-type: x proximity;
                scrollbar-gutter: stable;
                scrollbar-width: none;
                -ms-overflow-style: none;
            }

            .wh-kanban::-webkit-scrollbar {
                display: none;
            }

            .wh-kanban-scrollbar {
                overflow-x: auto;
                overflow-y: hidden;
                height: 0.875rem;
                margin-bottom: 0.75rem;
            }

            .wh-kanban-scrollbar-track {
                height: 1px;
            }

            @media (max-width: 1280px) {
                .wh-kanban {
                    grid-auto-columns: minmax(18rem, 21rem);
                }
            }

            @media (max-width: 640px) {
                .wh-kanban {
                    grid-auto-columns: minmax(17rem, calc(100vw - 3rem));
                    overscroll-behavior-inline: contain;
                }
            }

            .wh-kanban-column {
                display: flex;
                flex-direction: column;
                gap: 0.75rem;
                min-width: 0;
                scroll-snap-align: start;
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

            .wh-kanban-dot--danger {
                background: rgb(220 38 38);
            }

            .wh-kanban-dot--success {
                background: rgb(22 163 74);
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

            .wh-kanban-card-meta-row {
                display: flex;
                justify-content: space-between;
                gap: 0.75rem;
            }

            .wh-kanban-card-meta-row span {
                min-width: 0;
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

            .wh-kanban-load-more {
                display: flex;
                justify-content: center;
                padding-top: 0.25rem;
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
    <div
        wire:poll.5s="pollWarehousePanel"
        x-data="{
            kanbanWidth: 0,
            syncingKanbanScroll: false,
            kanbanResizeObserver: null,
            initKanbanScroll() {
                this.$nextTick(() => {
                    this.updateKanbanWidth()

                    this.kanbanResizeObserver = new ResizeObserver(() => this.updateKanbanWidth())
                    this.kanbanResizeObserver.observe(this.$refs.kanban)
                })
            },
            updateKanbanWidth() {
                this.kanbanWidth = this.$refs.kanban?.scrollWidth ?? 0
            },
            scrollKanbanFromTop() {
                if (this.syncingKanbanScroll || ! this.$refs.kanban) return

                this.syncingKanbanScroll = true
                this.$refs.kanban.scrollLeft = this.$refs.topScrollbar.scrollLeft
                requestAnimationFrame(() => this.syncingKanbanScroll = false)
            },
            scrollTopFromKanban() {
                if (this.syncingKanbanScroll || ! this.$refs.topScrollbar) return

                this.syncingKanbanScroll = true
                this.$refs.topScrollbar.scrollLeft = this.$refs.kanban.scrollLeft
                requestAnimationFrame(() => this.syncingKanbanScroll = false)
            },
            playWarehouseNotificationSound(url) {
                if (! url) {
                    return
                }

                const audio = new Audio(url)

                audio.play().catch(() => {})
            },
        }"
        @warehouse-ticket-loaded.window="playWarehouseNotificationSound($event.detail.soundUrl)"
    >
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

        <div
            class="wh-kanban-scrollbar"
            x-ref="topScrollbar"
            x-init="initKanbanScroll()"
            @scroll="scrollKanbanFromTop()"
            aria-label="Desplazamiento horizontal de columnas"
        >
            <div class="wh-kanban-scrollbar-track" :style="'width: ' + kanbanWidth + 'px'"></div>
        </div>

        <div class="wh-kanban" x-ref="kanban" @scroll="scrollTopFromKanban()">
            @foreach ($this->getColumns() as $column)
            @php
                $columnTickets = $this->ticketsByColumn->get($column->key, collect());
                $tickets = $this->visibleTicketsForColumn($column->key, $columnTickets);
                $hasMore = $this->columnHasMore($column->key, $columnTickets);
            @endphp

            <div class="wh-kanban-column" wire:key="column-{{ $column->key }}">
                <div class="wh-kanban-column-header">
                    <div class="wh-kanban-column-title">
                        <span @class([
                            'wh-kanban-dot',
                            'wh-kanban-dot--info' => $column->dotColor === 'info',
                            'wh-kanban-dot--warning' => $column->dotColor === 'warning',
                            'wh-kanban-dot--primary' => $column->dotColor === 'primary',
                            'wh-kanban-dot--danger' => $column->dotColor === 'danger',
                            'wh-kanban-dot--success' => $column->dotColor === 'success',
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
                                <p class="wh-kanban-card-meta wh-kanban-card-meta-row">
                                    <span>Chofer: {{ $ticket->currentDriver?->user?->name ?? 'Sin asignar' }}</span>
                                    <span>
                                        {{ $ticket->items_count ?? 0 }}
                                        {{ ($ticket->items_count ?? 0) === 1 ? 'producto' : 'productos' }}
                                    </span>
                                </p>
                                @if ($ticket->latestAssignment?->assistants?->isNotEmpty())
                                    <p class="wh-kanban-card-meta">
                                        Auxiliar: {{ $ticket->latestAssignment->assistants->pluck('name')->join(', ') }}
                                    </p>
                                @endif
                            </div>

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


                                @if ($this->canReceiveReturn($ticket))
                                    {!! $this->receiveReturnButtonHtml($ticket->id) !!}
                                @endif

                                @if ($this->canPrepareReassignment($ticket))
                                    {!! $this->prepareReassignmentButtonHtml($ticket->id) !!}
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="wh-kanban-empty">Sin tickets</div>
                    @endforelse

                    @if ($hasMore)
                        <div class="wh-kanban-load-more">
                            <x-filament::button
                                type="button"
                                color="gray"
                                size="sm"
                                outlined
                                wire:click="loadMore('{{ $column->key }}')"
                                wire:loading.attr="disabled"
                                wire:target="loadMore('{{ $column->key }}')"
                            >
                                Ver más
                            </x-filament::button>
                        </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>

    @endif

    <x-filament-actions::modals />
</x-filament-panels::page>
