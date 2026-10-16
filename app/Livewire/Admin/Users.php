<?php

namespace App\Livewire\Admin;

use App\Actions\CreateUser;
use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\UserRole;
use App\Models\User;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Users')]
class Users extends Component
{
    use PasswordValidationRules, ProfileValidationRules, WithPagination;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $role = 'author';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
    }

    /**
     * Create a new staff account with the chosen role.
     */
    public function createUser(CreateUser $createUser): void
    {
        $this->authorize('create', User::class);

        $validated = $this->validate([
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'role' => ['required', Rule::enum(UserRole::class)],
        ]);

        $createUser->handle(
            [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
            ],
            UserRole::from($validated['role']),
        );

        $this->reset('name', 'email', 'password', 'password_confirmation', 'role');

        Flux::modal('create-user')->close();
        Flux::toast(variant: 'success', text: __('User created.'));
    }

    /**
     * Change the role of an existing account.
     */
    public function changeRole(int $userId, string $role): void
    {
        $user = User::query()->findOrFail($userId);

        $this->authorize('update', $user);

        Validator::make(['role' => $role], [
            'role' => ['required', Rule::enum(UserRole::class)],
        ])->validate();

        $user->role = UserRole::from($role);
        $user->save();

        Flux::toast(variant: 'success', text: __('Role updated.'));
    }

    /**
     * @return LengthAwarePaginator<int, User>
     */
    #[Computed]
    public function users(): LengthAwarePaginator
    {
        return User::query()->orderBy('name')->paginate(20);
    }

    /**
     * @return array<int, UserRole>
     */
    #[Computed]
    public function roles(): array
    {
        return UserRole::cases();
    }
}
