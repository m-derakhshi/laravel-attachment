<?php

namespace MDerakhshi\LaravelAttachment\Eloquent\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class DatetimeUTC implements CastsAttributes
{

    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return ! empty($value) ? Carbon::parse($value)->shiftTimezone('UTC')->setTimezone(config('app.timezone', 'UTC'))->toDateTimeString() : null;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return Carbon::createFromFormat('Y-m-d H:i:s', $value)->shiftTimezone(config('app.timezone', 'UTC'))->setTimezone('UTC') ?? null;
    }

}