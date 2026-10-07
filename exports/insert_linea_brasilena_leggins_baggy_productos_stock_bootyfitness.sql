-- Carga idempotente de inventario BootyFitness.
-- Lote: linea_brasilena_leggins_baggy_20260904
-- Interpretacion: 28 variantes y 58 piezas.
-- Una cantidad para varias tallas se conserva en una talla compuesta.
-- El stock inicial se representa en colores_productos y en inventario_movimientos;
-- la base real no contiene una tabla separada de stock_inicial.

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET SESSION character_set_connection = 'utf8mb4';
SET SESSION collation_connection = 'utf8mb4_unicode_ci';

SET @lote_key = 'linea_brasilena_leggins_baggy_20260904';
SET @codigo_producto = 'leggins_baggy_lb_20260904';
SET @codigo_compra = 'carga_lb_leggins_baggy_20260904';

START TRANSACTION;

DROP TEMPORARY TABLE IF EXISTS tmp_bf_lote_productos;
CREATE TEMPORARY TABLE tmp_bf_lote_productos (
    lote_key VARCHAR(100) NOT NULL,
    codigo_producto VARCHAR(255) NOT NULL,
    nombre_producto VARCHAR(255) NOT NULL,
    linea_nombre VARCHAR(255) NOT NULL,
    categoria_nombre VARCHAR(255) NOT NULL,
    cantidad_total INT NOT NULL,
    precio_compra_usd DECIMAL(12,2) NOT NULL,
    precio_venta_usd DECIMAL(12,2) NOT NULL,
    promocion_activa TINYINT(1) NOT NULL DEFAULT 0,
    precio_promocional DECIMAL(12,2) NULL,
    promocion_etiqueta VARCHAR(60) NULL,
    observacion TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TEMPORARY TABLE IF EXISTS tmp_bf_lote_variantes;
CREATE TEMPORARY TABLE tmp_bf_lote_variantes (
    codigo_producto VARCHAR(255) NOT NULL,
    color_nombre VARCHAR(255) NOT NULL,
    color_hex VARCHAR(255) NOT NULL,
    talla VARCHAR(255) NOT NULL,
    cantidad INT NOT NULL,
    observacion TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO tmp_bf_lote_productos (
    lote_key, codigo_producto, nombre_producto, linea_nombre, categoria_nombre,
    cantidad_total, precio_compra_usd, precio_venta_usd, promocion_activa,
    precio_promocional, promocion_etiqueta, observacion
) VALUES (
    @lote_key,
    @codigo_producto,
    'LEGGINS BAGGY PROMO',
    'linea_brasilena',
    'LEGGINS BASTA ANCHA',
    58,
    13.00,
    30.00,
    0,
    NULL,
    'PROMO',
    'Tallas compuestas normalizadas; S-M, M-L y XS-S conservan la cantidad completa indicada.'
);

INSERT INTO tmp_bf_lote_variantes (
    codigo_producto, color_nombre, color_hex, talla, cantidad, observacion
) VALUES
    (@codigo_producto, 'negro', '#000000', 'XS', 6,
     'Talla individual: XS con 6 piezas.'),
    (@codigo_producto, 'negro', '#000000', 'S', 4,
     'Talla individual: S con 4 piezas.'),
    (@codigo_producto, 'negro', '#000000', 'M-L', 4,
     'Talla compuesta: M y L con una cantidad única de 4 piezas.'),
    (@codigo_producto, 'rosa', '#F781CE', 'XS', 4,
     'Talla individual: XS con 4 piezas.'),
    (@codigo_producto, 'rosa', '#F781CE', 'S-M', 1,
     'Talla compuesta: S y M con una cantidad única de 1 pieza.'),
    (@codigo_producto, 'rosa', '#F781CE', 'M-L', 2,
     'Talla compuesta: M y L con una cantidad única de 2 piezas.'),
    (@codigo_producto, 'vino', '#800020', 'XS', 3,
     'Talla individual: XS con 3 piezas.'),
    (@codigo_producto, 'vino', '#800020', 'S-M', 1,
     'Normalizacion de S -M; talla compuesta S-M con 1 pieza.'),
    (@codigo_producto, 'gris', '#A9A9A9', 'XS', 1,
     'Talla individual: XS con 1 pieza.'),
    (@codigo_producto, 'gris', '#A9A9A9', 'S-M', 2,
     'Talla compuesta: S y M con una cantidad única de 2 piezas.'),
    (@codigo_producto, 'gris oxford', '#6F7078', 'M-L', 1,
     'Color normalizado a gris oxford; talla compuesta M-L con 1 pieza.'),
    (@codigo_producto, 'lila', '#F2DEFF', 'XS-S', 1,
     'Talla compuesta: XS y S con una cantidad única de 1 pieza.'),
    (@codigo_producto, 'lila', '#F2DEFF', 'M', 2,
     'Talla individual: M con 2 piezas.'),
    (@codigo_producto, 'azul', '#0A17E1', 'S-M', 4,
     'Talla compuesta: S y M con una cantidad única de 4 piezas.'),
    (@codigo_producto, 'azul', '#0A17E1', 'XS', 1,
     'Talla individual: XS con 1 pieza.'),
    (@codigo_producto, 'azul claro', '#6FA8DC', 'XS-S', 1,
     'Talla compuesta: XS y S con una cantidad única de 1 pieza.'),
    (@codigo_producto, 'mezclilla', '#3F5F7F', 'XS-S', 3,
     'Talla compuesta: XS y S con una cantidad única de 3 piezas.'),
    (@codigo_producto, 'chedron', '#D42804', 'XS-S', 2,
     'Talla compuesta: XS y S con una cantidad única de 2 piezas.'),
    (@codigo_producto, 'chedron', '#D42804', 'S-M', 1,
     'Talla compuesta: S y M con una cantidad única de 1 pieza.'),
    (@codigo_producto, 'verde aqua', '#00BFA6', 'M-L', 1,
     'Talla compuesta: M y L con una cantidad única de 1 pieza.'),
    (@codigo_producto, 'celeste bebe', '#B7E4F9', 'M-L', 1,
     'Normalizacion de celeste bebé; talla compuesta M-L con 1 pieza.'),
    (@codigo_producto, 'uva', '#6F2DA8', 'S-M', 3,
     'Talla compuesta: S y M con una cantidad única de 3 piezas.'),
    (@codigo_producto, 'uva', '#6F2DA8', 'XS', 2,
     'Talla individual: XS con 2 piezas.'),
    (@codigo_producto, 'rojo', '#EA1515', 'S-M', 3,
     'Talla compuesta: S y M con una cantidad única de 3 piezas.'),
    (@codigo_producto, 'coffy', '#6F4E37', 'M-L', 1,
     'Talla compuesta: M y L con una cantidad única de 1 pieza.'),
    (@codigo_producto, 'cafe', '#8A2F2F', 'S-M', 1,
     'Normalizacion de café; talla compuesta S-M con 1 pieza.'),
    (@codigo_producto, 'lila bebe', '#D8B4E2', 'M-L', 1,
     'Normalizacion de lila bebé; talla compuesta M-L con 1 pieza.'),
    (@codigo_producto, 'negro musgo', '#3B4633', 'M-L', 1,
     'Talla compuesta: M y L con una cantidad única de 1 pieza.');

SET @linea_id = (
    SELECT lr.id
    FROM lineas_ropa lr
    WHERE LOWER(TRIM(lr.nombre_linea)) COLLATE utf8mb4_unicode_ci = 'linea_brasilena' COLLATE utf8mb4_unicode_ci
    ORDER BY lr.id
    LIMIT 1
);

INSERT INTO lineas_ropa (nombre_linea, estado_linea, created_at, updated_at)
SELECT 'linea_brasilena', '1', NOW(), NOW()
FROM (SELECT 1 AS uno) AS src
WHERE @linea_id IS NULL
  AND NOT EXISTS (
      SELECT 1
      FROM lineas_ropa lr
      WHERE LOWER(TRIM(lr.nombre_linea)) COLLATE utf8mb4_unicode_ci = 'linea_brasilena' COLLATE utf8mb4_unicode_ci
  );

SET @linea_id = (
    SELECT lr.id
    FROM lineas_ropa lr
    WHERE LOWER(TRIM(lr.nombre_linea)) COLLATE utf8mb4_unicode_ci = 'linea_brasilena' COLLATE utf8mb4_unicode_ci
    ORDER BY lr.id
    LIMIT 1
);

SET @categoria_id = (
    SELECT cp.id
    FROM categorias_productos cp
    WHERE cp.linea_ropa_id = @linea_id
      AND LOWER(TRIM(cp.nombre_categoria)) COLLATE utf8mb4_unicode_ci = 'leggins basta ancha' COLLATE utf8mb4_unicode_ci
    ORDER BY cp.id
    LIMIT 1
);

INSERT INTO categorias_productos (nombre_categoria, linea_ropa_id, estado_categoria, created_at, updated_at)
SELECT 'LEGGINS BASTA ANCHA', @linea_id, '1', NOW(), NOW()
FROM (SELECT 1 AS uno) AS src
WHERE @linea_id IS NOT NULL
  AND @categoria_id IS NULL
  AND NOT EXISTS (
      SELECT 1
      FROM categorias_productos cp
      WHERE cp.linea_ropa_id = @linea_id
        AND LOWER(TRIM(cp.nombre_categoria)) COLLATE utf8mb4_unicode_ci = 'leggins basta ancha' COLLATE utf8mb4_unicode_ci
  );

SET @categoria_id = (
    SELECT cp.id
    FROM categorias_productos cp
    WHERE cp.linea_ropa_id = @linea_id
      AND LOWER(TRIM(cp.nombre_categoria)) COLLATE utf8mb4_unicode_ci = 'leggins basta ancha' COLLATE utf8mb4_unicode_ci
    ORDER BY cp.id
    LIMIT 1
);

INSERT INTO colores (nombre_color, codigo_color, created_at, updated_at)
SELECT v.color_nombre, v.color_hex, NOW(), NOW()
FROM (
    SELECT 'negro' AS color_nombre, '#000000' AS color_hex
    UNION ALL SELECT 'rosa', '#F781CE'
    UNION ALL SELECT 'vino', '#800020'
    UNION ALL SELECT 'gris', '#A9A9A9'
    UNION ALL SELECT 'gris oxford', '#6F7078'
    UNION ALL SELECT 'lila', '#F2DEFF'
    UNION ALL SELECT 'azul', '#0A17E1'
    UNION ALL SELECT 'azul claro', '#6FA8DC'
    UNION ALL SELECT 'mezclilla', '#3F5F7F'
    UNION ALL SELECT 'chedron', '#D42804'
    UNION ALL SELECT 'verde aqua', '#00BFA6'
    UNION ALL SELECT 'celeste bebe', '#B7E4F9'
    UNION ALL SELECT 'uva', '#6F2DA8'
    UNION ALL SELECT 'rojo', '#EA1515'
    UNION ALL SELECT 'coffy', '#6F4E37'
    UNION ALL SELECT 'cafe', '#8A2F2F'
    UNION ALL SELECT 'lila bebe', '#D8B4E2'
    UNION ALL SELECT 'negro musgo', '#3B4633'
) AS v
WHERE NOT EXISTS (
    SELECT 1
    FROM colores c
    WHERE LOWER(TRIM(c.nombre_color)) COLLATE utf8mb4_unicode_ci = LOWER(TRIM(v.color_nombre)) COLLATE utf8mb4_unicode_ci
       OR LOWER(TRIM(c.codigo_color)) COLLATE utf8mb4_unicode_ci = LOWER(TRIM(v.color_hex)) COLLATE utf8mb4_unicode_ci
);

SET @proveedor_id = (
    SELECT p.id
    FROM proveedores p
    WHERE p.estado_proveedor = '1'
      AND (
          LOWER(CONCAT_WS(' ', p.tienda_proveedor, p.nombres_proveedor, p.apellidos_proveedor)) COLLATE utf8mb4_unicode_ci LIKE '%linea brasileña%' COLLATE utf8mb4_unicode_ci
          OR LOWER(CONCAT_WS(' ', p.tienda_proveedor, p.nombres_proveedor, p.apellidos_proveedor)) COLLATE utf8mb4_unicode_ci LIKE '%linea brasilena%' COLLATE utf8mb4_unicode_ci
      )
    ORDER BY p.id
    LIMIT 1
);

SELECT COALESCE(SUM(v.cantidad), 0), COUNT(*)
INTO @total_piezas, @total_variantes
FROM tmp_bf_lote_variantes v;

SELECT COALESCE(SUM(v.cantidad * p.precio_compra_usd), 0.00),
       COALESCE(SUM(v.cantidad * p.precio_venta_usd), 0.00)
INTO @total_costo, @total_venta
FROM tmp_bf_lote_variantes v
JOIN tmp_bf_lote_productos p ON p.codigo_producto = v.codigo_producto;

INSERT INTO compras (
    codigo_compra, descripcion_compra, envio_compra, total_pesos_compra,
    proveedor_id, estado_compra, total_dolares_compra, importacion_compra,
    total_final_compra, created_at, updated_at
)
SELECT
    @codigo_compra,
    'Carga inicial LEGGINS BAGGY PROMO - linea brasilena',
    0.00,
    0.00,
    @proveedor_id,
    '1',
    @total_costo,
    0.00,
    @total_costo,
    NOW(),
    NOW()
FROM (SELECT 1 AS uno) AS src
WHERE @proveedor_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM compras c
      WHERE c.codigo_compra = @codigo_compra COLLATE utf8mb4_unicode_ci
  );

SET @compra_id = (
    SELECT c.id
    FROM compras c
    WHERE c.codigo_compra = @codigo_compra COLLATE utf8mb4_unicode_ci
    LIMIT 1
);

UPDATE compras c
SET descripcion_compra = 'Carga inicial LEGGINS BAGGY PROMO - linea brasilena',
    proveedor_id = @proveedor_id,
    estado_compra = '1',
    total_pesos_compra = 0.00,
    total_dolares_compra = @total_costo,
    importacion_compra = 0.00,
    total_final_compra = @total_costo,
    updated_at = NOW()
WHERE c.id = @compra_id;

INSERT INTO productos (
    codigo_producto, descripcion_producto, imagen_producto,
    cantidad_compra_producto, stock_venta_producto, precio_pesos_producto,
    precio_dolares_producto, precio_venta_producto, precio_promocional,
    promocion_activa, promocion_fecha_inicio, promocion_fecha_fin,
    promocion_etiqueta, compras_id, estado_producto, created_at, updated_at,
    categoria_producto_id
)
SELECT
    p.codigo_producto,
    p.nombre_producto,
    NULL,
    p.cantidad_total,
    p.cantidad_total,
    0.00,
    p.precio_compra_usd,
    p.precio_venta_usd,
    p.precio_promocional,
    p.promocion_activa,
    NULL,
    NULL,
    p.promocion_etiqueta,
    @compra_id,
    '1',
    NOW(),
    NOW(),
    @categoria_id
FROM tmp_bf_lote_productos p
WHERE @compra_id IS NOT NULL
  AND @categoria_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM productos pr
      WHERE pr.codigo_producto = p.codigo_producto COLLATE utf8mb4_unicode_ci
  );

