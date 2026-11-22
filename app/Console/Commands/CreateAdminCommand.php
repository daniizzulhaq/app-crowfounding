<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class CreateAdminCommand extends Command
{
    protected $signature = 'app:create-admin {--name=} {--email=} {--password=}';

    protected $description = 'Buat akun admin panel Alkiroma Amanah';

    public function handle(): int
    {
        $name = $this->option('name') ?: text('Nama', required: true);
        $email = $this->option('email') ?: text('Email', required: true);
        $password = $this->option('password') ?: password('Password (min. 10 karakter)', required: true);

        $validator = Validator::make(compact('name', 'email', 'password'), [
            'name' => ['required', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'min:10'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::create(compact('name', 'email', 'password'));
        $user->forceFill(['is_admin' => true, 'email_verified_at' => now()])->save();

        $this->info("Admin {$email} dibuat. Login di ".url('/admin'));

        return self::SUCCESS;
    }
}
