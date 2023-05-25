<?php

namespace MDerakhshi\LaravelAttachment\Traits;

use MDerakhshi\LaravelAttachment\Eloquent\Casts\DatetimeUTC;

trait HasDatetimeUTCTrait
{

    public function getCasts(): array
    {
        foreach ($this->casts as $key => $value) {
            if ($value == 'datetime') {
                $this->casts[$key] = DatetimeUTC::class;
            }
        }

        return parent::getCasts();
    }

}