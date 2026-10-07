-- Correccion idempotente de tallas compuestas BootyFitness.
-- Base: guias
-- No modifica ventas, pedidos ni reservas.
-- Solo elimina filas duplicadas de las variantes origen especificadas.

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET SESSION character_set_connection = 'utf8mb4';
SET SESSION collation_connection = 'utf8mb4_unicode_ci';

SET @p_halterneck = (SELECT id FROM productos WHERE codigo_producto = 'halterneck_top_lb_20260904' LIMIT 1);
SET @p_lolita = (SELECT id FROM productos WHERE codigo_producto = 'set_lolita_flared_lb_20260904' LIMIT 1);
SET @p_iconic = (SELECT id FROM productos WHERE codigo_producto = 'enterizo_iconic_silueta_lb_20260904' LIMIT 1);
SET @p_sculpt = (SELECT id FROM productos WHERE codigo_producto = 'enterizo_sculpt_flared_lb_20260904' LIMIT 1);
SET @p_harmony = (SELECT id FROM productos WHERE codigo_producto = 'enterizo_harmony_zip_lb_20260904' LIMIT 1);
SET @p_bold = (SELECT id FROM productos WHERE codigo_producto = 'enterizo_the_bold_tenis_lb_20260904' LIMIT 1);

SET @c_halterneck = (SELECT compras_id FROM productos WHERE id = @p_halterneck);
SET @c_lolita = (SELECT compras_id FROM productos WHERE id = @p_lolita);
SET @c_iconic = (SELECT compras_id FROM productos WHERE id = @p_iconic);
SET @c_sculpt = (SELECT compras_id FROM productos WHERE id = @p_sculpt);
SET @c_harmony = (SELECT compras_id FROM productos WHERE id = @p_harmony);
SET @c_bold = (SELECT compras_id FROM productos WHERE id = @p_bold);

SELECT 'PRECHECK_PRODUCTOS' AS seccion,
       @p_halterneck AS halterneck_id,
       @p_lolita AS lolita_id,
       @p_iconic AS iconic_id,
       @p_sculpt AS sculpt_id,
       @p_harmony AS harmony_id,
       @p_bold AS bold_id;

START TRANSACTION;

-- Halterneck Top: blanco S + M -> S-M qty 2.
SET @h_cp_keep = (
    SELECT cp.id
    FROM colores_productos cp
    JOIN colores c ON c.id = cp.colores_id
    WHERE cp.producto_id = @p_halterneck
      AND LOWER(TRIM(c.nombre_color)) COLLATE utf8mb4_unicode_ci = 'blanco' COLLATE utf8mb4_unicode_ci
      AND UPPER(TRIM(cp.talla_por_color)) COLLATE utf8mb4_unicode_ci = 'S' COLLATE utf8mb4_unicode_ci
    ORDER BY cp.id LIMIT 1
);
SET @h_cp_drop = (
    SELECT cp.id
    FROM colores_productos cp
    JOIN colores c ON c.id = cp.colores_id
    WHERE cp.producto_id = @p_halterneck
      AND LOWER(TRIM(c.nombre_color)) COLLATE utf8mb4_unicode_ci = 'blanco' COLLATE utf8mb4_unicode_ci
      AND UPPER(TRIM(cp.talla_por_color)) COLLATE utf8mb4_unicode_ci = 'M' COLLATE utf8mb4_unicode_ci
    ORDER BY cp.id LIMIT 1
);
SET @h_ci_keep = (
    SELECT ci.id
    FROM compra_items ci
    JOIN colores c ON c.id = ci.colores_id
    WHERE ci.compra_id = @c_halterneck AND ci.producto_id = @p_halterneck
      AND LOWER(TRIM(c.nombre_color)) COLLATE utf8mb4_unicode_ci = 'blanco' COLLATE utf8mb4_unicode_ci
      AND UPPER(TRIM(ci.talla)) COLLATE utf8mb4_unicode_ci = 'S' COLLATE utf8mb4_unicode_ci
    ORDER BY ci.id LIMIT 1
);
SET @h_ci_drop = (
    SELECT ci.id
    FROM compra_items ci
    JOIN colores c ON c.id = ci.colores_id
    WHERE ci.compra_id = @c_halterneck AND ci.producto_id = @p_halterneck
      AND LOWER(TRIM(c.nombre_color)) COLLATE utf8mb4_unicode_ci = 'blanco' COLLATE utf8mb4_unicode_ci
      AND UPPER(TRIM(ci.talla)) COLLATE utf8mb4_unicode_ci = 'M' COLLATE utf8mb4_unicode_ci
    ORDER BY ci.id LIMIT 1
);
SET @h_im_keep = (SELECT id FROM inventario_movimientos WHERE producto_id = @p_halterneck AND colores_productos_id = @h_cp_keep ORDER BY id LIMIT 1);
SET @h_im_drop = (SELECT id FROM inventario_movimientos WHERE producto_id = @p_halterneck AND colores_productos_id = @h_cp_drop ORDER BY id LIMIT 1);

