<div class="rounded-xl border border-slate-200 bg-slate-50 p-5">
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div class="form-group">
            {{ Form::label('nombre_tienda', 'Nombre de tienda', ['class' => 'mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600']) }}
            {{ Form::text('nombre_tienda', $tienda->nombre_tienda, ['class' => 'form-control' . ($errors->has('nombre_tienda') ? ' is-invalid' : ''), 'placeholder' => 'Nombre de tienda']) }}
            {!! $errors->first('nombre_tienda', '<div class="invalid-feedback">:message</div>') !!}
        </div>
        <div class="form-group">
            {{ Form::label('nombres_dueno_tienda', 'Nombres del dueño', ['class' => 'mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600']) }}
            {{ Form::text('nombres_dueno_tienda', $tienda->nombres_dueno_tienda, ['class' => 'form-control' . ($errors->has('nombres_dueno_tienda') ? ' is-invalid' : ''), 'placeholder' => 'Nombres del dueño']) }}
            {!! $errors->first('nombres_dueno_tienda', '<div class="invalid-feedback">:message</div>') !!}
        </div>
        <div class="form-group">
            {{ Form::label('apellidos_dueno_tienda', 'Apellidos del dueño', ['class' => 'mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600']) }}
            {{ Form::text('apellidos_dueno_tienda', $tienda->apellidos_dueno_tienda, ['class' => 'form-control' . ($errors->has('apellidos_dueno_tienda') ? ' is-invalid' : ''), 'placeholder' => 'Apellidos del dueño']) }}
            {!! $errors->first('apellidos_dueno_tienda', '<div class="invalid-feedback">:message</div>') !!}
        </div>
        <div class="form-group">
            {{ Form::label('cedula_dueno_tienda', 'Cédula del dueño', ['class' => 'mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600']) }}
            {{ Form::text('cedula_dueno_tienda', $tienda->cedula_dueno_tienda, ['class' => 'form-control' . ($errors->has('cedula_dueno_tienda') ? ' is-invalid' : ''), 'placeholder' => 'Cédula o identificación']) }}
            {!! $errors->first('cedula_dueno_tienda', '<div class="invalid-feedback">:message</div>') !!}
        </div>
        <div class="form-group">
            {{ Form::label('telefono_dueno_tienda', 'Teléfono del dueño', ['class' => 'mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600']) }}
            {{ Form::text('telefono_dueno_tienda', $tienda->telefono_dueno_tienda, ['class' => 'form-control' . ($errors->has('telefono_dueno_tienda') ? ' is-invalid' : ''), 'placeholder' => 'Teléfono de contacto']) }}
            {!! $errors->first('telefono_dueno_tienda', '<div class="invalid-feedback">:message</div>') !!}
        </div>
        <div class="form-group">
            {{ Form::label('email_dueno_tienda', 'Email del dueño', ['class' => 'mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600']) }}
            {{ Form::text('email_dueno_tienda', $tienda->email_dueno_tienda, ['class' => 'form-control' . ($errors->has('email_dueno_tienda') ? ' is-invalid' : ''), 'placeholder' => 'correo@ejemplo.com']) }}
            {!! $errors->first('email_dueno_tienda', '<div class="invalid-feedback">:message</div>') !!}
        </div>
        <div class="form-group md:col-span-2">
            {{ Form::label('direccion_dueno_tienda', 'Dirección del dueño', ['class' => 'mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600']) }}
            {{ Form::text('direccion_dueno_tienda', $tienda->direccion_dueno_tienda, ['class' => 'form-control' . ($errors->has('direccion_dueno_tienda') ? ' is-invalid' : ''), 'placeholder' => 'Dirección']) }}
            {!! $errors->first('direccion_dueno_tienda', '<div class="invalid-feedback">:message</div>') !!}
        </div>
    </div>
    <div class="mt-5 flex items-center gap-2 border-t border-slate-200 pt-4">
        <button type="submit" class="btn btn-primary">Guardar tienda</button>
        <a href="{{ route('tiendas.index') }}" class="btn btn-secondary">Volver</a>
    </div>
</div>
