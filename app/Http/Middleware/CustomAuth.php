<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PeterPetrus\Auth\PassportToken;
use Illuminate\Support\Facades\DB;

class CustomAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();
        $token = PassportToken::dirtyDecode($token);
        if ($token['valid'] == true) {
            $tokenExist = PassportToken::existsValidToken($token['token_id'], $token['user_id']);
            if ($tokenExist) {
                $user = Auth::user();
                if ($user !== null) {
                    $request->merge(['userId' => $user['id']]);
                    $response = $next($request);
                    return $response;
                } else {
                    return $this->errorResponse(['code' => 4001, 'detail' => 'User not found'], 'unathorized', 401);
                }
            } else {
                return $this->errorResponse(['code' => 4002, 'detail' => "Token doesn't exist"], 'unathorized', 401);
            }
        } else {
            $user = Auth::user();
            if ($user !== null && Auth::guard('api')->check()) {
                $request->merge(['userId' => $user['id']]);
                $response = $next($request);
                return $response;
            }
            return $this->errorResponse(['code' => 4001, 'detail' => 'User not found & Token not valid'], 'unathorized', 401);
        }
    }

    protected function errorResponse($error = null, $message = null, $code = 403)
    {
        $response = [
            'success' => false,
            'message' => $message,
            'error' => $error
        ];

        return response()->json($response, $code);
    }
}
