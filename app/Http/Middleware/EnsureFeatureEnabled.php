<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureFeatureEnabled
{
    public function handle(Request $request, Closure $next, string $featureKey)
    {
        $enabled = (bool) config('security.features.' . $featureKey, false);
        if (!$enabled) {
            abort(404);
        }

        return $next($request);
    }
}

