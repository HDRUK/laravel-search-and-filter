# Laravel Search and Filter

A simple package for adding search and sort capabilities to your Eloquent models in Laravel 12.

## Installation

Copy the traits from [`src/Traits/Search.php`](src/Traits/Search.php) and [`src/Traits/Filter.php`](src/Traits/Filter.php) into your project, or require this package if published.

## Usage

Include the traits in your Eloquent models:

```php
use Hdruk\LaravelSearchAndFilter\Traits\Search;
use Hdruk\LaravelSearchAndFilter\Traits\Filter;

class MyModel extends Model
{
    use Search, Filter;

    protected static array $searchableColumns = [
        'name',
        'email',
        // add other searchable columns
    ];

    protected static array $sortableColumns = [
        'name',
        'created_at',
        // add other sortable columns
    ];
}
```

Searching

Use the `scopeSearch` method in your queries:

```php
$results = MyModel::search([
    'name' => ['John', 'Jane'],
    'email__or' => 'example@domain.com'
])->get();
```

Sorting

Use the `scopeSort` method in your queries:

```php
$results = MyModel::sort()->get();
```

You can pass a `sort` parameter in your request, e.g. `?sort=name:asc`.

## Requirements

- Laravel 12

## License
MIT
