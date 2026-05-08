    <style>
        .circular-input {
            height:20px; 
            width:20px; 
            -moz-border-radius:20px;
            -webkit-border-radius:20px;
            border-radius:20px; 
            border-color: black; 
        }
    </style>
    <script>
        function capturarValor() {
            // Obtener el elemento select
            var select = document.getElementById("opciones");
            
            // Obtener el valor seleccionado
            var valorSeleccionado = select.value;
            
            // Mostrar el valor seleccionado
            document.getElementById("resultado").value = valorSeleccionado;
                        $.ajax({
                url: '/id_colores/'+select.value, // Archivo PHP que procesará el valor
                type: 'GET',
                success: function(response) {
                    // Mostrar la respuesta de PHP
                    let container = $('#inputContainer');
                    container.empty(); // Limpiar el contenedor antes de agregar nuevos inputs
                       // console.log('Datos devueltos:', response);
                    response.forEach(function(item) {
                        let input = $('<input>').attr({
                        type: 'text',
                        name: 'input_' + item.id,
                        value: item.value,
                        class: 'circular-input'
                    }).css('background-color', item.codigo_color);

                    container.append('<br>');
                    container.append(input);
                    container.append('<br>'); // Agregar un salto de línea 
                        console.log('Datos devueltos:', item);
                    });
                        

                },
                error: function(xhr, status, error) {
                    // Manejo de errores
                    console.error('Error en la solicitud AJAX:', error);
                }
            });
        }
    </script>

<div class="box box-info padding-1">
    <div class="box-body">

        <div class="form-group">

            {{ Form::label('productos') }}
            <select class="js-example-basic-single" id="opciones" onchange="capturarValor(this)">
              @foreach($productos as $producto)
              <option value="{{ $producto->id }}">{{ $producto->descripcion_producto }} ( cantidad - {{ $producto->stock_venta_producto }} )</option>
              @endforeach
          </select>
        </div>
            <input type="text" value="" id="resultado">

            <div id="inputContainer"></div>

        <div>
            {{ Form::label('Colores Disponibles') }}<br>
              @foreach($productos_por_color->where('producto_id', 6) as $key=>$colorproducto)

              <input disabled style="height:20px; width:20px; -moz-border-radius:20px;-webkit-border-radius:20px;border-radius:20px; border-color: black; background-color: {{$colorproducto->codigo_color}};"></input>
              <span> ( cantidad - {{ $colorproducto->stock_por_color }} )</span>
              <input name="cantidad_{{$colorproducto->id}}"><br>
              @endforeach
        </div>

        <div class="form-group">
            {{ Form::hidden('pedido_id', $pedido_id) }}
        </div>

    </div>
    <div class="box-footer mt20">
        <button type="submit" class="btn btn-primary">{{ __('Confirmar Venta') }}</button>
    </div>
</div>