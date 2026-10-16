<?php

namespace App\Actions;

use App\Enums\UserRole;
use App\Models\User;

class CreateUser
{
    /**
     * Create an account on behalf of an admin. Admin-created accounts are pre-verified.
     *
     * @param  array{name: string, email: string, password: string}  $attributes
     */
    public function handle(array $attributes, UserRole $role): User
    {
        $user = new User($attributes);
        $user->role = $role;
        $user->email_verified_at = now();
        $user->save();

        return $user;
    }
}
