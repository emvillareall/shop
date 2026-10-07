-- Validacion posterior de la carga de LEGGINS IMPACT.

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET SESSION character_set_connection = 'utf8mb4';
SET SESSION collation_connection = 'utf8mb4_unicode_ci';

SET @codigo_producto = 'leggins_impact_la_20260905';
SET @codigo_compra = 'carga_la_leggins_impact_20260905';

SELECT 'PRODUCTO' AS seccion,
       p.id,
       p.codigo_producto,
       p.descripcion_producto,
       l.nombre_linea,
       cat.nombre_categoria,
       p.cantidad_compra_producto,
       p.stock_venta_producto,
       p.precio_dolares_producto,
       p.precio_venta_producto,
       p.precio_promocional,
       p.promocion_activa,
       p.promocion_etiqueta,
       p.compras_id
FROM productos p
LEFT JOIN categorias_productos cat ON cat.id = p.categoria_producto_id
LEFT JOIN lineas_ropa l ON l.id = cat.linea_ropa_id
WHERE p.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci;

SELECT 'VARIANTES' AS seccion,
       cp.id,
       c.nombre_color,
       c.codigo_color,
       cp.talla_por_color,
       cp.cantidad_por_color,
       cp.stock_por_color
FROM colores_productos cp
JOIN productos p ON p.id = cp.producto_id
JOIN colores c ON c.id = cp.colores_id
WHERE p.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci
ORDER BY c.nombre_color, cp.talla_por_color;

SELECT 'COMPRA_ITEMS' AS seccion,
       ci.id,
       c.nombre_color,
       ci.talla,
       ci.cantidad,
       ci.stock_ingresado,
       ci.costo_unitario_dolares,
       ci.subtotal_dolares,
       ci.estado
FROM compra_items ci
JOIN compras co ON co.id = ci.compra_id
JOIN productos p ON p.id = ci.producto_id
JOIN colores c ON c.id = ci.colores_id
WHERE co.codigo_compra = @codigo_compra COLLATE utf8mb4_unicode_ci
  AND p.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci
ORDER BY ci.id;

SELECT 'MOVIMIENTOS' AS seccion,
       im.id,
       im.colores_productos_id,
       im.compra_id,
       im.pedido_id,
       im.tipo_movimiento,
       im.cantidad,
       im.stock_antes,
       im.stock_despues
FROM inventario_movimientos im
JOIN compras co ON co.id = im.compra_id
JOIN productos p ON p.id = im.producto_id
WHERE co.codigo_compra = @codigo_compra COLLATE utf8mb4_unicode_ci
  AND p.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci
ORDER BY im.id;

SELECT 'RESUMEN' AS seccion,
       (SELECT COUNT(*) FROM productos p WHERE p.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci) AS productos,
       (SELECT COUNT(*)
        FROM colores_productos cp
        JOIN productos p ON p.id = cp.producto_id
        WHERE p.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci) AS variantes,
       (SELECT COALESCE(SUM(CAST(cp.cantidad_por_color AS UNSIGNED)), 0)
        FROM colores_productos cp
        JOIN productos p ON p.id = cp.producto_id
        WHERE p.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci) AS piezas,
       (SELECT COALESCE(SUM(ci.subtotal_dolares), 0.00)
        FROM compra_items ci
        JOIN compras co ON co.id = ci.compra_id
        WHERE co.codigo_compra = @codigo_compra COLLATE utf8mb4_unicode_ci) AS costo_total_usd,
       (SELECT COALESCE(SUM(ci.cantidad * p.precio_venta_producto), 0.00)
        FROM compra_items ci
        JOIN compras co ON co.id = ci.compra_id
        JOIN productos p ON p.id = ci.producto_id
        WHERE co.codigo_compra = @codigo_compra COLLATE utf8mb4_unicode_ci) AS venta_estimada_usd,
       (SELECT COALESCE(SUM(ci.cantidad * p.precio_venta_producto), 0.00)
        FROM compra_items ci
        JOIN compras co ON co.id = ci.compra_id
        JOIN productos p ON p.id = ci.producto_id
        WHERE co.codigo_compra = @codigo_compra COLLATE utf8mb4_unicode_ci)
       - (SELECT COALESCE(SUM(ci.subtotal_dolares), 0.00)
          FROM compra_items ci
          JOIN compras co ON co.id = ci.compra_id
          WHERE co.codigo_compra = @codigo_compra COLLATE utf8mb4_unicode_ci) AS utilidad_bruta_usd;

