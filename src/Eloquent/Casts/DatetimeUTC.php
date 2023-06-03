<?php

namespace MDerakhshi\LaravelAttachment\Eloquent\Casts;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class DatetimeUTC implements CastsAttributes
{

    public function get(Model $model, string $key, mixed $value, array $attributes): ?Carbon
    {
        if (is_null($value)) {
            return null;
        }

        return Carbon::parse($value)->shiftTimezone('UTC')->setTimezone(config('app.timezone', 'UTC'));
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?Carbon
    {
        if (is_null($value)) {
            return null;
        }

        if (! $value instanceof CarbonInterface) {
            $value = Carbon::parse($value);
        }

        return $value->utc() ?? null;
    }

}