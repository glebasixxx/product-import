<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->firstOrCreate(
            ['email' => (string) config('auth.admin.email')],
            [
                'name' => 'Administrator',
                'password' => (string) config('auth.admin.password'),
            ],
        );
    }
}
