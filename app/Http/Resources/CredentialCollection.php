<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @extends \Illuminate\Http\Resources\Json\ResourceCollection<\App\Models\Credential>
 */
class CredentialCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection
                ->map(fn ($credential) => new CredentialResource($credential))
                ->map(fn ($resource) => $resource->toArray($request))
                ->values()
                ->all(),
        ];
    }
}
