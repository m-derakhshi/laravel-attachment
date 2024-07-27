<?php

namespace MDerakhshi\LaravelAttachment\Eloquent\Casts;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class DatetimeUTC implements CastsAttributes
{
    /**
     * Retrieve the value and convert to the application timezone.
     *
     * @param  Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return string|Carbon|null
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): string|null|Carbon
    {
        if (is_null($value)) {
            return null;
        }
        if (!is_string($value)) {
            return $value;
        }
        return Carbon::createFromFormat('Y-m-d H:i:s', $value, 'UTC')->setTimezone(config('app.timezone', 'UTC'));
    }

    /**
     * Prepare the value for saving to the database in UTC.
     *
     * @param  Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return string|null
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if (is_null($value)) {
            return null;
        }

        // Ensure the value is a Carbon instance and convert to UTC for storage
        if (! $value instanceof CarbonInterface) {
            $value = Carbon::parse($value);
        }

        // old code:        return $value->utc() ?? null;
        return $value->setTimezone('UTC')->toDateTimeString();
    }
}
