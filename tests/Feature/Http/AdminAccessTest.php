<?php

use App\Enums\UserRole;
use App\Models\Article;
use App\Models\Author;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as RouteDefinition;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

/**
 * The staff roles allowed on each admin page. Every admin route must be listed here.
 *
 * @return array<string, array<int, UserRole>>
 */
function adminPageRoles(): array
{
    $staff = [UserRole::Admin, UserRole::Editor, UserRole::Author];
    $editors = [UserRole::Admin, UserRole::Editor];

    return [
        'admin.articles.index' => $staff,
        'admin.articles.create' => $staff,
        'admin.articles.edit' => $staff,
        'admin.authors.index' => $editors,
        'admin.categories.index' => $editors,
        'admin.comments.index' => $editors,
        'admin.fixtures.index' => $editors,
        'admin.leagues.index' => $editors,
        'admin.seasons.index' => $editors,
        'admin.tags.index' => $editors,
        'admin.teams.index' => $editors,
        'admin.sports.index' => [UserRole::Admin],
        'admin.users.index' => [UserRole::Admin],
    ];
}

/**
 * A URL for the admin page, with a draft by the user's author profile when the page edits an article.
 */
function adminPageUrl(string $routeName, User $user): string
{
    if ($routeName !== 'admin.articles.edit') {
        return route($routeName);
    }

    return route($routeName, Article::factory()->for($user->author)->create());
}

it('lists every admin route in the access matrix', function () {
    $adminRoutes = collect(Route::getRoutes()->getRoutes())
        ->map(fn (RouteDefinition $route) => $route->getName())
        ->filter(fn (?string $name) => str_starts_with((string) $name, 'admin.'))
        ->sort()
        ->values()
        ->all();

    expect($adminRoutes)->toBe(collect(adminPageRoles())->keys()->sort()->values()->all());
});

it('redirects guests to the login screen', function (string $routeName) {
    $this->get(route($routeName, $routeName === 'admin.articles.edit' ? Article::factory()->create() : []))
        ->assertRedirect(route('login'));
})->with(fn () => array_keys(adminPageRoles()));

it('allows only the permitted roles on each admin page', function (string $routeName, UserRole $role) {
    $user = User::factory()->role($role)->create();
    Author::factory()->forUser($user)->create();

    $response = $this->actingAs($user)->get(adminPageUrl($routeName, $user));

    in_array($role, adminPageRoles()[$routeName], true)
        ? $response->assertOk()
        : $response->assertForbidden();
})->with(fn () => array_keys(adminPageRoles()))->with(UserRole::cases());
