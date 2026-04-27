<?php

declare(strict_types=1);

namespace App\Http\Resources\App;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TableDataResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'table' => $this['table'],
            'schema' => $this['schema'],
            'totalRows' => $this['totalRows'],
            'columns' => $this['columns'],
            'rows' => $this['rows'],
            'pagination' => [
                'page' => $request->integer('page', 1),
                'perPage' => $request->integer('per_page', 50),
                'totalPages' => (int) ceil($this['totalRows'] / max(1, $request->integer('per_page', 50))),
                'totalRows' => $this['totalRows'],
            ],
        ];
    }
}
