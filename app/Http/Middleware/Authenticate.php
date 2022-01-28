<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    // protected function redirectTo($request)
    // {
    //     if (
    //         Str::contains($request->url(), "api") ||
    //         $request->expectsJson() || $request->ajax()
    //     ) {
    //         return route("unauthorized", 401);
    //     }

    //     if (!$request->expectsJson()) {
    //         return route('login');
    //     }
    // }

    public function handle($request, Closure $next, ...$guards)
    {
        // if (
        //     Str::contains($request->url(), "api") ||
        //     $request->expectsJson() || $request->ajax()
        // ) {
        //     try {
        //         if (!$token = Auth::parseToken()) {
        //             return  $this->unauthorize();
        //         } else {
        //             return  parent::handle($request, $next, ...$guards);
        //         }
        //     } catch (Exception $e) {
        //         if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException) {
        //             return   $this->unauthorize();
        //         } else if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException) {
        //             return   $this->unauthorize();
        //         } else if ($e instanceof \Tymon\JWTAuth\Exceptions\JWTException) {
        //             return   $this->unauthorize();
        //         } else {
        //             return   $this->unauthorize();
        //         }
        //     }
        //     return parent::handle($request, $next, ...$guards);
        // }

        if (Str::contains($request->url(), "api")) {
            $request->headers->set(
                "Authorization",
                Str::contains(strtolower(Cookie::get("jwt")), "bearer") ?
                    Cookie::get("jwt") : "Bearer " . Cookie::get("jwt")
            );

            try {
                return parent::handle($request, $next, $guards);
            } catch (\Illuminate\Auth\AuthenticationException $e) {
                return response("Unauthorize", 401);
            } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
                return response("Forbidden", 403);
            }
        }
    }
}
