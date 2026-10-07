-- Correccion idempotente de LEGGINS BAGGY PROMO.
-- Cambio: Negro / M-L de 4 a 5 piezas.
-- No modifica ventas, pedidos ni productos distintos.

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET SESSION character_set_connection = 'utf8mb4';
SET SESSION collation_connection = 'utf8mb4_unicode_ci';

SET @codigo_producto = 'leggins_baggy_lb_20260904';
SET @codigo_compra = 'carga_lb_leggins_baggy_20260904';

SET @producto_id = (
    SELECT p.id
    FROM productos p
    WHERE p.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci
    LIMIT 1
);

SET @compra_id = (
    SELECT c.id
    FROM compras c
    WHERE c.codigo_compra = @codigo_compra COLLATE utf8mb4_unicode_ci
    LIMIT 1
);

SET @negro_id = (
    SELECT c.id
    FROM colores c
    WHERE LOWER(TRIM(c.nombre_color)) COLLATE utf8mb4_unicode_ci = 'negro' COLLATE utf8mb4_unicode_ci
    ORDER BY c.id
    LIMIT 1
);

SET @negro_ml_cp_id = (
    SELECT cp.id
    FROM colores_productos cp
    WHERE cp.producto_id = @producto_id
      AND cp.colores_id = @negro_id
      AND UPPER(TRIM(cp.talla_por_color)) COLLATE utf8mb4_unicode_ci = 'M-L' COLLATE utf8mb4_unicode_ci
    ORDER BY cp.id
    LIMIT 1
);

SET @negro_ml_ci_id = (
    SELECT ci.id
    FROM compra_items ci
    WHERE ci.compra_id = @compra_id
      AND ci.producto_id = @producto_id
      AND ci.colores_id = @negro_id
      AND UPPER(TRIM(ci.talla)) COLLATE utf8mb4_unicode_ci = 'M-L' COLLATE utf8mb4_unicode_ci
    ORDER BY ci.id
    LIMIT 1
);

SET @negro_ml_mov_id = (
    SELECT im.id
    FROM inventario_movimientos im
    WHERE im.producto_id = @producto_id
      AND im.colores_productos_id = @negro_ml_cp_id
      AND im.compra_id = @compra_id
      AND im.tipo_movimiento = 'ENTRADA_COMPRA'
      AND im.pedido_id IS NULL
    ORDER BY im.id
    LIMIT 1
);

SET @pedidos_afectados = (
    SELECT COUNT(*)
    FROM detalle_pedidos dp
    WHERE dp.producto_id = @producto_id
       OR dp.id_color_producto IN (
           SELECT cp.id
           FROM colores_productos cp
           WHERE cp.producto_id = @producto_id
       )
);

SET @salidas_afectadas = (
    SELECT COUNT(*)
    FROM inventario_movimientos im
    WHERE im.producto_id = @producto_id
      AND (im.pedido_id IS NOT NULL OR im.tipo_movimiento <> 'ENTRADA_COMPRA')
);

SET @precheck_ok = IF(
    @producto_id IS NOT NULL
    AND @compra_id IS NOT NULL
    AND @negro_id IS NOT NULL
    AND @negro_ml_cp_id IS NOT NULL
    AND @negro_ml_ci_id IS NOT NULL
    AND @negro_ml_mov_id IS NOT NULL
    AND @pedidos_afectados = 0
    AND @salidas_afectadas = 0,
    1,
    0
);

SELECT 'PRECHECK_CORRECCION' AS seccion,
       @producto_id AS producto_id,
       @compra_id AS compra_id,
       @negro_ml_cp_id AS colores_productos_id,
       @negro_ml_ci_id AS compra_item_id,
       @negro_ml_mov_id AS movimiento_id,
       @pedidos_afectados AS pedidos_afectados,
       @salidas_afectadas AS salidas_afectadas,
       @precheck_ok AS listo_para_corregir;

START TRANSACTION;

UPDATE productos p
SET p.cantidad_compra_producto = '59',
    p.stock_venta_producto = '59',
    p.updated_at = NOW()
WHERE @precheck_ok = 1
  AND p.id = @producto_id;

UPDATE compras c
SET c.total_dolares_compra = 767.00,
    c.total_final_compra = 767.00,
    c.updated_at = NOW()
WHERE @precheck_ok = 1
  AND c.id = @compra_id;

UPDATE colores_productos cp
SET cp.cantidad_por_color = '5',
    cp.stock_por_color = '5',
    cp.updated_at = NOW()
WHERE @precheck_ok = 1
  AND cp.id = @negro_ml_cp_id;

UPDATE compra_items ci
SET ci.cantidad = 5,
    ci.stock_ingresado = 5,
    ci.costo_unitario_dolares = 13.00,
    ci.subtotal_dolares = 65.00,
    ci.updated_at = NOW()
WHERE @precheck_ok = 1
  AND ci.id = @negro_ml_ci_id;

UPDATE inventario_movimientos im
SET im.cantidad = 5,
    im.stock_despues = 5,
    im.updated_at = NOW()
WHERE @precheck_ok = 1
  AND im.id = @negro_ml_mov_id
  AND im.tipo_movimiento = 'ENTRADA_COMPRA'
  AND im.pedido_id IS NULL;

SELECT CASE
    WHEN @precheck_ok = 1 THEN 'CORRECCION_APLICADA'
    ELSE 'DETENER_REVISAR_PRECHECK'
END AS resultado_correccion;

SELECT 'RESULTADO_CORRECCION' AS seccion,
       (SELECT COUNT(*) FROM colores_productos cp WHERE cp.producto_id = @producto_id) AS variantes_finales,
       (SELECT COALESCE(SUM(CAST(cp.cantidad_por_color AS UNSIGNED)), 0) FROM colores_productos cp WHERE cp.producto_id = @producto_id) AS piezas_finales,
       (SELECT COALESCE(SUM(ci.subtotal_dolares), 0.00) FROM compra_items ci WHERE ci.compra_id = @compra_id) AS costo_total_final,
       (SELECT COALESCE(SUM(ci.cantidad * p.precio_venta_producto), 0.00)
        FROM compra_items ci
        JOIN productos p ON p.id = ci.producto_id
        WHERE ci.compra_id = @compra_id) AS venta_estimada_final,
       (SELECT COALESCE(SUM(ci.cantidad * p.precio_venta_producto), 0.00)
        FROM compra_items ci
        JOIN productos p ON p.id = ci.producto_id
        WHERE ci.compra_id = @compra_id)
       - (SELECT COALESCE(SUM(ci.subtotal_dolares), 0.00) FROM compra_items ci WHERE ci.compra_id = @compra_id) AS utilidad_bruta_final;

COMMIT;
