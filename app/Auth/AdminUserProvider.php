<?php

namespace App\Auth;

use App\Models\User;
use App\UserRole;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserProvider extends EloquentUserProvider
{
    public const ADMIN_ID = -1;

    public function retrieveById($identifier): ?Authenticatable
    {
        if ((string) $identifier === (string) self::ADMIN_ID) {
            return $this->configuredAdmin();
        }

        return parent::retrieveById($identifier);
    }

    public function retrieveByToken($identifier, #[\SensitiveParameter] $token): ?Authenticatable
    {
        if ((string) $identifier !== (string) self::ADMIN_ID) {
            return parent::retrieveByToken($identifier, $token);
        }

        $admin = $this->configuredAdmin();

        if ($admin === null) {
            return null;
        }

        $rememberToken = Cache::get($this->rememberTokenCacheKey($admin));

        if (! is_string($rememberToken) || ! hash_equals($rememberToken, $token)) {
            return null;
        }

        $admin->setRememberToken($rememberToken);

        return $admin;
    }

    public function updateRememberToken(UserContract $user, #[\SensitiveParameter] $token): void
    {
        if ((string) $user->getAuthIdentifier() !== (string) self::ADMIN_ID) {
            parent::updateRememberToken($user, $token);

            return;
        }

        $user->setRememberToken($token);
        $admin = $this->configuredAdmin();

        if ($admin !== null) {
            Cache::forever($this->rememberTokenCacheKey($admin), $token);
        }
    }

    public function retrieveByCredentials(#[\SensitiveParameter] array $credentials): ?Authenticatable
    {
        $user = parent::retrieveByCredentials($credentials);

        if ($user !== null) {
            return $user;
        }

        $email = $credentials['email'] ?? null;
        $admin = $this->configuredAdmin();

        if (! is_string($email) || $admin === null || Str::lower($email) !== Str::lower($admin->email)) {
            return null;
        }

        return $admin;
    }

    private function configuredAdmin(): ?User
    {
        $config = config('auth.admin', []);
        $email = $config['email'] ?? null;
        $password = $config['password'] ?? null;

        if (
            ! is_string($email)
            || filter_var($email, FILTER_VALIDATE_EMAIL) === false
            || ! is_string($password)
            || mb_strlen($password) < 16
        ) {
            return null;
        }

        return (new User)->forceFill([
            'id' => self::ADMIN_ID,
            'name' => $config['name'] ?? 'Administrateur',
            'email' => Str::lower($email),
            'password' => Hash::make($password),
            'role' => UserRole::Administrator,
        ]);
    }

    private function rememberTokenCacheKey(User $admin): string
    {
        return 'auth.configured-admin.remember.'.hash('sha256', Str::lower($admin->email));
    }
}
