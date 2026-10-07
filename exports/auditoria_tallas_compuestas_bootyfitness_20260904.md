# Informe ejecutivo: auditoría de tallas compuestas

**Proyecto:** `/var/www/bootyfitness`  
**Base auditada:** `guias`  
**Fecha:** 2026-09-04  
**Alcance:** lotes de productos BootyFitness insertados en la base y sus scripts SQL asociados.

## Criterio correcto

Una entrada como `2 piezas en talla S y M` representa una sola variante de talla compuesta `S-M` con cantidad `2`. No se debe crear una variante `S` con cantidad `1` y otra `M` con cantidad `1`.

La misma regla aplica a `M y L` (`M-L`) y `XS y S` (`XS-S`). La cantidad indicada se conserva completa en la talla compuesta.

## Resultado ejecutivo

- Productos auditados: **9**.
- Productos afectados: **6**.
- Productos correctamente registrados: **3**.
- Variantes lógicas excedentes: **12**.
- Piezas físicamente excedentes por la división incorrecta: **3**.
- Costo de compra sobre registrado: **USD 56.00**.
- Venta estimada sobre registrada: **USD 120.00**.
- Utilidad bruta sobre registrada: **USD 64.00**.
- No se ejecutaron correcciones ni se modificó la base durante esta auditoría.

## Productos afectados

| Producto | Registro actual | Registro correcto | Impacto |
|---|---:|---:|---|
| Halterneck Top | 2 variantes, 2 piezas | 1 variante `blanco / S-M`, 2 piezas | Error estructural; las piezas totales son correctas. |
| SET LOLITA FLARED PROMO | 4 variantes, 4 piezas | 2 variantes (`verde oliva / M-L` qty 2; `verde musgo / M-L` qty 1), 3 piezas | 1 pieza sobre registrada. |
| ENTERIZO ICONIC SILUETA PROMO | 8 variantes, 8 piezas | 6 variantes, 8 piezas | Error estructural; las piezas totales son correctas. |
| ENTERIZO SCULPT FLARED PROMO | 5 variantes, 5 piezas | 3 variantes, 4 piezas | 1 pieza sobre registrada. |
| ENTERIZO HARMONY ZIP PROMO | 2 variantes, 2 piezas | 1 variante `cafe / S-M`, 1 pieza | 1 pieza sobre registrada. |
| ENTERIZO THE BOLD TENIS | 11 variantes, 16 piezas | 7 variantes, 16 piezas | Error estructural; las piezas totales son correctas. |

### Detalle de las variantes que deben fusionarse

- **Halterneck Top:** `blanco / S` qty 1 + `blanco / M` qty 1 → `blanco / S-M` qty 2.
- **SET LOLITA FLARED PROMO:** `verde oliva / M` qty 1 + `verde oliva / L` qty 1 → `verde oliva / M-L` qty 2; `verde musgo / M` qty 1 + `verde musgo / L` qty 1 → `verde musgo / M-L` qty 1. La segunda fusión revela 1 pieza excedente creada por la interpretación anterior.
- **ENTERIZO ICONIC SILUETA PROMO:** `negro / XS` qty 1 + `negro / S` qty 1 → `negro / XS-S` qty 2; `rosa / S` qty 1 + `rosa / M` qty 1 → `rosa / S-M` qty 2.
- **ENTERIZO SCULPT FLARED PROMO:** `azul marino / S` qty 1 + `azul marino / M` qty 1 → `azul marino / S-M` qty 2; `uva / S` qty 1 + `uva / M` qty 1 → `uva / S-M` qty 1. La variante `uva / XS` permanece igual. La segunda fusión revela 1 pieza excedente.
- **ENTERIZO HARMONY ZIP PROMO:** `cafe / S` qty 1 + `cafe / M` qty 1 → `cafe / S-M` qty 1. Se debe retirar 1 pieza excedente.
- **ENTERIZO THE BOLD TENIS:** `negro / S` + `negro / M` → `negro / S-M` qty 2; `gris medio / S` + `gris medio / M` → `gris medio / S-M` qty 2; `rosa / S` + `rosa / M` → `rosa / S-M` qty 2; `azul marino / S` qty 2 + `azul marino / M` qty 2 → `azul marino / S-M` qty 4. Las cantidades totales permanecen en 16 piezas.

## Impacto financiero de los productos afectados

| Concepto | Actual | Correcto | Diferencia a corregir |
|---|---:|---:|---:|
| Variantes | 32 | 20 | -12 |
| Piezas | 37 | 34 | -3 |
| Costo total | USD 605.00 | USD 549.00 | -USD 56.00 |
| Venta estimada | USD 1,375.00 | USD 1,255.00 | -USD 120.00 |
| Utilidad bruta | USD 770.00 | USD 706.00 | -USD 64.00 |

## Productos sin error de tallas compuestas

- **ENTERIZO ICONIC SILUETA PROMO** — lote largo: ya usa `XS-S` y `M` como corresponde.
- **ENTERIZO ANIMAL PRINT FLARED PROMO** — ya usa `M-L`.
- **ENTERIZO POKED FLARED PROMO** — ya usa `M-L`.

## Recomendación para la corrección

Crear una migración SQL transaccional y auditable que:

1. Fusione las filas de `colores_productos` en las tallas compuestas correctas.
2. Ajuste los registros relacionados de `compra_items` e `inventario_movimientos`.
3. Corrija únicamente las 3 piezas excedentes detectadas en Set Lolita, Sculpt Flared y Harmony Zip.
4. Mantenga las cantidades totales correctas de Halterneck, Iconic Silueta y The Bold.
5. Ejecute un validador posterior y confirme que no existan duplicados ni movimientos huérfanos.

La corrección aún está pendiente de autorización; esta auditoría fue únicamente de lectura.
