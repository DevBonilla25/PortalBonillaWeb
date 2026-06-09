<x-filament-panels::page>
    @once
        <style>
            .mf-panel {
                display: flex;
                flex-direction: column;
                gap: 1rem;
            }

            .mf-filters {
                display: grid;
                grid-template-columns: minmax(12rem, 1fr) minmax(14rem, 2fr) repeat(4, minmax(8rem, 1fr)) auto;
                gap: 0.75rem;
                align-items: end;
            }

            @media (max-width: 1024px) {
                .mf-filters {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }
            }

            @media (max-width: 640px) {
                .mf-filters {
                    grid-template-columns: minmax(0, 1fr);
                }
            }

            .mf-field {
                display: flex;
                flex-direction: column;
                gap: 0.375rem;
                min-width: 0;
            }

            .mf-label {
                font-size: 0.75rem;
                font-weight: 600;
                color: rgb(75 85 99);
            }

            .dark .mf-label {
                color: rgb(209 213 219);
            }

            .mf-card {
                overflow: hidden;
                border-radius: 0.5rem;
                border: 1px solid rgb(229 231 235);
                background: #fff;
                box-shadow: 0 1px 2px rgb(0 0 0 / 0.05);
            }

            .dark .mf-card {
                border-color: rgb(255 255 255 / 0.1);
                background: rgb(17 24 39);
            }

            .mf-table-wrap {
                overflow-x: auto;
            }

            .mf-table {
                width: 100%;
                min-width: 62rem;
                border-collapse: collapse;
            }

            .mf-table th,
            .mf-table td {
                padding: 0.75rem 1rem;
                border-bottom: 1px solid rgb(243 244 246);
                text-align: left;
                vertical-align: top;
                font-size: 0.875rem;
            }

            .dark .mf-table th,
            .dark .mf-table td {
                border-bottom-color: rgb(255 255 255 / 0.08);
            }

            .mf-table th {
                background: rgb(249 250 251);
                color: rgb(75 85 99);
                font-size: 0.75rem;
                font-weight: 700;
                text-transform: uppercase;
            }

            .dark .mf-table th {
                background: rgb(31 41 55);
                color: rgb(209 213 219);
            }

            .mf-ticket {
                font-weight: 700;
                color: rgb(217 119 6);
            }

            .mf-muted {
                color: rgb(107 114 128);
                font-size: 0.75rem;
            }

            .mf-empty {
                padding: 2rem;
                text-align: center;
                color: rgb(107 114 128);
            }

            .mf-modal-backdrop {
                position: fixed;
                inset: 0;
                z-index: 50;
                display: flex;
                align-items: center;
                justify-content: center;
                background: rgb(0 0 0 / 0.55);
                padding: 1rem;
            }

            .mf-modal {
                width: min(72rem, 100%);
                max-height: calc(100vh - 2rem);
                overflow: hidden;
                border-radius: 0.5rem;
                background: #fff;
                box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1);
            }

            .dark .mf-modal {
                background: rgb(17 24 39);
            }

            .mf-modal-header,
            .mf-modal-body {
                padding: 1rem;
            }

            .mf-modal-header {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 1rem;
                border-bottom: 1px solid rgb(229 231 235);
            }

            .dark .mf-modal-header {
                border-bottom-color: rgb(255 255 255 / 0.1);
            }

            .mf-modal-body {
                max-height: calc(100vh - 8rem);
                overflow-y: auto;
            }

            .mf-detail-grid {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 0.75rem;
                margin-bottom: 1rem;
            }

            @media (max-width: 900px) {
                .mf-detail-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }
            }

            @media (max-width: 640px) {
                .mf-detail-grid {
                    grid-template-columns: minmax(0, 1fr);
                }
            }

            .mf-detail-item {
                border-radius: 0.5rem;
                border: 1px solid rgb(229 231 235);
                padding: 0.75rem;
            }

            .dark .mf-detail-item {
                border-color: rgb(255 255 255 / 0.1);
            }

            .mf-detail-label {
                margin-bottom: 0.25rem;
                font-size: 0.75rem;
                font-weight: 700;
                color: rgb(107 114 128);
                text-transform: uppercase;
            }

            .mf-detail-value {
                font-size: 0.875rem;
                font-weight: 600;
                color: rgb(17 24 39);
                overflow-wrap: anywhere;
            }

            .dark .mf-detail-value {
                color: #fff;
            }
        </style>
    @endonce

    <div class="mf-panel">
        @if (! auth()->user()?->morfeus_user_id)
            <x-filament::section>
                <x-filament::badge color="warning">Usuario sin vinculo Morfeus</x-filament::badge>
                <p class="mt-3 text-sm text-gray-600 dark:text-gray-300">
                    Configura el campo Usuario Morfeus en esta cuenta para consultar los tickets generados en Morfeus.
                </p>
            </x-filament::section>
        @else
            <x-filament::section>
                <div class="mf-filters">
                    <div class="mf-field">
                        <label class="mf-label">Vista</label>
                        <x-filament::input.wrapper>
                            <select wire:model.live="mode" class="fi-input block w-full border-none bg-transparent py-1.5 text-base text-gray-950 outline-none transition duration-75 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 sm:text-sm dark:text-white dark:placeholder:text-gray-500">
                                <option value="pending_warehouse">Pendientes bodega</option>
                                <option value="all">Historial Morfeus</option>
                            </select>
                        </x-filament::input.wrapper>
                    </div>

                    <div class="mf-field">
                        <label class="mf-label">Buscar</label>
                        <x-filament::input.wrapper>
                            <x-filament::input
                                type="search"
                                wire:model.live.debounce.400ms="search"
                                placeholder="Ticket o bodega"
                            />
                        </x-filament::input.wrapper>
                    </div>

                    <div class="mf-field">
                        <label class="mf-label">Desde</label>
                        <x-filament::input.wrapper>
                            <x-filament::input type="date" wire:model.live="dateFrom" />
                        </x-filament::input.wrapper>
                    </div>

                    <div class="mf-field">
                        <label class="mf-label">Hasta</label>
                        <x-filament::input.wrapper>
                            <x-filament::input type="date" wire:model.live="dateTo" />
                        </x-filament::input.wrapper>
                    </div>

                    <div class="mf-field">
                        <label class="mf-label">Estado</label>
                        <x-filament::input.wrapper>
                            <x-filament::input type="text" wire:model.live.debounce.400ms="status" placeholder="P" />
                        </x-filament::input.wrapper>
                    </div>

                    <div class="mf-field">
                        <label class="mf-label">Limite</label>
                        <x-filament::input.wrapper>
                            <select wire:model.live="limit" class="fi-input block w-full border-none bg-transparent py-1.5 text-base text-gray-950 outline-none transition duration-75 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 sm:text-sm dark:text-white dark:placeholder:text-gray-500">
                                <option value="50">50</option>
                                <option value="100">100</option>
                                <option value="200">200</option>
                                <option value="300">300</option>
                            </select>
                        </x-filament::input.wrapper>
                    </div>

                    <x-filament::button color="gray" wire:click="resetFilters">
                        Limpiar
                    </x-filament::button>
                </div>
            </x-filament::section>

            @php
                $tickets = $this->tickets;
            @endphp

            @if ($this->loadError)
                <x-filament::section>
                    <x-filament::badge color="danger">Morfeus no disponible</x-filament::badge>
                    <p class="mt-3 text-sm text-gray-600 dark:text-gray-300">{{ $this->loadError }}</p>
                </x-filament::section>
            @endif

            <div class="mf-card">
                <div class="mf-table-wrap">
                    <table class="mf-table">
                        <thead>
                            <tr>
                                <th>Ticket</th>
                                <th>Fecha</th>
                                <th>Bodega</th>
                                <th>Documento origen</th>
                                <th>Cajero</th>
                                <th>Estado Morfeus</th>
                                <th>Estado logistico</th>
                                <th>Pendiente</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($tickets as $ticket)
                                <tr wire:key="morfeus-ticket-{{ $ticket['source_type'] }}-{{ $ticket['detail_id'] }}-{{ $ticket['warehouse']['external_id'] ?? 0 }}">
                                    <td>
                                        <div class="mf-ticket">{{ $ticket['ticket_number'] }}</div>
                                        <div class="mf-muted">
                                            {{ $ticket['source_type'] === 'pending_invoice' ? 'Factura' : 'Despacho' }}
                                            ID {{ $ticket['detail_id'] }}
                                        </div>
                                    </td>
                                    <td>{{ $ticket['issued_at'] ?? '-' }}</td>
                                    <td>
                                        <div>{{ $ticket['warehouse']['name'] ?? '-' }}</div>
                                        <div class="mf-muted">ID {{ $ticket['warehouse']['external_id'] ?? '-' }}</div>
                                        @if ($ticket['warehouse']['mapped_warehouse'] ?? null)
                                            <div class="mf-muted">
                                                Laravel: {{ $ticket['warehouse']['mapped_warehouse']['name'] }}
                                            </div>
                                        @else
                                            <x-filament::badge color="warning" size="sm">
                                                Sin mapeo
                                            </x-filament::badge>
                                        @endif
                                    </td>
                                    <td>{{ $ticket['source_document_id'] ?? '-' }}</td>
                                    <td>
                                        <div>{{ $ticket['cashier']['name'] ?? '-' }}</div>
                                        <div class="mf-muted">ID {{ $ticket['cashier']['external_id'] ?? '-' }}</div>
                                    </td>
                                    <td>
                                        <x-filament::badge color="gray">
                                            {{ $ticket['morfeus_status'] ?? '-' }}
                                        </x-filament::badge>
                                    </td>
                                    <td>
                                        <x-filament::badge :color="$ticket['logistic_status'] === 'delivered' ? 'success' : ($ticket['logistic_status'] === 'cancelled' ? 'danger' : 'warning')">
                                            {{ $ticket['logistic_status'] }}
                                        </x-filament::badge>
                                    </td>
                                    <td>
                                        @if ($ticket['pending_items_count'] !== null)
                                            <div>{{ (int) $ticket['pending_items_count'] }} item(s)</div>
                                            <div class="mf-muted">Cant. {{ $ticket['pending_quantity'] ?? '-' }}</div>
                                        @else
                                            <span class="mf-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <x-filament::button
                                            size="sm"
                                            color="gray"
                                            wire:click="openTicketDetail('{{ $ticket['source_type'] }}', {{ (int) $ticket['detail_id'] }}, {{ (int) ($ticket['warehouse']['external_id'] ?? 0) }})"
                                        >
                                            Ver
                                        </x-filament::button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9">
                                        <div class="mf-empty">No hay tickets Morfeus para los filtros actuales.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($selectedTicket || $detailError)
                <div class="mf-modal-backdrop" wire:key="morfeus-detail-modal">
                    <div class="mf-modal">
                        <div class="mf-modal-header">
                            <div>
                                <div class="mf-ticket">
                                    {{ $selectedTicket['ticket_number'] ?? 'Detalle Morfeus' }}
                                </div>
                                @if ($selectedTicket)
                                    <div class="mf-muted">
                                        {{ $selectedTicket['source_type'] === 'pending_invoice' ? 'Factura' : 'Despacho' }}
                                        ID {{ $selectedTicket['detail_id'] }}
                                        ·
                                        {{ $selectedTicket['issued_at'] ?? '-' }}
                                    </div>
                                @endif
                            </div>

                            <x-filament::button color="gray" size="sm" wire:click="closeTicketDetail">
                                Cerrar
                            </x-filament::button>
                        </div>

                        <div class="mf-modal-body">
                            @if ($detailError)
                                <x-filament::badge color="danger">Error</x-filament::badge>
                                <p class="mt-3 text-sm text-gray-600 dark:text-gray-300">{{ $detailError }}</p>
                            @else
                                <div class="mf-detail-grid">
                                    <div class="mf-detail-item">
                                        <div class="mf-detail-label">Bodega</div>
                                        <div class="mf-detail-value">{{ $selectedTicket['warehouse']['name'] ?? '-' }}</div>
                                        <div class="mf-muted">ID {{ $selectedTicket['warehouse']['external_id'] ?? '-' }}</div>
                                        @if ($selectedTicket['warehouse']['mapped_warehouse'] ?? null)
                                            <div class="mf-muted">
                                                Laravel: {{ $selectedTicket['warehouse']['mapped_warehouse']['name'] }}
                                            </div>
                                        @else
                                            <x-filament::badge color="warning" size="sm">
                                                Sin mapeo Laravel
                                            </x-filament::badge>
                                        @endif
                                    </div>

                                    <div class="mf-detail-item">
                                        <div class="mf-detail-label">Cajero</div>
                                        <div class="mf-detail-value">{{ $selectedTicket['cashier']['name'] ?? '-' }}</div>
                                        <div class="mf-muted">ID {{ $selectedTicket['cashier']['external_id'] ?? '-' }}</div>
                                    </div>

                                    <div class="mf-detail-item">
                                        <div class="mf-detail-label">Documento origen</div>
                                        <div class="mf-detail-value">{{ $selectedTicket['source_document']['external_id'] ?? '-' }}</div>
                                        <div class="mf-muted">{{ $selectedTicket['source_document']['type'] ?? 'unknown' }}</div>
                                    </div>

                                    <div class="mf-detail-item">
                                        <div class="mf-detail-label">Entrega relacionada</div>
                                        <div class="mf-detail-value">{{ $selectedTicket['delivery']['ticket_number'] ?? '-' }}</div>
                                        <div class="mf-muted">{{ $selectedTicket['delivery']['delivered_at'] ?? 'Sin entrega detectada' }}</div>
                                    </div>
                                </div>

                                @if (filled($selectedTicket['delivery']['external_delivery_id'] ?? null))
                                    <x-filament::section>
                                        <x-filament::badge color="success">Entrega Morfeus detectada</x-filament::badge>
                                        <p class="mt-3 text-sm text-gray-600 dark:text-gray-300">
                                            Entrega ID {{ $selectedTicket['delivery']['external_delivery_id'] }}
                                            · Estado {{ $selectedTicket['delivery']['morfeus_status'] ?? '-' }}
                                            · {{ $selectedTicket['delivery']['delivered_by'] ?? 'Sin observacion' }}
                                        </p>
                                    </x-filament::section>
                                @endif

                                <div class="mf-card" style="margin-top: 1rem;">
                                    <div class="mf-table-wrap">
                                        <table class="mf-table">
                                            <thead>
                                                <tr>
                                                    <th>Linea</th>
                                                    <th>Producto</th>
                                                    <th>Codigos</th>
                                                    <th>Unidad</th>
                                                    <th>Cant. a despachar</th>
                                                    <th>Cant. despachada</th>
                                                    <th>Pendiente</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse (($selectedTicket['items'] ?? []) as $item)
                                                    <tr>
                                                        <td>{{ $item['line'] ?? '-' }}</td>
                                                        <td>
                                                            <div>{{ $item['description'] ?? '-' }}</div>
                                                            <div class="mf-muted">ID {{ $item['external_item_id'] ?? '-' }}</div>
                                                        </td>
                                                        <td>
                                                            <div>{{ $item['barcode'] ?? '-' }}</div>
                                                            <div class="mf-muted">{{ $item['alternative_code'] ?? '-' }}</div>
                                                        </td>
                                                        <td>
                                                            <div>{{ $item['unit']['name'] ?? '-' }}</div>
                                                            <div class="mf-muted">ID {{ $item['unit']['external_id'] ?? '-' }}</div>
                                                        </td>
                                                        <td>{{ $item['quantity_to_dispatch'] ?? '-' }}</td>
                                                        <td>{{ $item['quantity_dispatched'] ?? '-' }}</td>
                                                        <td>{{ $item['pending_quantity'] ?? '-' }}</td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="7">
                                                            <div class="mf-empty">Este ticket no tiene items registrados en Morfeus.</div>
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        @endif
    </div>
</x-filament-panels::page>
