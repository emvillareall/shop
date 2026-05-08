<div class="rounded-xl border border-slate-200 bg-slate-50 p-5">
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div class="form-group">
            {{ Form::label('codigo_compra', 'Codigo de compra', ['class' => 'mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600']) }}
            {{ Form::text('codigo_compra', $compra->codigo_compra, ['class' => 'form-control' . ($errors->has('codigo_compra') ? ' is-invalid' : ''), 'placeholder' => 'Ej. CMP-2026-001']) }}
            {!! $errors->first('codigo_compra', '<div class="invalid-feedback">:message</div>') !!}
        </div>

        <div class="form-group">
            {{ Form::label('descripcion_compra', 'Descripcion', ['class' => 'mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600']) }}
            {{ Form::text('descripcion_compra', $compra->descripcion_compra, ['class' => 'form-control' . ($errors->has('descripcion_compra') ? ' is-invalid' : ''), 'placeholder' => 'Descripcion de la compra']) }}
            {!! $errors->first('descripcion_compra', '<div class="invalid-feedback">:message</div>') !!}
        </div>

        <div class="form-group">
            {{ Form::label('envio_compra', 'Costo de envio', ['class' => 'mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600']) }}
            {{ Form::number('envio_compra', $compra->envio_compra, ['class' => 'form-control' . ($errors->has('envio_compra') ? ' is-invalid' : ''), 'placeholder' => '0.00', 'step' => '0.01']) }}
            {!! $errors->first('envio_compra', '<div class="invalid-feedback">:message</div>') !!}
        </div>

        <div class="form-group">
            {{ Form::label('importacion_compra', 'Importacion', ['class' => 'mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600']) }}
            {{ Form::number('importacion_compra', $compra->importacion_compra, ['class' => 'form-control' . ($errors->has('importacion_compra') ? ' is-invalid' : ''), 'placeholder' => '0.00', 'step' => '0.01']) }}
            {!! $errors->first('importacion_compra', '<div class="invalid-feedback">:message</div>') !!}
        </div>

        <div class="form-group">
            {{ Form::label('total_pesos_compra', 'Total pesos', ['class' => 'mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600']) }}
            {{ Form::number('total_pesos_compra', $compra->total_pesos_compra, ['class' => 'form-control' . ($errors->has('total_pesos_compra') ? ' is-invalid' : ''), 'placeholder' => '0.00', 'step' => '0.01']) }}
            {!! $errors->first('total_pesos_compra', '<div class="invalid-feedback">:message</div>') !!}
        </div>

        <div class="form-group">
            {{ Form::label('total_dolares_compra', 'Total dolares', ['class' => 'mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600']) }}
            {{ Form::number('total_dolares_compra', $compra->total_dolares_compra, ['class' => 'form-control' . ($errors->has('total_dolares_compra') ? ' is-invalid' : ''), 'placeholder' => '0.00', 'step' => '0.01']) }}
            {!! $errors->first('total_dolares_compra', '<div class="invalid-feedback">:message</div>') !!}
        </div>

        <div class="form-group">
            {{ Form::label('total_final_compra', 'Total final', ['class' => 'mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600']) }}
            {{ Form::number('total_final_compra', $compra->total_final_compra, ['class' => 'form-control' . ($errors->has('total_final_compra') ? ' is-invalid' : ''), 'placeholder' => '0.00', 'step' => '0.01']) }}
            {!! $errors->first('total_final_compra', '<div class="invalid-feedback">:message</div>') !!}
        </div>

        <div class="form-group">
            <label for="proveedor_id" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Proveedor</label>
            <select id="proveedor_id" name="proveedor_id" class="form-control @error('proveedor_id') is-invalid @enderror">
                <option value="">Seleccione proveedor</option>
                @foreach(($proveedor_id ?? []) as $id => $nombre)
                    <option value="{{ $id }}" {{ (string)old('proveedor_id', $compra->proveedor_id) === (string)$id ? 'selected' : '' }}>
                        {{ $nombre }}
                    </option>
                @endforeach
            </select>
            {!! $errors->first('proveedor_id', '<div class="invalid-feedback">:message</div>') !!}
        </div>
    </div>

    {{ Form::hidden('id', $compra->id) }}

    <div class="mt-5 flex items-center gap-2 border-t border-slate-200 pt-4">
        <button type="submit" class="btn btn-primary">Guardar compra</button>
        <a href="{{ route('compras.index') }}" class="btn btn-secondary">Volver</a>
    </div>
</div>
