<div class="grid gap-4 md:grid-cols-2">
    <div>
        <label for="nombres_clientes" class="mb-1 block text-sm font-semibold text-slate-700">Nombres</label>
        {{ Form::text('nombres_clientes', $cliente->nombres_clientes, ['id' => 'nombres_clientes', 'class' => 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200' . ($errors->has('nombres_clientes') ? ' border-rose-400 focus:border-rose-500 focus:ring-rose-200' : ''), 'placeholder' => 'Ej: Ana Maria']) }}
        {!! $errors->first('nombres_clientes', '<p class="mt-1 text-xs text-rose-600">:message</p>') !!}
    </div>

    <div>
        <label for="apellidos_clientes" class="mb-1 block text-sm font-semibold text-slate-700">Apellidos</label>
        {{ Form::text('apellidos_clientes', $cliente->apellidos_clientes, ['id' => 'apellidos_clientes', 'class' => 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200' . ($errors->has('apellidos_clientes') ? ' border-rose-400 focus:border-rose-500 focus:ring-rose-200' : ''), 'placeholder' => 'Ej: Perez Gomez']) }}
        {!! $errors->first('apellidos_clientes', '<p class="mt-1 text-xs text-rose-600">:message</p>') !!}
    </div>

    <div>
        <label for="cedula_clientes" class="mb-1 block text-sm font-semibold text-slate-700">Cedula / identificacion</label>
        {{ Form::text('cedula_clientes', $cliente->cedula_clientes, ['id' => 'cedula_clientes', 'class' => 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200' . ($errors->has('cedula_clientes') ? ' border-rose-400 focus:border-rose-500 focus:ring-rose-200' : ''), 'placeholder' => 'Ej: 0912345678']) }}
        {!! $errors->first('cedula_clientes', '<p class="mt-1 text-xs text-rose-600">:message</p>') !!}
    </div>

    <div>
        <label for="telefono_clientes" class="mb-1 block text-sm font-semibold text-slate-700">Telefono</label>
        {{ Form::text('telefono_clientes', $cliente->telefono_clientes, ['id' => 'telefono_clientes', 'class' => 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200' . ($errors->has('telefono_clientes') ? ' border-rose-400 focus:border-rose-500 focus:ring-rose-200' : ''), 'placeholder' => 'Ej: 0999999999']) }}
        {!! $errors->first('telefono_clientes', '<p class="mt-1 text-xs text-rose-600">:message</p>') !!}
    </div>

    <div>
        <label for="ciudad_clientes" class="mb-1 block text-sm font-semibold text-slate-700">Ciudad</label>
        {{ Form::text('ciudad_clientes', $cliente->ciudad_clientes, ['id' => 'ciudad_clientes', 'class' => 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200' . ($errors->has('ciudad_clientes') ? ' border-rose-400 focus:border-rose-500 focus:ring-rose-200' : ''), 'placeholder' => 'Ej: Guayaquil']) }}
        {!! $errors->first('ciudad_clientes', '<p class="mt-1 text-xs text-rose-600">:message</p>') !!}
    </div>

    <div>
        <label for="direccion_clientes" class="mb-1 block text-sm font-semibold text-slate-700">Direccion</label>
        {{ Form::text('direccion_clientes', $cliente->direccion_clientes, ['id' => 'direccion_clientes', 'class' => 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200' . ($errors->has('direccion_clientes') ? ' border-rose-400 focus:border-rose-500 focus:ring-rose-200' : ''), 'placeholder' => 'Ej: Av. Principal y Calle 2']) }}
        {!! $errors->first('direccion_clientes', '<p class="mt-1 text-xs text-rose-600">:message</p>') !!}
    </div>

    <div class="md:col-span-2">
        <label for="email_clientes" class="mb-1 block text-sm font-semibold text-slate-700">Correo electronico</label>
        {{ Form::email('email_clientes', $cliente->email_clientes, ['id' => 'email_clientes', 'class' => 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200' . ($errors->has('email_clientes') ? ' border-rose-400 focus:border-rose-500 focus:ring-rose-200' : ''), 'placeholder' => 'Ej: cliente@email.com']) }}
        {!! $errors->first('email_clientes', '<p class="mt-1 text-xs text-rose-600">:message</p>') !!}
    </div>
</div>

<div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 pt-4">
    <a href="{{ route('ecommerce.home') }}" class="btn btn-secondary">Cancelar</a>
    <button type="submit" class="btn btn-primary">Guardar y continuar</button>
</div>

