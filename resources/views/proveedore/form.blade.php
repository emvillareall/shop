<div class="rounded-xl border border-slate-200 bg-slate-50 p-5">
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div class="form-group">
            {{ Form::label('tienda_proveedor', 'Tienda proveedor', ['class' => 'mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600']) }}
            {{ Form::text('tienda_proveedor', $proveedore->tienda_proveedor, ['class' => 'form-control' . ($errors->has('tienda_proveedor') ? ' is-invalid' : ''), 'placeholder' => 'Nombre de tienda']) }}
            {!! $errors->first('tienda_proveedor', '<div class="invalid-feedback">:message</div>') !!}
        </div>
        <div class="form-group">
            {{ Form::label('nombres_proveedor', 'Nombres', ['class' => 'mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600']) }}
            {{ Form::text('nombres_proveedor', $proveedore->nombres_proveedor, ['class' => 'form-control' . ($errors->has('nombres_proveedor') ? ' is-invalid' : ''), 'placeholder' => 'Nombres del proveedor']) }}
            {!! $errors->first('nombres_proveedor', '<div class="invalid-feedback">:message</div>') !!}
        </div>
        <div class="form-group">
            {{ Form::label('apellidos_proveedor', 'Apellidos', ['class' => 'mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600']) }}
            {{ Form::text('apellidos_proveedor', $proveedore->apellidos_proveedor, ['class' => 'form-control' . ($errors->has('apellidos_proveedor') ? ' is-invalid' : ''), 'placeholder' => 'Apellidos del proveedor']) }}
            {!! $errors->first('apellidos_proveedor', '<div class="invalid-feedback">:message</div>') !!}
        </div>
        <div class="form-group">
            {{ Form::label('cedula_proveedor', 'Cédula', ['class' => 'mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600']) }}
            {{ Form::text('cedula_proveedor', $proveedore->cedula_proveedor, ['class' => 'form-control' . ($errors->has('cedula_proveedor') ? ' is-invalid' : ''), 'placeholder' => 'Cédula o identificación']) }}
            {!! $errors->first('cedula_proveedor', '<div class="invalid-feedback">:message</div>') !!}
        </div>
        <div class="form-group">
            {{ Form::label('telefono_proveedor', 'Teléfono', ['class' => 'mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600']) }}
            {{ Form::text('telefono_proveedor', $proveedore->telefono_proveedor, ['class' => 'form-control' . ($errors->has('telefono_proveedor') ? ' is-invalid' : ''), 'placeholder' => 'Teléfono de contacto']) }}
            {!! $errors->first('telefono_proveedor', '<div class="invalid-feedback">:message</div>') !!}
        </div>
        <div class="form-group">
            {{ Form::label('email_proveedor', 'Email', ['class' => 'mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600']) }}
            {{ Form::text('email_proveedor', $proveedore->email_proveedor, ['class' => 'form-control' . ($errors->has('email_proveedor') ? ' is-invalid' : ''), 'placeholder' => 'correo@ejemplo.com']) }}
            {!! $errors->first('email_proveedor', '<div class="invalid-feedback">:message</div>') !!}
        </div>
        <div class="form-group md:col-span-2">
            {{ Form::label('direccion_proveedor', 'Dirección', ['class' => 'mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600']) }}
            {{ Form::text('direccion_proveedor', $proveedore->direccion_proveedor, ['class' => 'form-control' . ($errors->has('direccion_proveedor') ? ' is-invalid' : ''), 'placeholder' => 'Dirección del proveedor']) }}
            {!! $errors->first('direccion_proveedor', '<div class="invalid-feedback">:message</div>') !!}
        </div>
    </div>
    <div class="mt-5 flex items-center gap-2 border-t border-slate-200 pt-4">
        <button type="submit" class="btn btn-primary">Guardar proveedor</button>
        <a href="{{ route('proveedores.index') }}" class="btn btn-secondary">Volver</a>
    </div>
</div>
