<?php

declare(strict_types=1);

namespace App\Features;

use App\Features\Base\Feature;

use Laravel\Pennant\Attributes\Name;

#[Name('database-creator')]
class DatabaseCreator extends Feature
{
}
