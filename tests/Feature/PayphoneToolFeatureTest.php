<?php

namespace Tests\Feature;

use App\Models\PaymentGatewayConfig;
use App\Models\PayphoneToolLink;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PayphoneToolFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $this->createSchema();
        $this->configureTool();
        $this->clearAccessRateLimits();
    }

    public function test_access_page_is_public_and_does_not_require_normal_login(): void
    {
        $this->get(route('payphone-tool.index'))
            ->assertOk()
            ->assertSee('BOOTYFITNESS')
            ->assertSee('Codigo de acceso');
    }

    public function test_access_screen_keeps_real_code_field_and_exposes_accessible_numeric_keyboard(): void
    {
        $this->get(route('payphone-tool.index'))
            ->assertOk()
            ->assertSee('Ingresa tu código de acceso')
            ->assertSee('name="code"', false)
            ->assertSee('data-key="1"', false)
            ->assertSee('data-key="0"', false)
            ->assertSee('data-clear-key', false)
            ->assertSee('type="button"', false)
            ->assertSee('.payphone-access-screen', false)
            ->assertSee('grid-template-columns: repeat(3, minmax(0, 1fr));', false)
            ->assertSee('max-width: 252px;', false)
            ->assertSee('max-width: 390px;', false)
            ->assertSee('box-sizing: border-box;', false)
            ->assertSee('readonly', false)
            ->assertSee('inputmode="none"', false)
            ->assertSee('input.value +=', false)
            ->assertSee('input.value = input.value.slice(0, -1)', false)
            ->assertDontSee('input.focus()', false)
            ->assertSee("event.key === 'Enter'", false)
            ->assertSee('form.requestSubmit()', false);
    }

    public function test_correct_code_authorizes_only_the_tool_session(): void
    {
        $this->post(route('payphone-tool.access'), ['code' => 'access-test-code'])
            ->assertRedirect(route('payphone-tool.index'))
            ->assertSessionHas('payphone_tool_authorized', true)
            ->assertSessionHas('payphone_tool_authorized_at');
    }

    public function test_wrong_code_is_limited_after_five_failures_per_ip(): void
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post(route('payphone-tool.access'), ['code' => 'wrong-code'])
                ->assertRedirect(route('payphone-tool.index'))
                ->assertSessionHasErrors('code');
        }

        $this->post(route('payphone-tool.access'), ['code' => 'wrong-code'])
            ->assertStatus(429)
            ->assertSee('Demasiados intentos');
    }

    public function test_logout_removes_only_the_tool_authorization(): void
    {
        $this->withSession([
            'normal_session_value' => 'preserved',
            'payphone_tool_authorized' => true,
            'payphone_tool_authorized_at' => now()->timestamp,
        ])->post(route('payphone-tool.logout'))
            ->assertRedirect(route('payphone-tool.index'))
            ->assertSessionMissing('payphone_tool_authorized')
            ->assertSessionMissing('payphone_tool_authorized_at')
            ->assertSessionHas('normal_session_value', 'preserved');
    }

    public function test_expired_tool_session_cannot_open_generator(): void
    {
        $this->withSession([
            'payphone_tool_authorized' => true,
            'payphone_tool_authorized_at' => now()->subHours(9)->timestamp,
        ])->get(route('payphone-tool.index'))
            ->assertOk()
            ->assertSee('Codigo de acceso')
            ->assertSessionMissing('payphone_tool_authorized')
            ->assertSessionMissing('payphone_tool_authorized_at');
    }

    public function test_unauthorized_generation_does_not_call_payphone(): void
    {
        Http::preventStrayRequests();
        Http::fake();

        $this->post(route('payphone-tool.generate'), [
            'amount' => '60.00',
            'reference' => 'Compra',
        ])->assertRedirect(route('payphone-tool.index'));

        Http::assertNothingSent();
        $this->assertDatabaseCount('payphone_tool_links', 0);
    }

    public function test_tool_post_routes_use_web_without_normal_auth(): void
    {
        foreach (['payphone-tool.access', 'payphone-tool.logout', 'payphone-tool.generate'] as $routeName) {
            $middleware = app('router')->getRoutes()->getByName($routeName)->gatherMiddleware();

            $this->assertContains('web', $middleware);
            $this->assertNotContains('auth', $middleware);
        }
    }

    public function test_unresolved_tax_policy_blocks_real_client_call(): void
    {
        Config::set('payments.payphone.tool.tax_component_mode', 'unresolved');
        Http::preventStrayRequests();
        Http::fake();

        $this->authorizedSession()
            ->post(route('payphone-tool.generate'), [
                'amount' => '60.00',
                'reference' => 'Compra',
            ])
            ->assertRedirect(route('payphone-tool.index'))
            ->assertSessionHasErrors('amount');

        Http::assertNothingSent();
        $this->assertDatabaseCount('payphone_tool_links', 0);
    }

    public function test_invalid_tax_policy_mode_blocks_real_client_call(): void
    {
        Config::set('payments.payphone.tool.tax_component_mode', 'invalid-mode');
        Http::preventStrayRequests();
        Http::fake();

        $this->authorizedSession()
            ->post(route('payphone-tool.generate'), [
                'amount' => '60.00',
                'reference' => 'Compra',
            ])
            ->assertRedirect(route('payphone-tool.index'))
            ->assertSessionHasErrors('amount');

        Http::assertNothingSent();
        $this->assertDatabaseCount('payphone_tool_links', 0);
    }

    public function test_legacy_checkout_service_fee_mode_blocks_real_client_call(): void
    {
        Config::set('payments.payphone.tool.tax_component_mode', 'checkout_service_fee');
        Http::preventStrayRequests();
        Http::fake();

        $this->authorizedSession()
            ->post(route('payphone-tool.generate'), [
                'amount' => '60.00',
                'reference' => 'Compra',
            ])
            ->assertRedirect(route('payphone-tool.index'))
            ->assertSessionHasErrors('amount');

        Http::assertNothingSent();
        $this->assertDatabaseCount('payphone_tool_links', 0);
    }

    public function test_ten_dollar_payload_uses_amount_without_tax_and_service_fee(): void
    {
        Http::fake([
            'https://pay.test/api/Links' => Http::response('https://payp.page.link/ten-dollar', 200),
        ]);

        $response = $this->authorizedSession()
            ->post(route('payphone-tool.generate'), [
                'amount' => '10.00',
                'reference' => 'Compra BootyFitness',
            ]);

        $response->assertOk()
            ->assertSee('$10.00')
            ->assertSee('$0.70')
            ->assertSee('$10.70');

        Http::assertSent(function ($request) {
            foreach ([
                'amount',
                'amountWithoutTax',
                'amountWithTax',
                'tax',
                'service',
                'tip',
            ] as $field) {
                if (!is_int($request[$field])) {
                    return false;
                }
            }

            return $request->url() === 'https://pay.test/api/Links'
                && $request['amount'] === 1070
                && $request['amountWithoutTax'] === 1000
                && $request['amountWithTax'] === 0
                && $request['tax'] === 0
                && $request['service'] === 70
                && $request['tip'] === 0
                && $request['oneTime'] === true
                && $request['isAmountEditable'] === false
                && $request['amount'] === $request['amountWithoutTax']
                    + $request['amountWithTax']
                    + $request['tax']
                    + $request['service']
                    + $request['tip'];
        });
    }

    public function test_success_uses_integer_payload_and_records_generated_link(): void
    {
        Http::fake([
            'https://pay.test/api/Links' => Http::response('https://payp.page.link/test-link', 200),
        ]);

        $response = $this->authorizedSession()
            ->post(route('payphone-tool.generate'), [
                'amount' => '60.00',
                'reference' => 'Compra BootyFitness',
            ]);

        $response->assertOk()
            ->assertSee('LINK GENERADO')
            ->assertSee('$60.00')
            ->assertSee('$4.20')
            ->assertSee('$64.20')
            ->assertSee('https://payp.page.link/test-link')
            ->assertSee('COPIAR MENSAJE')
            ->assertSee('WhatsApp')
            ->assertDontSee('Regenerar con otro monto o modalidad')
            ->assertDontSee('REGENERAR LINK')
            ->assertDontSee('fake-token');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://pay.test/api/Links'
                && $request['amount'] === 6420
                && is_int($request['amount'])
                && is_int($request['amountWithoutTax'])
                && is_int($request['amountWithTax'])
                && is_int($request['tax'])
                && is_int($request['service'])
                && is_int($request['tip'])
                && $request['amountWithoutTax'] === 6000
                && $request['amountWithTax'] === 0
                && $request['tax'] === 0
                && $request['service'] === 420
                && $request['tip'] === 0
                && $request['oneTime'] === true
                && $request['isAmountEditable'] === false
                && $request['amount'] === $request['amountWithoutTax']
                    + $request['amountWithTax']
                    + $request['tax']
                    + $request['service']
                    + $request['tip']
                && strlen((string) $request['clientTransactionId']) <= 15;
        });

        $audit = PayphoneToolLink::query()->first();
        $this->assertNotNull($audit);
        $this->assertSame(6000, $audit->base_amount_cents);
        $this->assertSame(420, $audit->surcharge_amount_cents);
        $this->assertSame(6420, $audit->total_amount_cents);
        $this->assertSame('GENERATED', $audit->status);
        $this->assertSame('https://payp.page.link/test-link', $audit->payment_url);
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{15}$/', $audit->client_transaction_id);
    }

    public function test_initial_generator_keeps_the_generation_form(): void
    {
        $this->authorizedSession()
            ->get(route('payphone-tool.index'))
            ->assertOk()
            ->assertSee('GENERAR LINK DE PAGO')
            ->assertSee('data-generate-form', false)
            ->assertSee('data-modality', false)
            ->assertDontSee('REGENERAR LINK');
    }

    public function test_three_month_modality_reuses_seven_percent_and_distributes_installment_remainder(): void
    {
        Http::fake([
            'https://pay.test/api/Links' => Http::response('https://payp.page.link/three-months', 200),
        ]);

        $response = $this->authorizedSession()
            ->post(route('payphone-tool.generate'), [
                'amount' => '200.00',
                'modalidad' => 'sin_intereses_3',
                'reference' => 'Tres meses',
            ]);

        $response->assertOk()
            ->assertSee('3 meses sin intereses')
            ->assertSee('$200.00')
            ->assertSee('$14.00')
            ->assertSee('$214.00')
            ->assertSee('1: $71.33')
            ->assertSee('2: $71.33')
            ->assertSee('3: $71.34');

        Http::assertSent(function ($request) {
            return $request['amount'] === 21400
                && $request['amountWithoutTax'] === 20000
                && $request['service'] === 1400
                && is_int($request['amount'])
                && is_int($request['service'])
                && !array_key_exists('months', $request->data());
        });
    }

    public function test_six_month_modality_is_visible_but_blocked_until_payphone_activation(): void
    {
        Http::preventStrayRequests();
        Http::fake();

        $this->authorizedSession()
            ->get(route('payphone-tool.index'))
            ->assertOk()
            ->assertSee('6 meses sin intereses')
            ->assertSee('Pendiente de activar en PayPhone Business');

        $this->authorizedSession()
            ->post(route('payphone-tool.generate'), [
                'amount' => '450.00',
                'modalidad' => 'sin_intereses_6',
            ])
            ->assertRedirect(route('payphone-tool.index'))
            ->assertSessionHasErrors('amount');

        Http::assertNothingSent();
        $this->assertDatabaseCount('payphone_tool_links', 0);
    }

    public function test_six_month_modality_uses_independent_ninety_two_percent_rate_when_enabled(): void
    {
        Config::set('payments.payphone.tool.modalities.sin_intereses_6.enabled', true);
        Config::set('payments.payphone.tool.modalities.sin_intereses_6.pending_activation', false);
        Http::fake([
            'https://pay.test/api/Links' => Http::response('https://payp.page.link/six-months', 200),
        ]);

        $response = $this->authorizedSession()
            ->post(route('payphone-tool.generate'), [
                'amount' => '450.00',
                'modalidad' => 'sin_intereses_6',
            ]);

        $response->assertOk()
            ->assertSee('6 meses sin intereses')
            ->assertSee('$450.00')
            ->assertSee('$41.40')
            ->assertSee('$491.40')
            ->assertSee('1: $81.90')
            ->assertSee('6: $81.90');

        Http::assertSent(function ($request) {
            return $request['amount'] === 49140
                && $request['amountWithoutTax'] === 45000
                && $request['service'] === 4140
                && is_int($request['amount'])
                && is_int($request['service']);
        });
    }

    public function test_three_month_minimum_is_validated_on_server_using_base_amount(): void
    {
        Http::preventStrayRequests();
        Http::fake();

        $this->authorizedSession()
            ->post(route('payphone-tool.generate'), [
                'amount' => '199.99',
                'modalidad' => 'sin_intereses_3',
            ])
            ->assertRedirect(route('payphone-tool.index'))
            ->assertSessionHasErrors('amount');

        Http::assertNothingSent();
        $this->assertDatabaseCount('payphone_tool_links', 0);
    }

    public function test_browser_cannot_override_server_rate_or_total(): void
    {
        Http::fake([
            'https://pay.test/api/Links' => Http::response('https://payp.page.link/server-calculation', 200),
        ]);

        $this->authorizedSession()
            ->post(route('payphone-tool.generate'), [
                'amount' => '60.00',
                'modalidad' => 'habitual',
                'surcharge_percent' => '0',
                'total' => '1.00',
            ])
            ->assertOk()
            ->assertSee('$64.20');

        Http::assertSent(function ($request) {
            return $request['amount'] === 6420
                && $request['amountWithoutTax'] === 6000
                && $request['service'] === 420;
        });
    }

    public function test_component_invariant_rejects_a_mismatched_sum_without_http_call(): void
    {
        $service = app(\App\Services\Pagos\PayphoneLinkService::class);
        $method = new \ReflectionMethod($service, 'assertComponentsInvariant');
        $method->setAccessible(true);

        Http::preventStrayRequests();
        Http::fake();

        try {
            $method->invoke($service, 6420, [
                'amountWithoutTax' => 0,
                'amountWithTax' => 6000,
                'tax' => 0,
                'service' => 419,
                'tip' => 0,
            ]);

            $this->fail('Se esperaba el rechazo de una suma de componentes inconsistente.');
        } catch (\App\Services\Pagos\PayphoneLinkException $exception) {
            $this->assertSame('payphone_tool_amount_components_mismatch', $exception->errorCode);
        }

        Http::assertNothingSent();
    }

    public function test_public_service_result_does_not_contain_payphone_token(): void
    {
        Http::fake([
            'https://pay.test/api/Links' => Http::response('https://payp.page.link/public-result', 200),
        ]);

        $result = app(\App\Services\Pagos\PayphoneLinkService::class)->generate(
            '60.00',
            'Compra',
            \Illuminate\Http\Request::create('/cobros', 'POST')
        );

        $publicJson = json_encode($result, JSON_THROW_ON_ERROR);

        $this->assertArrayNotHasKey('token', $result);
        $this->assertStringNotContainsString('fake-token', $publicJson);
    }

    public function test_reference_over_one_hundred_characters_is_rejected_without_call(): void
    {
        Http::preventStrayRequests();
        Http::fake();

        $this->authorizedSession()
            ->post(route('payphone-tool.generate'), [
                'amount' => '60.00',
                'reference' => str_repeat('x', 101),
            ])
            ->assertSessionHasErrors('reference');

        Http::assertNothingSent();
        $this->assertDatabaseCount('payphone_tool_links', 0);
    }

    public function test_three_decimals_and_zero_are_rejected(): void
    {
        Http::preventStrayRequests();
        Http::fake();

        $this->authorizedSession()
            ->post(route('payphone-tool.generate'), ['amount' => '60.001'])
            ->assertSessionHasErrors('amount');

        $this->authorizedSession()
            ->post(route('payphone-tool.generate'), ['amount' => '0'])
            ->assertRedirect(route('payphone-tool.index'))
            ->assertSessionHasErrors('amount');

        Http::assertNothingSent();
    }

    public function test_amount_above_configured_maximum_is_rejected(): void
    {
        Http::preventStrayRequests();
        Http::fake();

        $this->authorizedSession()
            ->post(route('payphone-tool.generate'), ['amount' => '10000.01'])
            ->assertRedirect(route('payphone-tool.index'))
            ->assertSessionHasErrors('amount');

        Http::assertNothingSent();
        $this->assertDatabaseCount('payphone_tool_links', 0);
    }

    public function test_payphone_timeout_does_not_create_audit_record(): void
    {
        Http::fake([
            'https://pay.test/api/Links' => Http::failedConnection(),
        ]);

        $this->authorizedSession()
            ->post(route('payphone-tool.generate'), ['amount' => '60.00'])
            ->assertRedirect(route('payphone-tool.index'))
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('payphone_tool_links', 0);
    }

    public function test_payphone_error_responses_do_not_create_audit_record(): void
    {
        Log::spy();
        Http::fake([
            'https://pay.test/api/Links' => Http::response([
                'message' => 'Validaciones fallidas',
                'errorCode' => 800,
                'errors' => [[
                    'message' => 'AmountWithTax',
                    'errorCode' => 0,
                    'errorDescriptions' => [
                        'El campo amountWithTax requiere tax.',
                        'Authorization: leaked-token',
                        'storeId: leaked-store',
                    ],
                ]],
            ], 400),
        ]);

        $this->authorizedSession()
            ->post(route('payphone-tool.generate'), ['amount' => '60.00'])
            ->assertRedirect(route('payphone-tool.index'))
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('payphone_tool_links', 0);

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(function (string $event, array $context): bool {
                $serialized = json_encode($context, JSON_THROW_ON_ERROR);

                return $event === 'payphone_tool_generation_rejected'
                    && $context['http_status'] === 400
                    && $context['provider_error_code'] === 800
                    && $context['provider_message'] === 'Validaciones fallidas'
                    && str_contains($serialized, 'El campo amountWithTax requiere tax.')
                    && !str_contains($serialized, 'leaked-token')
                    && !str_contains($serialized, 'leaked-store')
                    && !array_key_exists('payload', $context);
            });
    }

    public function test_payphone_rate_limit_response_does_not_create_audit_record(): void
    {
        Http::fake([
            'https://pay.test/api/Links' => Http::response(['message' => 'rate limited'], 429),
        ]);

        $this->authorizedSession()
            ->post(route('payphone-tool.generate'), ['amount' => '60.00'])
            ->assertRedirect(route('payphone-tool.index'))
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('payphone_tool_links', 0);
    }

    public function test_missing_access_hash_is_a_controlled_configuration_error(): void
    {
        Config::set('payments.payphone.tool.access_hash', null);

        $this->post(route('payphone-tool.access'), ['code' => 'anything'])
            ->assertStatus(503)
            ->assertSee('La herramienta no esta disponible')
            ->assertDontSee('anything');
    }

    private function configureTool(): void
    {
        Config::set('payments.payphone.tool.access_hash', Hash::make('access-test-code'));
        Config::set('payments.payphone.tool.max_amount_cents', 1000000);
        Config::set('payments.payphone.tool.tax_component_mode', 'no_tax_service_fee');

        PaymentGatewayConfig::query()->create([
            'gateway' => 'payphone',
            'environment' => 'production',
            'is_active' => true,
            'base_url' => 'https://pay.test/api',
            'secret_key' => 'fake-token',
            'merchant_id' => 'fake-store',
            'currency' => 'USD',
            'settings' => [],
        ]);
    }

    private function authorizedSession(): self
    {
        return $this->withSession([
            'payphone_tool_authorized' => true,
            'payphone_tool_authorized_at' => now()->timestamp,
        ]);
    }

    private function clearAccessRateLimits(): void
    {
        RateLimiter::clear('payphone-tool:access:minute:127.0.0.1');
        RateLimiter::clear('payphone-tool:access:hour:127.0.0.1');
    }

    private function createSchema(): void
    {
        Schema::create('payment_gateway_configs', function (Blueprint $table) {
            $table->id();
            $table->string('gateway', 30)->unique();
            $table->string('environment', 20)->default('sandbox');
            $table->boolean('is_active')->default(false);
            $table->string('base_url')->nullable();
            $table->string('public_key')->nullable();
            $table->text('secret_key')->nullable();
            $table->string('merchant_id', 120)->nullable();
            $table->string('currency', 10)->default('USD');
            $table->string('webhook_id', 180)->nullable();
            $table->string('webhook_secret', 200)->nullable();
            $table->string('verify_path', 120)->nullable();
            $table->boolean('strict_webhook')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('payphone_tool_links', function (Blueprint $table) {
            $table->id();
            $table->string('client_transaction_id', 15)->unique();
            $table->unsignedBigInteger('base_amount_cents');
            $table->decimal('surcharge_percent', 5, 2);
            $table->unsignedBigInteger('surcharge_amount_cents');
            $table->unsignedBigInteger('total_amount_cents');
            $table->char('currency', 3);
            $table->string('reference', 100);
            $table->text('payment_url');
            $table->string('status', 20)->default('GENERATED');
            $table->timestamp('link_expires_at')->nullable();
            $table->string('requester_ip', 64)->nullable();
            $table->timestamps();
        });
    }
}
