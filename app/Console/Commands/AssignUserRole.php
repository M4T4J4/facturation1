<?php

namespace App\Console\Commands;

use App\Models\User;
use App\UserRole;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:assign-user-role {identifier : User email address or phone number} {role : administrator, field_agent, or customer}')]
#[Description('Assign a platform role to an existing user')]
class AssignUserRole extends Command
{
    public function handle(): int
    {
        $role = UserRole::tryFrom($this->argument('role'));

        if ($role === null) {
            $this->error('The role must be administrator, field_agent, or customer.');

            return self::FAILURE;
        }

        $identifier = $this->argument('identifier');
        $user = filter_var($identifier, FILTER_VALIDATE_EMAIL)
            ? User::where('email', mb_strtolower($identifier))->first()
            : User::where('phone', User::normalizePhone($identifier))->first();

        if ($user === null) {
            $this->error('No user was found for the supplied email address or phone number.');

            return self::FAILURE;
        }

        $user->update(['role' => $role]);
        $this->info("{$user->name} now has the {$role->label()} role.");

        return self::SUCCESS;
    }
}
