-- Validacion posterior de la correccion de LEGGINS BAGGY PROMO.

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET SESSION character_set_connection = 'utf8mb4';
SET SESSION collation_connection = 'utf8mb4_unicode_ci';

SET @codigo_producto = 'leggins_baggy_lb_20260904';
SET @codigo_compra = 'carga_lb_leggins_baggy_20260904';

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

SELECT 'VARIANTAS_ESPERADAS' AS validacion, COUNT(*) AS total
FROM (
    SELECT 'negro' AS color_nombre, 'XS' AS talla, 6 AS cantidad
    UNION ALL SELECT 'negro', 'S', 4
    UNION ALL SELECT 'negro', 'M-L', 5
    UNION ALL SELECT 'rosa', 'XS', 4
    UNION ALL SELECT 'rosa', 'S-M', 1
    UNION ALL SELECT 'rosa', 'M-L', 2
    UNION ALL SELECT 'vino', 'XS', 3
    UNION ALL SELECT 'vino', 'S-M', 1
    UNION ALL SELECT 'gris', 'XS', 1
    UNION ALL SELECT 'gris', 'S-M', 2
    UNION ALL SELECT 'gris oxford', 'M-L', 1
    UNION ALL SELECT 'lila', 'XS-S', 1
    UNION ALL SELECT 'lila', 'M', 2
    UNION ALL SELECT 'azul', 'S-M', 4
    UNION ALL SELECT 'azul', 'XS', 1
    UNION ALL SELECT 'azul claro', 'XS-S', 1
    UNION ALL SELECT 'mezclilla', 'XS-S', 3
    UNION ALL SELECT 'chedron', 'XS-S', 2
    UNION ALL SELECT 'chedron', 'S-M', 1
    UNION ALL SELECT 'verde aqua', 'M-L', 1
    UNION ALL SELECT 'celeste bebe', 'M-L', 1
    UNION ALL SELECT 'uva', 'S-M', 3
    UNION ALL SELECT 'uva', 'XS', 2
    UNION ALL SELECT 'rojo', 'S-M', 3
    UNION ALL SELECT 'coffy', 'M-L', 1
    UNION ALL SELECT 'cafe', 'S-M', 1
    UNION ALL SELECT 'lila bebe', 'M-L', 1
    UNION ALL SELECT 'negro musgo', 'M-L', 1
) AS esperado
JOIN productos p ON p.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci
JOIN colores_productos cp ON cp.producto_id = p.id
JOIN colores c ON c.id = cp.colores_id
WHERE LOWER(TRIM(c.nombre_color)) COLLATE utf8mb4_unicode_ci = esperado.color_nombre COLLATE utf8mb4_unicode_ci
  AND UPPER(TRIM(cp.talla_por_color)) COLLATE utf8mb4_unicode_ci = UPPER(TRIM(esperado.talla)) COLLATE utf8mb4_unicode_ci
  AND CAST(cp.cantidad_por_color AS UNSIGNED) = esperado.cantidad
  AND CAST(cp.stock_por_color AS UNSIGNED) = esperado.cantidad;

SELECT 'DETALLES_ESPERADOS' AS validacion, COUNT(*) AS total
FROM (
    SELECT 'negro' AS color_nombre, 'XS' AS talla, 6 AS cantidad
    UNION ALL SELECT 'negro', 'S', 4
    UNION ALL SELECT 'negro', 'M-L', 5
    UNION ALL SELECT 'rosa', 'XS', 4
    UNION ALL SELECT 'rosa', 'S-M', 1
    UNION ALL SELECT 'rosa', 'M-L', 2
    UNION ALL SELECT 'vino', 'XS', 3
    UNION ALL SELECT 'vino', 'S-M', 1
    UNION ALL SELECT 'gris', 'XS', 1
    UNION ALL SELECT 'gris', 'S-M', 2
    UNION ALL SELECT 'gris oxford', 'M-L', 1
    UNION ALL SELECT 'lila', 'XS-S', 1
    UNION ALL SELECT 'lila', 'M', 2
    UNION ALL SELECT 'azul', 'S-M', 4
    UNION ALL SELECT 'azul', 'XS', 1
    UNION ALL SELECT 'azul claro', 'XS-S', 1
    UNION ALL SELECT 'mezclilla', 'XS-S', 3
    UNION ALL SELECT 'chedron', 'XS-S', 2
    UNION ALL SELECT 'chedron', 'S-M', 1
    UNION ALL SELECT 'verde aqua', 'M-L', 1
    UNION ALL SELECT 'celeste bebe', 'M-L', 1
    UNION ALL SELECT 'uva', 'S-M', 3
    UNION ALL SELECT 'uva', 'XS', 2
    UNION ALL SELECT 'rojo', 'S-M', 3
    UNION ALL SELECT 'coffy', 'M-L', 1
    UNION ALL SELECT 'cafe', 'S-M', 1
    UNION ALL SELECT 'lila bebe', 'M-L', 1
    UNION ALL SELECT 'negro musgo', 'M-L', 1
) AS esperado
JOIN compras co ON co.codigo_compra = @codigo_compra COLLATE utf8mb4_unicode_ci
JOIN compra_items ci ON ci.compra_id = co.id
JOIN colores c ON c.id = ci.colores_id
WHERE LOWER(TRIM(c.nombre_color)) COLLATE utf8mb4_unicode_ci = esperado.color_nombre COLLATE utf8mb4_unicode_ci
  AND UPPER(TRIM(ci.talla)) COLLATE utf8mb4_unicode_ci = UPPER(TRIM(esperado.talla)) COLLATE utf8mb4_unicode_ci
  AND ci.cantidad = esperado.cantidad
  AND ci.stock_ingresado = esperado.cantidad
  AND ci.costo_unitario_dolares = 13.00;

