<?php

namespace App\Console\Commands;

use App\Actions\AssignExclusiveBusinessRole;
use App\Enums\BusinessRole;
use App\Models\User;
use DomainException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
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

    public function handle(AssignExclusiveBusinessRole $assignRole): int
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

        $normalizedEmail = strtolower($email);
        $existingUser = User::query()->where('email', $normalizedEmail)->first();

        try {
            if ($existingUser) {
                $assignRole->assertCanAssign($existingUser, BusinessRole::Admin);
            }

            $user = DB::transaction(function () use ($normalizedEmail, $name, $password, $assignRole): User {
                $user = User::query()->updateOrCreate(['email' => $normalizedEmail], [
                    'name' => $name,
                    'password' => Hash::make($password),
                    'email_verified_at' => now('UTC'),
                ]);
                $assignRole->execute($user, BusinessRole::Admin);

                return $user;
            });
        } catch (DomainException) {
            $this->error('La cuenta ya tiene un rol de negocio distinto o una combinación inválida. No se realizaron cambios.');

            return self::FAILURE;
        }

        $this->info('Cuenta administradora lista: '.$user->email);

        return self::SUCCESS;
    }
}
