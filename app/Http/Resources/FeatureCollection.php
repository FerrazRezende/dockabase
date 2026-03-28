<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\DTOs\FeatureConfigDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FeatureCollection extends JsonResource
{
    /**
     * @param  iterable<FeatureConfigDTO>  $features
     */
    public function __construct($features)
    {
        parent::__construct($features);
    }

    public function toArray(Request $request): array
    {
        $collection = collect($this->resource);

        return [
            'data' => $collection->map(
                fn (FeatureConfigDTO $feature) => (new FeatureResource($feature))->toArray($request)
            )->values()->toArray(),
        ];
    }
}
