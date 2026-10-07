-- Validacion posterior de la correccion de CONTOUR T SHIRTS PROMO.

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET SESSION character_set_connection = 'utf8mb4';
SET SESSION collation_connection = 'utf8mb4_unicode_ci';

SET @codigo_producto = 'contour_t_shirts_lb_20260904';
SET @codigo_compra = 'carga_lb_contour_t_shirts_20260904';

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
       c.nombre_color,
       ci.talla,
       ci.cantidad,
       ci.stock_ingresado,
       ci.costo_unitario_dolares,
       ci.subtotal_dolares,
       ci.estado
FROM compra_items ci
JOIN compras co ON co.id = ci.compra_id
JOIN productos pr ON pr.id = ci.producto_id
JOIN colores c ON c.id = ci.colores_id
WHERE co.codigo_compra = @codigo_compra COLLATE utf8mb4_unicode_ci
  AND pr.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci
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
JOIN productos pr ON pr.id = im.producto_id
WHERE co.codigo_compra = @codigo_compra COLLATE utf8mb4_unicode_ci
  AND pr.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci
ORDER BY im.id;

SELECT 'RESUMEN' AS seccion,
       (SELECT COUNT(*) FROM productos pr WHERE pr.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci) AS productos,
       (SELECT COUNT(*) FROM colores_productos cp JOIN productos pr ON pr.id = cp.producto_id WHERE pr.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci) AS variantes,
       (SELECT COALESCE(SUM(CAST(cp.cantidad_por_color AS UNSIGNED)), 0) FROM colores_productos cp JOIN productos pr ON pr.id = cp.producto_id WHERE pr.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci) AS piezas,
       (SELECT COALESCE(SUM(ci.subtotal_dolares), 0.00) FROM compra_items ci JOIN compras co ON co.id = ci.compra_id WHERE co.codigo_compra = @codigo_compra COLLATE utf8mb4_unicode_ci) AS costo_total_usd,
       (SELECT COALESCE(SUM(ci.cantidad * pr.precio_venta_producto), 0.00) FROM compra_items ci JOIN compras co ON co.id = ci.compra_id JOIN productos pr ON pr.id = ci.producto_id WHERE co.codigo_compra = @codigo_compra COLLATE utf8mb4_unicode_ci) AS venta_estimada_usd,
       (SELECT COALESCE(SUM(ci.cantidad * pr.precio_venta_producto), 0.00) FROM compra_items ci JOIN compras co ON co.id = ci.compra_id JOIN productos pr ON pr.id = ci.producto_id WHERE co.codigo_compra = @codigo_compra COLLATE utf8mb4_unicode_ci)
       - (SELECT COALESCE(SUM(ci.subtotal_dolares), 0.00) FROM compra_items ci JOIN compras co ON co.id = ci.compra_id WHERE co.codigo_compra = @codigo_compra COLLATE utf8mb4_unicode_ci) AS utilidad_bruta_usd;

SELECT 'VARIANTES_ESPERADAS' AS validacion, COUNT(*) AS total
FROM (
    SELECT 'negro' AS color_nombre, '#000000' AS codigo_color, 'XS-S' AS talla, 6 AS cantidad
    UNION ALL SELECT 'negro', '#000000', 'M', 6
    UNION ALL SELECT 'rosa', '#F781CE', 'XS-S', 4
    UNION ALL SELECT 'azul marino', '#1B1D81', 'S-M', 1
    UNION ALL SELECT 'verde', '#008000', 'S-M', 1
    UNION ALL SELECT 'gris', '#A9A9A9', 'XS-S', 1
    UNION ALL SELECT 'azul', '#0A17E1', 'XS-S', 1
    UNION ALL SELECT 'azul', '#0A17E1', 'M-L', 1
    UNION ALL SELECT 'chedron', '#D42804', 'S-M', 1
) AS esperado
JOIN productos pr ON pr.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci
JOIN colores_productos cp ON cp.producto_id = pr.id
JOIN colores c ON c.id = cp.colores_id
WHERE LOWER(TRIM(c.nombre_color)) COLLATE utf8mb4_unicode_ci = esperado.color_nombre COLLATE utf8mb4_unicode_ci
  AND LOWER(TRIM(c.codigo_color)) COLLATE utf8mb4_unicode_ci = LOWER(TRIM(esperado.codigo_color)) COLLATE utf8mb4_unicode_ci
  AND UPPER(TRIM(cp.talla_por_color)) COLLATE utf8mb4_unicode_ci = UPPER(TRIM(esperado.talla)) COLLATE utf8mb4_unicode_ci
  AND CAST(cp.cantidad_por_color AS UNSIGNED) = esperado.cantidad
  AND CAST(cp.stock_por_color AS UNSIGNED) = esperado.cantidad;

