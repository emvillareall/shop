@php
    $isEdit = isset($producto) && $producto->exists;
    $selectedLineaId = old('linea_ropa_id', $producto->categoria->linea_ropa_id ?? '');
    $oldColorRows = old('colores_id');
    if (is_array($oldColorRows) && count($oldColorRows) > 0) {
        $variantRows = [];
        foreach ($oldColorRows as $idx => $colorId) {
            $variantRows[] = [
                'id' => old('variante_id.' . $idx),
                'colores_id' => $colorId,
                'talla_por_color' => old('talla_por_color.' . $idx),
                'cantidad_por_color' => old('cantidad_por_color.' . $idx),
                'stock_por_color' => old('stock_por_color.' . $idx),
            ];
        }
    } elseif ($isEdit) {
        $variantRows = $producto->coloresStock->map(function ($item) {
            return [
                'id' => $item->id,
                'colores_id' => $item->colores_id,
                'talla_por_color' => $item->talla_por_color,
                'cantidad_por_color' => $item->cantidad_por_color,
                'stock_por_color' => $item->stock_por_color,
            ];
        })->values()->all();
    } else {
        $variantRows = [];
    }

    if (count($variantRows) === 0) {
        $variantRows[] = ['id' => '', 'colores_id' => '', 'talla_por_color' => '', 'cantidad_por_color' => '', 'stock_por_color' => ''];
    }

    $colorImageMap = [];
    $colorImageIdMap = [];
    if ($isEdit && isset($producto)) {
        foreach ($producto->imagenes as $img) {
            if ((int) ($img->color_id ?? 0) > 0 && !empty($img->ruta) && (bool) ($img->activo ?? true)) {
                $colorImageMap[(int) $img->color_id] = route('media.producto', ['filename' => $img->ruta]);
                $colorImageIdMap[(int) $img->color_id] = (int) $img->id;
            }
        }
    }
