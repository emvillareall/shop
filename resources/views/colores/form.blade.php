<div class="rounded-xl border border-slate-200 bg-slate-50 p-5">
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div class="form-group">
            <label for="nombre_color" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Nombre del color</label>
            <input type="text"
                   name="nombre_color"
                   id="nombre_color"
                   class="form-control @error('nombre_color') is-invalid @enderror"
                   value="{{ old('nombre_color', $colore?->nombre_color) }}"
                   placeholder="Ej. Rojo, Verde oliva, Celeste bebe">
            {!! $errors->first('nombre_color', '<div class="invalid-feedback" role="alert"><strong>:message</strong></div>') !!}
        </div>

        <div class="form-group">
            <label for="codigo_color" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Codigo hexadecimal</label>
            <div class="flex items-center gap-2">
                <input type="text"
                       name="codigo_color"
                       id="codigo_color"
                       class="form-control @error('codigo_color') is-invalid @enderror"
                       value="{{ old('codigo_color', $colore?->codigo_color) }}"
                       placeholder="#RRGGBB">
                <span class="inline-block h-10 w-10 shrink-0 rounded border border-slate-300 bg-white"
                      style="background-color: {{ old('codigo_color', $colore?->codigo_color ?? '#FFFFFF') }}"></span>
            </div>
            {!! $errors->first('codigo_color', '<div class="invalid-feedback" role="alert"><strong>:message</strong></div>') !!}
        </div>
    </div>

    <div class="mt-5 flex items-center gap-2 border-t border-slate-200 pt-4">
        <button type="submit" class="btn btn-primary">Guardar color</button>
        <a href="{{ route('colores.index') }}" class="btn btn-secondary">Volver</a>
    </div>
</div>
