-- Validacion de la correccion de tallas compuestas BootyFitness.
-- Base: guias

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET SESSION character_set_connection = 'utf8mb4';
SET SESSION collation_connection = 'utf8mb4_unicode_ci';

SELECT 'PRODUCTOS_CORREGIDOS' AS seccion,
       pr.id,
       pr.descripcion_producto,
       pr.cantidad_compra_producto,
       pr.stock_venta_producto,
       pr.precio_dolares_producto,
       pr.precio_venta_producto,
       co.total_dolares_compra,
       co.total_final_compra
FROM productos pr
JOIN compras co ON co.id = pr.compras_id
WHERE pr.id IN (3, 4, 5, 9, 10, 11)
ORDER BY pr.id;

SELECT 'VARIANTES_CORREGIDAS' AS seccion,
       pr.descripcion_producto,
       c.nombre_color,
       cp.talla_por_color,
       cp.cantidad_por_color,
       cp.stock_por_color
FROM productos pr
JOIN colores_productos cp ON cp.producto_id = pr.id
JOIN colores c ON c.id = cp.colores_id
WHERE pr.id IN (3, 4, 5, 9, 10, 11)
ORDER BY pr.id, c.nombre_color, cp.talla_por_color;

SELECT 'COMPRA_ITEMS_CORREGIDOS' AS seccion,
       pr.descripcion_producto,
       c.nombre_color,
       ci.talla,
       ci.cantidad,
       ci.stock_ingresado,
       ci.subtotal_dolares
FROM compra_items ci
JOIN productos pr ON pr.id = ci.producto_id
JOIN colores c ON c.id = ci.colores_id
WHERE pr.id IN (3, 4, 5, 9, 10, 11)
ORDER BY pr.id, c.nombre_color, ci.talla;

SELECT 'MOVIMIENTOS_CORREGIDOS' AS seccion,
       pr.descripcion_producto,
       c.nombre_color,
       cp.talla_por_color,
       im.cantidad,
       im.stock_antes,
       im.stock_despues,
       im.tipo_movimiento
FROM inventario_movimientos im
JOIN productos pr ON pr.id = im.producto_id
JOIN colores_productos cp ON cp.id = im.colores_productos_id
JOIN colores c ON c.id = cp.colores_id
WHERE pr.id IN (3, 4, 5, 9, 10, 11)
ORDER BY pr.id, c.nombre_color, cp.talla_por_color;

SELECT
    (SELECT COUNT(*) FROM colores_productos cp WHERE cp.producto_id IN (3,4,5,9,10,11)) AS variantes_despues,
    (SELECT COALESCE(SUM(CAST(cp.cantidad_por_color AS UNSIGNED)), 0)
     FROM colores_productos cp WHERE cp.producto_id IN (3,4,5,9,10,11)) AS piezas_despues,
    (SELECT COALESCE(SUM(ci.subtotal_dolares), 0.00)
     FROM compra_items ci WHERE ci.producto_id IN (3,4,5,9,10,11)) AS costo_despues,
    (SELECT COALESCE(SUM(ci.cantidad * pr.precio_venta_producto), 0.00)
     FROM compra_items ci JOIN productos pr ON pr.id = ci.producto_id
     WHERE ci.producto_id IN (3,4,5,9,10,11)) AS venta_despues,
    (SELECT COALESCE(SUM(ci.cantidad * pr.precio_venta_producto), 0.00)
     FROM compra_items ci JOIN productos pr ON pr.id = ci.producto_id
     WHERE ci.producto_id IN (3,4,5,9,10,11))
    - (SELECT COALESCE(SUM(ci.subtotal_dolares), 0.00)
       FROM compra_items ci WHERE ci.producto_id IN (3,4,5,9,10,11)) AS utilidad_despues;

