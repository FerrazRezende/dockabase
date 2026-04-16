<?php

declare(strict_types=1);

namespace App\Http\Resources\System;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FeatureResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->resource['name'],
            'display_name' => $this->resource['display_name'],
            'description' => $this->resource['description'],
            'is_active' => $this->resource['is_active'],
            'implemented' => $this->resource['implemented'],
            'strategy' => $this->resource['strategy'],
            'strategy_label' => $this->resource['strategy_label'],
            'percentage' => $this->resource['percentage'],
            'user_ids' => $this->resource['user_ids'],
        ];
    }
}
