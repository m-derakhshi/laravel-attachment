<?php

namespace MDerakhshi\LaravelAttachment;

class MDHelpers
{
    public static function escapeUrl(string $url): string
    {
        $url = parse_url($url);
        $url['path'] = $url['path'] ?? '';
        $url['query'] = $url['query'] ?? '';

        if ($url['path'] !== '') {
            $url['path'] = implode('/', array_map('rawurlencode', explode('/', $url['path'])));
        }

        if ($url['query'] !== '') {
            $url['query'] = "?{$url['query']}";
        }

        return str_replace(
            ['&', "'", '"', '>', '<'],
            ['&amp;', '&apos;', '&quot;', '&gt;', '&lt;'],
            $url['scheme']."://{$url['host']}{$url['path']}{$url['query']}"
        );
    }

    public static function makeDirectoryPath($path): void
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

        if (str_starts_with($fileName, config('app.asset_url')) || str_starts_with($fileName, config('app.url'))) {
            $size = getimagesize($fileName);
            if ($size === false) {
                return null;
            }

            return ['width' => $size[0], 'height' => $size[1]];
        } elseif (str_contains($fileName, '/')) {
            return null;
        }

        $filePath = public_path(rtrim($imagePath, '/').'/'.$fileName);
        if (! file_exists($filePath)) {
            return null;
        }
        $size = getimagesize($filePath);
        if ($size === false) {
            return null;
        }

        return ['width' => $size[0], 'height' => $size[1]];
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