UPDATE colores_productos
SET talla_por_color = 'S-M', cantidad_por_color = '2', stock_por_color = '2', updated_at = NOW()
WHERE id = @h_cp_keep;
UPDATE compra_items
SET talla = 'S-M', cantidad = 2, stock_ingresado = 2,
    subtotal_pesos = 2 * costo_unitario_pesos,
    subtotal_dolares = 2 * costo_unitario_dolares,
    updated_at = NOW()
WHERE id = @h_ci_keep;
UPDATE inventario_movimientos
SET cantidad = 2, stock_antes = 0, stock_despues = 2, updated_at = NOW()
WHERE id = @h_im_keep;
DELETE FROM inventario_movimientos WHERE id = @h_im_drop;
DELETE FROM compra_items WHERE id = @h_ci_drop;
DELETE FROM colores_productos WHERE id = @h_cp_drop;

-- Set Lolita: verde oliva M + L -> M-L qty 2.
SET @lo_cp_keep = (
    SELECT cp.id FROM colores_productos cp JOIN colores c ON c.id = cp.colores_id
    WHERE cp.producto_id = @p_lolita AND c.nombre_color = 'verde oliva' AND UPPER(TRIM(cp.talla_por_color)) = 'M'
    ORDER BY cp.id LIMIT 1
);
SET @lo_cp_drop = (
    SELECT cp.id FROM colores_productos cp JOIN colores c ON c.id = cp.colores_id
    WHERE cp.producto_id = @p_lolita AND c.nombre_color = 'verde oliva' AND UPPER(TRIM(cp.talla_por_color)) = 'L'
    ORDER BY cp.id LIMIT 1
);
SET @lo_ci_keep = (
    SELECT ci.id FROM compra_items ci JOIN colores c ON c.id = ci.colores_id
    WHERE ci.compra_id = @c_lolita AND ci.producto_id = @p_lolita AND c.nombre_color = 'verde oliva' AND UPPER(TRIM(ci.talla)) = 'M'
    ORDER BY ci.id LIMIT 1
);
SET @lo_ci_drop = (
    SELECT ci.id FROM compra_items ci JOIN colores c ON c.id = ci.colores_id
    WHERE ci.compra_id = @c_lolita AND ci.producto_id = @p_lolita AND c.nombre_color = 'verde oliva' AND UPPER(TRIM(ci.talla)) = 'L'
    ORDER BY ci.id LIMIT 1
);
SET @lo_im_keep = (SELECT id FROM inventario_movimientos WHERE producto_id = @p_lolita AND colores_productos_id = @lo_cp_keep ORDER BY id LIMIT 1);
SET @lo_im_drop = (SELECT id FROM inventario_movimientos WHERE producto_id = @p_lolita AND colores_productos_id = @lo_cp_drop ORDER BY id LIMIT 1);
UPDATE colores_productos SET talla_por_color = 'M-L', cantidad_por_color = '2', stock_por_color = '2', updated_at = NOW()
WHERE id = @lo_cp_keep;
UPDATE compra_items SET talla = 'M-L', cantidad = 2, stock_ingresado = 2,
    subtotal_pesos = 2 * costo_unitario_pesos, subtotal_dolares = 2 * costo_unitario_dolares, updated_at = NOW()
WHERE id = @lo_ci_keep;
UPDATE inventario_movimientos SET cantidad = 2, stock_antes = 0, stock_despues = 2, updated_at = NOW() WHERE id = @lo_im_keep;
DELETE FROM inventario_movimientos WHERE id = @lo_im_drop;
DELETE FROM compra_items WHERE id = @lo_ci_drop;
DELETE FROM colores_productos WHERE id = @lo_cp_drop;