@endphp

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

            <div class="form-group md:col-span-2 xl:col-span-3">
                <div class="rounded-lg border border-brand-200 bg-brand-50/40 p-3">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-brand-700">Precio promocional</p>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                        <div class="form-group md:col-span-1">
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Activar promocion</label>
                            <input type="hidden" name="promocion_activa" value="0">
                            <label class="inline-flex items-center gap-2 rounded-md border border-slate-300 bg-white px-3 py-2">
                                <input type="checkbox" name="promocion_activa" value="1" {{ old('promocion_activa', $producto->promocion_activa ?? false) ? 'checked' : '' }}>
                                <span class="text-sm font-medium text-slate-700">Promocion activa</span>
                            </label>
                            {!! $errors->first('promocion_activa', '<div class="invalid-feedback">:message</div>') !!}
                        </div>
                        <div class="form-group md:col-span-1">
                            <label for="precio_promocional" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Precio promo</label>
                            {{ Form::number('precio_promocional', old('precio_promocional', $producto->precio_promocional ?? ''), ['id' => 'precio_promocional', 'class' => 'form-control' . ($errors->has('precio_promocional') ? ' is-invalid' : ''), 'placeholder' => '0.00', 'step' => '0.01', 'min' => '0']) }}
                            {!! $errors->first('precio_promocional', '<div class="invalid-feedback">:message</div>') !!}
                        </div>
                        <div class="form-group md:col-span-1">
                            <label for="promocion_fecha_inicio" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Inicio promo</label>
                            <input type="datetime-local" id="promocion_fecha_inicio" name="promocion_fecha_inicio"
                                   value="{{ old('promocion_fecha_inicio', isset($producto->promocion_fecha_inicio) && $producto->promocion_fecha_inicio ? \Illuminate\Support\Carbon::parse($producto->promocion_fecha_inicio)->format('Y-m-d\\TH:i') : '') }}"
                                   class="form-control{{ $errors->has('promocion_fecha_inicio') ? ' is-invalid' : '' }}">
                            {!! $errors->first('promocion_fecha_inicio', '<div class="invalid-feedback">:message</div>') !!}
                        </div>
                        <div class="form-group md:col-span-1">
                            <label for="promocion_fecha_fin" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Fin promo</label>
                            <input type="datetime-local" id="promocion_fecha_fin" name="promocion_fecha_fin"
                                   value="{{ old('promocion_fecha_fin', isset($producto->promocion_fecha_fin) && $producto->promocion_fecha_fin ? \Illuminate\Support\Carbon::parse($producto->promocion_fecha_fin)->format('Y-m-d\\TH:i') : '') }}"
                                   class="form-control{{ $errors->has('promocion_fecha_fin') ? ' is-invalid' : '' }}">
                            {!! $errors->first('promocion_fecha_fin', '<div class="invalid-feedback">:message</div>') !!}
                        </div>
                    </div>
                    <div class="form-group mt-2">
                        <label for="promocion_etiqueta" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Etiqueta promo (opcional)</label>
                        {{ Form::text('promocion_etiqueta', old('promocion_etiqueta', $producto->promocion_etiqueta ?? ''), ['id' => 'promocion_etiqueta', 'class' => 'form-control' . ($errors->has('promocion_etiqueta') ? ' is-invalid' : ''), 'placeholder' => 'Oferta, Liquidacion, Promo']) }}
                        {!! $errors->first('promocion_etiqueta', '<div class="invalid-feedback">:message</div>') !!}
                        <p class="mt-1 text-xs text-slate-500">El precio promocional debe ser menor al precio de venta normal.</p>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="linea_ropa_id" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Linea de ropa</label>
                <select id="linea_ropa_id" name="linea_ropa_id" class="form-control @error('linea_ropa_id') is-invalid @enderror">
                    <option value="">-- Selecciona linea --</option>
                    @foreach($lineasRopa as $linea)
                        <option value="{{ $linea->id }}" {{ (string) $selectedLineaId === (string) $linea->id ? 'selected' : '' }}>
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
                    @if($selectedLineaId)
                        @foreach(\App\Models\CategoriasProducto::where('linea_ropa_id', $selectedLineaId)->get() as $cat)
                            <option value="{{ $cat->id }}" {{ old('categoria_producto_id', $producto->categoria_producto_id ?? '') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->nombre_categoria }}
                            </option>
                        @endforeach
                    @endif
                </select>
                {!! $errors->first('categoria_producto_id','<div class="invalid-feedback">:message</div>') !!}
            </div>

            <div class="form-group md:col-span-2 xl:col-span-1">
                <label for="imagen" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Imagen principal</label>
                {{ Form::file('imagen', ['id' => 'imagen', 'class' => 'form-control' . ($errors->has('imagen') ? ' is-invalid' : '')]) }}
                {!! $errors->first('imagen', '<div class="invalid-feedback">:message</div>') !!}
            </div>
        </div>

        @if($isEdit && $producto->imagen_producto)
            <div class="mt-4 rounded-lg border border-slate-200 bg-white p-3">
                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Imagen principal actual</p>
                <img src="{{ $producto->imageUrl() }}" alt="{{ $producto->descripcion_producto }}" class="h-28 w-28 rounded-lg object-cover border border-slate-200">
            </div>
        @endif

        <div class="mt-4 rounded-lg border border-slate-200 bg-white p-4">
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Galeria (multiples imagenes)</p>
            <input type="file" name="imagenes[]" multiple class="form-control @error('imagenes.*') is-invalid @enderror">
            @error('imagenes.*')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        @if($isEdit && $producto->imagenes->count() > 0)
            <div class="mt-3 rounded-lg border border-slate-200 bg-white p-4">
                <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Imagenes actuales</p>
                <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                    @foreach($producto->imagenes as $imagen)
                        <label class="rounded-lg border border-slate-200 p-2 text-center">
                            <img src="{{ route('media.producto', ['filename' => $imagen->ruta]) }}" class="mx-auto h-24 w-24 rounded-md object-cover border border-slate-200" alt="Imagen {{ $loop->iteration }}">
                            <span class="mt-2 flex items-center justify-center gap-2 text-xs text-slate-600">
                                <input type="checkbox" name="remove_imagenes[]" value="{{ $imagen->id }}">
                                Eliminar
                            </span>
                            @if(!empty($imagen->color_nombre))
                                <span class="mt-1 block text-xs font-semibold text-brand-700">{{ $imagen->color_nombre }}</span>
                            @endif
                        </label>
                    @endforeach
                </div>
            </div>
        @endif

        {{ Form::hidden('compras_id', $compras_id) }}
    </div>

    <div class="rounded-xl border border-slate-200 bg-slate-50 p-5">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-600">Colores, tallas y cantidades</h3>
            <button type="button" id="add-color-row" class="btn btn-secondary btn-sm">+ Anadir combinacion</button>
        </div>

        <div id="colores-container" class="space-y-3">
            @foreach($variantRows as $row)
                @php
                    $rowColorId = (int) ($row['colores_id'] ?? 0);
                    $rowColorImage = $rowColorId > 0 ? ($colorImageMap[$rowColorId] ?? '') : '';
                    $rowColorImageId = $rowColorId > 0 ? ($colorImageIdMap[$rowColorId] ?? '') : '';
                @endphp
                <div class="color-row rounded-lg border border-slate-200 bg-white p-3" data-row-index="{{ $loop->index }}">
                    <input type="hidden" name="variante_id[{{ $loop->index }}]" value="{{ $row['id'] ?? '' }}">
                    <input type="hidden" name="imagen_color_actual_id[{{ $loop->index }}]" class="color-image-id-input" value="{{ $rowColorImageId }}">
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-12">
                        <div class="md:col-span-4">
                            <label class="mb-1 flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-slate-600">
                                Color
                                <span class="color-preview inline-block h-5 w-5 rounded-full border border-slate-300"></span>
                            </label>
                            <select name="colores_id[{{ $loop->index }}]" class="form-control color-selector" onchange="mostrarColorSeleccionado(this)">
                                <option value="">Seleccione color</option>
                                @foreach(App\Models\Colores::all() as $color)
                                    <option value="{{ $color->id }}" data-color="{{ $color->codigo_color }}" @selected((int)($row['colores_id'] ?? 0) === (int)$color->id)>{{ $color->nombre_color }}</option>
                                @endforeach
                            </select>
                            <div class="mt-2 flex items-center gap-2 color-image-tools">
                                <label class="inline-flex cursor-pointer items-center rounded-md border border-slate-300 bg-slate-50 px-2 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-100">
                                    Subir imagen color
                                    <input type="file" name="imagen_color_row[{{ $loop->index }}]" class="hidden color-image-input" accept=".jpg,.jpeg,.png,.webp">
                                </label>
                                <img src="{{ $rowColorImage }}" alt="Preview color" class="color-image-preview {{ $rowColorImage ? '' : 'hidden' }} h-10 w-10 rounded-md border border-slate-200 object-cover">
                                <span class="color-image-empty {{ $rowColorImage ? 'hidden' : '' }} text-[11px] text-slate-500">Sin imagen</span>
                                <label class="color-image-remove-wrap {{ $rowColorImageId ? '' : 'hidden' }} inline-flex items-center gap-1 text-[11px] text-rose-600">
                                    <input type="checkbox" name="desactivar_imagen_color_id[{{ $loop->index }}]" class="color-image-remove-checkbox" value="{{ $rowColorImageId }}">
                                    Quitar
                                </label>
                            </div>
                            <p class="color-image-shared-note mt-1 hidden text-[11px] font-medium text-slate-500">
                                Imagen gestionada en otra talla de este color.
                            </p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Talla</label>
                            <input type="text" name="talla_por_color[{{ $loop->index }}]" class="form-control" placeholder="S, M, L, XS" value="{{ $row['talla_por_color'] ?? '' }}">
                        </div>

                        <div class="md:col-span-2">
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Cantidad</label>
                            <input type="number" name="cantidad_por_color[{{ $loop->index }}]" class="form-control" min="0" value="{{ $row['cantidad_por_color'] ?? 0 }}">
                        </div>

                        <div class="md:col-span-2">
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Stock</label>
                            <input type="number" name="stock_por_color[{{ $loop->index }}]" class="form-control" min="0" value="{{ $row['stock_por_color'] ?? 0 }}">
                        </div>

                        <div class="md:col-span-2 flex items-end justify-end">
                            <button type="button" class="btn btn-danger btn-sm remove-color-row">Eliminar</button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="mt-5 flex items-center gap-2 border-t border-slate-200 pt-4">
        <button type="submit" class="btn btn-primary">Guardar producto</button>
        <a href="{{ route('productos.index') }}" class="btn btn-secondary">Volver</a>
    </div>
