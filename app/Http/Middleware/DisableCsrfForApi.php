<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DisableCsrfForApi
{
    protected $except = [
        'api/login',
        'api/logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        foreach ($this->except as $route) {
            if ($request->is($route)) {
                config(['session.driver' => 'array']);
                break;
            }
        }
        
        return $next($request);
    }
}