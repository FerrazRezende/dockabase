<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\DTOs\FeatureConfigDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FeatureResource extends JsonResource
{
    public function __construct(
        private FeatureConfigDTO $feature
    ) {
        parent::__construct($feature);
    }

    public function toArray(Request $request): array
    {
        return [
            'name' => $this->feature->name,
            'display_name' => $this->feature->displayName,
            'description' => $this->feature->description,
            'is_active' => $this->feature->isActive,
            'strategy' => $this->feature->strategy->value,
            'strategy_label' => $this->feature->strategy->label(),
            'percentage' => $this->feature->percentage,
        ];
    }
}
