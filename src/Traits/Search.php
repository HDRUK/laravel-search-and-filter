<?php

namespace Hdruk\LaravelSearchAndFilter\Traits;

trait Search
{
    public function scopeSearch($query, ?array $input = null): mixed
    {
        $input = $input ?? request()->all();

        $orGroups = [];
        $andGroups = [];

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

            if (!in_array(strtolower($field), static::$searchableColumns)) {
                continue;
            }

            if ($logic === 'or') {
                $orGroups[$field] = $searchValue;
            } else {
                $andGroups[$field] = $searchValue;
            }
        }

        return $query->where(function ($outerQuery) use ($orGroups, $andGroups) {
            foreach ($andGroups as $field => $terms) {
                $outerQuery->where(function ($q) use ($field, $terms) {
                    foreach ($terms as $term) {
                        $q->where($field, 'LIKE', '%' . $term . '%');
                    }
                });
            }

            if (!empty($orGroups)) {
                $outerQuery->where(function ($q) use ($orGroups) {
                    foreach ($orGroups as $field => $terms) {
                        if (is_array($terms)) {
                            foreach ($terms as $term) {
                                $q->orWhere($field, 'LIKE', '%' . $term . '%');
                            }
                        } else {
                            $q->orWhere($field, 'LIKE', '%' . $terms . '%');
                        }
                    }
                });
            }
        });
    }

    public function scopeSort($query): mixed
    {
        $input = \request()->all();

        // If no sort option passed, then always default to the first
        // element of our sortableColumns array on the model
        $sort = $input['sort'] ?? static::$sortableColumns[0];

        $tmp = explode(':', $sort);
        $field = strtolower($tmp[0]);

        if (isset(static::$sortableColumns) && !in_array(strtolower($field), static::$sortableColumns)) {
            throw new \Exception('field ' . $field . ' is not a sortable column');
        }

        $direction = strtolower($tmp[1]);
        if (!in_array($direction, ['asc', 'desc'])) {
            throw new \Exception('invalid sort direction ' . $direction);
        }

        return $query->orderBy($field, $direction);
    }
}