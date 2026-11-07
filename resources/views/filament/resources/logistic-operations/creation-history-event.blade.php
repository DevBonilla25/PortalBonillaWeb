@php
    $createdAt = $operation->created_at
        ?->timezone('America/Guayaquil')
        ->format('d/m/Y H:i:s') ?? '-';
    $creatorName = $operation->creator?->name ?? '-';
@endphp

@if ($empty)
    <div style="padding: 1.5rem;">
        <div style="display: grid; gap: 0.75rem; padding: 1rem; border: 1px solid var(--gray-200); border-radius: 0.75rem;">
            <div style="font-weight: 600;">Abastecimiento creado</div>
            <div>{{ $createdAt }}</div>
            <div>Estado inicial: <x-filament::badge color="warning">Planificada</x-filament::badge></div>
            <div>Creado por: {{ $creatorName }}</div>
        </div>
    </div>
@else
    <td class="fi-ta-cell" style="padding: 1rem 1.5rem; white-space: nowrap;">
        {{ $createdAt }}
    </td>
    <td class="fi-ta-cell" style="padding: 1rem 1.5rem;">
        <x-filament::badge color="warning">Abastecimiento creado</x-filament::badge>
    </td>
    <td class="fi-ta-cell" style="padding: 1rem 1.5rem; color: var(--gray-400);">-</td>
    <td class="fi-ta-cell" style="padding: 1rem 1.5rem;">
        <x-filament::badge color="warning">Planificada</x-filament::badge>
    </td>
    <td class="fi-ta-cell" style="padding: 1rem 1.5rem;">{{ $creatorName }}</td>
    <td class="fi-ta-cell" style="padding: 1rem 1.5rem; color: var(--gray-400);">-</td>
@endif