-- Set Lolita: verde musgo M + L -> M-L qty 1.
SET @lm_cp_keep = (
    SELECT cp.id FROM colores_productos cp JOIN colores c ON c.id = cp.colores_id
    WHERE cp.producto_id = @p_lolita AND c.nombre_color = 'verde musgo' AND UPPER(TRIM(cp.talla_por_color)) = 'M'
    ORDER BY cp.id LIMIT 1
);
SET @lm_cp_drop = (
    SELECT cp.id FROM colores_productos cp JOIN colores c ON c.id = cp.colores_id
    WHERE cp.producto_id = @p_lolita AND c.nombre_color = 'verde musgo' AND UPPER(TRIM(cp.talla_por_color)) = 'L'
    ORDER BY cp.id LIMIT 1
);
SET @lm_ci_keep = (
    SELECT ci.id FROM compra_items ci JOIN colores c ON c.id = ci.colores_id
    WHERE ci.compra_id = @c_lolita AND ci.producto_id = @p_lolita AND c.nombre_color = 'verde musgo' AND UPPER(TRIM(ci.talla)) = 'M'
    ORDER BY ci.id LIMIT 1
);
SET @lm_ci_drop = (
    SELECT ci.id FROM compra_items ci JOIN colores c ON c.id = ci.colores_id
    WHERE ci.compra_id = @c_lolita AND ci.producto_id = @p_lolita AND c.nombre_color = 'verde musgo' AND UPPER(TRIM(ci.talla)) = 'L'
    ORDER BY ci.id LIMIT 1
);
SET @lm_im_keep = (SELECT id FROM inventario_movimientos WHERE producto_id = @p_lolita AND colores_productos_id = @lm_cp_keep ORDER BY id LIMIT 1);
SET @lm_im_drop = (SELECT id FROM inventario_movimientos WHERE producto_id = @p_lolita AND colores_productos_id = @lm_cp_drop ORDER BY id LIMIT 1);
UPDATE colores_productos SET talla_por_color = 'M-L', cantidad_por_color = '1', stock_por_color = '1', updated_at = NOW()
WHERE id = @lm_cp_keep;
UPDATE compra_items SET talla = 'M-L', cantidad = 1, stock_ingresado = 1,
    subtotal_pesos = 1 * costo_unitario_pesos, subtotal_dolares = 1 * costo_unitario_dolares, updated_at = NOW()
WHERE id = @lm_ci_keep;
UPDATE inventario_movimientos SET cantidad = 1, stock_antes = 0, stock_despues = 1, updated_at = NOW() WHERE id = @lm_im_keep;
DELETE FROM inventario_movimientos WHERE id = @lm_im_drop;
DELETE FROM compra_items WHERE id = @lm_ci_drop;
DELETE FROM colores_productos WHERE id = @lm_cp_drop;

-- Enterizo Iconic Silueta: negro XS + S -> XS-S qty 2.
SET @in_cp_keep = (
    SELECT cp.id FROM colores_productos cp JOIN colores c ON c.id = cp.colores_id
    WHERE cp.producto_id = @p_iconic AND c.nombre_color = 'negro' AND UPPER(TRIM(cp.talla_por_color)) = 'XS'
    ORDER BY cp.id LIMIT 1
);
SET @in_cp_drop = (
    SELECT cp.id FROM colores_productos cp JOIN colores c ON c.id = cp.colores_id
    WHERE cp.producto_id = @p_iconic AND c.nombre_color = 'negro' AND UPPER(TRIM(cp.talla_por_color)) = 'S'
    ORDER BY cp.id LIMIT 1
);
SET @in_ci_keep = (
    SELECT ci.id FROM compra_items ci JOIN colores c ON c.id = ci.colores_id
    WHERE ci.compra_id = @c_iconic AND ci.producto_id = @p_iconic AND c.nombre_color = 'negro' AND UPPER(TRIM(ci.talla)) = 'XS'
    ORDER BY ci.id LIMIT 1
);
SET @in_ci_drop = (
    SELECT ci.id FROM compra_items ci JOIN colores c ON c.id = ci.colores_id
    WHERE ci.compra_id = @c_iconic AND ci.producto_id = @p_iconic AND c.nombre_color = 'negro' AND UPPER(TRIM(ci.talla)) = 'S'
    ORDER BY ci.id LIMIT 1
);
SET @in_im_keep = (SELECT id FROM inventario_movimientos WHERE producto_id = @p_iconic AND colores_productos_id = @in_cp_keep ORDER BY id LIMIT 1);
SET @in_im_drop = (SELECT id FROM inventario_movimientos WHERE producto_id = @p_iconic AND colores_productos_id = @in_cp_drop ORDER BY id LIMIT 1);
UPDATE colores_productos SET talla_por_color = 'XS-S', cantidad_por_color = '2', stock_por_color = '2', updated_at = NOW()
WHERE id = @in_cp_keep;
UPDATE compra_items SET talla = 'XS-S', cantidad = 2, stock_ingresado = 2,
    subtotal_pesos = 2 * costo_unitario_pesos, subtotal_dolares = 2 * costo_unitario_dolares, updated_at = NOW()
WHERE id = @in_ci_keep;
UPDATE inventario_movimientos SET cantidad = 2, stock_antes = 0, stock_despues = 2, updated_at = NOW() WHERE id = @in_im_keep;
DELETE FROM inventario_movimientos WHERE id = @in_im_drop;
DELETE FROM compra_items WHERE id = @in_ci_drop;
DELETE FROM colores_productos WHERE id = @in_cp_drop;

