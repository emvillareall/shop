<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureStorefrontOpen
{
    public function handle(Request $request, Closure $next)
    {
        $isOpen = (bool) config('security.storefront_open', false);

        if (!$isOpen) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'La tienda estara disponible pronto.',
                ], 503);
            }

            return redirect('/')->with('info', 'Estamos en preparacion de lanzamiento.');
        }

        return $next($request);
    }
}

