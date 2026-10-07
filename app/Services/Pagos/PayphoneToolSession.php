<?php

namespace App\Services\Pagos;

use Illuminate\Contracts\Session\Session;

class PayphoneToolSession
{
    public const AUTHORIZED_KEY = 'payphone_tool_authorized';

    public const AUTHORIZED_AT_KEY = 'payphone_tool_authorized_at';

    public function isAuthorized(Session $session): bool
    {
        if (! (bool) $session->get(self::AUTHORIZED_KEY, false)) {
            return false;
        }

        $authorizedAt = $session->get(self::AUTHORIZED_AT_KEY);
        $ttlMinutes = min(
            8 * 60,
            max(1, (int) config('payments.payphone.tool.session_ttl_minutes', 480))
        );

        if (!is_numeric($authorizedAt) || (int) $authorizedAt <= 0) {
            $this->logout($session);

            return false;
        }

        if (now()->timestamp - (int) $authorizedAt >= $ttlMinutes * 60) {
            $this->logout($session);

            return false;
        }

        return true;
    }

    public function authorize(Session $session): void
    {
        $session->regenerate();
        $session->put([
            self::AUTHORIZED_KEY => true,
            self::AUTHORIZED_AT_KEY => now()->timestamp,
        ]);
    }

    public function logout(Session $session): void
    {
        $session->forget([
            self::AUTHORIZED_KEY,
            self::AUTHORIZED_AT_KEY,
        ]);
    }
}