SET @producto_id = (
    SELECT pr.id
    FROM productos pr
    WHERE pr.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci
    LIMIT 1
);

UPDATE productos pr
JOIN tmp_bf_lote_productos p ON p.codigo_producto = pr.codigo_producto
SET pr.descripcion_producto = p.nombre_producto,
    pr.cantidad_compra_producto = p.cantidad_total,
    pr.stock_venta_producto = p.cantidad_total,
    pr.precio_pesos_producto = 0.00,
    pr.precio_dolares_producto = p.precio_compra_usd,
    pr.precio_venta_producto = p.precio_venta_usd,
    pr.precio_promocional = p.precio_promocional,
    pr.promocion_activa = p.promocion_activa,
    pr.promocion_etiqueta = p.promocion_etiqueta,
    pr.compras_id = @compra_id,
    pr.categoria_producto_id = @categoria_id,
    pr.estado_producto = '1',
    pr.updated_at = NOW()
WHERE pr.id = @producto_id;

INSERT INTO colores_productos (
    producto_id, colores_id, cantidad_por_color, stock_por_color,
    talla_por_color, created_at, updated_at
)
SELECT pr.id, c.id, v.cantidad, v.cantidad, v.talla, NOW(), NOW()
FROM tmp_bf_lote_variantes v
JOIN productos pr ON pr.codigo_producto = v.codigo_producto COLLATE utf8mb4_unicode_ci
JOIN colores c ON (
    LOWER(TRIM(c.nombre_color)) COLLATE utf8mb4_unicode_ci = LOWER(TRIM(v.color_nombre)) COLLATE utf8mb4_unicode_ci
    OR LOWER(TRIM(c.codigo_color)) COLLATE utf8mb4_unicode_ci = LOWER(TRIM(v.color_hex)) COLLATE utf8mb4_unicode_ci
)
WHERE NOT EXISTS (
    SELECT 1
    FROM colores_productos cp
    WHERE cp.producto_id = pr.id
      AND cp.colores_id = c.id
      AND UPPER(TRIM(cp.talla_por_color)) COLLATE utf8mb4_unicode_ci = UPPER(TRIM(v.talla)) COLLATE utf8mb4_unicode_ci
);

