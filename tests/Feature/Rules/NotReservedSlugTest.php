<?php

use App\Rules\NotReservedSlug;
use Illuminate\Support\Facades\Validator;

it('rejects slugs that are reserved for application routes', function (string $slug) {
    $validator = Validator::make(['slug' => $slug], ['slug' => [new NotReservedSlug]]);

    expect($validator->fails())->toBeTrue();
})->with([
    'admin' => 'admin',
    'login' => 'login',
    'teams namespace' => 'teams',
    'uppercase' => 'SEARCH',
]);

it('accepts slugs that are not reserved', function (string $slug) {
    $validator = Validator::make(['slug' => $slug], ['slug' => [new NotReservedSlug]]);

    expect($validator->passes())->toBeTrue();
})->with([
    'football' => 'podosfairo',
    'prefix of a reserved slug' => 'admins-corner',
]);
