<?php

use App\Enums\UserRole;
use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('every staff role can upload media', function (UserRole $role) {
    expect(User::factory()->role($role)->create()->can('create', Media::class))->toBeTrue();
})->with(UserRole::cases());

test('admins and editors can edit and delete media uploaded by others', function (UserRole $role, bool $allowed) {
    $user = User::factory()->role($role)->create();
    $media = Media::factory()->create(['uploaded_by' => User::factory()->create()->id]);

    expect($user->can('update', $media))->toBe($allowed)
        ->and($user->can('delete', $media))->toBe($allowed);
})->with([
    'admin' => [UserRole::Admin, true],
    'editor' => [UserRole::Editor, true],
    'author' => [UserRole::Author, false],
]);

test('authors can edit and delete their own uploads', function () {
    $author = User::factory()->author()->create();
    $media = Media::factory()->create(['uploaded_by' => $author->id]);

    expect($author->can('update', $media))->toBeTrue()
        ->and($author->can('delete', $media))->toBeTrue();
});
