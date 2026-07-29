<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\MediaAttachmentResource;
use App\Models\LogisticOperation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OperationAttachmentController extends Controller
{
    public function store(Request $request, LogisticOperation $logisticOperation): JsonResponse
    {
        abort_unless((int) $logisticOperation->company_id === (int) $request->user()->company_id, 404);
        if ($request->user()->hasAnyRole(['driver', 'external_driver'])) {
            abort_unless((int) $logisticOperation->driver_id === (int) $request->user()->driverProfile?->id, 404);
        }
        $data = $request->validate(['file' => ['required', 'file', 'max:10240'], 'collection' => ['nullable', 'in:guide,evidence,document,plant_exit_document']]);
        $file = $data['file'];
        $disk = config('filesystems.logistics_media_disk', 'public');
        $path = $file->store("logistic-operations/{$logisticOperation->id}", $disk);
        $attachment = $logisticOperation->mediaAttachments()->create([
            'driver_id' => $request->user()->driverProfile?->id,
            'collection' => $data['collection'] ?? 'document', 'disk' => $disk, 'path' => $path,
            'original_name' => $file->getClientOriginalName(), 'mime_type' => $file->getMimeType(), 'size' => $file->getSize(),
        ]);

        return MediaAttachmentResource::make($attachment)->response()->setStatusCode(201);
    }
}
