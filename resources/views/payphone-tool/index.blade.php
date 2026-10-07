@extends('layouts.payphone-tool', ['title' => 'Cobros PayPhone | Booty Fitness'])

@section('content')
    @php($hasResult = is_array($result ?? null))

    <div
        data-payphone-tool
        data-has-result="{{ $hasResult ? '1' : '0' }}"
        data-modalities='@json($modalidades)'
        class="space-y-4"
    >
        <section class="rounded-3xl border border-brand-100 bg-white p-4 shadow-xl shadow-brand-900/10 sm:p-6">
            <div class="flex flex-wrap items-start justify-between gap-3 sm:flex-nowrap sm:gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.22em] text-brand-600">BOOTY FITNESS</p>
                    <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900">Cobro rápido</h1>
                    <p class="mt-1 text-sm text-slate-500">Genera un link PayPhone de un solo uso.</p>
                </div>

                <form action="{{ route('payphone-tool.logout') }}" method="POST" class="ml-auto">
                    @csrf
                    <button type="submit" class="rounded-xl border border-slate-200 px-2.5 py-1.5 text-[11px] font-bold text-slate-500 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-700">
                        CERRAR ACCESO
                    </button>
                </form>
            </div>

            @if($errors->any())
                <div class="mt-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            @if(!$hasResult)
                <form action="{{ route('payphone-tool.generate') }}" method="POST" class="mt-5 space-y-4" data-generate-form>
                    @csrf
                    @include('payphone-tool.partials.generator-form', [
                        'formId' => 'amount',
                        'amountValue' => old('amount'),
                        'referenceValue' => old('reference', $defaultReference),
                        'selectedModality' => old('modalidad', 'habitual'),
                        'buttonText' => 'GENERAR LINK DE PAGO',
                    ])
                </form>
            @else
                <div class="mt-7 space-y-4">
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-4 text-emerald-800">
                        <p class="font-bold">✓ LINK GENERADO</p>
                        <div class="mt-3 space-y-1 text-sm">
                            <p>Valor base: <strong>${{ $result['base_display'] }}</strong></p>
                            <p>Modalidad: <strong>{{ $result['modalidad_label'] }}</strong></p>
                            <p>Recargo {{ $result['surcharge_percent'] }}%: <strong>${{ $result['surcharge_display'] }}</strong></p>
                            <p>Total: <strong>${{ $result['total_display'] }}</strong></p>
                            @if(($result['months'] ?? 1) > 1)
                                <p>Cuotas estimadas:
                                    <strong>
                                        @foreach($result['installments'] as $index => $installment)
                                            {{ $index + 1 }}: ${{ \App\Support\Money\AmountInCents::format($installment) }}{{ !$loop->last ? ' · ' : '' }}
                                        @endforeach
                                    </strong>
                                </p>
                            @endif
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="mb-2 text-xs font-bold uppercase tracking-wide text-slate-500">Link de pago</p>
                        <label for="payment-link" class="sr-only">Link de pago generado</label>
                        <input
                            id="payment-link"
                            type="text"
                            readonly
                            value="{{ $result['payment_url'] }}"
                            data-payment-link-input
                            aria-label="Link de pago generado"
                            autocomplete="off"
                            spellcheck="false"
                            class="block w-full min-w-0 rounded-xl border border-slate-300 bg-white px-3 py-3 text-sm font-semibold text-brand-700 outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100"
                        >
                    </div>

                    <div class="grid grid-cols-1 gap-3 min-[420px]:grid-cols-2">
                        <button type="button" data-copy-message aria-live="polite" class="inline-flex items-center justify-center rounded-2xl border border-brand-200 bg-white px-4 py-3 text-sm font-bold text-brand-800 transition hover:bg-brand-50">
                            COPIAR MENSAJE
                        </button>
                    </div>

                    <div class="flex flex-wrap items-center justify-center gap-3" aria-label="Opciones para compartir">
                        <a data-whatsapp-link target="_blank" rel="noopener noreferrer" href="#" class="inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-bold shadow-sm transition hover:brightness-95 focus:outline-none focus:ring-4 focus:ring-emerald-200" style="min-width: 160px; max-width: 205px; min-height: 44px; flex: 1 1 160px; background-color: #16a34a; border: 1px solid #15803d; color: #ffffff;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path fill="#ffffff" d="M12 2.25a9.75 9.75 0 0 0-8.4 14.7L2.3 21.7l4.9-1.28A9.75 9.75 0 1 0 12 2.25Zm0 17.8c-1.56 0-3.08-.42-4.4-1.22l-.31-.19-2.91.76.78-2.83-.2-.32A8.2 8.2 0 1 1 12 20.05Z" /><path fill="#ffffff" d="M16.63 13.9c-.25-.13-1.47-.72-1.7-.8-.23-.09-.4-.13-.57.13-.17.25-.65.8-.8.97-.15.17-.3.19-.56.06-.25-.13-1.06-.39-2.02-1.25-.75-.67-1.25-1.5-1.4-1.75-.14-.25-.01-.39.11-.52.11-.11.25-.29.38-.44.12-.15.16-.25.24-.42.08-.17.04-.32-.02-.45-.06-.13-.57-1.37-.78-1.88-.2-.49-.41-.42-.57-.43h-.49c-.17 0-.44.06-.67.32-.23.25-.88.86-.88 2.1s.9 2.44 1.02 2.61c.13.17 1.77 2.7 4.29 3.79.6.26 1.06.42 1.42.54.6.19 1.15.16 1.58.1.48-.07 1.47-.6 1.67-1.18.21-.58.21-1.08.15-1.18-.06-.1-.23-.16-.48-.29Z" /></svg>
                            <span>WhatsApp</span>
                        </a>

                        <button type="button" data-instagram-share aria-label="Compartir en Instagram" title="Compartir en Instagram" class="inline-flex items-center justify-center rounded-xl shadow-sm transition hover:brightness-105 focus:outline-none focus:ring-4 focus:ring-pink-200" style="width: 44px; height: 44px; background-color: #ffffff; border: 1px solid #e5e7eb;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><defs><linearGradient id="instagram-icon-gradient" x1="4" y1="20" x2="20" y2="4" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#feda75" /><stop offset="0.5" stop-color="#d62976" /><stop offset="1" stop-color="#4f5bd5" /></linearGradient></defs><rect width="16" height="16" x="4" y="4" rx="4" stroke="url(#instagram-icon-gradient)" stroke-width="1.8" /><circle cx="12" cy="12" r="3.5" stroke="url(#instagram-icon-gradient)" stroke-width="1.8" /><circle cx="17.5" cy="6.5" r="1" fill="#d62976" /></svg>
                        </button>
                        <a data-facebook-link target="_blank" rel="noopener noreferrer" href="#" aria-label="Compartir en Facebook" title="Compartir en Facebook" class="inline-flex items-center justify-center rounded-xl shadow-sm transition hover:brightness-95 focus:outline-none focus:ring-4 focus:ring-blue-200" style="width: 44px; height: 44px; background-color: #1877f2; border: 1px solid #166fe5;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path fill="#ffffff" d="M13.5 21v-8h2.75l.5-3h-3.25V8.05c0-.87.24-1.55 1.6-1.55h1.8V3.82A24.2 24.2 0 0 0 14.8 3.7c-2.6 0-4.4 1.59-4.4 4.52V10H7.5v3h2.9v8h3.1Z" /></svg>
                        </a>
                    </div>

                    <p data-copy-feedback class="hidden text-center text-xs leading-5 text-rose-700" role="status" aria-live="polite"></p>

                    <a href="{{ route('payphone-tool.index') }}" class="inline-flex w-full items-center justify-center rounded-2xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                        GENERAR OTRO LINK
                    </a>
                </div>
            @endif

            <details class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <summary class="cursor-pointer list-none text-sm font-bold text-slate-800">Consultar tarjetas y diferidos</summary>
                <div class="mt-4 space-y-5 text-sm leading-6 text-slate-600">
                    <section>
                        <h2 class="font-bold text-slate-900">Pago corriente</h2>
                        <p>Visa, Mastercard, Diners Club y Discover, de crédito o débito, nacionales e internacionales, sujetas a aprobación de PayPhone. American Express no figura como aceptada en la documentación consultada.</p>
                    </section>

                    <section>
                        <h2 class="font-bold text-slate-900">Sin intereses</h2>
                        <p class="mb-2">Estas combinaciones corresponden a la configuración mostrada por el comercio; no generalizan el beneficio a todas las tarjetas de cada banco:</p>
                        <ul class="space-y-1">
                            <li><strong>Banco Comercial Manabí:</strong> Visa.</li>
                            <li><strong>Banco Diners Club del Ecuador, S.A.:</strong> Diners Club International, Discover, Mastercard y Visa.</li>
                            <li><strong>Banco Pichincha (PA):</strong> Diners Club International.</li>
                            <li><strong>BGR:</strong> Visa.</li>
                            <li><strong>Cooperativa Andalucía:</strong> Visa.</li>
                            <li><strong>Interdin:</strong> Diners Club International, Discover, Mastercard y Visa.</li>
                            <li><strong>Loja:</strong> Visa.</li>
                            <li><strong>Pichincha:</strong> Discover, Mastercard y Visa.</li>
                            <li><strong>Rumiñahui:</strong> Visa.</li>
                        </ul>
                        <p class="mt-2 text-xs text-slate-500">Referencia: captura del comercio, 06/10/2026.</p>
                    </section>

                    <section>
                        <h2 class="font-bold text-slate-900">Diferidos con intereses</h2>
                        <p>Es un beneficio diferente: desde $50, a 3, 6, 9 o 12 meses según la tarjeta y el banco. El interés lo determina el banco. <a class="font-semibold text-brand-700 underline" href="https://help.payphone.app/hc/es/articles/31454433855899--Los-cobros-que-hago-con-Payphone-se-pueden-diferir" target="_blank" rel="noopener noreferrer">Consultar la guía oficial de bancos compatibles</a>.</p>
                    </section>

                    <section>
                        <h2 class="font-bold text-slate-900">Pago corriente sin diferimiento</h2>
                        <p>Las tarjetas de débito y extranjeras no califican para estas cuotas. En la guía de diferidos con intereses se publican como exclusiones Banco del Pacífico, Banco Guayaquil y Titanium. Esto significa que no permiten diferir bajo ese programa, no que rechacen el pago corriente; como el catálogo sin intereses puede diferir, verifica la tarjeta en PayPhone.</p>
                    </section>
                </div>
            </details>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const root = document.querySelector('[data-payphone-tool]');
            if (!root) return;

            let modalities = {};
            try { modalities = JSON.parse(root.dataset.modalities || '{}'); } catch (error) { modalities = {}; }

            const parseCents = (value) => {
                const normalized = String(value || '').trim().replace(',', '.');
                if (!/^\d+(?:\.\d{1,2})?$/.test(normalized)) return null;
                const [whole, fraction = ''] = normalized.split('.');
                return BigInt(whole) * 100n + BigInt((fraction + '00').slice(0, 2));
            };

            const parsePercentHundredths = (value) => {
                const normalized = String(value || '').trim().replace(',', '.');
                if (!/^\d{1,3}(?:\.\d{1,2})?$/.test(normalized)) return null;
                const [whole, fraction = ''] = normalized.split('.');
                return BigInt(whole) * 100n + BigInt((fraction + '00').slice(0, 2));
            };

            const formatCents = (cents) => `${cents / 100n}.${String(cents % 100n).padStart(2, '0')}`;
            const copyButton = root.querySelector('[data-copy-message]');
            const whatsappLink = root.querySelector('[data-whatsapp-link]');
            const instagramButton = root.querySelector('[data-instagram-share]');
            const facebookLink = root.querySelector('[data-facebook-link]');
            const paymentUrl = String(@json($result['payment_url'] ?? '') || '').trim();
            const total = String(@json($result['total_display'] ?? '') || '').trim();
            let copyFeedbackTimer;
            const copyFeedback = root.querySelector('[data-copy-feedback]');

            const calculate = (base, modality) => {
                const rate = parsePercentHundredths(modality?.surcharge_percent);
                if (rate === null) return null;
                const surcharge = (base * rate + 5000n) / 10000n;
                const totalCents = base + surcharge;
                const months = Number(modality.months || 1);
                const installments = [];
                if (months > 1) {
                    const installment = totalCents / BigInt(months);
                    const remainder = totalCents % BigInt(months);
                    for (let index = 0; index < months; index++) {
                        installments.push(installment + (index === months - 1 ? remainder : 0n));
                    }
                }
                return { surcharge, total: totalCents, installments };
            };

            const updatePreview = (form) => {
                const amountInput = form.querySelector('[data-amount]');
                const modalityInput = form.querySelector('[data-modality]');
                const baseDisplay = form.querySelector('[data-base-display]');
                const surchargeDisplay = form.querySelector('[data-surcharge-display]');
                const totalDisplay = form.querySelector('[data-total-display]');
                const percentLabel = form.querySelector('[data-percent-label]');
                const modalityLabel = form.querySelector('[data-modality-display]');
                const installmentBlock = form.querySelector('[data-installments-block]');
                const installmentDisplay = form.querySelector('[data-installments-display]');
                const notice = form.querySelector('[data-modality-notice]');
                const minimum = form.querySelector('[data-minimum-display]');
                const submitButton = form.querySelector('[data-submit-button]');
                const modality = modalities[modalityInput?.value] || modalities.habitual;
                const base = parseCents(amountInput?.value);
                const calculation = base === null ? null : calculate(base, modality);
                const minCents = modality?.min_amount_cents === null || modality?.min_amount_cents === undefined ? null : BigInt(modality.min_amount_cents);
                const belowMinimum = calculation !== null && minCents !== null && base < minCents;
                const pending = modality?.pending_activation === true || modality?.enabled !== true;

                if (baseDisplay) baseDisplay.textContent = base === null ? '0.00' : formatCents(base);
                if (surchargeDisplay) surchargeDisplay.textContent = calculation === null ? '0.00' : formatCents(calculation.surcharge);
                if (totalDisplay) totalDisplay.textContent = calculation === null ? '0.00' : formatCents(calculation.total);
                if (percentLabel) percentLabel.textContent = modality?.surcharge_percent || '';
                if (modalityLabel) modalityLabel.textContent = modality?.label || '';
                if (minimum) minimum.textContent = minCents === null ? '—' : `$${formatCents(minCents)}`;

                if (installmentBlock && installmentDisplay) {
                    installmentBlock.classList.toggle('hidden', calculation === null || calculation.installments.length === 0);
                    installmentDisplay.textContent = calculation?.installments.map((value, index) => `${index + 1}: $${formatCents(value)}`).join(' · ') || '';
                }

                if (notice) {
                    let noticeText = 'El cliente debe seleccionar el plazo en PayPhone. Su disponibilidad depende de la tarjeta y de la activación del comercio.';
                    let noticeClass = 'mt-1.5 text-xs leading-5 text-slate-500';
                    if (modality?.pending_activation === true || modality?.enabled !== true) {
                        noticeText = 'Pendiente de activar en PayPhone Business. Puedes consultar el cálculo, pero no se puede generar el link bajo esta modalidad.';
                        noticeClass = 'mt-1.5 text-xs leading-5 text-amber-700';
                    } else if (belowMinimum) {
                        noticeText = `Esta modalidad requiere una compra mínima de $${formatCents(minCents)} usando el monto base.`;
                        noticeClass = 'mt-1.5 text-xs leading-5 text-amber-700';
                    }
                    notice.textContent = noticeText;
                    notice.className = noticeClass;
                }

                if (submitButton) submitButton.disabled = calculation === null || belowMinimum || pending;
            };

            const forms = [...root.querySelectorAll('[data-generate-form]')];
            forms.forEach((form) => {
                form.querySelector('[data-amount]')?.addEventListener('input', () => {
                    updatePreview(form);
                });
                form.querySelector('[data-modality]')?.addEventListener('change', () => {
                    updatePreview(form);
                });
                updatePreview(form);
                form.addEventListener('submit', () => {
                    const submitButton = form.querySelector('[data-submit-button]');
                    if (submitButton) {
                        submitButton.disabled = true;
                        submitButton.textContent = 'GENERANDO...';
                    }
                });
            });

            const buildShareMessage = () => ['Hola 👋', '', 'Te compartimos tu enlace de pago de BootyFitness.', '', `Total a pagar: $${total}`, '', 'Puedes realizar tu pago mediante PayPhone aquí:', paymentUrl, '', 'Gracias por tu compra 💜'].join('\n');
            if (whatsappLink && paymentUrl) whatsappLink.href = `https://wa.me/?text=${encodeURIComponent(buildShareMessage())}`;
            if (facebookLink && paymentUrl) facebookLink.href = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(paymentUrl)}`;

            const showCopyFeedback = (buttonText, message = '', delay = 1800) => {
                window.clearTimeout(copyFeedbackTimer);
                if (copyButton) copyButton.textContent = buttonText;
                if (copyFeedback) {
                    copyFeedback.textContent = message;
                    copyFeedback.classList.toggle('hidden', message === '');
                }
                copyFeedbackTimer = window.setTimeout(() => {
                    if (copyButton) copyButton.textContent = 'COPIAR MENSAJE';
                    if (copyFeedback) {
                        copyFeedback.textContent = '';
                        copyFeedback.classList.add('hidden');
                    }
                }, delay);
            };

            const copyText = async (text) => {
                const copyTarget = document.createElement('textarea');
                copyTarget.value = text;
                copyTarget.setAttribute('readonly', '');
                copyTarget.style.position = 'fixed';
                copyTarget.style.top = '0';
                copyTarget.style.left = '-9999px';
                document.body.appendChild(copyTarget);
                copyTarget.focus();
                copyTarget.select();
                copyTarget.setSelectionRange(0, copyTarget.value.length);
                let copied = false;
                try { copied = document.execCommand('copy'); } catch (error) { copied = false; }
                copyTarget.remove();
                if (copied === true) return true;
                if (window.isSecureContext === true && navigator.clipboard?.writeText) {
                    try { await navigator.clipboard.writeText(text); return true; } catch (error) { return false; }
                }
                return false;
            };

            copyButton?.addEventListener('click', async (event) => {
                event.preventDefault();
                if (!paymentUrl) {
                    showCopyFeedback('COPIAR MENSAJE', 'No hay un enlace disponible para compartir.', 2500);
                    return;
                }
                if (await copyText(buildShareMessage())) {
                    showCopyFeedback('✓ MENSAJE COPIADO');
                    return;
                }
                showCopyFeedback('COPIAR MENSAJE', 'No se pudo copiar el mensaje. Inténtalo nuevamente.', 2500);
            });

            instagramButton?.addEventListener('click', async (event) => {
                event.preventDefault();
                if (!paymentUrl) {
                    showCopyFeedback('COPIAR MENSAJE', 'No hay un enlace disponible para compartir.', 2500);
                    return;
                }
                const message = buildShareMessage();
                if (typeof navigator.share === 'function') {
                    try { await navigator.share({ text: message }); } catch (error) {
                        if (error?.name !== 'AbortError') showCopyFeedback('COPIAR MENSAJE', 'No se pudo abrir la hoja de compartir.', 2500);
                    }
                    return;
                }
                if (await copyText(message)) {
                    showCopyFeedback('COPIAR MENSAJE', 'Mensaje copiado. Ábrelo en Instagram para compartirlo.', 5000);
                    return;
                }
                showCopyFeedback('COPIAR MENSAJE', 'No se pudo copiar el mensaje. Ábrelo en Instagram para compartirlo.', 4000);
            });
        })();
    </script>
@endpush
