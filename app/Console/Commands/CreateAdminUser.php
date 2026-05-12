<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\UserRoles;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Validator;

class CreateAdminUser extends Command
{
    protected $signature = 'wallet:create-admin
        {--name= : Admin full name}
        {--email= : Admin email}
        {--password= : Admin password}';

    protected $description = 'Create or update the first Wallet Yanga Super Admin account.';

    public function handle(): int
    {
        $name = $this->option('name') ?: $this->ask('Full name');
        $email = $this->option('email') ?: $this->ask('Email address');
        $password = $this->option('password') ?: $this->secret('Password');

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', Password::min(8)->letters()->numbers()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::query()->updateOrCreate([
            'email' => $email,
        ], [
            'name' => $name,
            'password' => Hash::make($password),
            'role' => UserRoles::SUPER_ADMIN,
            'status' => 'active',
        ]);

        $this->info("Super Admin ready: {$user->email}");

        return self::SUCCESS;
    }
}