INSERT INTO compra_items (
    compra_id, producto_id, colores_id, talla, cantidad, stock_ingresado,
    costo_unitario_pesos, costo_unitario_dolares, subtotal_pesos,
    subtotal_dolares, estado, observacion, metadata, created_at, updated_at
)
SELECT
    @compra_id,
    pr.id,
    c.id,
    v.talla,
    v.cantidad,
    v.cantidad,
    0.00,
    p.precio_compra_usd,
    0.00,
    v.cantidad * p.precio_compra_usd,
    'ACTIVO',
    v.observacion,
    JSON_OBJECT('lote', @lote_key, 'fuente', 'carga_sql'),
    NOW(),
    NOW()
FROM tmp_bf_lote_variantes v
JOIN tmp_bf_lote_productos p ON p.codigo_producto = v.codigo_producto
JOIN productos pr ON pr.codigo_producto = v.codigo_producto COLLATE utf8mb4_unicode_ci
JOIN colores c ON (
    LOWER(TRIM(c.nombre_color)) COLLATE utf8mb4_unicode_ci = LOWER(TRIM(v.color_nombre)) COLLATE utf8mb4_unicode_ci
    OR LOWER(TRIM(c.codigo_color)) COLLATE utf8mb4_unicode_ci = LOWER(TRIM(v.color_hex)) COLLATE utf8mb4_unicode_ci
)
WHERE NOT EXISTS (
    SELECT 1
    FROM compra_items ci
    WHERE ci.compra_id = @compra_id
      AND ci.producto_id = pr.id
      AND ci.colores_id = c.id
      AND UPPER(TRIM(ci.talla)) COLLATE utf8mb4_unicode_ci = UPPER(TRIM(v.talla)) COLLATE utf8mb4_unicode_ci
);

