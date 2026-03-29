<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\RolloutStrategyEnum;
use Illuminate\Support\Collection;
use InvalidArgumentException;

readonly class FeatureConfigDTO
{
    public function __construct(
        public string $name,
        public string $displayName,
        public string $description,
        public bool $isActive,
        public RolloutStrategyEnum $strategy,
        public int $percentage = 0,
        public ?Collection $userIds = null,
    ) {}

    public static function fromDefinition(
        string $name,
        array $definition,
        ?RolloutStrategyEnum $strategy = null,
        bool $isActive = false,
        int $percentage = 0,
        ?array $userIds = null
    ): self {
        if (! isset($definition['name']) || ! isset($definition['description'])) {
            throw new InvalidArgumentException('Definition must contain name and description');
        }

        return new self(
            name: $name,
            displayName: $definition['name'],
            description: $definition['description'],
            isActive: $isActive,
            strategy: $strategy ?? RolloutStrategyEnum::Inactive,
            percentage: $percentage,
            userIds: $userIds ? collect($userIds) : null,
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'display_name' => $this->displayName,
            'description' => $this->description,
            'is_active' => $this->isActive,
            'strategy' => $this->strategy->value,
            'strategy_label' => $this->strategy->label(),
            'percentage' => $this->percentage,
        ];
    }
}