SELECT 'PEDIDOS_AFECTADOS' AS validacion, COUNT(*) AS total
FROM detalle_pedidos dp
JOIN productos p ON p.id = dp.producto_id
WHERE p.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci;

SELECT 'SALIDAS_O_MOVIMIENTOS_NO_INICIALES' AS validacion, COUNT(*) AS total
FROM inventario_movimientos im
JOIN productos p ON p.id = im.producto_id
WHERE p.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci
  AND (im.pedido_id IS NOT NULL OR im.tipo_movimiento <> 'ENTRADA_COMPRA');

SELECT CASE
    WHEN (SELECT COUNT(*) FROM productos p WHERE p.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci) = 1
     AND (SELECT COUNT(*) FROM compras c WHERE c.codigo_compra = @codigo_compra COLLATE utf8mb4_unicode_ci) = 1
     AND (SELECT COUNT(*)
          FROM productos p
          JOIN categorias_productos cat ON cat.id = p.categoria_producto_id
          JOIN lineas_ropa l ON l.id = cat.linea_ropa_id
          WHERE p.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci
            AND LOWER(TRIM(l.nombre_linea)) COLLATE utf8mb4_unicode_ci IN (
                'linea_brasilena' COLLATE utf8mb4_unicode_ci,
                'linea brasileña' COLLATE utf8mb4_unicode_ci
            )
            AND LOWER(TRIM(cat.nombre_categoria)) COLLATE utf8mb4_unicode_ci = 'leggins basta ancha' COLLATE utf8mb4_unicode_ci) = 1
     AND (SELECT COUNT(*)
          FROM productos p
          WHERE p.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci
            AND p.cantidad_compra_producto = '59'
            AND p.stock_venta_producto = '59'
            AND p.precio_dolares_producto = 13.00
            AND p.precio_venta_producto = 30.00
            AND p.promocion_etiqueta = 'PROMO') = 1
     AND (SELECT COUNT(*)
          FROM colores_productos cp
          JOIN productos p ON p.id = cp.producto_id
          WHERE p.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci) = 28
     AND (SELECT COALESCE(SUM(CAST(cp.cantidad_por_color AS UNSIGNED)), 0)
          FROM colores_productos cp
          JOIN productos p ON p.id = cp.producto_id
          WHERE p.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci) = 59
     AND (SELECT COUNT(*)
          FROM compra_items ci
          JOIN compras c ON c.id = ci.compra_id
          WHERE c.codigo_compra = @codigo_compra COLLATE utf8mb4_unicode_ci) = 28
     AND (SELECT COALESCE(SUM(ci.cantidad), 0)
          FROM compra_items ci
          JOIN compras c ON c.id = ci.compra_id
          WHERE c.codigo_compra = @codigo_compra COLLATE utf8mb4_unicode_ci) = 59
     AND (SELECT COALESCE(SUM(ci.subtotal_dolares), 0.00)
          FROM compra_items ci
          JOIN compras c ON c.id = ci.compra_id
          WHERE c.codigo_compra = @codigo_compra COLLATE utf8mb4_unicode_ci) = 767.00
     AND (SELECT COUNT(*)
          FROM inventario_movimientos im
          JOIN compras c ON c.id = im.compra_id
          WHERE c.codigo_compra = @codigo_compra COLLATE utf8mb4_unicode_ci
            AND im.tipo_movimiento = 'ENTRADA_COMPRA'
            AND im.pedido_id IS NULL) = 28
     AND (SELECT COALESCE(SUM(im.cantidad), 0)
          FROM inventario_movimientos im
          JOIN compras c ON c.id = im.compra_id
          WHERE c.codigo_compra = @codigo_compra COLLATE utf8mb4_unicode_ci
            AND im.tipo_movimiento = 'ENTRADA_COMPRA'
            AND im.pedido_id IS NULL) = 59
     AND (SELECT COUNT(*)
          FROM detalle_pedidos dp
          JOIN productos p ON p.id = dp.producto_id
          WHERE p.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci) = 0
     AND (SELECT COUNT(*)
          FROM inventario_movimientos im
          JOIN productos p ON p.id = im.producto_id
          WHERE p.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci
            AND (im.pedido_id IS NOT NULL OR im.tipo_movimiento <> 'ENTRADA_COMPRA')) = 0
    THEN 'OK'
    ELSE 'REVISAR'
END AS resultado_validacion;