INSERT INTO inventario_movimientos (
    producto_id, colores_productos_id, pedido_id, compra_id, user_id,
    tipo_movimiento, cantidad, stock_antes, stock_despues, motivo,
    metadata, created_at, updated_at
)
SELECT
    pr.id,
    cp.id,
    NULL,
    @compra_id,
    NULL,
    'ENTRADA_COMPRA',
    v.cantidad,
    0,
    v.cantidad,
    'Entrada inicial por carga SQL de producto',
    JSON_OBJECT('lote', @lote_key, 'compra_item', ci.id),
    NOW(),
    NOW()
FROM tmp_bf_lote_variantes v
JOIN productos pr ON pr.codigo_producto = v.codigo_producto COLLATE utf8mb4_unicode_ci
JOIN colores c ON (
    LOWER(TRIM(c.nombre_color)) COLLATE utf8mb4_unicode_ci = LOWER(TRIM(v.color_nombre)) COLLATE utf8mb4_unicode_ci
    OR LOWER(TRIM(c.codigo_color)) COLLATE utf8mb4_unicode_ci = LOWER(TRIM(v.color_hex)) COLLATE utf8mb4_unicode_ci
)
JOIN colores_productos cp ON cp.producto_id = pr.id
    AND cp.colores_id = c.id
    AND UPPER(TRIM(cp.talla_por_color)) COLLATE utf8mb4_unicode_ci = UPPER(TRIM(v.talla)) COLLATE utf8mb4_unicode_ci
