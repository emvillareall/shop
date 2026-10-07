<?php

namespace App\Http\Controllers;

use App\Http\Requests\PayphoneToolAccessRequest;
use App\Http\Requests\PayphoneToolGenerateRequest;
use App\Services\Pagos\PayphoneLinkException;
use App\Services\Pagos\PayphoneLinkService;
use App\Services\Pagos\PayphoneToolSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class PayphoneToolController extends Controller
{
    public function __construct(
        private readonly PayphoneToolSession $toolSession,
        private readonly PayphoneLinkService $linkService
    ) {}

    public function index(Request $request): View
    {
        $configurationError = trim((string) config('payments.payphone.tool.access_hash', '')) === '';

        if ($configurationError) {
            $this->toolSession->logout($request->session());

            return view('payphone-tool.access', [
                'configurationError' => true,
                'rateLimited' => false,
            ]);
        }

        if ($this->toolSession->isAuthorized($request->session())) {
            return $this->generatorView();
        }

        return view('payphone-tool.access', [
            'configurationError' => false,
            'rateLimited' => false,
        ]);
    }

    public function access(PayphoneToolAccessRequest $request): RedirectResponse|Response
    {
        if ($this->accessLimitReached($request)) {
            return $this->accessRateLimitedResponse($request);
        }

        $hash = trim((string) config('payments.payphone.tool.access_hash', ''));
        if ($hash === '') {
            $this->toolSession->logout($request->session());

            return response()->view('payphone-tool.access', [
                'configurationError' => true,
                'rateLimited' => false,
            ], 503);
        }

        $validCode = false;
        try {
            $validCode = Hash::check((string) $request->validated('code'), $hash);
        } catch (\Throwable) {
            $validCode = false;
        }

        if (!$validCode) {
            $this->registerAccessFailure($request);

            return redirect()
                ->route('payphone-tool.index')
                ->withErrors(['code' => 'El codigo de acceso no es correcto.']);
        }

        $this->toolSession->authorize($request->session());

        return redirect()->route('payphone-tool.index');
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->toolSession->logout($request->session());

        return redirect()
            ->route('payphone-tool.index')
            ->with('success', 'Acceso cerrado.');
    }

    public function generate(PayphoneToolGenerateRequest $request): View|RedirectResponse
    {
        try {
            $result = $this->linkService->generate(
                (string) $request->validated('amount'),
                $request->validated('reference'),
                $request,
                (string) $request->validated('modalidad', 'habitual')
            );
        } catch (PayphoneLinkException $exception) {
            Log::warning('payphone_tool_generation_rejected', [
                'error_code' => $exception->errorCode,
                'http_status' => $exception->httpStatus,
                'provider_error_code' => $exception->providerError['errorCode'] ?? null,
                'provider_message' => $exception->providerError['message'] ?? null,
                'provider_errors' => $exception->providerError['errors'] ?? [],
            ]);

            return redirect()
                ->route('payphone-tool.index')
                ->withInput()
                ->withErrors(['amount' => $exception->getMessage()]);
        } catch (\Throwable) {
            Log::error('payphone_tool_generation_failed', [
                'error_code' => 'unexpected_generation_failure',
                'requester_ip' => $request->ip(),
            ]);

            return redirect()
                ->route('payphone-tool.index')
                ->withInput()
                ->withErrors(['amount' => 'No se pudo generar el link en este momento.']);
        }

        return $this->generatorView($result);
    }

    private function generatorView(?array $result = null): View
    {
        return view('payphone-tool.index', [
            'surchargePercent' => (string) config('payments.payphone.tool.surcharge_percent', '7'),
            'defaultReference' => (string) config(
                'payments.payphone.tool.default_reference',
                'Compra BootyFitness'
            ),
            'modalidades' => $this->linkService->modalidadesParaUi(),
            'result' => $result,
        ]);
    }

    private function accessLimitReached(Request $request): bool
    {
        [$minuteKey, $hourKey] = $this->accessLimiterKeys($request);

        return RateLimiter::tooManyAttempts(
            $minuteKey,
            max(1, (int) config('payments.payphone.tool.access_attempts_per_minute', 5))
        ) || RateLimiter::tooManyAttempts(
            $hourKey,
            max(1, (int) config('payments.payphone.tool.access_attempts_per_hour', 20))
        );
    }

    private function registerAccessFailure(Request $request): void
    {
        [$minuteKey, $hourKey] = $this->accessLimiterKeys($request);
        RateLimiter::hit($minuteKey, 60);
        RateLimiter::hit($hourKey, 3600);
    }

    private function accessLimiterKeys(Request $request): array
    {
        $ip = $request->ip() ?: 'unknown';

        return [
            'payphone-tool:access:minute:' . $ip,
            'payphone-tool:access:hour:' . $ip,
        ];
    }

    private function accessRateLimitedResponse(Request $request): Response
    {
        [$minuteKey, $hourKey] = $this->accessLimiterKeys($request);
        $retryAfter = max(
            RateLimiter::availableIn($minuteKey),
            RateLimiter::availableIn($hourKey)
        );

        return response()
            ->view('payphone-tool.access', [
                'configurationError' => false,
                'rateLimited' => true,
            ], 429)
            ->header('Retry-After', (string) max(1, $retryAfter));
    }
}
