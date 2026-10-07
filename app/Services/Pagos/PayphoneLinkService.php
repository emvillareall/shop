<?php

namespace App\Services\Pagos;

use App\Models\PayphoneToolLink;
use App\Support\Money\AmountInCents;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use InvalidArgumentException;

class PayphoneLinkService
{
    public function __construct(
        private readonly PayphoneLinkClient $client,
        private readonly PaymentConfigService $configService
    ) {}

    /**
     * @return array<string,mixed>
     */
    public function generate(
        string|int $amountInput,
        ?string $reference,
        Request $request,
        string $modalidad = 'habitual'
    ): array
    {
        try {
            $baseCents = AmountInCents::parse($amountInput);
        } catch (InvalidArgumentException $exception) {
            throw new PayphoneLinkException(
                'Revisa el monto ingresado.',
                422,
                'payphone_tool_amount_invalid'
            );
        }

        $modality = $this->resolveModality($modalidad);

        try {
            $percentHundredths = AmountInCents::parsePercentHundredths($modality['surcharge_percent']);
        } catch (InvalidArgumentException $exception) {
            throw new PayphoneLinkException(
                'La modalidad de cobro no esta configurada correctamente.',
                503,
                'payphone_tool_modality_percentage_invalid'
            );
        }

        if (!$modality['enabled'] || $modality['pending_activation']) {
            throw new PayphoneLinkException(
                sprintf(
                    'La modalidad %s esta pendiente de activar en PayPhone Business.',
                    $modality['label']
                ),
                422,
                'payphone_tool_modality_pending_activation'
            );
        }

        $minCents = max(1, (int) config('payments.payphone.tool.min_amount_cents', 1));
        if ($modality['min_amount_cents'] !== null) {
            $minCents = max($minCents, (int) $modality['min_amount_cents']);
        }
        $maxCents = (int) config('payments.payphone.tool.max_amount_cents', 0);

        if ($maxCents <= 0) {
            throw new PayphoneLinkException(
                'La herramienta no esta configurada para generar cobros.',
                503,
                'payphone_tool_maximum_not_configured'
            );
        }

        if ($baseCents < $minCents || $baseCents > $maxCents) {
            $message = $modality['min_amount_cents'] !== null && $baseCents < $modality['min_amount_cents']
                ? sprintf(
                    'La modalidad %s requiere una compra minima de %s.',
                    $modality['label'],
                    AmountInCents::format((int) $modality['min_amount_cents'])
                )
                : 'El monto esta fuera del rango permitido.';

            throw new PayphoneLinkException(
                $message,
                422,
                'payphone_tool_amount_out_of_range'
            );
        }

        $surchargeCents = AmountInCents::percentage($baseCents, $percentHundredths);
        if ($surchargeCents > PHP_INT_MAX - $baseCents) {
            throw new PayphoneLinkException(
                'El monto esta fuera del rango permitido.',
                422,
                'payphone_tool_total_overflow'
            );
        }

        $totalCents = $baseCents + $surchargeCents;
        $reference = $this->normalizeReference($reference);
        $components = $this->taxComponents($baseCents, $surchargeCents, $totalCents);
        $config = $this->configService->get('payphone');
        $currency = strtoupper(trim((string) ($config['currency'] ?? '')));

        if (!preg_match('/\A[A-Z]{3}\z/', $currency)) {
            throw new PayphoneLinkException(
                'PayPhone no esta configurado correctamente.',
                503,
                'payphone_currency_invalid'
            );
        }

        $clientTransactionId = $this->uniqueClientTransactionId();
        $expireHours = max(1, (int) config('payments.payphone.tool.link_expire_hours', 24));
        $expiresAt = now()->addHours($expireHours);

        $payload = array_merge([
            'amount' => $totalCents,
            'currency' => $currency,
            'clientTransactionId' => $clientTransactionId,
            'storeId' => (string) ($config['store_id'] ?? $config['merchant_id'] ?? ''),
            'reference' => $reference,
            'oneTime' => true,
            'expireIn' => $expireHours,
            'isAmountEditable' => false,
        ], $components);

        $this->assertComponentsInvariant($totalCents, $components);

        $paymentUrl = $this->client->create($payload);

        PayphoneToolLink::query()->create([
            'client_transaction_id' => $clientTransactionId,
            'base_amount_cents' => $baseCents,
            'surcharge_percent' => $modality['surcharge_percent'],
            'surcharge_amount_cents' => $surchargeCents,
            'total_amount_cents' => $totalCents,
            'currency' => $currency,
            'reference' => $reference,
            'payment_url' => $paymentUrl,
            'status' => 'GENERATED',
            'link_expires_at' => $expiresAt,
            'requester_ip' => $request->ip(),
        ]);

        return [
            'base_amount_cents' => $baseCents,
            'surcharge_amount_cents' => $surchargeCents,
            'total_amount_cents' => $totalCents,
            'surcharge_percent' => $modality['surcharge_percent'],
            'modalidad' => $modality['key'],
            'modalidad_label' => $modality['label'],
            'months' => $modality['months'],
            'installments' => $this->installmentCents($totalCents, $modality['months']),
            'minimum_amount_cents' => $modality['min_amount_cents'],
            'reference' => $reference,
            'payment_url' => $paymentUrl,
            'link_expires_at' => $expiresAt,
            'base_display' => AmountInCents::format($baseCents),
            'surcharge_display' => AmountInCents::format($surchargeCents),
            'total_display' => AmountInCents::format($totalCents),
        ];
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    public function modalidadesParaUi(): array
    {
        $modalities = [];

        foreach (['habitual', 'sin_intereses_3', 'sin_intereses_6'] as $key) {
            $modality = $this->resolveModality($key);
            $modalities[$key] = [
                'key' => $modality['key'],
                'label' => $modality['label'],
                'months' => $modality['months'],
                'enabled' => $modality['enabled'],
                'pending_activation' => $modality['pending_activation'],
                'min_amount_cents' => $modality['min_amount_cents'],
                'surcharge_percent' => $modality['surcharge_percent'],
            ];
        }

        return $modalities;
    }

    /**
     * @return array{key:string,label:string,months:int,enabled:bool,pending_activation:bool,min_amount_cents:?int,surcharge_percent:string}
     */
    private function resolveModality(string $key): array
    {
        $configured = config("payments.payphone.tool.modalities.{$key}");

        if (!is_array($configured)) {
            throw new PayphoneLinkException(
                'La modalidad de cobro no esta configurada.',
                503,
                'payphone_tool_modality_invalid'
            );
        }

        $surchargePercent = $configured['uses_tool_surcharge_percent'] ?? false
            ? config('payments.payphone.tool.surcharge_percent', '7')
            : ($configured['surcharge_percent'] ?? null);

        if (!is_string($surchargePercent) && !is_int($surchargePercent) && !is_float($surchargePercent)) {
            throw new PayphoneLinkException(
                'La modalidad de cobro no esta configurada correctamente.',
                503,
                'payphone_tool_modality_percentage_invalid'
            );
        }

        return [
            'key' => $key,
            'label' => (string) ($configured['label'] ?? $key),
            'months' => max(1, (int) ($configured['months'] ?? 1)),
            'enabled' => (bool) ($configured['enabled'] ?? false),
            'pending_activation' => (bool) ($configured['pending_activation'] ?? false),
            'min_amount_cents' => isset($configured['min_amount_cents'])
                ? max(0, (int) $configured['min_amount_cents'])
                : null,
            'surcharge_percent' => (string) $surchargePercent,
        ];
    }

    /**
     * @return list<int>
     */
    private function installmentCents(int $totalCents, int $months): array
    {
        if ($months <= 1) {
            return [];
        }

        $baseInstallment = intdiv($totalCents, $months);
        $remainder = $totalCents % $months;
        $installments = [];

        for ($index = 0; $index < $months; $index++) {
            $installments[] = $baseInstallment + ($index === $months - 1 ? $remainder : 0);
        }

        return $installments;
    }

    private function taxComponents(int $baseCents, int $surchargeCents, int $totalCents): array
    {
        $mode = (string) config('payments.payphone.tool.tax_component_mode', 'unresolved');

        if ($mode !== 'no_tax_service_fee') {
            throw new PayphoneLinkException(
                'La clasificacion tributaria de este cobro aun no esta configurada.',
                503,
                $mode === 'unresolved'
                    ? 'payphone_tool_tax_policy_unresolved'
                    : 'payphone_tool_tax_policy_invalid'
            );
        }

        return [
            'amountWithoutTax' => $baseCents,
            'amountWithTax' => 0,
            'tax' => 0,
            'service' => $surchargeCents,
            'tip' => 0,
        ];
    }

    private function assertComponentsInvariant(int $amountCents, array $components): void
    {
        $sumCents = (int) ($components['amountWithoutTax'] ?? 0)
            + (int) ($components['amountWithTax'] ?? 0)
            + (int) ($components['tax'] ?? 0)
            + (int) ($components['service'] ?? 0)
            + (int) ($components['tip'] ?? 0);

        if ($amountCents !== $sumCents) {
            throw new PayphoneLinkException(
                'No se pudo preparar el cobro.',
                503,
                'payphone_tool_amount_components_mismatch'
            );
        }
    }

    private function normalizeReference(?string $reference): string
    {
        $reference = trim((string) $reference);

        if ($reference === '') {
            $reference = trim((string) config(
                'payments.payphone.tool.default_reference',
                'Compra BootyFitness'
            ));
        }

        $reference = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $reference) ?: '';
        $reference = preg_replace('/\s+/u', ' ', trim($reference)) ?: '';

        if ($reference === '' || mb_strlen($reference) > 100) {
            throw new PayphoneLinkException(
                'La referencia no es valida.',
                422,
                'payphone_tool_reference_invalid'
            );
        }

        return $reference;
    }

    private function uniqueClientTransactionId(): string
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $candidate = Str::upper(Str::random(15));

            if (!PayphoneToolLink::query()->where('client_transaction_id', $candidate)->exists()) {
                return $candidate;
            }
        }

        throw new PayphoneLinkException(
            'No se pudo preparar el cobro.',
            503,
            'payphone_tool_transaction_id_collision'
        );
    }
}
