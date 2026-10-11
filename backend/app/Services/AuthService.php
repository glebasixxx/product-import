<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    /**
     * @return array{token: string, user: User}|null
     */
    public function login(string $email, string $password): ?array
    {
        $user = Auth::getProvider()->retrieveByCredentials(['email' => $email]);

        if (! $user instanceof User || ! Hash::check($password, $user->getAuthPassword())) {
            return null;
        }

        $token = $user->createToken('spa', ['*'], now()->addDays(7))->plainTextToken;

        return ['token' => $token, 'user' => $user];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }
}
