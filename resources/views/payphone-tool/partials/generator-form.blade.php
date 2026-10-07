<div>
    <label for="{{ $formId }}" class="mb-1.5 block text-sm font-semibold text-slate-700">Valor de la compra</label>
    <div class="flex items-center overflow-hidden rounded-2xl border border-slate-300 bg-white transition focus-within:border-brand-500 focus-within:ring-4 focus-within:ring-brand-100">
        <span class="pl-4 text-xl font-bold text-slate-400">$</span>
        <input id="{{ $formId }}" name="amount" value="{{ $amountValue }}" type="text" inputmode="decimal" autocomplete="off" placeholder="60.00" required data-amount class="w-full border-0 px-3 py-3 text-2xl font-bold text-slate-900 outline-none focus:ring-0">
    </div>
    <p class="mt-1.5 text-xs leading-5 text-slate-500">El recargo se calcula sobre el monto base y se muestra por separado.</p>
</div>

<div>
    <label for="{{ $formId }}-modality" class="mb-1.5 block text-sm font-semibold text-slate-700">Modalidad prevista</label>
    <select id="{{ $formId }}-modality" name="modalidad" data-modality class="block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
        @foreach($modalidades as $modality)
            <option value="{{ $modality['key'] }}" @selected($selectedModality === $modality['key'])>
                {{ $modality['label'] }}{{ $modality['key'] === 'sin_intereses_3' ? ' — mínimo $200.00' : ($modality['key'] === 'sin_intereses_6' ? ' — mínimo $450.00; pendiente de activar' : '') }}
            </option>
        @endforeach
    </select>
</div>

<div class="rounded-2xl border border-brand-100 bg-brand-50/60 p-3.5 text-sm">
    <div class="flex items-center justify-between gap-4 text-slate-600">
        <span>Valor base</span>
        <span class="font-semibold">$<span data-base-display>0.00</span></span>
    </div>
    <div class="mt-1.5 flex items-center justify-between gap-4 text-slate-600">
        <span>Modalidad</span>
        <span class="text-right font-semibold" data-modality-display>Cobro habitual</span>
    </div>
    <div class="mt-1.5 flex items-center justify-between gap-4 text-slate-600">
        <span>Recargo <span data-percent-label>7</span>%</span>
        <span class="font-semibold">$<span data-surcharge-display>0.00</span></span>
    </div>
    <div class="mt-3 flex items-center justify-between gap-4 border-t border-brand-200 pt-3 text-lg font-extrabold text-brand-900">
        <span>TOTAL</span>
        <span class="text-xl">$<span data-total-display>0.00</span></span>
    </div>
    <div data-installments-block class="mt-2 hidden border-t border-brand-100 pt-2 text-sm text-slate-600">
        <span class="font-semibold">Cuotas estimadas:</span>
        <span data-installments-display></span>
    </div>
    <p data-modality-notice class="mt-1.5 text-xs leading-5 text-slate-500">El cliente debe seleccionar el plazo en PayPhone. Su disponibilidad depende de la tarjeta y de la activación del comercio.</p>
    <p class="mt-1 text-xs leading-5 text-slate-500">“Sin intereses” describe el financiamiento para el cliente; el recargo de esta herramienta se identifica por separado.</p>
    <p class="mt-1 text-xs leading-5 text-slate-500">Mínimo de esta modalidad: <span data-minimum-display>—</span></p>
</div>

<div>
    <label for="{{ $formId }}-reference" class="mb-1.5 block text-sm font-semibold text-slate-700">Referencia / descripción</label>
    <input id="{{ $formId }}-reference" name="reference" value="{{ $referenceValue }}" type="text" maxlength="100" autocomplete="off" class="block w-full rounded-2xl border border-slate-300 bg-white px-4 py-2.5 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
</div>

<button type="submit" data-submit-button class="inline-flex w-full items-center justify-center rounded-2xl bg-brand-700 px-5 py-4 text-base font-bold text-white shadow-sm transition hover:bg-brand-800 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-brand-200 disabled:cursor-not-allowed disabled:bg-slate-300 disabled:text-slate-500 disabled:opacity-100 disabled:shadow-none">
    {{ $buttonText }}
</button>
