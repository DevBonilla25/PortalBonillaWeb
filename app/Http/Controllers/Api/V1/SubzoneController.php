<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Subzone;
use App\Models\Zone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubzoneController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Subzone::query()->whereHas('zone', fn ($query) => $query->where('company_id', $request->user()->company_id));
        if ($request->filled('zone_id')) {
            $query->where('zone_id', $request->integer('zone_id'));
        }

        return response()->json(['data' => $query->orderBy('name')->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_if($request->user()->hasAnyRole(['driver', 'chofer_externo']), 403);
        $data = $this->validateData($request);
        $this->ensureZone($request, $data['zone_id']);

        return response()->json(['data' => Subzone::query()->create($data)], 201);
    }

    public function update(Request $request, Subzone $subzone): JsonResponse
    {
        abort_if($request->user()->hasAnyRole(['driver', 'chofer_externo']), 403);
        $this->ensureZone($request, $subzone->zone_id);
        $data = $this->validateData($request, $subzone);
        $this->ensureZone($request, $data['zone_id']);
        $subzone->update($data);

        return response()->json(['data' => $subzone->refresh()]);
    }

    public function destroy(Request $request, Subzone $subzone): JsonResponse
    {
        abort_if($request->user()->hasAnyRole(['driver', 'chofer_externo']), 403);
        $this->ensureZone($request, $subzone->zone_id);
        $subzone->update(['is_active' => false]);

        return response()->json(status: 204);
    }

    private function validateData(Request $request, ?Subzone $subzone = null): array
    {
        return $request->validate(['zone_id' => ['required', 'integer', 'exists:zones,id'], 'name' => ['required', 'string', 'max:255', Rule::unique('subzones')->where('zone_id', $request->integer('zone_id'))->ignore($subzone)], 'is_active' => ['sometimes', 'boolean']]);
    }

    private function ensureZone(Request $request, int $zoneId): void
    {
        abort_unless(Zone::query()->whereKey($zoneId)->where('company_id', $request->user()->company_id)->exists(), 404);
    }
}
