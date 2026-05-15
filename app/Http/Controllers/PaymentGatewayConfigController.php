<?php

namespace App\Http\Controllers;

use App\Services\Pagos\PaymentConfigService;
use Illuminate\Http\Request;

class PaymentGatewayConfigController extends Controller
{
    public function __construct(
        private readonly PaymentConfigService $configService
    ) {}

    public function index()
    {
        $paypal = $this->configService->get('paypal');
        $payphone = $this->configService->get('payphone');

        return view('pagos.configuracion', compact('paypal', 'payphone'));
    }

    public function update(Request $request, string $gateway)
    {
        $gateway = strtolower($gateway);
        if (!in_array($gateway, ['paypal', 'payphone'], true)) {
            abort(404);
        }

        $rules = [
            'environment' => 'required|in:sandbox,production',
            'is_active' => 'nullable|boolean',
            'base_url' => 'nullable|url|max:255',
            'public_key' => 'nullable|string|max:2048',
            'secret_key' => 'nullable|string|max:4096',
            'merchant_id' => 'nullable|string|max:120',
            'store_id' => 'nullable|string|max:120',
            'currency' => 'nullable|string|max:10',
            'webhook_id' => 'nullable|string|max:2048',
            'webhook_secret' => 'nullable|string|max:4096',
            'verify_path' => 'nullable|string|max:120',
            'strict_webhook' => 'nullable|boolean',
            'payphone_fee_percent' => 'nullable|numeric|min:0|max:100',
            'iva_percent' => 'nullable|numeric|min:0|max:100',
        ];

        $data = $request->validate($rules);
        $data['is_active'] = $request->boolean('is_active');
        $data['strict_webhook'] = $request->boolean('strict_webhook', true);
        if ($gateway === 'paypal') {
            $data['merchant_id'] = null;
            $data['currency'] = 'USD';
        }
        if ($gateway === 'payphone') {
            $data['public_key'] = null;
            $data['webhook_id'] = null;
            $storeId = trim((string) ($data['store_id'] ?? ''));
            $data['settings'] = array_merge((array) ($data['settings'] ?? []), [
                'store_id' => $storeId !== '' ? $storeId : null,
                'payphone_fee_percent' => (float) ($data['payphone_fee_percent'] ?? 0),
                'iva_percent' => (float) ($data['iva_percent'] ?? 0),
            ]);
        }
        if ($gateway === 'paypal') {
            unset($data['payphone_fee_percent'], $data['iva_percent']);
        }
        unset($data['store_id']);

        $this->configService->upsert($gateway, $data);

        return back()->with('success', strtoupper($gateway) . ' actualizado correctamente.');
    }
}
