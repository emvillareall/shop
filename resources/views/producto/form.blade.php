<div class="space-y-5">
    <div class="rounded-xl border border-slate-200 bg-slate-50 p-5">
        <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-600">Datos del producto</h3>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            <div class="form-group">
                <label for="codigo_producto" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Codigo producto</label>
                {{ Form::text('codigo_producto', $producto->codigo_producto, ['id' => 'codigo_producto', 'class' => 'form-control' . ($errors->has('codigo_producto') ? ' is-invalid' : ''), 'placeholder' => 'Codigo producto']) }}
                {!! $errors->first('codigo_producto', '<div class="invalid-feedback">:message</div>') !!}
            </div>

            <div class="form-group md:col-span-2 xl:col-span-2">
                <label for="descripcion_producto" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Descripcion</label>
                {{ Form::text('descripcion_producto', $producto->descripcion_producto, ['id' => 'descripcion_producto', 'class' => 'form-control' . ($errors->has('descripcion_producto') ? ' is-invalid' : ''), 'placeholder' => 'Descripcion producto']) }}
                {!! $errors->first('descripcion_producto', '<div class="invalid-feedback">:message</div>') !!}
            </div>

            <div class="form-group">
                <label for="cantidad_compra_producto" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Cantidad compra</label>
                {{ Form::number('cantidad_compra_producto', $producto->cantidad_compra_producto, ['id' => 'cantidad_compra_producto', 'class' => 'form-control' . ($errors->has('cantidad_compra_producto') ? ' is-invalid' : ''), 'placeholder' => '0', 'step' => '1']) }}
                {!! $errors->first('cantidad_compra_producto', '<div class="invalid-feedback">:message</div>') !!}
            </div>

            <div class="form-group">
                <label for="stock_venta_producto" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Stock venta</label>
                {{ Form::number('stock_venta_producto', $producto->stock_venta_producto, ['id' => 'stock_venta_producto', 'class' => 'form-control' . ($errors->has('stock_venta_producto') ? ' is-invalid' : ''), 'placeholder' => '0', 'step' => '1']) }}
                {!! $errors->first('stock_venta_producto', '<div class="invalid-feedback">:message</div>') !!}
            </div>

            <div class="form-group">
                <label for="precio_pesos_producto" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Precio unitario pesos</label>
                {{ Form::number('precio_pesos_producto', $producto->precio_pesos_producto, ['id' => 'precio_pesos_producto', 'class' => 'form-control' . ($errors->has('precio_pesos_producto') ? ' is-invalid' : ''), 'placeholder' => '0.00', 'step' => '0.01']) }}
                {!! $errors->first('precio_pesos_producto', '<div class="invalid-feedback">:message</div>') !!}
            </div>

            <div class="form-group">
                <label for="precio_dolares_producto" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Precio unitario dolares</label>
                {{ Form::number('precio_dolares_producto', $producto->precio_dolares_producto, ['id' => 'precio_dolares_producto', 'class' => 'form-control' . ($errors->has('precio_dolares_producto') ? ' is-invalid' : ''), 'placeholder' => '0.00', 'step' => '0.01']) }}
                {!! $errors->first('precio_dolares_producto', '<div class="invalid-feedback">:message</div>') !!}
            </div>

            <div class="form-group">
                <label for="precio_venta_producto" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Precio unitario venta</label>
                {{ Form::number('precio_venta_producto', $producto->precio_venta_producto, ['id' => 'precio_venta_producto', 'class' => 'form-control' . ($errors->has('precio_venta_producto') ? ' is-invalid' : ''), 'placeholder' => '0.00', 'step' => '0.01']) }}
                {!! $errors->first('precio_venta_producto', '<div class="invalid-feedback">:message</div>') !!}
            </div>

            <div class="form-group">
                <label for="linea_ropa_id" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Linea de ropa</label>
                <select id="linea_ropa_id" name="linea_ropa_id" class="form-control @error('linea_ropa_id') is-invalid @enderror">
                    <option value="">-- Selecciona linea --</option>
                    @foreach($lineasRopa as $linea)
                        <option value="{{ $linea->id }}" {{ old('linea_ropa_id', $producto->linea_ropa_id ?? '') == $linea->id ? 'selected' : '' }}>
                            {{ $linea->nombre_linea }}
                        </option>
                    @endforeach
                </select>
                {!! $errors->first('linea_ropa_id','<div class="invalid-feedback">:message</div>') !!}
            </div>

            <div class="form-group">
                <label for="categoria_producto_id" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Categoria</label>
                <select id="categoria_producto_id" name="categoria_producto_id" class="form-control @error('categoria_producto_id') is-invalid @enderror">
                    <option value="">-- Selecciona categoria --</option>
                    @if(old('linea_ropa_id', $producto->linea_ropa_id ?? false))
                        @foreach(\App\Models\CategoriasProducto::where('linea_ropa_id', old('linea_ropa_id', $producto->linea_ropa_id))->get() as $cat)
                            <option value="{{ $cat->id }}" {{ old('categoria_producto_id', $producto->categoria_producto_id ?? '') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->nombre_categoria }}
                            </option>
                        @endforeach
                    @endif
                </select>
                {!! $errors->first('categoria_producto_id','<div class="invalid-feedback">:message</div>') !!}
            </div>

            <div class="form-group md:col-span-2 xl:col-span-1">
                <label for="imagen" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Imagen del producto</label>
                {{ Form::file('imagen', ['id' => 'imagen', 'class' => 'form-control' . ($errors->has('imagen') ? ' is-invalid' : '')]) }}
                {!! $errors->first('imagen', '<div class="invalid-feedback">:message</div>') !!}
            </div>
        </div>

        {{ Form::hidden('compras_id', $compras_id) }}
    </div>

    <div class="rounded-xl border border-slate-200 bg-slate-50 p-5">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-600">Colores, tallas y cantidades</h3>
            <button type="button" id="add-color-row" class="btn btn-secondary btn-sm">+ Anadir combinacion</button>
        </div>

        <div id="colores-container" class="space-y-3">
            <div class="color-row rounded-lg border border-slate-200 bg-white p-3">
                <div class="grid grid-cols-1 gap-3 md:grid-cols-12">
                    <div class="md:col-span-4">
                        <label class="mb-1 flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-slate-600">
                            Color
                            <span class="color-preview inline-block h-5 w-5 rounded-full border border-slate-300"></span>
                        </label>
                        <select name="colores_id[]" class="form-control color-selector" onchange="mostrarColorSeleccionado(this)">
                            @foreach(App\Models\Colores::all() as $color)
                                <option value="{{ $color->id }}" data-color="{{ $color->codigo_color }}">{{ $color->nombre_color }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Talla</label>
                        <input type="text" name="talla_por_color[]" class="form-control" placeholder="S, M, L, XS">
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Cantidad</label>
                        <input type="number" name="cantidad_por_color[]" class="form-control" min="1">
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Stock</label>
                        <input type="number" name="stock_por_color[]" class="form-control" min="0">
                    </div>

                    <div class="md:col-span-2 flex items-end justify-end">
                        <button type="button" class="btn btn-danger btn-sm remove-color-row">Eliminar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-5 flex items-center gap-2 border-t border-slate-200 pt-4">
        <button type="submit" class="btn btn-primary">Guardar producto</button>
        <a href="{{ route('productos.index') }}" class="btn btn-secondary">Volver</a>
    </div>
</div>

<script>
    function mostrarColorSeleccionado(select) {
        const selectedOption = select.options[select.selectedIndex];
        const color = selectedOption.getAttribute('data-color');
        const row = select.closest('.color-row');
        const preview = row ? row.querySelector('.color-preview') : null;
        if (preview) {
            preview.style.backgroundColor = color;
        }
    }

    document.querySelectorAll('.color-selector').forEach(function (select) {
        mostrarColorSeleccionado(select);
    });

    document.getElementById('add-color-row').addEventListener('click', function () {
        const container = document.getElementById('colores-container');
        const row = container.querySelector('.color-row').cloneNode(true);
        row.querySelectorAll('input').forEach(el => el.value = '');
        row.querySelectorAll('select').forEach(el => el.selectedIndex = 0);
        container.appendChild(row);
        const select = row.querySelector('.color-selector');
        if (select) mostrarColorSeleccionado(select);
    });

    document.addEventListener('click', function (e) {
        if (e.target && e.target.classList.contains('remove-color-row')) {
            const rows = document.querySelectorAll('.color-row');
            if (rows.length > 1) {
                e.target.closest('.color-row').remove();
            }
        }
    });

    document.getElementById('linea_ropa_id').addEventListener('change', function () {
        const lineaId = this.value;
        const catSelect = document.getElementById('categoria_producto_id');
        catSelect.innerHTML = '<option>Cargando...</option>';

        if (!lineaId) {
            catSelect.innerHTML = '<option value="">-- Selecciona categoria --</option>';
            return;
        }

        fetch(`/lineas/${lineaId}/categorias`)
            .then(res => res.json())
            .then(data => {
                let options = '<option value="">-- Selecciona categoria --</option>';
                data.forEach(cat => {
                    options += `<option value="${cat.id}">${cat.nombre_categoria}</option>`;
                });
                catSelect.innerHTML = options;
            })
            .catch(() => {
                catSelect.innerHTML = '<option value="">Error al cargar</option>';
            });
    });
</script>
