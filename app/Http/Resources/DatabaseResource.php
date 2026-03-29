<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DatabaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'display_name' => $this->display_name,
            'description' => $this->description,
            'host' => $this->host,
            'port' => $this->port,
            'database_name' => $this->database_name,
            'is_active' => $this->is_active,
            'status' => $this->status,
            'current_step' => $this->current_step,
            'progress' => $this->progress,
            'error_message' => $this->error_message,
            'settings' => $this->settings,
            'credentials_count' => $this->whenCounted('credentials'),
            'credentials' => $this->whenLoaded('credentials', fn () => CredentialResource::collection($this->credentials)),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
