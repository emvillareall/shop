@extends('layouts.app')

@section('template_title')
    Configuracion de pasarelas
@endsection

@section('content')
    <div class="space-y-5">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0 pl-4">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card overflow-hidden rounded-2xl border border-slate-200 shadow-sm">
            <div class="card-header bg-white px-5 py-4">
                <h3 class="mb-0 text-lg font-semibold text-slate-900">Configuracion de pasarelas de pago</h3>
            </div>
            <div class="card-body bg-slate-50/40 px-5 py-4">
                <p class="text-sm leading-relaxed text-slate-600">
                    Desde aqui configuras PayPhone y PayPal para checkout ecommerce. El sistema no guarda datos de tarjeta;
                    solo credenciales tecnicas de integracion y webhooks.
                </p>
            </div>
        </div>

        <div class="grid gap-5 lg:grid-cols-2">
            <form action="{{ route('pagos.configuracion.update', 'payphone') }}" method="POST" class="card flex h-full flex-col overflow-hidden rounded-2xl border border-slate-200 shadow-sm">
                @csrf
                <div class="card-header flex items-center justify-between bg-white px-5 py-4">
                    <span class="text-lg font-semibold text-slate-900">PayPhone</span>
                    <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
                        <input type="checkbox" name="is_active" value="1" @checked($payphone['is_active'] ?? false)>
                        Activo
                    </label>
                </div>
                <div class="card-body flex-1 space-y-4 bg-white px-5 py-5">
                    <div class="grid gap-3 md:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Entorno</label>
                            <select name="environment" class="form-control">
                                <option value="sandbox" @selected(($payphone['environment'] ?? '') === 'sandbox')>Sandbox</option>
                                <option value="production" @selected(($payphone['environment'] ?? '') === 'production')>Produccion</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Moneda</label>
                            <input class="form-control" name="currency" value="{{ $payphone['currency'] ?? 'USD' }}" placeholder="USD">
                        </div>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Base URL API</label>
                        <input class="form-control" name="base_url" value="{{ $payphone['base_url'] ?? '' }}" placeholder="https://...">
                    </div>
                    <div class="grid gap-3 md:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Store ID (PayPhone)</label>
                            <input class="form-control" name="store_id" value="{{ $payphone['store_id'] ?? ($payphone['merchant_id'] ?? '') }}" placeholder="Ej: 0200898385001">
                            <p class="mt-1 text-xs text-slate-500">
                                Usa el <strong>Store ID</strong> de PayPhone Developer (no el Client ID ni la clave secreta).
                            </p>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Token secreto</label>
                            <input class="form-control" name="secret_key" placeholder="Dejar vacio para mantener actual">
                        </div>
                    </div>
                    <div class="grid gap-3 md:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Webhook secret</label>
                            <input class="form-control" name="webhook_secret" value="{{ $payphone['webhook_secret'] ?? '' }}">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Ruta de verificacion</label>
                            <input class="form-control" name="verify_path" value="{{ $payphone['verify_path'] ?? '/sale/{id}' }}">
                        </div>
                    </div>
                    <label class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                        <input type="checkbox" name="strict_webhook" value="1" @checked($payphone['strict_webhook'] ?? true)>
                        Validacion estricta de webhook
                    </label>
                </div>
                <div class="card-footer mt-auto border-t border-slate-200 bg-slate-50 px-5 py-4">
                    <button type="submit" class="btn btn-primary w-full rounded-xl py-2.5 text-base font-semibold">Guardar PayPhone</button>
                </div>
            </form>

            <form action="{{ route('pagos.configuracion.update', 'paypal') }}" method="POST" class="card flex h-full flex-col overflow-hidden rounded-2xl border border-slate-200 shadow-sm">
                @csrf
                <div class="card-header flex items-center justify-between bg-white px-5 py-4">
                    <span class="text-lg font-semibold text-slate-900">PayPal</span>
                    <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
                        <input type="checkbox" name="is_active" value="1" @checked($paypal['is_active'] ?? false)>
                        Activo
                    </label>
                </div>
                <div class="card-body flex-1 space-y-4 bg-white px-5 py-5">
                    <div class="grid gap-3 md:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Entorno</label>
                            <select name="environment" class="form-control">
                                <option value="sandbox" @selected(($paypal['environment'] ?? '') === 'sandbox')>Sandbox</option>
                                <option value="production" @selected(($paypal['environment'] ?? '') === 'production')>Produccion</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Webhook ID</label>
                            <input class="form-control" name="webhook_id" value="{{ $paypal['webhook_id'] ?? '' }}">
                        </div>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Base URL API</label>
                        <input class="form-control" name="base_url" value="{{ $paypal['base_url'] ?? '' }}" placeholder="https://api-m.sandbox.paypal.com">
                    </div>
                    <div class="grid gap-3 md:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Client ID</label>
                            <input class="form-control" name="public_key" value="{{ $paypal['public_key'] ?? '' }}">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Client Secret</label>
                            <input class="form-control" name="secret_key" placeholder="Dejar vacio para mantener actual">
                        </div>
                    </div>
                    <label class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                        <input type="checkbox" name="strict_webhook" value="1" @checked($paypal['strict_webhook'] ?? true)>
                        Validacion estricta de webhook
                    </label>
                </div>
                <div class="card-footer mt-auto border-t border-slate-200 bg-slate-50 px-5 py-4">
                    <button type="submit" class="btn btn-primary w-full rounded-xl py-2.5 text-base font-semibold">Guardar PayPal</button>
                </div>
            </form>
        </div>
    </div>
@endsection