-- Enterizo Iconic Silueta: rosa S + M -> S-M qty 2.
SET @ir_cp_keep = (
    SELECT cp.id FROM colores_productos cp JOIN colores c ON c.id = cp.colores_id
    WHERE cp.producto_id = @p_iconic AND c.nombre_color = 'rosa' AND UPPER(TRIM(cp.talla_por_color)) = 'S'
    ORDER BY cp.id LIMIT 1
);
SET @ir_cp_drop = (
    SELECT cp.id FROM colores_productos cp JOIN colores c ON c.id = cp.colores_id
    WHERE cp.producto_id = @p_iconic AND c.nombre_color = 'rosa' AND UPPER(TRIM(cp.talla_por_color)) = 'M'
    ORDER BY cp.id LIMIT 1
);
SET @ir_ci_keep = (
    SELECT ci.id FROM compra_items ci JOIN colores c ON c.id = ci.colores_id
    WHERE ci.compra_id = @c_iconic AND ci.producto_id = @p_iconic AND c.nombre_color = 'rosa' AND UPPER(TRIM(ci.talla)) = 'S'
    ORDER BY ci.id LIMIT 1
);
SET @ir_ci_drop = (
    SELECT ci.id FROM compra_items ci JOIN colores c ON c.id = ci.colores_id
    WHERE ci.compra_id = @c_iconic AND ci.producto_id = @p_iconic AND c.nombre_color = 'rosa' AND UPPER(TRIM(ci.talla)) = 'M'
    ORDER BY ci.id LIMIT 1
);
SET @ir_im_keep = (SELECT id FROM inventario_movimientos WHERE producto_id = @p_iconic AND colores_productos_id = @ir_cp_keep ORDER BY id LIMIT 1);
SET @ir_im_drop = (SELECT id FROM inventario_movimientos WHERE producto_id = @p_iconic AND colores_productos_id = @ir_cp_drop ORDER BY id LIMIT 1);
UPDATE colores_productos SET talla_por_color = 'S-M', cantidad_por_color = '2', stock_por_color = '2', updated_at = NOW()
WHERE id = @ir_cp_keep;
UPDATE compra_items SET talla = 'S-M', cantidad = 2, stock_ingresado = 2,
    subtotal_pesos = 2 * costo_unitario_pesos, subtotal_dolares = 2 * costo_unitario_dolares, updated_at = NOW()
WHERE id = @ir_ci_keep;
UPDATE inventario_movimientos SET cantidad = 2, stock_antes = 0, stock_despues = 2, updated_at = NOW() WHERE id = @ir_im_keep;
DELETE FROM inventario_movimientos WHERE id = @ir_im_drop;
DELETE FROM compra_items WHERE id = @ir_ci_drop;
DELETE FROM colores_productos WHERE id = @ir_cp_drop;

-- Enterizo Sculpt Flared: azul marino S + M -> S-M qty 2.
SET @scb_cp_keep = (
    SELECT cp.id FROM colores_productos cp JOIN colores c ON c.id = cp.colores_id
    WHERE cp.producto_id = @p_sculpt AND c.nombre_color = 'azul marino' AND UPPER(TRIM(cp.talla_por_color)) = 'S'
    ORDER BY cp.id LIMIT 1
);
SET @scb_cp_drop = (
    SELECT cp.id FROM colores_productos cp JOIN colores c ON c.id = cp.colores_id
    WHERE cp.producto_id = @p_sculpt AND c.nombre_color = 'azul marino' AND UPPER(TRIM(cp.talla_por_color)) = 'M'
    ORDER BY cp.id LIMIT 1
);
SET @scb_ci_keep = (
    SELECT ci.id FROM compra_items ci JOIN colores c ON c.id = ci.colores_id
    WHERE ci.compra_id = @c_sculpt AND ci.producto_id = @p_sculpt AND c.nombre_color = 'azul marino' AND UPPER(TRIM(ci.talla)) = 'S'
    ORDER BY ci.id LIMIT 1
);
SET @scb_ci_drop = (
    SELECT ci.id FROM compra_items ci JOIN colores c ON c.id = ci.colores_id
    WHERE ci.compra_id = @c_sculpt AND ci.producto_id = @p_sculpt AND c.nombre_color = 'azul marino' AND UPPER(TRIM(ci.talla)) = 'M'
    ORDER BY ci.id LIMIT 1
);
SET @scb_im_keep = (SELECT id FROM inventario_movimientos WHERE producto_id = @p_sculpt AND colores_productos_id = @scb_cp_keep ORDER BY id LIMIT 1);
SET @scb_im_drop = (SELECT id FROM inventario_movimientos WHERE producto_id = @p_sculpt AND colores_productos_id = @scb_cp_drop ORDER BY id LIMIT 1);
UPDATE colores_productos SET talla_por_color = 'S-M', cantidad_por_color = '2', stock_por_color = '2', updated_at = NOW()
WHERE id = @scb_cp_keep;
UPDATE compra_items SET talla = 'S-M', cantidad = 2, stock_ingresado = 2,
    subtotal_pesos = 2 * costo_unitario_pesos, subtotal_dolares = 2 * costo_unitario_dolares, updated_at = NOW()
