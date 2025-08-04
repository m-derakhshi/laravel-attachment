<?php

namespace MDerakhshi\LaravelAttachment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MDHelpers
{
    public static function cleanContent(?string $content, string $availableTags = '<br>'): ?string
    {
        if (is_null($content)) {
            return null;
        }

        return preg_replace(
            '/^(<br>\s*)+|(<br>\s*)+$/', '', preg_replace('@(<br[\s/]*>\s*)+@', '<br>', strip_tags(nl2br($content, false), $availableTags)));
    }

    public static function updateGeomFromCoordinates(Model $model, string|array $prefixes): void
    {
        $prefixes = is_array($prefixes) ? $prefixes : [$prefixes];

        foreach ($prefixes as $prefix) {
            $latField = "{$prefix}_latitude";
            $lngField = "{$prefix}_longitude";
            $geomField = "{$prefix}_geom";

            $lat = $model->{$latField} ?? null;
            $lng = $model->{$lngField} ?? null;

            if (! is_null($lat) && ! is_null($lng)) {
                if (
                    $model->isDirty($latField) ||
                    $model->isDirty($lngField) ||
                    is_null($model->{$geomField})
                ) {
                    $pointWKT = "POINT({$lat} {$lng})";
                    $model->{$geomField} = DB::raw("ST_GeomFromText('{$pointWKT}')");
                }
            } elseif (! is_null($model->{$geomField})) {
                $model->{$geomField} = null;
            }
        }
    }

    public static function shortString(string $value, int $maxLength = 20, int $prefixLength = 15, int $suffixLength = 5): string
    {
        if (strlen($value) <= $maxLength) {
            return $value;
        }

        return substr($value, 0, $prefixLength).'...'.substr($value, -$suffixLength);
    }

    public static function normalizeNumberValue($value, bool $format = false)
    {
        if (is_string($value) && is_numeric($value)) {
            $value = ((float) $value == (int) $value) ? (int) $value : (float) $value;
            if ($format) {
                if (is_int($value)) {
                    return number_format($value);
                }

                [$intPart, $decPart] = explode('.', (string) $value);

                return number_format($intPart).'.'.$decPart;
            }
        }

        return $value;
    }

    public static function cleanDecimalString(float $amount): string
    {
        return rtrim(rtrim(number_format($amount, 9, '.', ''), '0'), '.');
    }

    public static function flattenTreeArray(array $elements, $parentId = null, $parentKey = 'parent_id', $idKey = 'id'): array
    {
        $flat = [];

        foreach ($elements as $element) {
            if (($element[$parentKey] ?? null) === $parentId) {
                $flat[] = $element;
                $children = self::flattenTreeArray($elements, $element[$idKey], $parentKey, $idKey);
                $flat = array_merge($flat, $children);
            }
        }

        return $flat;
    }

    public static function flattenTreeCollection(Collection $elements, $parentId = null, $parentKey = 'parent_id', $idKey = 'id'): Collection
    {
        $flat = collect();

        $elements->where($parentKey, $parentId)
            ->each(function ($element) use ($elements, &$flat, $parentKey, $idKey) {
                $flat->push($element);
                $children = self::flattenTreeCollection($elements, $element->$idKey, $parentKey, $idKey);
                $children->each(fn ($child) => $flat->push($child));
            });

        return $flat;
    }

    public static function normalizeCollectionParentIds(Collection $items, string $idKey = 'id', string $parentKey = 'parent_id'): Collection
    {
        $allIds = $items->pluck($idKey)->all();

        return $items->map(function ($item) use ($allIds, $parentKey) {
            if ($item->$parentKey !== null && ! in_array($item->$parentKey, $allIds)) {
                $item->$parentKey = null;
            }

            return $item;
        });
    }

    public static function sortArrayByColumnSort(array &$array, string $direction = 'asc'): void
    {
        $array = array_map(function ($item) {
            $item['sort'] = $item['sort'] ?? null;

            return $item;
        }, $array);

        usort($array, function ($a, $b) use ($direction) {
            if ($a['sort'] === null && $b['sort'] === null) {
                return 0;
            }
            if ($a['sort'] === null) {
                return 1;
            }
            if ($b['sort'] === null) {
                return -1;
            }

            if ($direction === 'desc') {
                return $b['sort'] <=> $a['sort'];
            }

            return $a['sort'] <=> $b['sort'];
        });

        $array = array_values($array);
    }

    public static function generateUniqueLicenseKey(string $input = '', int $length = 255): string
    {
        if ($length < 32) {
            $length = 32;
        }

        $randomData = $input.microtime(true).Str::random(32);
        $hash = hash('sha512', $randomData);

        while (strlen($hash) < $length) {
            $hash .= hash('sha512', $hash.Str::random(32));
        }

        return strtoupper(substr($hash, 0, $length));
    }

    public static function makeDirectoryPath(string $path): void
    {
        if (! file_exists($path) || ! is_dir($path)) {
            $newPath = '/';
            foreach (explode('/', $path) as $route) {
                $newPath .= $route.'/';
                if (! file_exists($newPath) || ! is_dir($newPath)) {
                    mkdir($newPath);
                }
            }
        }
    }

    public static function makeStorageDirectoryPath(string $path, ?string $disk = null): void
    {
        $diskInstance = $disk ? Storage::disk($disk) : Storage::disk(config('filesystems.default'));

        if (! $diskInstance->exists($path)) {
            $newPath = '/';
            foreach (explode('/', $path) as $route) {
                $newPath .= $route.'/';
                if (! $diskInstance->exists($newPath)) {
                    $diskInstance->makeDirectory($newPath);
                }
            }
        }
    }

    public static function getImageSize(string $imagePath, string $fileName): ?array
    {
        if (empty($fileName)) {
            return null;
        }

        $validExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (! in_array($fileExtension, $validExtensions)) {
            return null;
        }
        if (str_contains($fileName, '/')) {
            if (str_contains($fileName, '..')) {
                return null;
            }
            $appAssetUrl = config('app.asset_url');
            $appUrl = config('app.url');

            if (! str_starts_with($fileName, $appAssetUrl) || ! str_starts_with($fileName, $appUrl)) {
                return null;
            }
            $filePath = public_path(str_replace([$appAssetUrl, $appUrl], '', $fileName));
        } else {
            $filePath = public_path(rtrim($imagePath, '/').'/'.$fileName);

        }
        if (! file_exists($filePath)) {
            return null;
        }

        $size = getimagesize($filePath);

        return $size ? ['width' => $size[0], 'height' => $size[1]] : null;
    }

    public static function addSuffixToParentEntryName(string $name, string $suffix, string $separator = '_'): string
    {
        $response = (str_contains($name, '.') || str_contains($name, '[')) ? preg_replace('@^(\w+)([.\[])@', '\\1'.$separator.$suffix.'\\2', $name) : $name.$separator.$suffix;

        return str_replace('[]', '', trim($response, '.'));
    }

    public static function convertHtmlAttributes(array $data, ?array $acceptableKeys = null, ?array $aliasKeys = null): ?string
    {
        $responseString = null;
        foreach ($data as $key => $value) {
            if (! is_null($aliasKeys) && array_key_exists($key, $aliasKeys)) {
                $key = $aliasKeys[$key];
            }
            if (
                (! is_null($acceptableKeys) && ! in_array($key, $acceptableKeys, true))
                || is_array($value)
                || is_null($value)
                || is_numeric($key)
                || str_starts_with($key, ':')
                || $value === false
            ) {
                continue;
            }
            $responseString .= ' '.($value === true ? $key : $key.'="'.$value.'"');
        }

        return $responseString;
    }
}