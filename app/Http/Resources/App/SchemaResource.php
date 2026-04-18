<?php

declare(strict_types=1);

namespace App\Http\Resources\App;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SchemaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'schemas' => $this->resource,
        ];
    }
}