SELECT 'OBJETIVOS_TALLAS' AS validacion, COUNT(*) AS total
FROM (
    SELECT 3 AS producto_id, 'blanco' AS color_nombre, 'S-M' AS talla, 2 AS cantidad
    UNION ALL SELECT 4, 'verde oliva', 'M-L', 2
    UNION ALL SELECT 4, 'verde musgo', 'M-L', 1
    UNION ALL SELECT 5, 'negro', 'XS-S', 2
    UNION ALL SELECT 5, 'rosa', 'S-M', 2
    UNION ALL SELECT 9, 'azul marino', 'S-M', 2
    UNION ALL SELECT 9, 'uva', 'S-M', 1
    UNION ALL SELECT 9, 'uva', 'XS', 1
    UNION ALL SELECT 10, 'cafe', 'S-M', 1
    UNION ALL SELECT 11, 'negro', 'S-M', 2
    UNION ALL SELECT 11, 'gris medio', 'S-M', 2
    UNION ALL SELECT 11, 'rosa', 'S-M', 2
    UNION ALL SELECT 11, 'azul marino', 'S-M', 4
) e
JOIN colores_productos cp ON cp.producto_id = e.producto_id
JOIN colores c ON c.id = cp.colores_id
WHERE LOWER(TRIM(c.nombre_color)) COLLATE utf8mb4_unicode_ci = e.color_nombre COLLATE utf8mb4_unicode_ci
  AND UPPER(TRIM(cp.talla_por_color)) COLLATE utf8mb4_unicode_ci = e.talla COLLATE utf8mb4_unicode_ci
  AND CAST(cp.cantidad_por_color AS UNSIGNED) = e.cantidad;

SELECT 'ORIGENES_RESTANTES' AS validacion, COUNT(*) AS total
FROM colores_productos cp
JOIN colores c ON c.id = cp.colores_id
WHERE cp.producto_id IN (3,4,5,9,10,11)
  AND (
       (cp.producto_id = 3 AND c.nombre_color = 'blanco' AND cp.talla_por_color IN ('S','M'))
    OR (cp.producto_id = 4 AND c.nombre_color IN ('verde oliva','verde musgo') AND cp.talla_por_color IN ('M','L'))
    OR (cp.producto_id = 5 AND ((c.nombre_color = 'negro' AND cp.talla_por_color IN ('XS','S')) OR (c.nombre_color = 'rosa' AND cp.talla_por_color IN ('S','M'))))
    OR (cp.producto_id = 9 AND ((c.nombre_color = 'azul marino' AND cp.talla_por_color IN ('S','M')) OR (c.nombre_color = 'uva' AND cp.talla_por_color IN ('S','M'))))
    OR (cp.producto_id = 10 AND c.nombre_color = 'cafe' AND cp.talla_por_color IN ('S','M'))
    OR (cp.producto_id = 11 AND c.nombre_color IN ('negro','gris medio','rosa','azul marino') AND cp.talla_por_color IN ('S','M'))
  );

SELECT 'DETALLES_ORIGEN_RESTANTES' AS validacion, COUNT(*) AS total
FROM compra_items ci
JOIN colores c ON c.id = ci.colores_id
WHERE ci.producto_id IN (3,4,5,9,10,11)
  AND (
       (ci.producto_id = 3 AND c.nombre_color = 'blanco' AND ci.talla IN ('S','M'))
    OR (ci.producto_id = 4 AND c.nombre_color IN ('verde oliva','verde musgo') AND ci.talla IN ('M','L'))
    OR (ci.producto_id = 5 AND ((c.nombre_color = 'negro' AND ci.talla IN ('XS','S')) OR (c.nombre_color = 'rosa' AND ci.talla IN ('S','M'))))
    OR (ci.producto_id = 9 AND ((c.nombre_color = 'azul marino' AND ci.talla IN ('S','M')) OR (c.nombre_color = 'uva' AND ci.talla IN ('S','M'))))
    OR (ci.producto_id = 10 AND c.nombre_color = 'cafe' AND ci.talla IN ('S','M'))
    OR (ci.producto_id = 11 AND c.nombre_color IN ('negro','gris medio','rosa','azul marino') AND ci.talla IN ('S','M'))
  );

SELECT 'DETALLE_PEDIDOS_AFECTADOS' AS validacion, COUNT(*) AS total
FROM detalle_pedidos
WHERE producto_id IN (3,4,5,9,10,11);

SELECT 'RESERVAS_AFECTADAS' AS validacion, COUNT(*) AS total
FROM stock_reservas
WHERE producto_id IN (3,4,5,9,10,11);

