<?php

namespace Hdruk\LaravelSearchAndFilter\Traits;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;

trait Filter
{
    /**
     * Scope a query to apply filters from request or input array.
     */
    public function scopeFilterViaRequest(Builder $query, ?array $input = null): Builder
    {
        $input = $input ?? request()->all();
        $filterable = static::$filterableColumns ?? [];

        foreach ($input as $rawField => $rawValue) {
            // Skip if not allowed
            if (!static::isFilterableField($rawField, $filterable)) {
                continue;
            }

            // Detect operator suffixes (e.g. age__gte, created_at__lte, status__in)
            [$field, $operator] = static::parseFilterField($rawField);

            $value = is_array($rawValue) ? $rawValue : [$rawValue];
            $operator = strtolower($operator);

            // Build filter clause
            $query->where(function ($q) use ($field, $operator, $value) {
                switch ($operator) {
                    case 'gte':
                        $q->where($field, '>=', $value[0]);
                        break;
                    case 'lte':
                        $q->where($field, '<=', $value[0]);
                        break;
                    case 'gt':
                        $q->where($field, '>', $value[0]);
                        break;
                    case 'lt':
                        $q->where($field, '<', $value[0]);
                        break;
                    case 'ne':
                        $q->where($field, '<>', $value[0]);
                        break;
                    case 'in':
                        $q->whereIn($field, explode(',', $value[0]));
                        break;
                    case 'nin':
                        $q->whereNotIn($field, explode(',', $value[0]));
                        break;
                    case 'null':
                        $q->whereNull($field);
                        break;
                    case 'notnull':
                        $q->whereNotNull($field);
                        break;
                    default:
                        $q->where($field, '=', $value[0]);
                        break;
                }
            });
        }

        return $query;
    }

    /**
     * Check if the field is filterable.
     */
    protected static function isFilterableField(string $field, array $filterable): bool
    {
        $baseField = explode('__', $field)[0];
        return in_array($baseField, $filterable, true);
    }

    /**
     * Parse a field name into [field, operator].
     *
     * e.g. "age__gte" → ["age", "gte"]
     */
    protected static function parseFilterField(string $field): array
    {
        $parts = explode('__', $field);
        return [$parts[0], $parts[1] ?? '='];
    }
}
