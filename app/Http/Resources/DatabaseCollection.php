<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class DatabaseCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->map(
                fn ($database) => (new DatabaseResource($database))->toArray($request)
            )->values()->toArray(),
        ];
    }
}
