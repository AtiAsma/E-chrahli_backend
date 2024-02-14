<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class StudentMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            // Get the JWT token from the request header
            $token = $request->header('Authorization');
            if (!$token) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            // Sets the guard to students
            Auth::shouldUse('students');

            // Decode the JWT token
            $user = JWTAuth::parseToken()->authenticate();

            // Check if the authenticated user is a student
            if (!$user || !($user instanceof \App\Models\Student)) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            // Set the authenticated user in the request
            Auth::guard('students')->setUser($user);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 401);
        }

        return $next($request);
    }

}