SELECT 'VARIANTES_ESPERADAS' AS validacion, COUNT(*) AS total
FROM (
    SELECT 'negro' AS color_nombre, '#000000' AS codigo_color, 'XS-S' AS talla, 2 AS cantidad
    UNION ALL SELECT 'negro', '#000000', 'M', 4
    UNION ALL SELECT 'rosa barbie', '#E0218A', 'XS-S', 3
    UNION ALL SELECT 'vino quemado', '#6B2737', 'XS-S', 2
    UNION ALL SELECT 'vino rojizo', '#8B2F39', 'XS-S', 1
    UNION ALL SELECT 'vino rojizo', '#8B2F39', 'M', 1
    UNION ALL SELECT 'cafe', '#8a2f2f', 'XS-S', 2
    UNION ALL SELECT 'coral', '#FF7F50', 'XS-S', 2
    UNION ALL SELECT 'coral', '#FF7F50', 'M', 1
    UNION ALL SELECT 'azul', '#0a17e1', 'M', 1
    UNION ALL SELECT 'azul marino', '#1b1d81', 'XS-S', 1
) AS esperado
JOIN productos p ON p.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci
JOIN colores_productos cp ON cp.producto_id = p.id
JOIN colores c ON c.id = cp.colores_id
WHERE LOWER(TRIM(c.nombre_color)) COLLATE utf8mb4_unicode_ci = esperado.color_nombre COLLATE utf8mb4_unicode_ci
  AND LOWER(TRIM(c.codigo_color)) COLLATE utf8mb4_unicode_ci = LOWER(TRIM(esperado.codigo_color)) COLLATE utf8mb4_unicode_ci
  AND UPPER(TRIM(cp.talla_por_color)) COLLATE utf8mb4_unicode_ci = UPPER(TRIM(esperado.talla)) COLLATE utf8mb4_unicode_ci
  AND CAST(cp.cantidad_por_color AS UNSIGNED) = esperado.cantidad
  AND CAST(cp.stock_por_color AS UNSIGNED) = esperado.cantidad;

SELECT 'DETALLES_ESPERADOS' AS validacion, COUNT(*) AS total
FROM (
    SELECT 'negro' AS color_nombre, 'XS-S' AS talla, 2 AS cantidad
    UNION ALL SELECT 'negro', 'M', 4
    UNION ALL SELECT 'rosa barbie', 'XS-S', 3
    UNION ALL SELECT 'vino quemado', 'XS-S', 2
    UNION ALL SELECT 'vino rojizo', 'XS-S', 1
    UNION ALL SELECT 'vino rojizo', 'M', 1
    UNION ALL SELECT 'cafe', 'XS-S', 2
    UNION ALL SELECT 'coral', 'XS-S', 2
    UNION ALL SELECT 'coral', 'M', 1
    UNION ALL SELECT 'azul', 'M', 1
    UNION ALL SELECT 'azul marino', 'XS-S', 1
) AS esperado
JOIN compras co ON co.codigo_compra = @codigo_compra COLLATE utf8mb4_unicode_ci
JOIN compra_items ci ON ci.compra_id = co.id
JOIN colores c ON c.id = ci.colores_id
WHERE LOWER(TRIM(c.nombre_color)) COLLATE utf8mb4_unicode_ci = esperado.color_nombre COLLATE utf8mb4_unicode_ci
  AND UPPER(TRIM(ci.talla)) COLLATE utf8mb4_unicode_ci = UPPER(TRIM(esperado.talla)) COLLATE utf8mb4_unicode_ci
  AND ci.cantidad = esperado.cantidad
  AND ci.stock_ingresado = esperado.cantidad
  AND ci.costo_unitario_dolares = 12.50;

SELECT 'MOVIMIENTOS_ESPERADOS' AS validacion,
       COUNT(*) AS total,
       COALESCE(SUM(im.cantidad), 0) AS piezas_movidas
