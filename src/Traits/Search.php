<?php

namespace Hdruk\LaravelSearchAndFilter\Traits;

trait Search
{
    public function scopeSearchViaRequest($query, ?array $input = null): mixed
    {
        $input = $input ?? request()->all();
        $searchable = static::$searchableColumns ?? [];

        $orGroups = [];
        $andGroups = [];

        // Parse input fields and logic suffixes (__or / __add)
        foreach ($input as $fieldWithOperator => $searchValue) {
            if (str_ends_with($fieldWithOperator, '__or')) {
                $field = str_replace('__or', '', $fieldWithOperator);
                $logic = 'or';
            } else if (str_ends_with($fieldWithOperator, '__and')) {
                $field = str_replace('__and', '', $fieldWithOperator);
                $logic = 'and';
            } else {
                $field = $fieldWithOperator;
                $logic = 'or';
            }

            $columns = static::searchableColumnsFor($field);
            if (empty($columns)) {
                continue;
            }

            if ($logic === 'or') {
                $orGroups[$field] = $searchValue;
            } else {
                $andGroups[$field] = $searchValue;
            }
        }

        // Build query conditions
        return $query->where(function ($outerQuery) use ($orGroups, $andGroups) {
            // AND logic groups
            foreach ($andGroups as $field => $terms) {
                $columns = static::searchableColumnsFor($field);
                $terms = (array)$terms;

                $outerQuery->where(function ($q) use ($columns, $terms) {
                    foreach ($terms as $term) {
                        $q->where(function ($subQ) use ($columns, $term) {
                            foreach ($columns as $col) {
                                $subQ->orWhere($col, 'LIKE', '%' . $term . '%');
                            }
                        });
                    }
                });
            }

            // OR logic groups
            if (!empty($orGroups)) {
                $outerQuery->where(function ($q) use ($orGroups) {
                    foreach ($orGroups as $field => $terms) {
                        $columns = static::searchableColumnsFor($field);
                        foreach ((array)$terms as $term) {
                            $q->orWhere(function ($subQ) use ($columns, $term) {
                                foreach ($columns as $col) {
                                    $subQ->orWhere($col, 'LIKE', '%' . $term . '%');
                                }
                            });
                        }
                    }
                });
            }
        });
    }

    public function scopeApplySorting($query, string $defaultField = 'created_at', string $defaultDirection = 'desc'): mixed
    {
        $input = \request()->all();

        // If no sort option passed, then always default to the first
        // element of our sortableColumns array on the model
        $sort = $input['sort'] ?? static::$sortableColumns[0];
        if (!$sort) {
            $query->orderBy($defaultField, $defaultDirection);
        }

        $tmp = explode(':', $sort);
        $field = strtolower($tmp[0]);

        if (isset(static::$sortableColumns) && !in_array(strtolower($field), static::$sortableColumns)) {
            throw new \Exception('field ' . $field . ' is not a sortable column');
        }

        $direction = (isset($tmp[1]) ? strtolower($tmp[1]) : 'asc');
        if (!in_array($direction, ['asc', 'desc'])) {
            throw new \Exception('invalid sort direction ' . $direction);
        }

        return $query->orderBy($field, $direction);
    }

    protected static function searchableColumnsFor(string $field): array
    {
        $searchable = static::$searchableColumns ?? [];
        $field = strtolower($field);

        if (array_key_exists($field, $searchable)) {
            // Group mapping example: 'name' => ['first_name', 'last_name']
            return (array)$searchable[$field];
        }

        if (in_array($field, $searchable, true)) {
            // Plain searchable column
            return [$field];
        }

        return [];
    }
}