<?php

namespace App\Facades;

use App\Contracts\SupabaseAuthInterface;
use Illuminate\Support\Facades\Facade;

/**
 * @method static array adminSignInApiCall(string $email, string $password)
 * @method static array verifyAdminCredentialsApiCall(string $email, string $password)
 * @method static array refreshAdminAccessTokenApiCall(string $refreshToken)
 * @method static array getAuthenticatedAdminApiCall(string $accessToken)
 * @method static array updateAuthenticatedAdminApiCall(string $accessToken, array $data)
 * @method static array updateAdminPasswordApiCall(string $accessToken, string $newPassword)
 * @method static array adminSignOutApiCall(string $accessToken)
 * @method static array createStudentAccountApiCall(string $email, string $password, array $data = [], bool $emailConfirm = true)
 * @method static array deleteAdminAccountApiCall(string $userId)
 * @method static array verifyJwtTokenApiCall(string $token)
 */
class SupabaseAuth extends Facade
{
  protected static function getFacadeAccessor(): string
  {
    return SupabaseAuthInterface::class;
  }
}
