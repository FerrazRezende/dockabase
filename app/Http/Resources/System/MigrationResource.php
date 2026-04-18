<?php

declare(strict_types=1);

namespace App\Http\Resources\System;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MigrationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'batch' => $this->batch,
            'name' => $this->name,
            'operation' => $this->operation,
            'tableName' => $this->table_name,
            'schemaName' => $this->schema_name,
            'status' => $this->status,
            'errorMessage' => $this->error_message,
            'executedAt' => $this->executed_at?->toIso8601String(),
            'createdAt' => $this->created_at->toIso8601String(),
        ];
    }
}
