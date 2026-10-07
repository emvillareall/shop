<?php

namespace App\Http\Middleware;

use App\Services\Pagos\PayphoneToolSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePayphoneToolAuthorized
{
    public function __construct(
        private readonly PayphoneToolSession $toolSession
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (trim((string) config('payments.payphone.tool.access_hash', '')) === '') {
            $this->toolSession->logout($request->session());

            return redirect()->route('payphone-tool.index');
        }

        if (!$this->toolSession->isAuthorized($request->session())) {
            return redirect()->route('payphone-tool.index');
        }

        return $next($request);
    }
}
