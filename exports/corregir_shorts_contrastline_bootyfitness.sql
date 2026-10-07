-- Correccion idempotente de SHORTS CONTRASTLINE PROMO.
-- Producto: shorts_contrastline_la_20260904
-- Cambio: agregar rosa/M-L con 1 pieza.
-- No se modifican ventas ni pedidos.

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET SESSION character_set_connection = 'utf8mb4';
SET SESSION collation_connection = 'utf8mb4_unicode_ci';

SET @codigo_producto = 'shorts_contrastline_la_20260904';
SET @codigo_compra = 'carga_la_shorts_contrastline_20260904';

START TRANSACTION;

SET @producto_id = (
    SELECT pr.id
    FROM productos pr
    WHERE pr.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci
    LIMIT 1
);

SET @compra_id = (
    SELECT co.id
    FROM compras co
    WHERE co.codigo_compra = @codigo_compra COLLATE utf8mb4_unicode_ci
    LIMIT 1
);

SET @rosa_id = (
    SELECT c.id
    FROM colores c
    WHERE LOWER(TRIM(c.nombre_color)) COLLATE utf8mb4_unicode_ci = 'rosa' COLLATE utf8mb4_unicode_ci
    ORDER BY c.id
    LIMIT 1
);

-- Actualiza los totales del producto y de su compra inicial.
UPDATE productos pr
SET pr.cantidad_compra_producto = '22',
    pr.stock_venta_producto = '22',
    pr.updated_at = NOW()
WHERE pr.id = @producto_id;

UPDATE compras co
SET co.total_dolares_compra = 154.00,
    co.total_final_compra = 154.00,
    co.updated_at = NOW()
WHERE co.id = @compra_id;

-- La variante rosa/XS-S ya existía; se conserva en 1 pieza.
UPDATE colores_productos cp
SET cp.cantidad_por_color = '1',
    cp.stock_por_color = '1',
    cp.updated_at = NOW()
WHERE cp.producto_id = @producto_id
  AND cp.colores_id = @rosa_id
  AND UPPER(TRIM(cp.talla_por_color)) COLLATE utf8mb4_unicode_ci = 'XS-S' COLLATE utf8mb4_unicode_ci;

UPDATE compra_items ci
SET ci.cantidad = 1,
    ci.stock_ingresado = 1,
    ci.costo_unitario_dolares = 7.00,
    ci.subtotal_dolares = 7.00,
    ci.updated_at = NOW()
WHERE ci.compra_id = @compra_id
  AND ci.producto_id = @producto_id
  AND ci.colores_id = @rosa_id
  AND UPPER(TRIM(ci.talla)) COLLATE utf8mb4_unicode_ci = 'XS-S' COLLATE utf8mb4_unicode_ci;

UPDATE inventario_movimientos im
JOIN colores_productos cp ON cp.id = im.colores_productos_id
SET im.cantidad = 1,
    im.stock_despues = 1,
    im.updated_at = NOW()
WHERE im.producto_id = @producto_id
  AND im.compra_id = @compra_id
  AND cp.producto_id = @producto_id
  AND cp.colores_id = @rosa_id
  AND UPPER(TRIM(cp.talla_por_color)) COLLATE utf8mb4_unicode_ci = 'XS-S' COLLATE utf8mb4_unicode_ci
  AND im.tipo_movimiento = 'ENTRADA_COMPRA'
  AND im.pedido_id IS NULL;

-- Agrega o normaliza la nueva variante rosa/M-L.
UPDATE colores_productos cp
SET cp.cantidad_por_color = '1',
    cp.stock_por_color = '1',
    cp.updated_at = NOW()
WHERE cp.producto_id = @producto_id
  AND cp.colores_id = @rosa_id
  AND UPPER(TRIM(cp.talla_por_color)) COLLATE utf8mb4_unicode_ci = 'M-L' COLLATE utf8mb4_unicode_ci;

INSERT INTO colores_productos (
    producto_id, colores_id, cantidad_por_color, stock_por_color,
    talla_por_color, created_at, updated_at
)
SELECT @producto_id, @rosa_id, '1', '1', 'M-L', NOW(), NOW()
FROM (SELECT 1 AS uno) AS src
WHERE @producto_id IS NOT NULL
  AND @rosa_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM colores_productos cp
      WHERE cp.producto_id = @producto_id
        AND cp.colores_id = @rosa_id
        AND UPPER(TRIM(cp.talla_por_color)) COLLATE utf8mb4_unicode_ci = 'M-L' COLLATE utf8mb4_unicode_ci
  );

UPDATE compra_items ci
SET ci.cantidad = 1,
    ci.stock_ingresado = 1,
    ci.costo_unitario_dolares = 7.00,
    ci.subtotal_dolares = 7.00,
    ci.updated_at = NOW()
