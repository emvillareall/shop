<div class="box box-info padding-1">
    <div class="box-body">
        
        <div class="form-group">
            {{ Form::label('nombres_clientes') }}
            {{ Form::text('nombres_clientes', $cliente->nombres_clientes, ['class' => 'form-control' . ($errors->has('nombres_clientes') ? ' is-invalid' : ''), 'placeholder' => 'Nombres Clientes']) }}
            {!! $errors->first('nombres_clientes', '<div class="invalid-feedback">:message</div>') !!}
        </div>
        <div class="form-group">
            {{ Form::label('apellidos_clientes') }}
            {{ Form::text('apellidos_clientes', $cliente->apellidos_clientes, ['class' => 'form-control' . ($errors->has('apellidos_clientes') ? ' is-invalid' : ''), 'placeholder' => 'Apellidos Clientes']) }}
            {!! $errors->first('apellidos_clientes', '<div class="invalid-feedback">:message</div>') !!}
        </div>
        <div class="form-group">
            {{ Form::label('cedula_clientes') }}
            {{ Form::text('cedula_clientes', $cliente->cedula_clientes, ['class' => 'form-control' . ($errors->has('cedula_clientes') ? ' is-invalid' : ''), 'placeholder' => 'Cedula Clientes']) }}
            {!! $errors->first('cedula_clientes', '<div class="invalid-feedback">:message</div>') !!}
        </div>
        <div class="form-group">
            {{ Form::label('telefono_clientes') }}
            {{ Form::text('telefono_clientes', $cliente->telefono_clientes, ['class' => 'form-control' . ($errors->has('telefono_clientes') ? ' is-invalid' : ''), 'placeholder' => 'Telefono Clientes']) }}
            {!! $errors->first('telefono_clientes', '<div class="invalid-feedback">:message</div>') !!}
        </div>
        <div class="form-group">
            {{ Form::label('ciudad_clientes') }}
            {{ Form::text('ciudad_clientes', $cliente->ciudad_clientes, ['class' => 'form-control' . ($errors->has('ciudad_clientes') ? ' is-invalid' : ''), 'placeholder' => 'Ciudad Clientes']) }}
            {!! $errors->first('ciudad_clientes', '<div class="invalid-feedback">:message</div>') !!}
        </div>
        <div class="form-group">
            {{ Form::label('direccion_clientes') }}
            {{ Form::text('direccion_clientes', $cliente->direccion_clientes, ['class' => 'form-control' . ($errors->has('direccion_clientes') ? ' is-invalid' : ''), 'placeholder' => 'Direccion Clientes']) }}
            {!! $errors->first('direccion_clientes', '<div class="invalid-feedback">:message</div>') !!}
        </div>
        <div class="form-group">
            {{ Form::label('email_dueno_clientes') }}
            {{ Form::text('email_dueno_clientes', $cliente->email_dueno_clientes, ['class' => 'form-control' . ($errors->has('email_dueno_clientes') ? ' is-invalid' : ''), 'placeholder' => 'Email Clientes']) }}
            {!! $errors->first('email_dueno_clientes', '<div class="invalid-feedback">:message</div>') !!}
        </div>

    </div>
    <div class="box-footer mt20">
        <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
    </div>
</div>