    <script type="text/javascript">
    
function cambiaColor (codigo_color){
 
  var circulo = document.getElementById("circulo");
  circulo.style.backgroundColor = codigo_color.value;
}
       
    </script>
<div class="box box-info padding-1">
    <div class="box-body">
        
        <div class="form-group">
            {{ Form::label('descripcion_producto',$descripcion_producto->descripcion_producto) }}
            {{ Form::hidden('producto_id',$id_producto) }}
            <br>
            {{ Form::label('Numero de prendas por asignar color: ') }}
            {{ Form::label('cantidad_total',$cantidad_total) }}
            {{ Form::hidden('cantidad_total',$cantidad_total) }}
            {{ Form::hidden('producto_id', $id_producto, ['class' => 'form-control' . ($errors->has('producto_id') ? ' is-invalid' : ''), 'placeholder' => 'Producto Id']) }}
            {!! $errors->first('producto_id', '<div class="invalid-feedback">:message</div>') !!}
        </div>

       <div class="form-group">
            {{ Form::label('Colores') }}
            <select name="codigo_color" onchange="cambiaColor(this)">
                <?php foreach ($colores as $value): 
                    echo "<option  value='".$value->codigo_color."' style='background:".$value->codigo_color."'>".$value->nombre_color."</option>";

                 endforeach ?>
            </select>
                    <div id="circulo" style="height:50px; width:50px; -moz-border-radius:50px;-webkit-border-radius:50px;border-radius:50px; border-color: black;">
                    </div>

        </div>

        <div class="form-group">
            {{ Form::label('cantidad_por_color') }}
            {{ Form::text('cantidad_por_color', $coloresProducto->cantidad_por_color, ['class' => 'form-control' . ($errors->has('cantidad_por_color') ? ' is-invalid' : ''), 'placeholder' => 'Cantidad Por Color']) }}
            {!! $errors->first('cantidad_por_color', '<div class="invalid-feedback">:message</div>') !!}
        </div>

        <div class="form-group">
    {{ Form::label('talla_por_color', 'Talla por color') }}
    {{ Form::text('talla_por_color', $coloresProducto->talla_por_color, ['class' => 'form-control' . ($errors->has('talla_por_color') ? ' is-invalid' : ''), 'placeholder' => 'Ej. M, L, 38']) }}
    {!! $errors->first('talla_por_color', '<div class="invalid-feedback">:message</div>') !!}
</div>


    </div>
    <div class="box-footer mt20">
        <button type="submit" class="btn btn-primary">{{ __('Agregar color') }}</button>
    </div>
</div>