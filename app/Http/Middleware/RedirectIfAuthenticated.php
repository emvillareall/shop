<?php

namespace App\Http\Middleware;

use App\Models\Producto;
use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                $user = Auth::guard($guard)->user();
                $destination = $user->can('administracion-completa')
                    ? RouteServiceProvider::HOME
                    : ($user->can('viewAny', Producto::class) ? route('productos.index') : route('ecommerce.home'));

                return redirect($destination);
            }
        }

        return $next($request);
    }
}
