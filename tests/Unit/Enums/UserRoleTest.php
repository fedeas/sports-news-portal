<?php

use App\Enums\UserRole;

test('only admin is an admin', function (UserRole $role, bool $expected) {
    expect($role->isAdmin())->toBe($expected);
})->with([
    'admin' => [UserRole::Admin, true],
    'editor' => [UserRole::Editor, false],
    'author' => [UserRole::Author, false],
]);

test('admin and editor are editor or above', function (UserRole $role, bool $expected) {
    expect($role->isEditorOrAbove())->toBe($expected);
})->with([
    'admin' => [UserRole::Admin, true],
    'editor' => [UserRole::Editor, true],
    'author' => [UserRole::Author, false],
]);
