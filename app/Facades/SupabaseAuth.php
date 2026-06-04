<?php

namespace App\Facades;

use App\Contracts\SupabaseAuthInterface;
use Illuminate\Support\Facades\Facade;

/**
 * @method static array signUp(string $email, string $password, array $data = [])
 * @method static array signIn(string $email, string $password)
 * @method static array signOut(string $accessToken)
 * @method static array refreshToken(string $refreshToken)
 * @method static array getUser(string $accessToken)
 * @method static array updateUser(string $accessToken, array $data)
 * @method static array resetPasswordForEmail(string $email, ?string $redirectTo = null)
 * @method static array updatePassword(string $accessToken, string $newPassword)
 * @method static array verifyToken(string $token)
 * @method static array getUserById(string $userId)
 * @method static array deleteUser(string $userId)
 */
class SupabaseAuth extends Facade
{
  protected static function getFacadeAccessor(): string
  {
    return SupabaseAuthInterface::class;
  }
}