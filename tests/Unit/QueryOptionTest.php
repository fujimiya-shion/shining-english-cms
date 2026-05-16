<?php

use App\ValueObjects\QueryOption;

it('throws when getting page without setting it', function (): void {
    $option = new QueryOption;

    expect(fn () => $option->getPage())
        ->toThrow(TypeError::class);
});

it('initializes with perPage default and empty with', function (): void {
    $option = new QueryOption;

    expect($option->getPerPage())->toBe(15);
    expect($option->getWith())->toBe([]);
    expect($option->getOrderBy())->toBe('created_at');
    expect($option->getOrderDirection())->toBe('desc');
});

it('sets page and perPage with minimums', function (): void {
    $option = new QueryOption;

    $option->setPage(0);
    $option->setPerPage(0);

    expect($option->getPage())->toBe(1);
    expect($option->getPerPage())->toBe(1);
});

it('keeps page null when setter receives null', function (): void {
    $option = new QueryOption;

    $option->setPage(null);

    expect(fn () => $option->getPage())
        ->toThrow(TypeError::class);
});

it('filters non-string eager loads', function (): void {
    $option = new QueryOption;

    $option->setWith(['posts', 1, null, 'comments']);

    expect($option->getWith())->toBe(['posts', 'comments']);
});

it('builds from array with comma separated with', function (): void {
    $option = QueryOption::fromArray([
        'page' => '2',
        'perPage' => '5',
        'with' => 'posts,comments',
    ]);

    expect($option->getPage())->toBe(2);
    expect($option->getPerPage())->toBe(5);
    expect($option->getWith())->toBe(['posts', 'comments']);
});

it('builds from array with with array', function (): void {
    $option = QueryOption::fromArray([
        'with' => ['posts', 'comments'],
    ]);

    expect($option->getWith())->toBe(['posts', 'comments']);
});

it('sets order by and normalizes direction', function (): void {
    $option = new QueryOption;

    $option->setOrderBy('id');
    $option->setOrderDirection('ASC');

    expect($option->getOrderBy())->toBe('id');
    expect($option->getOrderDirection())->toBe('asc');
});

it('builds from array with ordering fields', function (): void {
    $option = QueryOption::fromArray([
        'orderBy' => 'id',
        'orderDirection' => 'ASC',
    ]);

    expect($option->getOrderBy())->toBe('id');
    expect($option->getOrderDirection())->toBe('asc');
});