WHERE ci.compra_id = @compra_id
  AND ci.producto_id = @producto_id
  AND ci.colores_id = @rosa_id
  AND UPPER(TRIM(ci.talla)) COLLATE utf8mb4_unicode_ci = 'M-L' COLLATE utf8mb4_unicode_ci;

INSERT INTO compra_items (
    compra_id, producto_id, colores_id, talla, cantidad, stock_ingresado,
    costo_unitario_pesos, costo_unitario_dolares, subtotal_pesos,
    subtotal_dolares, estado, observacion, metadata, created_at, updated_at
)
SELECT
    @compra_id,
    @producto_id,
    @rosa_id,
    'M-L',
    1,
    1,
    0.00,
    7.00,
    0.00,
    7.00,
    'ACTIVO',
    'Variante agregada por correccion de inventario: rosa/M-L 1 pieza.',
    JSON_OBJECT('lote', 'correccion_shorts_contrastline_20260904', 'fuente', 'correccion_sql'),
    NOW(),
    NOW()
FROM (SELECT 1 AS uno) AS src
WHERE @compra_id IS NOT NULL
  AND @producto_id IS NOT NULL
  AND @rosa_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM compra_items ci
      WHERE ci.compra_id = @compra_id
        AND ci.producto_id = @producto_id
        AND ci.colores_id = @rosa_id
        AND UPPER(TRIM(ci.talla)) COLLATE utf8mb4_unicode_ci = 'M-L' COLLATE utf8mb4_unicode_ci
  );

SET @rosa_ml_cp_id = (
    SELECT cp.id
    FROM colores_productos cp
    WHERE cp.producto_id = @producto_id
      AND cp.colores_id = @rosa_id
      AND UPPER(TRIM(cp.talla_por_color)) COLLATE utf8mb4_unicode_ci = 'M-L' COLLATE utf8mb4_unicode_ci
    ORDER BY cp.id
    LIMIT 1
);

SET @rosa_ml_ci_id = (
    SELECT ci.id
    FROM compra_items ci
    WHERE ci.compra_id = @compra_id
      AND ci.producto_id = @producto_id
      AND ci.colores_id = @rosa_id
      AND UPPER(TRIM(ci.talla)) COLLATE utf8mb4_unicode_ci = 'M-L' COLLATE utf8mb4_unicode_ci
    ORDER BY ci.id
    LIMIT 1
);

INSERT INTO inventario_movimientos (
    producto_id, colores_productos_id, pedido_id, compra_id, user_id,
    tipo_movimiento, cantidad, stock_antes, stock_despues, motivo,
    metadata, created_at, updated_at
)
SELECT
    @producto_id,
    @rosa_ml_cp_id,
    NULL,
    @compra_id,
    NULL,
    'ENTRADA_COMPRA',
    1,
    0,
    1,
    'Entrada por correccion de inventario de producto',
    JSON_OBJECT('lote', 'correccion_shorts_contrastline_20260904', 'compra_item', @rosa_ml_ci_id),
    NOW(),
    NOW()
FROM (SELECT 1 AS uno) AS src
WHERE @producto_id IS NOT NULL
  AND @compra_id IS NOT NULL
  AND @rosa_ml_cp_id IS NOT NULL
  AND @rosa_ml_ci_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM inventario_movimientos im
      WHERE im.producto_id = @producto_id
        AND im.colores_productos_id = @rosa_ml_cp_id
        AND im.compra_id = @compra_id
        AND im.tipo_movimiento = 'ENTRADA_COMPRA'
        AND im.pedido_id IS NULL
  );

SELECT 'CAMBIOS_APLICADOS' AS seccion,
       @producto_id AS producto_id,
       'rosa/M-L: agregado 1 pieza' AS cambios;

SELECT 'RESULTADO_CORRECCION' AS seccion,
       (SELECT COUNT(*) FROM colores_productos cp WHERE cp.producto_id = @producto_id) AS variantes_finales,
       (SELECT COALESCE(SUM(CAST(cp.cantidad_por_color AS UNSIGNED)), 0) FROM colores_productos cp WHERE cp.producto_id = @producto_id) AS piezas_finales,
       (SELECT COALESCE(SUM(ci.subtotal_dolares), 0.00) FROM compra_items ci WHERE ci.compra_id = @compra_id) AS costo_total_final,
       (SELECT COALESCE(SUM(ci.cantidad * pr.precio_venta_producto), 0.00) FROM compra_items ci JOIN productos pr ON pr.id = ci.producto_id WHERE ci.compra_id = @compra_id) AS venta_estimada_final,
       (SELECT COALESCE(SUM(ci.cantidad * pr.precio_venta_producto), 0.00) FROM compra_items ci JOIN productos pr ON pr.id = ci.producto_id WHERE ci.compra_id = @compra_id)
       - (SELECT COALESCE(SUM(ci.subtotal_dolares), 0.00) FROM compra_items ci WHERE ci.compra_id = @compra_id) AS utilidad_bruta_final;

COMMIT;