JOIN compra_items ci ON ci.compra_id = @compra_id
    AND ci.producto_id = pr.id
    AND ci.colores_id = c.id
    AND UPPER(TRIM(ci.talla)) COLLATE utf8mb4_unicode_ci = UPPER(TRIM(v.talla)) COLLATE utf8mb4_unicode_ci
WHERE NOT EXISTS (
    SELECT 1
    FROM inventario_movimientos im
    WHERE im.compra_id = @compra_id
      AND im.producto_id = pr.id
      AND im.colores_productos_id = cp.id
      AND im.tipo_movimiento = 'ENTRADA_COMPRA'
      AND im.cantidad = v.cantidad
);

SELECT 'INTERPRETACION' AS seccion,
       v.color_nombre AS color,
       v.talla,
       v.cantidad,
       p.precio_compra_usd AS costo_unitario_usd,
       p.precio_venta_usd AS precio_venta_unitario_usd,
       v.observacion
FROM tmp_bf_lote_variantes v
JOIN tmp_bf_lote_productos p ON p.codigo_producto = v.codigo_producto
ORDER BY v.color_nombre, v.talla;

SELECT 'TOTALES_LOTE' AS seccion,
       @total_variantes AS variantes,
       @total_piezas AS piezas,
       @total_costo AS costo_total_usd,
       @total_venta AS venta_estimada_usd,
       @total_venta - @total_costo AS utilidad_bruta_usd;

DROP TEMPORARY TABLE IF EXISTS tmp_bf_lote_variantes;
DROP TEMPORARY TABLE IF EXISTS tmp_bf_lote_productos;

COMMIT;
