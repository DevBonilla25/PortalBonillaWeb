<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\DeliveryRouteStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreDeliveryRouteNoveltyRequest;
use App\Http\Resources\Api\V1\MediaAttachmentResource;
use App\Models\DeliveryRoute;
use App\Models\NoveltyReason;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;

class DeliveryRouteNoveltyController extends Controller
{
    public function store(StoreDeliveryRouteNoveltyRequest $request, DeliveryRoute $deliveryRoute): JsonResponse
    {
        $this->ensureVisible($request, $deliveryRoute);
        abort_if(in_array($deliveryRoute->status, [DeliveryRouteStatus::Completed, DeliveryRouteStatus::Cancelled], true), 422, 'No se pueden registrar novedades en una ruta finalizada.');

        $reason = NoveltyReason::query()->whereKey($request->integer('novelty_reason_id'))
            ->where('company_id', $request->user()->company_id)->active()->first();
        abort_unless($reason, 422, 'El motivo de novedad no esta disponible.');

        $photos = collect([$request->file('photo'), ...($request->file('photos', []))])->filter(fn ($file) => $file instanceof UploadedFile)->values();
        abort_if($reason->requires_photo && $photos->isEmpty(), 422, 'Este motivo de novedad requiere una foto.');

        $data = $request->safe()->except(['photo', 'photos']);
        $novelty = $deliveryRoute->novelties()->create([
            ...$data,
            'reported_by' => $request->user()->id,
            'occurred_at' => isset($data['occurred_at']) ? Carbon::parse($data['occurred_at']) : now(),
        ]);
        $disk = config('filesystems.logistics_media_disk', 'public');
        foreach ($photos as $index => $photo) {
            $novelty->mediaAttachments()->create([
                'driver_id' => $deliveryRoute->driver_id,
                'collection' => 'delivery_route_novelty_photos',
                'disk' => $disk,
                'path' => $photo->store("delivery-routes/{$deliveryRoute->id}/novelties", $disk),
                'original_name' => $photo->getClientOriginalName(),
                'mime_type' => $photo->getMimeType(),
                'size' => $photo->getSize(),
                'sort_order' => $index,
            ]);
        }
        $novelty->load(['reason', 'mediaAttachments']);

        return response()->json(['data' => [
            ...$novelty->toArray(),
            'reason' => $novelty->reason,
            'photos' => MediaAttachmentResource::collection($novelty->mediaAttachments),
        ]], 201);
    }

    private function ensureVisible(StoreDeliveryRouteNoveltyRequest $request, DeliveryRoute $route): void
    {
        abort_unless((int) $route->company_id === (int) $request->user()->company_id, 404);
        if ($request->user()->hasAnyRole(['driver', 'external_driver'])) {
            abort_unless((int) $route->driver_id === (int) $request->user()->driverProfile?->id, 404);
        }
    }
}
