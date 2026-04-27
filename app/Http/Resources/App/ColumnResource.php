<?php

declare(strict_types=1);

namespace App\Http\Resources\App;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ColumnResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'name' => $this['name'],
            'type' => $this['type'],
            'nullable' => $this['nullable'],
            'defaultValue' => $this['defaultValue'],
            'isPrimaryKey' => $this['isPrimaryKey'],
            'isForeignKey' => $this['isForeignKey'],
            'isUnique' => false, // TODO: implement unique detection
            'foreignKey' => $this['foreignKey'],
        ];
    }
}
