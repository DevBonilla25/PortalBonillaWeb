<?php

namespace App\Http\Controllers\Api\V1\Driver;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\NoveltyReasonResource;
use App\Models\NoveltyReason;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NoveltyReasonController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $companyId = $request->user()?->company_id;

        return NoveltyReasonResource::collection(
            NoveltyReason::query()
                ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
                ->active()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        );
    }
}