WHERE id = @scb_ci_keep;
UPDATE inventario_movimientos SET cantidad = 2, stock_antes = 0, stock_despues = 2, updated_at = NOW() WHERE id = @scb_im_keep;
DELETE FROM inventario_movimientos WHERE id = @scb_im_drop;
DELETE FROM compra_items WHERE id = @scb_ci_drop;
DELETE FROM colores_productos WHERE id = @scb_cp_drop;

-- Enterizo Sculpt Flared: uva S + M -> S-M qty 1; uva XS permanece intacta.
SET @scu_cp_keep = (
    SELECT cp.id FROM colores_productos cp JOIN colores c ON c.id = cp.colores_id
    WHERE cp.producto_id = @p_sculpt AND c.nombre_color = 'uva' AND UPPER(TRIM(cp.talla_por_color)) = 'S'
    ORDER BY cp.id LIMIT 1
);
SET @scu_cp_drop = (
    SELECT cp.id FROM colores_productos cp JOIN colores c ON c.id = cp.colores_id
    WHERE cp.producto_id = @p_sculpt AND c.nombre_color = 'uva' AND UPPER(TRIM(cp.talla_por_color)) = 'M'
    ORDER BY cp.id LIMIT 1
);
SET @scu_ci_keep = (
    SELECT ci.id FROM compra_items ci JOIN colores c ON c.id = ci.colores_id
    WHERE ci.compra_id = @c_sculpt AND ci.producto_id = @p_sculpt AND c.nombre_color = 'uva' AND UPPER(TRIM(ci.talla)) = 'S'
    ORDER BY ci.id LIMIT 1
);
SET @scu_ci_drop = (
    SELECT ci.id FROM compra_items ci JOIN colores c ON c.id = ci.colores_id
    WHERE ci.compra_id = @c_sculpt AND ci.producto_id = @p_sculpt AND c.nombre_color = 'uva' AND UPPER(TRIM(ci.talla)) = 'M'
    ORDER BY ci.id LIMIT 1
);
SET @scu_im_keep = (SELECT id FROM inventario_movimientos WHERE producto_id = @p_sculpt AND colores_productos_id = @scu_cp_keep ORDER BY id LIMIT 1);
SET @scu_im_drop = (SELECT id FROM inventario_movimientos WHERE producto_id = @p_sculpt AND colores_productos_id = @scu_cp_drop ORDER BY id LIMIT 1);
UPDATE colores_productos SET talla_por_color = 'S-M', cantidad_por_color = '1', stock_por_color = '1', updated_at = NOW()
WHERE id = @scu_cp_keep;
UPDATE compra_items SET talla = 'S-M', cantidad = 1, stock_ingresado = 1,
    subtotal_pesos = 1 * costo_unitario_pesos, subtotal_dolares = 1 * costo_unitario_dolares, updated_at = NOW()
WHERE id = @scu_ci_keep;
UPDATE inventario_movimientos SET cantidad = 1, stock_antes = 0, stock_despues = 1, updated_at = NOW() WHERE id = @scu_im_keep;
DELETE FROM inventario_movimientos WHERE id = @scu_im_drop;
DELETE FROM compra_items WHERE id = @scu_ci_drop;
DELETE FROM colores_productos WHERE id = @scu_cp_drop;

