<div class="box box-info padding-1">
    <div class="box-body">
        
        <div class="form-group">
            {{ Form::label('nombre_parametro') }}
            {{ Form::text('nombre_parametro', $parametro->nombre_parametro, ['class' => 'form-control' . ($errors->has('nombre_parametro') ? ' is-invalid' : ''), 'placeholder' => 'Nombre Parametro']) }}
            {!! $errors->first('nombre_parametro', '<div class="invalid-feedback">:message</div>') !!}
        </div>
        <div class="form-group">
            {{ Form::label('valor_parametro') }}
            {{ Form::text('valor_parametro', $parametro->valor_parametro, ['class' => 'form-control' . ($errors->has('valor_parametro') ? ' is-invalid' : ''), 'placeholder' => 'Valor Parametro']) }}
            {!! $errors->first('valor_parametro', '<div class="invalid-feedback">:message</div>') !!}
        </div>

    </div>
    <div class="box-footer mt20">
        <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
    </div>
</div>