<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdministrator extends Command
{
    protected $signature = 'flowerflow:admin
        {email? : Correo de la cuenta administradora}
        {--name= : Nombre visible}
        {--password= : Contraseña; omítela para captura oculta}';

    protected $description = 'Crea o actualiza una cuenta administradora verificada sin registrar secretos en logs.';

    public function handle(): int
    {
        $email = (string) ($this->argument('email') ?: $this->ask('Correo electrónico'));
        $name = (string) ($this->option('name') ?: $this->ask('Nombre', 'Administración FlowerFlow'));
        $password = (string) ($this->option('password') ?: $this->secret('Contraseña'));

        $validator = Validator::make(compact('email', 'name', 'password'), [
            'email' => ['required', 'email:rfc', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', Password::default()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::query()->updateOrCreate(['email' => strtolower($email)], [
            'name' => $name,
            'password' => Hash::make($password),
            'email_verified_at' => now('UTC'),
        ]);
        $user->syncRoles(['admin']);

        $this->info('Cuenta administradora lista: '.$user->email);

        return self::SUCCESS;
    }
}
