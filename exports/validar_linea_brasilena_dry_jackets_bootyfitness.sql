-- Validacion posterior del lote linea_brasilena_dry_jackets_20260904.

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET SESSION character_set_connection = 'utf8mb4';
SET SESSION collation_connection = 'utf8mb4_unicode_ci';

SET @codigo_producto = 'dry_jackets_lb_20260904';
SET @codigo_compra = 'carga_lb_dry_jackets_20260904';

SELECT 'COMPRA' AS seccion,
       c.id,
       c.codigo_compra,
       c.proveedor_id,
       c.total_dolares_compra,
       c.total_final_compra,
       c.estado_compra
FROM compras c
WHERE c.codigo_compra = @codigo_compra COLLATE utf8mb4_unicode_ci;

SELECT 'PRODUCTO' AS seccion,
       pr.id,
       pr.codigo_producto,
       pr.descripcion_producto,
       lr.id AS linea_id,
       lr.nombre_linea,
       cat.id AS categoria_id,
       cat.nombre_categoria,
       pr.cantidad_compra_producto,
       pr.stock_venta_producto,
       pr.precio_dolares_producto,
       pr.precio_venta_producto,
       pr.precio_promocional,
       pr.promocion_activa,
       pr.promocion_etiqueta,
       pr.compras_id
FROM productos pr
LEFT JOIN categorias_productos cat ON cat.id = pr.categoria_producto_id
LEFT JOIN lineas_ropa lr ON lr.id = cat.linea_ropa_id
WHERE pr.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci;

SELECT 'VARIANTES' AS seccion,
       cp.id,
       c.nombre_color,
       c.codigo_color,
       cp.talla_por_color,
       cp.cantidad_por_color,
       cp.stock_por_color
FROM colores_productos cp
JOIN productos pr ON pr.id = cp.producto_id
JOIN colores c ON c.id = cp.colores_id
WHERE pr.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci
ORDER BY c.nombre_color, cp.talla_por_color;

SELECT 'COMPRA_ITEMS' AS seccion,
       ci.id,
       ci.compra_id,
       ci.producto_id,
       c.nombre_color,
       ci.talla,
       ci.cantidad,
       ci.stock_ingresado,
       ci.costo_unitario_dolares,
       ci.subtotal_dolares,
       ci.estado
FROM compra_items ci
JOIN compras co ON co.id = ci.compra_id
JOIN colores c ON c.id = ci.colores_id
WHERE co.codigo_compra = @codigo_compra COLLATE utf8mb4_unicode_ci
ORDER BY ci.id;

SELECT 'MOVIMIENTOS' AS seccion,
       im.id,
       im.producto_id,
       im.colores_productos_id,
       im.compra_id,
       im.tipo_movimiento,
       im.cantidad,
       im.stock_antes,
       im.stock_despues,
       im.motivo
FROM inventario_movimientos im
JOIN compras co ON co.id = im.compra_id
WHERE co.codigo_compra = @codigo_compra COLLATE utf8mb4_unicode_ci
ORDER BY im.id;

SELECT 'RESUMEN' AS seccion,
       (SELECT COUNT(*)
        FROM productos pr
        WHERE pr.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci) AS productos,
       (SELECT COUNT(*)
        FROM colores_productos cp
        JOIN productos pr ON pr.id = cp.producto_id
        WHERE pr.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci) AS variantes,
       (SELECT COALESCE(SUM(CAST(cp.cantidad_por_color AS UNSIGNED)), 0)
        FROM colores_productos cp
        JOIN productos pr ON pr.id = cp.producto_id
        WHERE pr.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci) AS piezas,
       (SELECT COALESCE(SUM(ci.subtotal_dolares), 0.00)
        FROM compra_items ci
        JOIN compras co ON co.id = ci.compra_id
        WHERE co.codigo_compra = @codigo_compra COLLATE utf8mb4_unicode_ci) AS costo_total_usd,
       (SELECT COALESCE(SUM(ci.cantidad * pr.precio_venta_producto), 0.00)
        FROM compra_items ci
        JOIN compras co ON co.id = ci.compra_id
        JOIN productos pr ON pr.id = ci.producto_id
        WHERE co.codigo_compra = @codigo_compra COLLATE utf8mb4_unicode_ci) AS venta_estimada_usd,
       (SELECT COALESCE(SUM(ci.cantidad * pr.precio_venta_producto), 0.00)
        FROM compra_items ci
        JOIN compras co ON co.id = ci.compra_id
        JOIN productos pr ON pr.id = ci.producto_id
        WHERE co.codigo_compra = @codigo_compra COLLATE utf8mb4_unicode_ci)
       - (SELECT COALESCE(SUM(ci.subtotal_dolares), 0.00)
          FROM compra_items ci
          JOIN compras co ON co.id = ci.compra_id
          WHERE co.codigo_compra = @codigo_compra COLLATE utf8mb4_unicode_ci) AS utilidad_bruta_usd;

