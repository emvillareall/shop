<?php

namespace App\Console\Commands;

use App\Models\Compra;
use App\Models\CompraItem;
use App\Models\CompraRecepcion;
use App\Models\CompraRecepcionItem;
use App\Models\InventarioMovimiento;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class NormalizeHistoricalPurchaseReceptionsCommand extends Command
{
    private const CATEGORY_SAFE = 'SEGURA_PARA_NORMALIZAR';
    private const CATEGORY_REVIEW = 'REQUIERE_REVISION';
    private const CATEGORY_CANCELLED = 'ANULADA';
    private const CATEGORY_INDETERMINATE = 'INDETERMINADA';

    private const HISTORICAL_OBSERVATION = 'Recepción histórica normalizada; no generó movimiento de inventario.';

    /** RFC 4122 namespace URL, used as a stable namespace for UUID v5. */
    private const UUID_NAMESPACE = '6ba7b811-9dad-11d1-80b4-00c04fd430c8';

    private const UUID_NAME_PREFIX = 'bootyfitness:compras:recepcion-historica:v1:';

    protected $signature = 'compras:normalizar-recepciones-historicas
                            {--dry-run : Analiza y muestra el plan sin escribir datos}
                            {--apply : Ejecuta la normalización histórica}';

    protected $description = 'Normaliza compras históricas al modelo de recepción sin modificar inventario.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $apply = (bool) $this->option('apply');

        if ($dryRun === $apply) {
            $this->error('Debes especificar exactamente una opción: --dry-run o --apply.');
            return self::FAILURE;
        }

        $audits = $this->auditHistoricalPurchases();

        $this->renderPlan($audits);

        if ($dryRun) {
            $this->info('DRY-RUN: no se realizaron escrituras.');
            return self::SUCCESS;
        }

        return $this->applyPlan($audits);
    }

    /**
     * Returns the deterministic historical idempotency key for a purchase.
     */
    public static function historicalIdempotencyKey(int $compraId): string
    {
        $namespaceHex = str_replace('-', '', self::UUID_NAMESPACE);
        $namespace = hex2bin($namespaceHex);

        if ($namespace === false) {
            throw new RuntimeException('El namespace UUID histórico no es válido.');
        }

        $hash = sha1($namespace . self::UUID_NAME_PREFIX . $compraId, true);
        $hash[6] = chr((ord($hash[6]) & 0x0f) | 0x50); // UUID version 5.
        $hash[8] = chr((ord($hash[8]) & 0x3f) | 0x80); // RFC 4122 variant.
        $hex = bin2hex($hash);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12)
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function auditHistoricalPurchases(): array
    {
        return Compra::query()
            ->whereNull('estado_recepcion')
            ->orderBy('id')
            ->get()
            ->map(fn (Compra $compra): array => $this->auditPurchase($compra))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function auditPurchase(Compra $compra): array
    {
        $compraId = (int) $compra->id;
        $items = CompraItem::query()
            ->where('compra_id', $compraId)
            ->orderBy('id')
            ->get();
        $movimientos = InventarioMovimiento::query()
            ->where('compra_id', $compraId)
            ->whereIn('tipo_movimiento', ['ENTRADA_COMPRA', 'REVERSO_COMPRA'])
            ->orderBy('id')
            ->get();
        $recepciones = CompraRecepcion::query()
            ->where('compra_id', $compraId)
            ->with('items')
            ->orderBy('id')
            ->get();

        if (strtoupper(trim((string) $compra->estado_compra)) === 'ANULADA') {
            return $this->auditResult(
                $compra,
                $items,
                self::CATEGORY_CANCELLED,
                ['La compra ya está ANULADA.'],
                $movimientos,
                $recepciones
            );
        }

        $reasons = [];
        $indeterminate = false;
        $itemsById = $items->keyBy(fn (CompraItem $item): int => (int) $item->id);
        $entries = $movimientos->where('tipo_movimiento', 'ENTRADA_COMPRA')->values();
        $reversals = $movimientos->where('tipo_movimiento', 'REVERSO_COMPRA')->values();
        $entryTotals = [];
        $entryCounts = [];

        if ($items->isEmpty()) {
            $reasons[] = 'No tiene compra_items.';
        }

        $inactiveItems = $items->filter(
            fn (CompraItem $item): bool => strtoupper(trim((string) $item->estado)) !== 'ACTIVO'
        );
        if ($inactiveItems->isNotEmpty()) {
            $reasons[] = 'Contiene compra_items que no están ACTIVO: '.$inactiveItems->pluck('id')->implode(', ').'.';
        }

        foreach ($entries as $movimiento) {
            [$itemId, $resolutionReason] = $this->resolveMovementItemId($movimiento);

            if ($resolutionReason !== null) {
                $reasons[] = "Movimiento {$movimiento->id}: {$resolutionReason}.";
                continue;
            }

            if ($itemId === null || !$itemsById->has($itemId)) {
                $reasons[] = "Movimiento {$movimiento->id}: ENTRADA_COMPRA sin compra_item inequívoco de esta compra.";
                continue;
            }

            $cantidad = (int) $movimiento->cantidad;
            if ($cantidad <= 0) {
                $reasons[] = "Movimiento {$movimiento->id}: cantidad de entrada no positiva.";
                continue;
            }

            $entryTotals[$itemId] = ($entryTotals[$itemId] ?? 0) + $cantidad;
            $entryCounts[$itemId] = ($entryCounts[$itemId] ?? 0) + 1;
        }

        foreach ($items as $item) {
            $itemId = (int) $item->id;
            $cantidad = (int) $item->cantidad;
            $stockIngresado = (int) $item->stock_ingresado;
            $entradaTotal = (int) ($entryTotals[$itemId] ?? 0);

            if ($cantidad !== $stockIngresado) {
                $reasons[] = "Item {$itemId}: cantidad ({$cantidad}) no coincide con stock_ingresado ({$stockIngresado}).";
            }

            if (!isset($entryCounts[$itemId])) {
                $reasons[] = "Item {$itemId}: no tiene ENTRADA_COMPRA histórica identificable.";
                $indeterminate = true;
                continue;
            }

            if ($entradaTotal !== $cantidad || $entradaTotal !== $stockIngresado) {
                $reasons[] = "Item {$itemId}: ENTRADA_COMPRA suma {$entradaTotal}; esperaba cantidad {$cantidad} y stock_ingresado {$stockIngresado}.";
            }
        }

        if ($reversals->isNotEmpty()) {
            $reasons[] = 'Tiene REVERSO_COMPRA históricos: '.$reversals->pluck('id')->implode(', ').'.';
        }

        [$fechaRecepcion, $fechaFuente] = $this->historicalDate($compra, $items, $entries);
        if ($fechaRecepcion === null) {
            $reasons[] = 'No existe una fecha histórica válida.';
            $indeterminate = true;
        }

        $idempotencyKey = self::historicalIdempotencyKey($compraId);
        $alreadyNormalized = false;

        $uuidOwner = CompraRecepcion::query()
            ->where('idempotency_key', $idempotencyKey)
            ->first();
        if ($uuidOwner && (int) $uuidOwner->compra_id !== $compraId) {
            $reasons[] = "La idempotency_key histórica ya pertenece a la compra {$uuidOwner->compra_id}.";
        }

        if ($recepciones->isNotEmpty()) {
            if ($recepciones->count() !== 1 || (int) $recepciones->first()->compra_id !== $compraId) {
                $reasons[] = 'Existe más de una recepción o una relación de compra inconsistente.';
            } else {
                $recepcion = $recepciones->first();
                if ((string) $recepcion->idempotency_key !== $idempotencyKey) {
                    $reasons[] = 'Existe una recepción con idempotency_key distinta de la histórica esperada.';
                } elseif (!$this->existingReceptionMatches($recepcion, $items, $fechaRecepcion)) {
                    $reasons[] = 'La recepción histórica existente no coincide exactamente con el plan esperado.';
                } else {
                    $alreadyNormalized = true;
                }
            }
        }

        $category = $reasons === []
            ? self::CATEGORY_SAFE
            : ($indeterminate ? self::CATEGORY_INDETERMINATE : self::CATEGORY_REVIEW);

        return $this->auditResult(
            $compra,
            $items,
            $category,
            array_values(array_unique($reasons)),
            $movimientos,
            $recepciones,
            $fechaRecepcion,
            $fechaFuente,
            $idempotencyKey,
            $alreadyNormalized
        );
    }

    /**
     * @return array{0:?int,1:?string}
     */
    private function resolveMovementItemId(InventarioMovimiento $movimiento): array
    {
        $metadata = is_array($movimiento->metadata) ? $movimiento->metadata : [];
        $candidateIds = [];

        if ($movimiento->compra_item_id !== null) {
            $candidateIds[] = $this->parsePositiveInteger($movimiento->compra_item_id);
        }

        foreach (['compra_item_id', 'compra_item'] as $key) {
            if (!array_key_exists($key, $metadata)) {
                continue;
            }

            $parsed = $this->parsePositiveInteger($metadata[$key]);
            if ($parsed === null) {
                return [null, "metadata.{$key} no contiene un ID válido"];
            }
            $candidateIds[] = $parsed;
        }

        $candidateIds = array_values(array_unique(array_filter($candidateIds)));
        if (count($candidateIds) > 1) {
            return [null, 'las referencias a compra_item son contradictorias'];
        }

        return [$candidateIds[0] ?? null, null];
    }

    private function parsePositiveInteger(mixed $value): ?int
    {
        if (is_int($value) && $value > 0) {
            return $value;
        }

        if (is_string($value) && preg_match('/^[1-9]\d*$/', $value) === 1) {
            return (int) $value;
        }

        if (is_float($value) && $value > 0 && floor($value) === $value) {
            return (int) $value;
        }

        return null;
    }

    /**
     * @return array{0:?Carbon,1:string}
     */
    private function historicalDate(Compra $compra, Collection $items, Collection $entries): array
    {
        $entryDates = $entries
            ->pluck('created_at')
            ->filter(fn (mixed $value): bool => $this->validDate($value))
            ->map(fn (mixed $value): Carbon => Carbon::parse((string) $value))
            ->sortBy(fn (Carbon $date): int => $date->getTimestamp());

        if ($entryDates->isNotEmpty()) {
            return [$entryDates->last(), 'ENTRADA_COMPRA'];
        }

        $itemDates = $items
            ->pluck('created_at')
            ->filter(fn (mixed $value): bool => $this->validDate($value))
            ->map(fn (mixed $value): Carbon => Carbon::parse((string) $value))
            ->sortBy(fn (Carbon $date): int => $date->getTimestamp());

        if ($itemDates->isNotEmpty()) {
            return [$itemDates->last(), 'COMPRA_ITEM'];
        }

        if ($this->validDate($compra->created_at)) {
            return [Carbon::parse((string) $compra->created_at), 'COMPRA'];
        }

        return [null, 'NINGUNA'];
    }

    private function validDate(mixed $value): bool
    {
        if ($value instanceof \DateTimeInterface) {
            return true;
        }

        if (!is_string($value) || trim($value) === '') {
            return false;
        }

        try {
            Carbon::parse($value);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function existingReceptionMatches(
        CompraRecepcion $recepcion,
        Collection $items,
        ?Carbon $expectedDate
    ): bool {
        if ($recepcion->user_id !== null
            || (string) $recepcion->observacion !== self::HISTORICAL_OBSERVATION
            || $expectedDate === null
            || !$recepcion->fecha_recepcion instanceof \DateTimeInterface
            || $recepcion->fecha_recepcion->format('Y-m-d H:i:s') !== $expectedDate->format('Y-m-d H:i:s')) {
            return false;
        }

        $expectedItems = $items->keyBy(fn (CompraItem $item): int => (int) $item->id);
        $existingItems = $recepcion->items->keyBy(fn (CompraRecepcionItem $item): int => (int) $item->compra_item_id);

        if ($existingItems->count() !== $expectedItems->count()) {
            return false;
        }

        foreach ($expectedItems as $itemId => $item) {
            $existing = $existingItems->get($itemId);
            if (!$existing) {
                return false;
            }

            if ((int) $existing->cantidad_recibida !== (int) $item->cantidad
                || (int) $existing->cantidad_aceptada !== (int) $item->stock_ingresado
                || (int) $existing->cantidad_danada !== 0
                || (int) $existing->cantidad_incorrecta !== 0
                || (int) $existing->cantidad_faltante_declarada !== 0
                || !(bool) $existing->cierra_item
                || $existing->observacion !== null) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param Collection<int, CompraItem> $items
     * @param Collection<int, InventarioMovimiento> $movimientos
     * @param Collection<int, CompraRecepcion> $recepciones
     * @return array<string, mixed>
     */
    private function auditResult(
        Compra $compra,
        Collection $items,
        string $category,
        array $reasons,
        Collection $movimientos,
        Collection $recepciones,
        ?Carbon $fechaRecepcion = null,
        string $fechaFuente = 'NINGUNA',
        ?string $idempotencyKey = null,
        bool $alreadyNormalized = false
    ): array {
        return [
            'compra_id' => (int) $compra->id,
            'category' => $category,
            'reasons' => $reasons,
            'items' => $items,
            'movimientos' => $movimientos,
            'recepciones' => $recepciones,
            'fecha_recepcion' => $fechaRecepcion,
            'fecha_fuente' => $fechaFuente,
            'idempotency_key' => $idempotencyKey ?: self::historicalIdempotencyKey((int) $compra->id),
            'already_normalized' => $alreadyNormalized,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $audits
     */
    private function renderPlan(array $audits): void
    {
        $counts = array_fill_keys([
            self::CATEGORY_SAFE,
            self::CATEGORY_REVIEW,
            self::CATEGORY_CANCELLED,
            self::CATEGORY_INDETERMINATE,
        ], 0);
        $ids = array_fill_keys(array_keys($counts), []);
        $receptionsToCreate = 0;
        $itemsToCreate = 0;
        $receivedUnits = 0;
        $acceptedUnits = 0;
        $fallbackIds = [];
        $alreadyNormalized = 0;

        foreach ($audits as $audit) {
            $category = $audit['category'];
            $counts[$category]++;
            $ids[$category][] = $audit['compra_id'];

            if ($category !== self::CATEGORY_CANCELLED && $audit['fecha_fuente'] !== 'ENTRADA_COMPRA') {
                $fallbackIds[] = $audit['compra_id'];
            }

            if ($category !== self::CATEGORY_SAFE) {
                continue;
            }

            if ($audit['already_normalized']) {
                $alreadyNormalized++;
                continue;
            }

            $receptionsToCreate++;
            $itemsToCreate += $audit['items']->count();
            $receivedUnits += (int) $audit['items']->sum(fn (CompraItem $item): int => (int) $item->cantidad);
            $acceptedUnits += (int) $audit['items']->sum(fn (CompraItem $item): int => (int) $item->stock_ingresado);
        }

        $this->line('Compras históricas analizadas: '.count($audits));
        foreach (array_keys($counts) as $category) {
            $this->line($category.': '.$counts[$category].' | IDs: '.$this->formatIds($ids[$category]));
        }
        $this->line('Recepciones que se crearían: '.$receptionsToCreate);
        $this->line('Items de recepción que se crearían: '.$itemsToCreate);
        $this->line('Unidades recibidas sintéticas: '.$receivedUnits);
        $this->line('Unidades aceptadas sintéticas: '.$acceptedUnits);
        $this->line('Movimientos a crear: 0');
        $this->line('Cambio esperado en stock: 0');
        $this->line('Cambio esperado stock_ingresado: 0');
        $this->line('Compras que quedarían RECIBIDA: '.$counts[self::CATEGORY_SAFE]);
        $this->line('Compras ya normalizadas verificadas: '.$alreadyNormalized);
        $this->line('Fallback de fecha utilizado: '.count($fallbackIds).' | IDs: '.$this->formatIds($fallbackIds));

        foreach ([self::CATEGORY_REVIEW, self::CATEGORY_INDETERMINATE] as $category) {
            foreach ($audits as $audit) {
                if ($audit['category'] !== $category) {
                    continue;
                }

                $this->line(
                    $category.' | Compra '.$audit['compra_id'].': '.implode(' ', $audit['reasons'])
                );
            }
        }
    }

    /**
     * @param array<int, array<string, mixed>> $audits
     */
    private function applyPlan(array $audits): int
    {
        $normalized = 0;
        $reviewed = 0;

        foreach ($audits as $audit) {
            if ($audit['category'] !== self::CATEGORY_SAFE) {
                continue;
            }

            $compraId = (int) $audit['compra_id'];
            $result = DB::transaction(function () use ($compraId): array {
                $compra = Compra::query()
                    ->whereKey($compraId)
                    ->lockForUpdate()
                    ->first();

                if (!$compra || $compra->estado_recepcion !== null) {
                    return ['status' => 'skipped'];
                }

                $currentAudit = $this->auditPurchase($compra);
                if ($currentAudit['category'] !== self::CATEGORY_SAFE) {
                    return ['status' => 'review', 'audit' => $currentAudit];
                }

                if ($currentAudit['already_normalized']) {
                    DB::table('compras')
                        ->where('id', $compraId)
                        ->update(['estado_recepcion' => 'RECIBIDA']);
                    return ['status' => 'normalized', 'created' => false];
                }

                $recepcion = CompraRecepcion::query()->create([
                    'compra_id' => $compraId,
                    'user_id' => null,
                    'fecha_recepcion' => $currentAudit['fecha_recepcion'],
                    'observacion' => self::HISTORICAL_OBSERVATION,
                    'idempotency_key' => $currentAudit['idempotency_key'],
                ]);

                foreach ($currentAudit['items'] as $item) {
                    CompraRecepcionItem::query()->create([
                        'compra_recepcion_id' => $recepcion->id,
                        'compra_item_id' => $item->id,
                        'cantidad_recibida' => (int) $item->cantidad,
                        'cantidad_aceptada' => (int) $item->stock_ingresado,
                        'cantidad_danada' => 0,
                        'cantidad_incorrecta' => 0,
                        'cantidad_faltante_declarada' => 0,
                        'cierra_item' => true,
                        'observacion' => null,
                    ]);
                }

                $verification = $this->auditPurchase($compra->fresh());
                if ($verification['category'] !== self::CATEGORY_SAFE
                    || !$verification['already_normalized']) {
                    throw new RuntimeException(
                        "La recepción histórica de la compra {$compraId} no superó la verificación posterior."
                    );
                }

                DB::table('compras')
                    ->where('id', $compraId)
                    ->update(['estado_recepcion' => 'RECIBIDA']);

                return ['status' => 'normalized', 'created' => true];
            }, 3);

            if ($result['status'] === 'review') {
                $reviewed++;
                $this->warn("Compra {$compraId} pasó a revisión durante --apply y no fue modificada.");
                continue;
            }

            if ($result['status'] === 'normalized') {
                $normalized++;
            }
        }

        $this->info("Compras normalizadas: {$normalized}");
        if ($reviewed > 0) {
            $this->warn("Compras enviadas a revisión durante la ejecución: {$reviewed}");
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * @param array<int, int> $ids
     */
    private function formatIds(array $ids): string
    {
        return $ids === [] ? 'ninguno' : implode(', ', $ids);
    }
}
