<?php

namespace MDerakhshi\LaravelAttachment\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class EncryptedCast implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes): ?string
    {
        return $value ? decrypt($value) : null;
    }

    public function set($model, string $key, $value, array $attributes): ?string
    {
        return $value ? encrypt($value) : null;
    }
}
