<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsAdmin
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
        if (!auth()->check() ||auth()->user()->user_role != 'admin') {
            return response()->json(['status'=>false ,'message'=>'Only Admin has permission']);
        }
        return $next($request);
    }
}
