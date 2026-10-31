<?php

declare(strict_types=1);

namespace Sunny\Foundation\Support;

final class Arr
{
    private function __construct()
    {
    }

    /**
     * @param  array<int, array<string, mixed>|object>  $items
     * @return array<int, array<string, mixed>>
     */
    public static function tree(array $items, string $id = 'id', string $parentId = 'parent_id', string $childrenKey = 'children'): array
    {
        $nodes = [];
        $ids = [];

        foreach (array_values($items) as $item) {
            if (is_object($item)) {
                $item = (array) $item;
            }

            if (! is_array($item) || ! array_key_exists($id, $item)) {
                throw new \InvalidArgumentException("Each item must contain an [{$id}] key.");
            }

            if (! is_int($item[$id]) && ! is_string($item[$id])) {
                throw new \InvalidArgumentException("The [{$id}] value must be a string or integer.");
            }

            $key = (string) $item[$id];

            if (isset($ids[$key])) {
                throw new \InvalidArgumentException("Duplicate [{$id}] value [{$key}].");
            }

            $ids[$key] = true;
            $item[$childrenKey] = [];
            $nodes[] = $item;
        }

        $grouped = [];

        foreach ($nodes as $index => $node) {
            $parent = $node[$parentId] ?? null;
            $parentKey = ($parent === null || $parent === '' || ! is_scalar($parent)) ? '' : (string) $parent;

            if ($parentKey !== '' && isset($ids[$parentKey])) {
                $grouped[$parentKey][] = $index;
            } else {
                $grouped[''][] = $index;
            }
        }

        $build = function (string $parentKey) use (&$build, $nodes, $grouped, $id, $childrenKey): array {
            $branch = [];

            foreach ($grouped[$parentKey] ?? [] as $index) {
                $node = $nodes[$index];
                $node[$childrenKey] = $build((string) $node[$id]);
                $branch[] = $node;
            }

            return $branch;
        };

        return $build('');
    }

    /**
     * @param  array<mixed>  $array
     * @param  array<int, int|string>  $keys
     * @return array<mixed>
     */
    public static function only(array $array, array $keys): array
    {
        $result = [];

        foreach ($keys as $key) {
            if ((is_int($key) || is_string($key)) && array_key_exists($key, $array)) {
                $result[$key] = $array[$key];
            }
        }

        return $result;
    }

    /**
     * @param  array<mixed>  $array
     * @param  array<int, int|string>  $keys
     * @return array<mixed>
     */
    public static function except(array $array, array $keys): array
    {
        foreach ($keys as $key) {
            if (is_int($key) || is_string($key)) {
                unset($array[$key]);
            }
        }

        return $array;
    }

    /**
     * @param  array<mixed>  $array
     * @param  array<string, string>  $map
     * @return array<mixed>
     */
    public static function rename(array $array, array $map): array
    {
        foreach ($map as $from => $to) {
            if (array_key_exists($from, $array)) {
                $array[$to] = $array[$from];

                if ($from !== $to) {
                    unset($array[$from]);
                }
            }
        }

        return $array;
    }

    /**
     * @param  array<mixed>  $array
     * @return array<mixed>
     */
    public static function whereNotNull(array $array): array
    {
        return array_filter($array, static fn (mixed $value): bool => $value !== null);
    }

    /**
     * @param  array<int, mixed>  $items
     * @return array<string, mixed>
     */
    public static function keyBy(array $items, string $key): array
    {
        $result = [];

        foreach ($items as $item) {
            $result[(string) self::read($item, $key)] = $item;
        }

        return $result;
    }

    /**
     * @param  array<int, mixed>  $items
     * @return array<string, array<int, mixed>>
     */
    public static function groupBy(array $items, string $key): array
    {
        $result = [];

        foreach ($items as $item) {
            $result[(string) self::read($item, $key)][] = $item;
        }

        return $result;
    }

    /**
     * @param  array<int, mixed>  $items
     * @return array<int, mixed>
     */
    public static function uniqueBy(array $items, string $key): array
    {
        $seen = [];
        $result = [];

        foreach ($items as $item) {
            $value = (string) self::read($item, $key);

            if (isset($seen[$value])) {
                continue;
            }

            $seen[$value] = true;
            $result[] = $item;
        }

        return $result;
    }

    /**
     * @param  array<int, mixed>  $items
     * @return array<int, mixed>
     */
    public static function column(array $items, string $key): array
    {
        $result = [];

        foreach ($items as $item) {
            $result[] = self::read($item, $key);
        }

        return $result;
    }

    /**
     * @param  array<mixed>  $array
     */
    public static function isAssoc(array $array): bool
    {
        return ! array_is_list($array);
    }

    /**
     * @param  array<mixed>  $array
     */
    public static function first(array $array, mixed $default = null): mixed
    {
        if ($array === []) {
            return $default;
        }

        $values = array_values($array);

        return $values[0];
    }

    /**
     * @param  array<mixed>  $array
     */
    public static function last(array $array, mixed $default = null): mixed
    {
        if ($array === []) {
            return $default;
        }

        $values = array_values($array);

        return $values[count($values) - 1];
    }

    /**
     * @param  array<mixed>  $array
     */
    public static function get(array $array, string|int|null $key, mixed $default = null): mixed
    {
        if ($key === null) {
            return $array;
        }

        if (array_key_exists($key, $array)) {
            return $array[$key];
        }

        if (! is_string($key) || ! str_contains($key, '.')) {
            return $default;
        }

        $cursor = $array;

        foreach (explode('.', $key) as $segment) {
            if (! is_array($cursor) || ! array_key_exists($segment, $cursor)) {
                return $default;
            }

            $cursor = $cursor[$segment];
        }

        return $cursor;
    }

    /**
     * @param  array<mixed>  $array
     * @return array<mixed>
     */
    public static function set(array &$array, string $key, mixed $value): array
    {
        $segments = explode('.', $key);
        $cursor = &$array;

        foreach ($segments as $index => $segment) {
            if ($index === count($segments) - 1) {
                $cursor[$segment] = $value;
                break;
            }

            if (! isset($cursor[$segment]) || ! is_array($cursor[$segment])) {
                $cursor[$segment] = [];
            }

            $cursor = &$cursor[$segment];
        }

        return $array;
    }

    private static function read(mixed $item, string $key): mixed
    {
        if (is_array($item)) {
            return $item[$key] ?? null;
        }

        if (is_object($item)) {
            return $item->{$key} ?? null;
        }

        return null;
    }
}
