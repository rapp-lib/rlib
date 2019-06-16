<?php
namespace R\Lib\Laravel\Http\Middleware;

use \Firebase\JWT\JWT;
use \Illuminate\Auth\GenericUser;

class JwtAuth
{
  public function handle($request, $next, $required)
  {
    $claims = $this->captureJwt($request);
    if ($required && ! $claims) return abort(403);
    if ($claims) {
      $user = new GenericUser($claims);
      app("auth")->login($user);
    }

    return $next($request);
  }

  protected function captureJwt($request)
  {
    $auth_header = $request->header("authorization");
    if (preg_match('!^Bearer\s+(.+)$!i', $auth_header, $_)) {
      $token = $_[1];
      $public_key = env("JWT_PUBLIC_KEY");
      $public_key = "-----BEGIN PUBLIC KEY-----\n".$public_key."\n-----END PUBLIC KEY-----";
      try {
        $claims = (array)JWT::decode($token, $public_key, array('RS256'));
        if ($claims["exp"] && $claims["exp"] < time()) {
          throw new \Exception("Token is expired exp=".$claims["exp"]);
        }
        return $claims;
      } catch (\Exception $e) {
        debug($e->getMessage(), $e);
      }
      return array();
    }
  }
}