SELECT 'DETALLES_ESPERADOS' AS validacion, COUNT(*) AS total
FROM (
    SELECT 'negro' AS color_nombre, 'XS-S' AS talla, 6 AS cantidad
    UNION ALL SELECT 'negro', 'M', 6
    UNION ALL SELECT 'rosa', 'XS-S', 4
    UNION ALL SELECT 'azul marino', 'S-M', 1
    UNION ALL SELECT 'verde', 'S-M', 1
    UNION ALL SELECT 'gris', 'XS-S', 1
    UNION ALL SELECT 'azul', 'XS-S', 1
    UNION ALL SELECT 'azul', 'M-L', 1
    UNION ALL SELECT 'chedron', 'S-M', 1
) AS esperado
JOIN compras co ON co.codigo_compra = @codigo_compra COLLATE utf8mb4_unicode_ci
JOIN compra_items ci ON ci.compra_id = co.id
JOIN colores c ON c.id = ci.colores_id
WHERE LOWER(TRIM(c.nombre_color)) COLLATE utf8mb4_unicode_ci = esperado.color_nombre COLLATE utf8mb4_unicode_ci
  AND UPPER(TRIM(ci.talla)) COLLATE utf8mb4_unicode_ci = UPPER(TRIM(esperado.talla)) COLLATE utf8mb4_unicode_ci
  AND ci.cantidad = esperado.cantidad
  AND ci.stock_ingresado = esperado.cantidad
  AND ci.costo_unitario_dolares = 10.00;

SELECT 'PEDIDOS_AFECTADOS' AS validacion, COUNT(*) AS total
FROM detalle_pedidos dp
JOIN productos pr ON pr.id = dp.producto_id
WHERE pr.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci;

SELECT 'SALIDAS_O_MOVIMIENTOS_NO_INICIALES' AS validacion, COUNT(*) AS total
FROM inventario_movimientos im
JOIN productos pr ON pr.id = im.producto_id
WHERE pr.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci
  AND (im.pedido_id IS NOT NULL OR im.tipo_movimiento <> 'ENTRADA_COMPRA');

SELECT CASE
    WHEN (SELECT COUNT(*) FROM productos pr WHERE pr.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci) = 1
     AND (SELECT COUNT(*) FROM productos pr JOIN categorias_productos cat ON cat.id = pr.categoria_producto_id JOIN lineas_ropa lr ON lr.id = cat.linea_ropa_id
          WHERE pr.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci
            AND LOWER(TRIM(lr.nombre_linea)) COLLATE utf8mb4_unicode_ci = 'linea_brasilena' COLLATE utf8mb4_unicode_ci
            AND LOWER(TRIM(cat.nombre_categoria)) COLLATE utf8mb4_unicode_ci = 't shirts' COLLATE utf8mb4_unicode_ci) = 1
     AND (SELECT COUNT(*) FROM productos pr WHERE pr.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci
            AND pr.cantidad_compra_producto = '22'
            AND pr.stock_venta_producto = '22'
            AND pr.precio_dolares_producto = 10.00
            AND pr.precio_venta_producto = 20.00
            AND pr.promocion_etiqueta = 'PROMO') = 1
     AND (SELECT COUNT(*) FROM colores_productos cp JOIN productos pr ON pr.id = cp.producto_id WHERE pr.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci) = 9
     AND (SELECT COALESCE(SUM(CAST(cp.cantidad_por_color AS UNSIGNED)), 0) FROM colores_productos cp JOIN productos pr ON pr.id = cp.producto_id WHERE pr.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci) = 22
     AND (SELECT COUNT(*) FROM compra_items ci JOIN compras co ON co.id = ci.compra_id WHERE co.codigo_compra = @codigo_compra COLLATE utf8mb4_unicode_ci) = 9
     AND (SELECT COALESCE(SUM(ci.cantidad), 0) FROM compra_items ci JOIN compras co ON co.id = ci.compra_id WHERE co.codigo_compra = @codigo_compra COLLATE utf8mb4_unicode_ci) = 22
     AND (SELECT COALESCE(SUM(ci.subtotal_dolares), 0.00) FROM compra_items ci JOIN compras co ON co.id = ci.compra_id WHERE co.codigo_compra = @codigo_compra COLLATE utf8mb4_unicode_ci) = 220.00
     AND (SELECT COUNT(*) FROM inventario_movimientos im JOIN compras co ON co.id = im.compra_id WHERE co.codigo_compra = @codigo_compra COLLATE utf8mb4_unicode_ci AND im.tipo_movimiento = 'ENTRADA_COMPRA') = 9
     AND (SELECT COUNT(*) FROM detalle_pedidos dp JOIN productos pr ON pr.id = dp.producto_id WHERE pr.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci) = 0
     AND (SELECT COUNT(*) FROM inventario_movimientos im JOIN productos pr ON pr.id = im.producto_id WHERE pr.codigo_producto = @codigo_producto COLLATE utf8mb4_unicode_ci AND (im.pedido_id IS NOT NULL OR im.tipo_movimiento <> 'ENTRADA_COMPRA')) = 0
    THEN 'OK'
    ELSE 'REVISAR'
END AS resultado_validacion;