SELECT
    32 AS variantes_antes,
    (SELECT COUNT(*) FROM colores_productos WHERE producto_id IN (3,4,5,9,10,11)) AS variantes_despues,
    37 AS piezas_antes,
    (SELECT COALESCE(SUM(CAST(cantidad_por_color AS UNSIGNED)),0) FROM colores_productos WHERE producto_id IN (3,4,5,9,10,11)) AS piezas_despues,
    605.00 AS costo_antes,
    (SELECT COALESCE(SUM(subtotal_dolares),0.00) FROM compra_items WHERE producto_id IN (3,4,5,9,10,11)) AS costo_despues,
    1375.00 AS venta_antes,
    (SELECT COALESCE(SUM(ci.cantidad * pr.precio_venta_producto),0.00)
     FROM compra_items ci JOIN productos pr ON pr.id = ci.producto_id
     WHERE ci.producto_id IN (3,4,5,9,10,11)) AS venta_despues,
    770.00 AS utilidad_antes,
    (SELECT COALESCE(SUM(ci.cantidad * pr.precio_venta_producto),0.00)
     FROM compra_items ci JOIN productos pr ON pr.id = ci.producto_id
     WHERE ci.producto_id IN (3,4,5,9,10,11))
    - (SELECT COALESCE(SUM(ci.subtotal_dolares),0.00) FROM compra_items ci WHERE ci.producto_id IN (3,4,5,9,10,11)) AS utilidad_despues;

SELECT CASE
    WHEN (SELECT COUNT(*) FROM colores_productos WHERE producto_id IN (3,4,5,9,10,11)) = 20
     AND (SELECT COALESCE(SUM(CAST(cantidad_por_color AS UNSIGNED)),0) FROM colores_productos WHERE producto_id IN (3,4,5,9,10,11)) = 34
     AND (SELECT COALESCE(SUM(subtotal_dolares),0.00) FROM compra_items WHERE producto_id IN (3,4,5,9,10,11)) = 549.00
     AND (SELECT COALESCE(SUM(ci.cantidad * pr.precio_venta_producto),0.00)
          FROM compra_items ci JOIN productos pr ON pr.id = ci.producto_id
          WHERE ci.producto_id IN (3,4,5,9,10,11)) = 1255.00
     AND (SELECT COUNT(*) FROM colores_productos cp
          JOIN colores c ON c.id = cp.colores_id
          WHERE cp.producto_id IN (3,4,5,9,10,11)
            AND (
                 (cp.producto_id = 3 AND c.nombre_color = 'blanco' AND cp.talla_por_color IN ('S','M'))
              OR (cp.producto_id = 4 AND c.nombre_color IN ('verde oliva','verde musgo') AND cp.talla_por_color IN ('M','L'))
              OR (cp.producto_id = 5 AND ((c.nombre_color = 'negro' AND cp.talla_por_color IN ('XS','S')) OR (c.nombre_color = 'rosa' AND cp.talla_por_color IN ('S','M'))))
              OR (cp.producto_id = 9 AND ((c.nombre_color = 'azul marino' AND cp.talla_por_color IN ('S','M')) OR (c.nombre_color = 'uva' AND cp.talla_por_color IN ('S','M'))))
              OR (cp.producto_id = 10 AND c.nombre_color = 'cafe' AND cp.talla_por_color IN ('S','M'))
              OR (cp.producto_id = 11 AND c.nombre_color IN ('negro','gris medio','rosa','azul marino') AND cp.talla_por_color IN ('S','M'))
            )) = 0
     AND (SELECT COUNT(*) FROM compra_items ci JOIN colores c ON c.id = ci.colores_id
          WHERE ci.producto_id IN (3,4,5,9,10,11)
            AND (
                 (ci.producto_id = 3 AND c.nombre_color = 'blanco' AND ci.talla IN ('S','M'))
              OR (ci.producto_id = 4 AND c.nombre_color IN ('verde oliva','verde musgo') AND ci.talla IN ('M','L'))
              OR (ci.producto_id = 5 AND ((c.nombre_color = 'negro' AND ci.talla IN ('XS','S')) OR (c.nombre_color = 'rosa' AND ci.talla IN ('S','M'))))
              OR (ci.producto_id = 9 AND ((c.nombre_color = 'azul marino' AND ci.talla IN ('S','M')) OR (c.nombre_color = 'uva' AND ci.talla IN ('S','M'))))
              OR (ci.producto_id = 10 AND c.nombre_color = 'cafe' AND ci.talla IN ('S','M'))
              OR (ci.producto_id = 11 AND c.nombre_color IN ('negro','gris medio','rosa','azul marino') AND ci.talla IN ('S','M'))
            )) = 0
     AND (SELECT COUNT(*) FROM detalle_pedidos WHERE producto_id IN (3,4,5,9,10,11)) = 0
     AND (SELECT COUNT(*) FROM stock_reservas WHERE producto_id IN (3,4,5,9,10,11)) = 0
    THEN 'OK'
    ELSE 'REVISAR'
END AS resultado_validacion;
