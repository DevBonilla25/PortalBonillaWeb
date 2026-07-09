@php
    use Illuminate\Support\Facades\Storage;

    $record->loadMissing('mediaAttachments');

    $items = $record->mediaAttachments
        ->sortBy('sort_order')
        ->map(fn ($attachment) => [
            'url' => Storage::disk($attachment->disk)->url($attachment->path),
            'name' => $attachment->original_name ?: basename($attachment->path),
        ])
        ->values();

    if ($items->isEmpty() && filled($record->photo_path)) {
        $items = collect([[
            'url' => Storage::disk($fallbackDisk)->url($record->photo_path),
            'name' => basename($record->photo_path),
        ]]);
    }
@endphp

<div class="grid gap-4 sm:grid-cols-2">
    @forelse ($items as $item)
        <a href="{{ $item['url'] }}" target="_blank" rel="noopener noreferrer" class="block rounded-lg border border-gray-200 p-2">
            <img src="{{ $item['url'] }}" alt="{{ $item['name'] }}" class="h-48 w-full rounded-md object-cover">
            <div class="mt-2 truncate text-sm text-gray-600">{{ $item['name'] }}</div>
        </a>
    @empty
        <div class="text-sm text-gray-500">No hay imagenes registradas.</div>
    @endforelse
</div>
