<?php

namespace MDerakhshi\LaravelAttachment;

class Helpers
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

    public static function addSuffixToParentEntryName(string $name, string $suffix, string $separator = '_'): string
    {
        $response = (str_contains($name, '.') || str_contains($name, '[')) ? preg_replace('@^(\w+)([.\[])@', '\\1'.$separator.$suffix.'\\2', $name) : $name.$separator.$suffix;

        return str_replace('[]', '', trim($response, '.'));
    }

    public static function buildPaginateStructure(int $total, string $paginateRequestKey = 'page'): array
    {
        $page = request($paginateRequestKey, 1);
        $page = (! is_numeric($page) || $page > $total) ? $total : $page;
        if ($total <= 9) {
            $paginateArray = range(1, $total);
        } elseif ($page > $total - 5) {
            $paginateArray = array_merge([1, '...'], range($total - 6, $total));
        } elseif ($page < 5) {
            $paginateArray = array_merge(range(1, 7), ['...', $total]);
        } else {
            $paginateArray = array_merge([1, '...'], range($page - 2, min($page + 2, $total - 2)), ['...', $total]);
        }

        return $paginateArray;
    }


    public static function convertHtmlAttributes(array $data, array $acceptableKeys = null, array $aliasKeys = null): ?string
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