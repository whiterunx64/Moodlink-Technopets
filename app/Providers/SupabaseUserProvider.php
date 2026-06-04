<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\SupabaseAuthInterface;
use App\Contracts\SupabaseAuthenticatable;
use App\Contracts\SupabaseUserProviderInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Override;

class SupabaseUserProvider implements SupabaseUserProviderInterface
{
    /**
     * @param SupabaseAuthInterface $supabase Supabase auth service for credential validation
     * @param string                $model    Fully-qualified model class name
     */
    public function __construct(
        private readonly SupabaseAuthInterface $supabase,
        private string $model,
    ) {
    }

    /**
     * Retrieve a user by their primary identifier (UUID).
     *
     * @param  mixed $identifier auth.users UUID
     * @return Authenticatable|null
     */
    #[Override]
    public function retrieveById(mixed $identifier): ?Authenticatable
    {
        $model = $this->createModel();

        return $model->newQuery()
            ->where($model->getAuthIdentifierName(), $identifier)
            ->first();
    }

    #[Override]
    public function retrieveByToken(mixed $_identifier, mixed $_token): ?Authenticatable
    {
        return null; // auth.users has no remember_token column
    }

    #[Override]
    public function updateRememberToken(Authenticatable $_user, mixed $_token): void
    {
        // no-op: auth.users has no remember_token column
    }

    #[Override]
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        if (empty($credentials['email'])) {
            return null;
        }

        return $this->createModel()->newQuery()
            ->where('email', $credentials['email'])
            ->first();
    }

    #[Override]
    public function validateCredentials(Authenticatable $_user, array $_credentials): bool
    {
        // SupabaseGuard::attempt() owns the full sign-in flow and never delegates here.
        // Returning false prevents a silent half-authenticated state if called by mistake.
        return false;
    }

    #[Override]
    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false): void
    {
        // Supabase owns the password hash — no local rehashing needed
    }

    /**
     * Return an existing auth.users row, or build an in-memory stub.
     * Never INSERT — Supabase already created the row.
     */
    #[Override]
    public function createFromSupabase(array $userData): SupabaseAuthenticatable
    {
        $model = $this->createModel();
        $existing = $model->newQuery()
            ->where($model->getAuthIdentifierName(), $userData['id'])
            ->first();

        if ($existing instanceof SupabaseAuthenticatable) {
            return $existing;
        }

        $model->forceFill([
            'id' => $userData['id'],
            'email' => $userData['email'] ?? null,
        ]);
        $model->exists = true; // prevent Eloquent from issuing an INSERT

        return $model;
    }
    /**
     * Instantiate a fresh model instance from the configured class.
     *
     * @return Model&SupabaseAuthenticatable
     */

    public function createModel(): Model&SupabaseAuthenticatable
    {
        $class = '\\' . ltrim($this->model, '\\');

        return new $class;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function setModel(string $model): static
    {
        $this->model = $model;
        return $this;
    }
}
