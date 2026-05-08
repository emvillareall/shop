<div class="rounded-xl border border-slate-200 bg-slate-50 p-5">
    <div class="grid grid-cols-1 gap-4">
        <div class="form-group">
            <label for="nombre_linea" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Nombre de linea</label>
            <input type="text" name="nombre_linea" class="form-control @error('nombre_linea') is-invalid @enderror" value="{{ old('nombre_linea', $lineasRopa?->nombre_linea) }}" id="nombre_linea" placeholder="Ej. Linea basica, premium, deportiva">
            {!! $errors->first('nombre_linea', '<div class="invalid-feedback" role="alert"><strong>:message</strong></div>') !!}
        </div>
    </div>

    <div class="mt-5 flex items-center gap-2 border-t border-slate-200 pt-4">
        <button type="submit" class="btn btn-primary">Guardar linea</button>
        <a href="{{ route('lineas-ropa.index') }}" class="btn btn-secondary">Volver</a>
    </div>
</div>