-- Harmony Zip: cafe S + M -> S-M qty 1.
SET @hy_cp_keep = (
    SELECT cp.id FROM colores_productos cp JOIN colores c ON c.id = cp.colores_id
    WHERE cp.producto_id = @p_harmony AND c.nombre_color = 'cafe' AND UPPER(TRIM(cp.talla_por_color)) = 'S'
    ORDER BY cp.id LIMIT 1
);
SET @hy_cp_drop = (
    SELECT cp.id FROM colores_productos cp JOIN colores c ON c.id = cp.colores_id
    WHERE cp.producto_id = @p_harmony AND c.nombre_color = 'cafe' AND UPPER(TRIM(cp.talla_por_color)) = 'M'
    ORDER BY cp.id LIMIT 1
);
SET @hy_ci_keep = (
    SELECT ci.id FROM compra_items ci JOIN colores c ON c.id = ci.colores_id
    WHERE ci.compra_id = @c_harmony AND ci.producto_id = @p_harmony AND c.nombre_color = 'cafe' AND UPPER(TRIM(ci.talla)) = 'S'
    ORDER BY ci.id LIMIT 1
);
SET @hy_ci_drop = (
    SELECT ci.id FROM compra_items ci JOIN colores c ON c.id = ci.colores_id
    WHERE ci.compra_id = @c_harmony AND ci.producto_id = @p_harmony AND c.nombre_color = 'cafe' AND UPPER(TRIM(ci.talla)) = 'M'
    ORDER BY ci.id LIMIT 1
);
SET @hy_im_keep = (SELECT id FROM inventario_movimientos WHERE producto_id = @p_harmony AND colores_productos_id = @hy_cp_keep ORDER BY id LIMIT 1);
SET @hy_im_drop = (SELECT id FROM inventario_movimientos WHERE producto_id = @p_harmony AND colores_productos_id = @hy_cp_drop ORDER BY id LIMIT 1);
UPDATE colores_productos SET talla_por_color = 'S-M', cantidad_por_color = '1', stock_por_color = '1', updated_at = NOW()
WHERE id = @hy_cp_keep;
UPDATE compra_items SET talla = 'S-M', cantidad = 1, stock_ingresado = 1,
    subtotal_pesos = 1 * costo_unitario_pesos, subtotal_dolares = 1 * costo_unitario_dolares, updated_at = NOW()
WHERE id = @hy_ci_keep;
UPDATE inventario_movimientos SET cantidad = 1, stock_antes = 0, stock_despues = 1, updated_at = NOW() WHERE id = @hy_im_keep;
DELETE FROM inventario_movimientos WHERE id = @hy_im_drop;
DELETE FROM compra_items WHERE id = @hy_ci_drop;
DELETE FROM colores_productos WHERE id = @hy_cp_drop;

-- The Bold Tenis: negro S + M -> S-M qty 2.
SET @bn_cp_keep = (
    SELECT cp.id FROM colores_productos cp JOIN colores c ON c.id = cp.colores_id
    WHERE cp.producto_id = @p_bold AND c.nombre_color = 'negro' AND UPPER(TRIM(cp.talla_por_color)) = 'S'
    ORDER BY cp.id LIMIT 1
);
SET @bn_cp_drop = (
    SELECT cp.id FROM colores_productos cp JOIN colores c ON c.id = cp.colores_id
    WHERE cp.producto_id = @p_bold AND c.nombre_color = 'negro' AND UPPER(TRIM(cp.talla_por_color)) = 'M'
    ORDER BY cp.id LIMIT 1
);
SET @bn_ci_keep = (
    SELECT ci.id FROM compra_items ci JOIN colores c ON c.id = ci.colores_id
    WHERE ci.compra_id = @c_bold AND ci.producto_id = @p_bold AND c.nombre_color = 'negro' AND UPPER(TRIM(ci.talla)) = 'S'
    ORDER BY ci.id LIMIT 1
);
SET @bn_ci_drop = (
    SELECT ci.id FROM compra_items ci JOIN colores c ON c.id = ci.colores_id
    WHERE ci.compra_id = @c_bold AND ci.producto_id = @p_bold AND c.nombre_color = 'negro' AND UPPER(TRIM(ci.talla)) = 'M'
    ORDER BY ci.id LIMIT 1
);
SET @bn_im_keep = (SELECT id FROM inventario_movimientos WHERE producto_id = @p_bold AND colores_productos_id = @bn_cp_keep ORDER BY id LIMIT 1);
SET @bn_im_drop = (SELECT id FROM inventario_movimientos WHERE producto_id = @p_bold AND colores_productos_id = @bn_cp_drop ORDER BY id LIMIT 1);
UPDATE colores_productos SET talla_por_color = 'S-M', cantidad_por_color = '2', stock_por_color = '2', updated_at = NOW()
WHERE id = @bn_cp_keep;
UPDATE compra_items SET talla = 'S-M', cantidad = 2, stock_ingresado = 2,
    subtotal_pesos = 2 * costo_unitario_pesos, subtotal_dolares = 2 * costo_unitario_dolares, updated_at = NOW()
WHERE id = @bn_ci_keep;
UPDATE inventario_movimientos SET cantidad = 2, stock_antes = 0, stock_despues = 2, updated_at = NOW() WHERE id = @bn_im_keep;
DELETE FROM inventario_movimientos WHERE id = @bn_im_drop;
DELETE FROM compra_items WHERE id = @bn_ci_drop;
DELETE FROM colores_productos WHERE id = @bn_cp_drop;

