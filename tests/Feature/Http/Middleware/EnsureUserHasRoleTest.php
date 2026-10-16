<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

/**
 * Replay a Livewire update for the first component on a page, as the browser would after the page loaded.
 */
function replayLivewireUpdate(TestResponse $page): TestResponse
{
    preg_match('/wire:snapshot="([^"]+)"/', $page->getContent(), $matches);

    return test()->withHeader('X-Livewire', 'true')->postJson(app('livewire')->getUpdateUri(), [
        '_token' => csrf_token(),
        'components' => [[
            'snapshot' => html_entity_decode($matches[1]),
            'updates' => [],
            'calls' => [['path' => '', 'method' => '$refresh', 'params' => []]],
        ]],
    ]);
}

it('forbids further component requests once a user loses the role the page requires', function () {
    $user = User::factory()->admin()->create();
    $page = $this->actingAs($user)->get(route('admin.users.index'));

    $user->forceFill(['role' => UserRole::Author])->save();

    replayLivewireUpdate($page)->assertForbidden();
});

it('keeps serving component requests to users who still hold the role', function () {
    $page = $this->actingAs(User::factory()->admin()->create())->get(route('admin.users.index'));

    replayLivewireUpdate($page)->assertOk();
});
