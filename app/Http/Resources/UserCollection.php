<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class UserCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection
                ->map(fn ($user) => new UserResource($user))
                ->map(fn ($resource) => $resource->toArray($request))
                ->values()
                ->all(),
        ];
    }
}