-- The Bold Tenis: gris medio S + M -> S-M qty 2.
SET @bg_cp_keep = (
    SELECT cp.id FROM colores_productos cp JOIN colores c ON c.id = cp.colores_id
    WHERE cp.producto_id = @p_bold AND c.nombre_color = 'gris medio' AND UPPER(TRIM(cp.talla_por_color)) = 'S'
    ORDER BY cp.id LIMIT 1
);
SET @bg_cp_drop = (
    SELECT cp.id FROM colores_productos cp JOIN colores c ON c.id = cp.colores_id
    WHERE cp.producto_id = @p_bold AND c.nombre_color = 'gris medio' AND UPPER(TRIM(cp.talla_por_color)) = 'M'
    ORDER BY cp.id LIMIT 1
);
SET @bg_ci_keep = (
    SELECT ci.id FROM compra_items ci JOIN colores c ON c.id = ci.colores_id
    WHERE ci.compra_id = @c_bold AND ci.producto_id = @p_bold AND c.nombre_color = 'gris medio' AND UPPER(TRIM(ci.talla)) = 'S'
    ORDER BY ci.id LIMIT 1
);
SET @bg_ci_drop = (
    SELECT ci.id FROM compra_items ci JOIN colores c ON c.id = ci.colores_id
    WHERE ci.compra_id = @c_bold AND ci.producto_id = @p_bold AND c.nombre_color = 'gris medio' AND UPPER(TRIM(ci.talla)) = 'M'
    ORDER BY ci.id LIMIT 1
);
SET @bg_im_keep = (SELECT id FROM inventario_movimientos WHERE producto_id = @p_bold AND colores_productos_id = @bg_cp_keep ORDER BY id LIMIT 1);
SET @bg_im_drop = (SELECT id FROM inventario_movimientos WHERE producto_id = @p_bold AND colores_productos_id = @bg_cp_drop ORDER BY id LIMIT 1);
UPDATE colores_productos SET talla_por_color = 'S-M', cantidad_por_color = '2', stock_por_color = '2', updated_at = NOW()
WHERE id = @bg_cp_keep;
UPDATE compra_items SET talla = 'S-M', cantidad = 2, stock_ingresado = 2,
    subtotal_pesos = 2 * costo_unitario_pesos, subtotal_dolares = 2 * costo_unitario_dolares, updated_at = NOW()
WHERE id = @bg_ci_keep;
UPDATE inventario_movimientos SET cantidad = 2, stock_antes = 0, stock_despues = 2, updated_at = NOW() WHERE id = @bg_im_keep;
DELETE FROM inventario_movimientos WHERE id = @bg_im_drop;
DELETE FROM compra_items WHERE id = @bg_ci_drop;
DELETE FROM colores_productos WHERE id = @bg_cp_drop;

-- The Bold Tenis: rosa S + M -> S-M qty 2.
SET @br_cp_keep = (
    SELECT cp.id FROM colores_productos cp JOIN colores c ON c.id = cp.colores_id
    WHERE cp.producto_id = @p_bold AND c.nombre_color = 'rosa' AND UPPER(TRIM(cp.talla_por_color)) = 'S'
    ORDER BY cp.id LIMIT 1
);
SET @br_cp_drop = (
    SELECT cp.id FROM colores_productos cp JOIN colores c ON c.id = cp.colores_id
    WHERE cp.producto_id = @p_bold AND c.nombre_color = 'rosa' AND UPPER(TRIM(cp.talla_por_color)) = 'M'
    ORDER BY cp.id LIMIT 1
);
SET @br_ci_keep = (
    SELECT ci.id FROM compra_items ci JOIN colores c ON c.id = ci.colores_id
    WHERE ci.compra_id = @c_bold AND ci.producto_id = @p_bold AND c.nombre_color = 'rosa' AND UPPER(TRIM(ci.talla)) = 'S'
    ORDER BY ci.id LIMIT 1
);
SET @br_ci_drop = (
    SELECT ci.id FROM compra_items ci JOIN colores c ON c.id = ci.colores_id
    WHERE ci.compra_id = @c_bold AND ci.producto_id = @p_bold AND c.nombre_color = 'rosa' AND UPPER(TRIM(ci.talla)) = 'M'
    ORDER BY ci.id LIMIT 1
);
SET @br_im_keep = (SELECT id FROM inventario_movimientos WHERE producto_id = @p_bold AND colores_productos_id = @br_cp_keep ORDER BY id LIMIT 1);
SET @br_im_drop = (SELECT id FROM inventario_movimientos WHERE producto_id = @p_bold AND colores_productos_id = @br_cp_drop ORDER BY id LIMIT 1);
UPDATE colores_productos SET talla_por_color = 'S-M', cantidad_por_color = '2', stock_por_color = '2', updated_at = NOW()
WHERE id = @br_cp_keep;
UPDATE compra_items SET talla = 'S-M', cantidad = 2, stock_ingresado = 2,
    subtotal_pesos = 2 * costo_unitario_pesos, subtotal_dolares = 2 * costo_unitario_dolares, updated_at = NOW()