SELECT 'OBJETIVOS_TALLAS' AS validacion, COUNT(*) AS total
FROM colores_productos cp
JOIN productos pr ON pr.id = cp.producto_id
JOIN colores c ON c.id = cp.colores_id
WHERE pr.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci
  AND LOWER(TRIM(c.nombre_color)) COLLATE utf8mb4_unicode_ci = 'vainilla' COLLATE utf8mb4_unicode_ci
  AND LOWER(TRIM(c.codigo_color)) COLLATE utf8mb4_unicode_ci = '#f3e5ab' COLLATE utf8mb4_unicode_ci
  AND UPPER(TRIM(cp.talla_por_color)) COLLATE utf8mb4_unicode_ci = 'M-L' COLLATE utf8mb4_unicode_ci
  AND CAST(cp.cantidad_por_color AS UNSIGNED) = 1
  AND CAST(cp.stock_por_color AS UNSIGNED) = 1;

SELECT 'DETALLES_ESPERADOS' AS validacion, COUNT(*) AS total
FROM compra_items ci
JOIN compras co ON co.id = ci.compra_id
JOIN colores c ON c.id = ci.colores_id
WHERE co.codigo_compra = @codigo_compra COLLATE utf8mb4_unicode_ci
  AND LOWER(TRIM(c.nombre_color)) COLLATE utf8mb4_unicode_ci = 'vainilla' COLLATE utf8mb4_unicode_ci
  AND UPPER(TRIM(ci.talla)) COLLATE utf8mb4_unicode_ci = 'M-L' COLLATE utf8mb4_unicode_ci
  AND ci.cantidad = 1
  AND ci.stock_ingresado = 1
  AND ci.costo_unitario_dolares = 12.00
  AND ci.subtotal_dolares = 12.00;

SELECT 'MOVIMIENTOS_ESPERADOS' AS validacion, COUNT(*) AS total
FROM inventario_movimientos im
JOIN compras co ON co.id = im.compra_id
WHERE co.codigo_compra = @codigo_compra COLLATE utf8mb4_unicode_ci
  AND im.tipo_movimiento = 'ENTRADA_COMPRA'
  AND im.cantidad = 1
  AND im.stock_antes = 0
  AND im.stock_despues = 1;

SELECT 'VENTAS_PEDIDOS_AFECTADOS' AS validacion, COUNT(*) AS total
FROM detalle_pedidos dp
JOIN productos pr ON pr.id = dp.producto_id
WHERE pr.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci;

SELECT CASE
    WHEN (SELECT COUNT(*)
          FROM productos pr
          WHERE pr.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci) = 1
     AND (SELECT COUNT(*)
          FROM compras c
          WHERE c.codigo_compra = @codigo_compra COLLATE utf8mb4_unicode_ci) = 1
     AND (SELECT COUNT(*)
          FROM colores_productos cp
          JOIN productos pr ON pr.id = cp.producto_id
          WHERE pr.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci) = 1
     AND (SELECT COALESCE(SUM(CAST(cp.cantidad_por_color AS UNSIGNED)), 0)
          FROM colores_productos cp
          JOIN productos pr ON pr.id = cp.producto_id
          WHERE pr.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci) = 1
     AND (SELECT COALESCE(SUM(ci.subtotal_dolares), 0.00)
          FROM compra_items ci
          JOIN compras co ON co.id = ci.compra_id
          WHERE co.codigo_compra = @codigo_compra COLLATE utf8mb4_unicode_ci) = 12.00
     AND (SELECT COALESCE(SUM(ci.cantidad * pr.precio_venta_producto), 0.00)
          FROM compra_items ci
          JOIN compras co ON co.id = ci.compra_id
          JOIN productos pr ON pr.id = ci.producto_id
          WHERE co.codigo_compra = @codigo_compra COLLATE utf8mb4_unicode_ci) = 25.00
     AND (SELECT COUNT(*)
          FROM compra_items ci
          JOIN compras co ON co.id = ci.compra_id
          WHERE co.codigo_compra = @codigo_compra COLLATE utf8mb4_unicode_ci) = 1
     AND (SELECT COUNT(*)
          FROM inventario_movimientos im
          JOIN compras co ON co.id = im.compra_id
          WHERE co.codigo_compra = @codigo_compra COLLATE utf8mb4_unicode_ci
            AND im.tipo_movimiento = 'ENTRADA_COMPRA') = 1
     AND (SELECT COUNT(*)
          FROM colores_productos cp
          JOIN productos pr ON pr.id = cp.producto_id
          JOIN colores c ON c.id = cp.colores_id
          WHERE pr.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci
            AND LOWER(TRIM(c.nombre_color)) COLLATE utf8mb4_unicode_ci = 'vainilla' COLLATE utf8mb4_unicode_ci
            AND UPPER(TRIM(cp.talla_por_color)) COLLATE utf8mb4_unicode_ci = 'M-L' COLLATE utf8mb4_unicode_ci
            AND CAST(cp.cantidad_por_color AS UNSIGNED) = 1) = 1
     AND (SELECT COUNT(*)
          FROM detalle_pedidos dp
          JOIN productos pr ON pr.id = dp.producto_id
          WHERE pr.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci) = 0
    THEN 'OK'
    ELSE 'REVISAR'
END AS resultado_validacion;
