<div class="rounded-xl border border-slate-200 bg-slate-50 p-5">
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div class="form-group">
            <label for="nombre_categoria" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Nombre categoria</label>
            <input type="text" name="nombre_categoria" class="form-control @error('nombre_categoria') is-invalid @enderror" value="{{ old('nombre_categoria', $categoriasProducto?->nombre_categoria) }}" id="nombre_categoria" placeholder="Ej. Bodys, Tops, Leggins">
            {!! $errors->first('nombre_categoria', '<div class="invalid-feedback" role="alert"><strong>:message</strong></div>') !!}
        </div>

        <div class="form-group">
            <label for="linea_ropa_id" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Linea de ropa</label>
            <select name="linea_ropa_id" class="form-select @error('linea_ropa_id') is-invalid @enderror" id="linea_ropa_id">
                <option value="">-- Seleccione una linea --</option>
                @foreach($lineasRopa as $linea)
                    <option value="{{ $linea->id }}" {{ old('linea_ropa_id', $categoriasProducto?->linea_ropa_id) == $linea->id ? 'selected' : '' }}>
                        {{ $linea->nombre_linea }}
                    </option>
                @endforeach
            </select>
            {!! $errors->first('linea_ropa_id', '<div class="invalid-feedback" role="alert"><strong>:message</strong></div>') !!}
        </div>
    </div>

    <div class="mt-5 flex items-center gap-2 border-t border-slate-200 pt-4">
        <button type="submit" class="btn btn-primary">Guardar categoria</button>
        <a href="{{ route('categorias-productos.index') }}" class="btn btn-secondary">Volver</a>
    </div>
</div>