FROM inventario_movimientos im
JOIN compras co ON co.id = im.compra_id
JOIN productos p ON p.id = im.producto_id
WHERE co.codigo_compra = @codigo_compra COLLATE utf8mb4_unicode_ci
  AND p.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci
  AND im.tipo_movimiento = 'ENTRADA_COMPRA'
  AND im.pedido_id IS NULL;

SELECT 'PEDIDOS_AFECTADOS' AS validacion, COUNT(*) AS total
FROM detalle_pedidos dp
JOIN productos p ON p.id = dp.producto_id
WHERE p.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci;

SELECT CASE
    WHEN (SELECT COUNT(*) FROM productos p WHERE p.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci) = 1
     AND (SELECT COUNT(*) FROM compras c WHERE c.codigo_compra = @codigo_compra COLLATE utf8mb4_unicode_ci) = 1
     AND (SELECT COUNT(*)
          FROM productos p
          JOIN categorias_productos cat ON cat.id = p.categoria_producto_id
          JOIN lineas_ropa l ON l.id = cat.linea_ropa_id
          WHERE p.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci
            AND LOWER(TRIM(l.nombre_linea)) COLLATE utf8mb4_unicode_ci IN (
                'americana' COLLATE utf8mb4_unicode_ci,
                'linea_americana' COLLATE utf8mb4_unicode_ci,
                'linea americana' COLLATE utf8mb4_unicode_ci
            )
            AND LOWER(TRIM(cat.nombre_categoria)) COLLATE utf8mb4_unicode_ci = 'leggins' COLLATE utf8mb4_unicode_ci) = 1
     AND (SELECT COUNT(*)
          FROM productos p
          WHERE p.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci
            AND p.cantidad_compra_producto = '20'
            AND p.stock_venta_producto = '20'
            AND p.precio_dolares_producto = 12.50
            AND p.precio_venta_producto = 35.00
            AND p.promocion_etiqueta IS NULL) = 1
     AND (SELECT COUNT(*)
          FROM colores_productos cp
          JOIN productos p ON p.id = cp.producto_id
          WHERE p.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci) = 11
     AND (SELECT COALESCE(SUM(CAST(cp.cantidad_por_color AS UNSIGNED)), 0)
          FROM colores_productos cp
          JOIN productos p ON p.id = cp.producto_id
          WHERE p.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci) = 20
     AND (SELECT COUNT(*)
          FROM compra_items ci
          JOIN compras c ON c.id = ci.compra_id
          WHERE c.codigo_compra = @codigo_compra COLLATE utf8mb4_unicode_ci) = 11
     AND (SELECT COALESCE(SUM(ci.cantidad), 0)
          FROM compra_items ci
          JOIN compras c ON c.id = ci.compra_id
          WHERE c.codigo_compra = @codigo_compra COLLATE utf8mb4_unicode_ci) = 20
     AND (SELECT COALESCE(SUM(ci.subtotal_dolares), 0.00)
          FROM compra_items ci
          JOIN compras c ON c.id = ci.compra_id
          WHERE c.codigo_compra = @codigo_compra COLLATE utf8mb4_unicode_ci) = 250.00
     AND (SELECT COUNT(*)
          FROM inventario_movimientos im
          JOIN compras c ON c.id = im.compra_id
          JOIN productos p ON p.id = im.producto_id
          WHERE c.codigo_compra = @codigo_compra COLLATE utf8mb4_unicode_ci
            AND p.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci
            AND im.tipo_movimiento = 'ENTRADA_COMPRA'
            AND im.pedido_id IS NULL) = 11
     AND (SELECT COALESCE(SUM(im.cantidad), 0)
          FROM inventario_movimientos im
          JOIN compras c ON c.id = im.compra_id
          JOIN productos p ON p.id = im.producto_id
          WHERE c.codigo_compra = @codigo_compra COLLATE utf8mb4_unicode_ci
            AND p.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci
            AND im.tipo_movimiento = 'ENTRADA_COMPRA'
            AND im.pedido_id IS NULL) = 20
     AND (SELECT COUNT(*)
          FROM detalle_pedidos dp
          JOIN productos p ON p.id = dp.producto_id
          WHERE p.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci) = 0
    THEN 'OK'
    ELSE 'REVISAR'
END AS resultado_validacion;