</div>

<script>
    const categoriasPorLineaTemplate = @json(route('lineas.categorias', ['linea' => '__LINEA__']));
    const colorImageMap = @json($colorImageMap);
    const colorImageIdMap = @json($colorImageIdMap);
    let colorRowCounter = document.querySelectorAll('.color-row').length;

    function setRowIndex(row, index) {
        row.dataset.rowIndex = String(index);
        row.querySelectorAll('[name]').forEach(function (field) {
            const base = field.name.replace(/\[\d*]/, '');
            field.name = `${base}[${index}]`;
        });
    }

    function updateRowsByColor(colorId, src = '') {
        if (!colorId) return;

        document.querySelectorAll('.color-row').forEach(function (row) {
            const selector = row.querySelector('.color-selector');
            if (!selector || Number(selector.value || 0) !== Number(colorId)) return;

            const imagePreview = row.querySelector('.color-image-preview');
            const imageEmpty = row.querySelector('.color-image-empty');
            const colorImageIdInput = row.querySelector('.color-image-id-input');
            const removeWrap = row.querySelector('.color-image-remove-wrap');
            const removeCheckbox = row.querySelector('.color-image-remove-checkbox');
            if (!imagePreview || !imageEmpty) return;
            const mappedId = colorImageIdMap[colorId] || '';

            if (src) {
                imagePreview.src = src;
                imagePreview.classList.remove('hidden');
                imageEmpty.classList.add('hidden');
                if (colorImageIdInput) colorImageIdInput.value = mappedId;
                if (removeCheckbox) {
                    removeCheckbox.value = mappedId;
                    removeCheckbox.checked = false;
                }
                if (removeWrap) removeWrap.classList.toggle('hidden', !mappedId);
            } else {
                imagePreview.src = '';
                imagePreview.classList.add('hidden');
                imageEmpty.classList.remove('hidden');
                if (colorImageIdInput) colorImageIdInput.value = '';
                if (removeCheckbox) {
                    removeCheckbox.value = '';
                    removeCheckbox.checked = false;
                }
                if (removeWrap) removeWrap.classList.add('hidden');
            }
        });
        refreshColorImageControls();
    }

    function refreshColorImageControls() {
        const seen = new Set();
        document.querySelectorAll('.color-row').forEach(function (row, rowIndex) {
            const selector = row.querySelector('.color-selector');
            const tools = row.querySelector('.color-image-tools');
            const note = row.querySelector('.color-image-shared-note');
            if (!selector || !tools || !note) return;

            const colorId = Number(selector.value || 0);
            // Si no hay color seleccionado, mostramos el control para no bloquear al usuario.
            if (colorId <= 0) {
                tools.classList.remove('hidden');
                note.classList.add('hidden');
                return;
            }

            if (!seen.has(colorId)) {
                seen.add(colorId);
                tools.classList.remove('hidden');
                note.classList.add('hidden');
                return;
            }

            // Colores repetidos: ocultar controles duplicados y dejar nota.
            tools.classList.add('hidden');
            note.classList.remove('hidden');
        });
    }

    function mostrarColorSeleccionado(select) {
        const selectedOption = select.options[select.selectedIndex];
        const color = selectedOption ? selectedOption.getAttribute('data-color') : null;
        const row = select.closest('.color-row');
        const preview = row ? row.querySelector('.color-preview') : null;
        const imagePreview = row ? row.querySelector('.color-image-preview') : null;
        const imageEmpty = row ? row.querySelector('.color-image-empty') : null;

        if (preview) {
            preview.style.backgroundColor = color || '#ffffff';
        }

        if (imagePreview && imageEmpty) {
            const colorId = Number(select.value || 0);
            const src = colorId > 0 ? (colorImageMap[colorId] || '') : '';
            updateRowsByColor(colorId, src);
        }
    }

    function initColorRows(scope = document) {
        scope.querySelectorAll('.color-selector').forEach(function(select) {
            mostrarColorSeleccionado(select);
            select.addEventListener('change', function () {
                const colorId = Number(select.value || 0);
                const src = colorId > 0 ? (colorImageMap[colorId] || '') : '';
                updateRowsByColor(colorId, src);
                refreshColorImageControls();
            });
        });

        scope.querySelectorAll('.color-image-input').forEach(function(input) {
            input.addEventListener('change', function () {
                const row = input.closest('.color-row');
                if (!row) return;

                const imagePreview = row.querySelector('.color-image-preview');
                const imageEmpty = row.querySelector('.color-image-empty');
                const file = input.files && input.files[0] ? input.files[0] : null;
                const colorSelector = row.querySelector('.color-selector');
                const colorId = Number(colorSelector?.value || 0);

                if (!imagePreview || !imageEmpty) return;

                if (!file) {
                    imagePreview.src = '';
                    imagePreview.classList.add('hidden');
                    imageEmpty.classList.remove('hidden');
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (e) {
                    const src = e.target?.result || '';
                    if (colorId > 0) {
                        updateRowsByColor(colorId, src);
                    } else {
                        imagePreview.src = src;
                        imagePreview.classList.remove('hidden');
                        imageEmpty.classList.add('hidden');
                    }
                };
                reader.readAsDataURL(file);
            });
        });
    }

    initColorRows();
    refreshColorImageControls();

    document.getElementById('add-color-row').addEventListener('click', function () {
        const container = document.getElementById('colores-container');
        const row = container.querySelector('.color-row').cloneNode(true);
        setRowIndex(row, colorRowCounter++);
        row.querySelectorAll('input').forEach(el => {
            if (el.name.startsWith('variante_id[')) {
                el.value = '';
            } else if (el.type !== 'file') {
                el.value = '';
            } else {
                el.value = null;
            }
        });
        const imagePreview = row.querySelector('.color-image-preview');
        const imageEmpty = row.querySelector('.color-image-empty');
        const colorImageIdInput = row.querySelector('.color-image-id-input');
        const removeWrap = row.querySelector('.color-image-remove-wrap');
        const removeCheckbox = row.querySelector('.color-image-remove-checkbox');
        if (imagePreview && imageEmpty) {
            imagePreview.src = '';
            imagePreview.classList.add('hidden');
            imageEmpty.classList.remove('hidden');
        }
        if (colorImageIdInput) colorImageIdInput.value = '';
        if (removeCheckbox) {
            removeCheckbox.value = '';
            removeCheckbox.checked = false;
        }
        if (removeWrap) removeWrap.classList.add('hidden');
        row.querySelectorAll('select').forEach(el => el.selectedIndex = 0);
        container.appendChild(row);
        initColorRows(row);
        refreshColorImageControls();
    });

    document.addEventListener('click', function (e) {
        if (e.target && e.target.classList.contains('remove-color-row')) {
            const rows = document.querySelectorAll('.color-row');
            if (rows.length > 1) {
                e.target.closest('.color-row').remove();
                refreshColorImageControls();
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

        const endpoint = categoriasPorLineaTemplate.replace('__LINEA__', encodeURIComponent(lineaId));
        fetch(endpoint, { headers: { 'Accept': 'application/json' } })
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
