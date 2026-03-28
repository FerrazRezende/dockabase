<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\DTOs\FeatureConfigDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class FeatureCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->map(fn (FeatureConfigDTO $feature) => [
                'name' => $feature->name,
                'display_name' => $feature->displayName,
                'description' => $feature->description,
                'is_active' => $feature->isActive,
                'strategy' => $feature->strategy->value,
                'strategy_label' => $feature->strategy->label(),
                'percentage' => $feature->percentage,
            ]),
        ];
    }
}
