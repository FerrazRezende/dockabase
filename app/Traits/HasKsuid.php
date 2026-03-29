<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Concerns\HasUniqueStringIds;
use Tuupola\KsuidFactory;

trait HasKsuid
{
    use HasUniqueStringIds;

    /**
     * Generate a new KSUID for the model.
     */
    public function newUniqueId(): string
    {
        return (string) KsuidFactory::create();
    }

    /**
     * Determine if the given key is a valid KSUID.
     */
    public function isValidUniqueId(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        // KSUID tem 27 caracteres
        if (strlen($value) !== 27) {
            return false;
        }

        // KSUID usa apenas caracteres alfanuméricos (base62)
        return preg_match('/^[0-9a-zA-Z]{27}$/', $value) === 1;
    }
}
