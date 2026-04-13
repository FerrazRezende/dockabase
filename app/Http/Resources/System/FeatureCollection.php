<?php

declare(strict_types=1);

namespace App\Http\Resources\System;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FeatureCollection extends JsonResource
{
    public function toArray(Request $request): array
    {
        $collection = collect($this->resource);

        return [
            'data' => $collection->map(
                fn (array $feature) => (new FeatureResource($feature))->toArray($request)
            )->values()->toArray(),
        ];
    }
}