WHERE id = @br_ci_keep;
UPDATE inventario_movimientos SET cantidad = 2, stock_antes = 0, stock_despues = 2, updated_at = NOW() WHERE id = @br_im_keep;
DELETE FROM inventario_movimientos WHERE id = @br_im_drop;
DELETE FROM compra_items WHERE id = @br_ci_drop;
DELETE FROM colores_productos WHERE id = @br_cp_drop;

-- The Bold Tenis: azul marino S qty 2 + M qty 2 -> S-M qty 4.
SET @ba_cp_keep = (
    SELECT cp.id FROM colores_productos cp JOIN colores c ON c.id = cp.colores_id
    WHERE cp.producto_id = @p_bold AND c.nombre_color = 'azul marino' AND UPPER(TRIM(cp.talla_por_color)) = 'S'
    ORDER BY cp.id LIMIT 1
);
SET @ba_cp_drop = (
    SELECT cp.id FROM colores_productos cp JOIN colores c ON c.id = cp.colores_id
    WHERE cp.producto_id = @p_bold AND c.nombre_color = 'azul marino' AND UPPER(TRIM(cp.talla_por_color)) = 'M'
    ORDER BY cp.id LIMIT 1
);
SET @ba_ci_keep = (
    SELECT ci.id FROM compra_items ci JOIN colores c ON c.id = ci.colores_id
    WHERE ci.compra_id = @c_bold AND ci.producto_id = @p_bold AND c.nombre_color = 'azul marino' AND UPPER(TRIM(ci.talla)) = 'S'
    ORDER BY ci.id LIMIT 1
);
SET @ba_ci_drop = (
    SELECT ci.id FROM compra_items ci JOIN colores c ON c.id = ci.colores_id
    WHERE ci.compra_id = @c_bold AND ci.producto_id = @p_bold AND c.nombre_color = 'azul marino' AND UPPER(TRIM(ci.talla)) = 'M'
    ORDER BY ci.id LIMIT 1
);
SET @ba_im_keep = (SELECT id FROM inventario_movimientos WHERE producto_id = @p_bold AND colores_productos_id = @ba_cp_keep ORDER BY id LIMIT 1);
SET @ba_im_drop = (SELECT id FROM inventario_movimientos WHERE producto_id = @p_bold AND colores_productos_id = @ba_cp_drop ORDER BY id LIMIT 1);
UPDATE colores_productos SET talla_por_color = 'S-M', cantidad_por_color = '4', stock_por_color = '4', updated_at = NOW()
WHERE id = @ba_cp_keep;
UPDATE compra_items SET talla = 'S-M', cantidad = 4, stock_ingresado = 4,
    subtotal_pesos = 4 * costo_unitario_pesos, subtotal_dolares = 4 * costo_unitario_dolares, updated_at = NOW()
WHERE id = @ba_ci_keep;
UPDATE inventario_movimientos SET cantidad = 4, stock_antes = 0, stock_despues = 4, updated_at = NOW() WHERE id = @ba_im_keep;
DELETE FROM inventario_movimientos WHERE id = @ba_im_drop;
DELETE FROM compra_items WHERE id = @ba_ci_drop;
DELETE FROM colores_productos WHERE id = @ba_cp_drop;

-- Totales de producto corregidos.
UPDATE productos SET cantidad_compra_producto = '2', stock_venta_producto = '2', updated_at = NOW() WHERE id = @p_halterneck;
UPDATE productos SET cantidad_compra_producto = '3', stock_venta_producto = '3', updated_at = NOW() WHERE id = @p_lolita;
UPDATE productos SET cantidad_compra_producto = '8', stock_venta_producto = '8', updated_at = NOW() WHERE id = @p_iconic;
UPDATE productos SET cantidad_compra_producto = '4', stock_venta_producto = '4', updated_at = NOW() WHERE id = @p_sculpt;
UPDATE productos SET cantidad_compra_producto = '1', stock_venta_producto = '1', updated_at = NOW() WHERE id = @p_harmony;
UPDATE productos SET cantidad_compra_producto = '16', stock_venta_producto = '16', updated_at = NOW() WHERE id = @p_bold;

-- Totales de compra derivados de los detalles corregidos.
UPDATE compras co
JOIN (
    SELECT ci.compra_id, COALESCE(SUM(ci.subtotal_dolares), 0.00) AS total_corregido
    FROM compra_items ci
    WHERE ci.compra_id IN (@c_halterneck, @c_lolita, @c_iconic, @c_sculpt, @c_harmony, @c_bold)
    GROUP BY ci.compra_id
) t ON t.compra_id = co.id
SET co.total_dolares_compra = t.total_corregido,
    co.total_final_compra = t.total_corregido,
    co.updated_at = NOW();

COMMIT;
