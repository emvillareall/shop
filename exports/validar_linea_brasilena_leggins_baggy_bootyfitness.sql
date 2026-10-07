-- Validacion posterior del lote linea_brasilena_leggins_baggy_20260904.

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET SESSION character_set_connection = 'utf8mb4';
SET SESSION collation_connection = 'utf8mb4_unicode_ci';

SET @codigo_producto = 'leggins_baggy_lb_20260904';
SET @codigo_compra = 'carga_lb_leggins_baggy_20260904';

SELECT 'PRODUCTO' AS seccion,
       pr.id,
       pr.codigo_producto,
       pr.descripcion_producto,
       lr.nombre_linea,
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
       (SELECT COUNT(*) FROM productos pr
        WHERE pr.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci) AS productos,
       (SELECT COUNT(*) FROM colores_productos cp JOIN productos pr ON pr.id = cp.producto_id
        WHERE pr.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci) AS variantes,
       (SELECT COALESCE(SUM(CAST(cp.cantidad_por_color AS UNSIGNED)), 0)
        FROM colores_productos cp JOIN productos pr ON pr.id = cp.producto_id
        WHERE pr.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci) AS piezas,
       (SELECT COALESCE(SUM(ci.subtotal_dolares), 0.00)
        FROM compra_items ci JOIN compras co ON co.id = ci.compra_id
        WHERE co.codigo_compra = @codigo_compra COLLATE utf8mb4_unicode_ci) AS costo_total_usd,
       (SELECT COALESCE(SUM(ci.cantidad * pr.precio_venta_producto), 0.00)
        FROM compra_items ci JOIN compras co ON co.id = ci.compra_id
        JOIN productos pr ON pr.id = ci.producto_id
        WHERE co.codigo_compra = @codigo_compra COLLATE utf8mb4_unicode_ci) AS venta_estimada_usd,
       (SELECT COALESCE(SUM(ci.cantidad * pr.precio_venta_producto), 0.00)
        FROM compra_items ci JOIN compras co ON co.id = ci.compra_id
        JOIN productos pr ON pr.id = ci.producto_id
        WHERE co.codigo_compra = @codigo_compra COLLATE utf8mb4_unicode_ci)
       - (SELECT COALESCE(SUM(ci.subtotal_dolares), 0.00)
          FROM compra_items ci JOIN compras co ON co.id = ci.compra_id
          WHERE co.codigo_compra = @codigo_compra COLLATE utf8mb4_unicode_ci) AS utilidad_bruta_usd;

SELECT 'VARIANTES_ESPERADAS' AS validacion, COUNT(*) AS total
FROM (
    SELECT 'negro' AS color_nombre, 'XS' AS talla, 6 AS cantidad
    UNION ALL SELECT 'negro', 'S', 4
    UNION ALL SELECT 'negro', 'M-L', 4
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
JOIN productos pr ON pr.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci
JOIN colores_productos cp ON cp.producto_id = pr.id
JOIN colores c ON c.id = cp.colores_id
WHERE LOWER(TRIM(c.nombre_color)) COLLATE utf8mb4_unicode_ci = esperado.color_nombre COLLATE utf8mb4_unicode_ci
  AND UPPER(TRIM(cp.talla_por_color)) COLLATE utf8mb4_unicode_ci = UPPER(TRIM(esperado.talla)) COLLATE utf8mb4_unicode_ci
  AND CAST(cp.cantidad_por_color AS UNSIGNED) = esperado.cantidad
  AND CAST(cp.stock_por_color AS UNSIGNED) = esperado.cantidad;

SELECT 'DETALLES_ESPERADOS' AS validacion, COUNT(*) AS total
FROM (
    SELECT 'negro' AS color_nombre, 'XS' AS talla, 6 AS cantidad
    UNION ALL SELECT 'negro', 'S', 4
    UNION ALL SELECT 'negro', 'M-L', 4
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

SELECT 'MOVIMIENTOS_ESPERADOS' AS validacion,
       COUNT(*) AS total,
       SUM(CASE WHEN im.stock_antes = 0 AND im.stock_despues = im.cantidad THEN 1 ELSE 0 END) AS entradas_con_stock_correcto
FROM inventario_movimientos im
JOIN compras co ON co.id = im.compra_id
WHERE co.codigo_compra = @codigo_compra COLLATE utf8mb4_unicode_ci
  AND im.tipo_movimiento = 'ENTRADA_COMPRA';

SELECT 'TALLAS_COMPUESTAS' AS validacion,
       COUNT(*) AS total_variantes_compuestas
FROM colores_productos cp
JOIN productos pr ON pr.id = cp.producto_id
WHERE pr.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci
  AND INSTR(cp.talla_por_color, '-') > 0;

SELECT 'PEDIDOS_AFECTADOS' AS validacion, COUNT(*) AS total
FROM detalle_pedidos dp
JOIN productos pr ON pr.id = dp.producto_id
WHERE pr.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci;

SELECT CASE
    WHEN (SELECT COUNT(*) FROM productos pr
          WHERE pr.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci) = 1
     AND (SELECT COUNT(*) FROM compras co
          WHERE co.codigo_compra = @codigo_compra COLLATE utf8mb4_unicode_ci) = 1
     AND (SELECT COUNT(*) FROM productos pr
          WHERE pr.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci
            AND pr.cantidad_compra_producto = '58'
            AND pr.stock_venta_producto = '58'
            AND pr.precio_dolares_producto = 13.00
            AND pr.precio_venta_producto = 30.00
            AND pr.promocion_etiqueta = 'PROMO') = 1
     AND (SELECT COUNT(*) FROM colores_productos cp JOIN productos pr ON pr.id = cp.producto_id
          WHERE pr.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci) = 28
     AND (SELECT COALESCE(SUM(CAST(cp.cantidad_por_color AS UNSIGNED)), 0)
          FROM colores_productos cp JOIN productos pr ON pr.id = cp.producto_id
          WHERE pr.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci) = 58
     AND (SELECT COUNT(*) FROM compra_items ci JOIN compras co ON co.id = ci.compra_id
          WHERE co.codigo_compra = @codigo_compra COLLATE utf8mb4_unicode_ci) = 28
     AND (SELECT COALESCE(SUM(ci.cantidad), 0) FROM compra_items ci JOIN compras co ON co.id = ci.compra_id
          WHERE co.codigo_compra = @codigo_compra COLLATE utf8mb4_unicode_ci) = 58
     AND (SELECT COALESCE(SUM(ci.subtotal_dolares), 0.00) FROM compra_items ci JOIN compras co ON co.id = ci.compra_id
          WHERE co.codigo_compra = @codigo_compra COLLATE utf8mb4_unicode_ci) = 754.00
     AND (SELECT COUNT(*) FROM inventario_movimientos im JOIN compras co ON co.id = im.compra_id
          WHERE co.codigo_compra = @codigo_compra COLLATE utf8mb4_unicode_ci
            AND im.tipo_movimiento = 'ENTRADA_COMPRA') = 28
     AND (SELECT COUNT(*) FROM detalle_pedidos dp JOIN productos pr ON pr.id = dp.producto_id
          WHERE pr.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci) = 0
    THEN 'OK'
    ELSE 'REVISAR'
END AS resultado_validacion;
