<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CredentialCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->map(
                fn ($credential) => (new CredentialResource($credential))->toArray($request)
            )->values()->toArray(),
        ];
    }
}
