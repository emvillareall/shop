<?php

namespace Tests\Feature;

use App\Models\Compra;
use App\Models\CompraRecepcionItem;
use App\Models\ProveedorReclamo;
use App\Models\ProveedorReclamoEvento;
use App\Models\ProveedorReclamoItem;
use App\Models\ProveedorReclamoResolucion;
use App\Models\User;
use App\Services\Compras\ProveedorReclamoException;
use App\Services\Compras\ProveedorReclamoService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProveedorReclamoServiceTest extends TestCase
{
    private ProveedorReclamoService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $this->createBaseSchema();
        $this->runClaimMigrations();
        $this->service = app(ProveedorReclamoService::class);
    }

    public function test_creates_damage_claim_and_records_creation_and_line_events(): void
    {
        $fixture = $this->createReceptionFixture(['cantidad_danada' => 2]);
        $actor = User::findOrFail($fixture['user_id']);

        $reclamo = $this->service->crearBorrador(
            Compra::findOrFail($fixture['compra_id']),
            $actor,
            [[
                'compra_recepcion_item_id' => $fixture['recepcion_item_id'],
                'tipo_incidencia' => ProveedorReclamoItem::TIPO_DANADA,
                'cantidad_reclamada' => 1,
                'observacion' => 'Una unidad llegó dañada.',
            ]],
            'PROV-001',
            'Reclamo inicial'
        );

        $this->assertSame(ProveedorReclamo::ESTADO_BORRADOR, $reclamo->estado);
        $this->assertSame($fixture['user_id'], $reclamo->creado_por);
        $this->assertSame(1, $reclamo->items()->count());
        $this->assertSame(1, $this->service->cantidadReclamable(
            $fixture['recepcion_item_id'],
            ProveedorReclamoItem::TIPO_DANADA
        ));

        $this->assertSame([
            ProveedorReclamoEvento::ACCION_RECLAMO_CREADO,
            ProveedorReclamoEvento::ACCION_LINEA_AGREGADA,
        ], DB::table('proveedor_reclamo_eventos')
            ->orderBy('id')
            ->pluck('accion')
            ->all());
        $this->assertSame(
            $fixture['user_id'],
            DB::table('proveedor_reclamo_eventos')->first()->usuario_id
        );
    }

    public function test_maps_damaged_incorrect_and_missing_separately(): void
    {
        $fixture = $this->createReceptionFixture([
            'cantidad_danada' => 1,
            'cantidad_incorrecta' => 2,
            'cantidad_faltante_declarada' => 3,
        ]);
        $actor = User::findOrFail($fixture['user_id']);

        $this->service->crearBorrador(
            Compra::findOrFail($fixture['compra_id']),
            $actor,
            [
                [
                    'compra_recepcion_item_id' => $fixture['recepcion_item_id'],
                    'tipo_incidencia' => 'INCORRECTA',
                    'cantidad_reclamada' => 2,
                ],
                [
                    'compra_recepcion_item_id' => $fixture['recepcion_item_id'],
                    'tipo_incidencia' => 'FALTANTE',
                    'cantidad_reclamada' => 1,
                ],
            ]
        );

        $this->assertSame(0, $this->service->cantidadReclamable(
            $fixture['recepcion_item_id'],
            ProveedorReclamoItem::TIPO_INCORRECTA
        ));
        $this->assertSame(2, $this->service->cantidadReclamable(
            $fixture['recepcion_item_id'],
            ProveedorReclamoItem::TIPO_FALTANTE
        ));
        $this->assertSame(1, $this->service->cantidadReclamable(
            $fixture['recepcion_item_id'],
            ProveedorReclamoItem::TIPO_DANADA
        ));
        $this->assertSame([
            'DANADA' => 1,
            'INCORRECTA' => 0,
            'FALTANTE' => 2,
        ], $this->service->saldosReclamables($fixture['recepcion_item_id']));
    }

    public function test_rejected_types_quantities_and_zero_incidents_do_not_persist_data(): void
    {
        $fixture = $this->createReceptionFixture();
        $actor = User::findOrFail($fixture['user_id']);
        $compra = Compra::findOrFail($fixture['compra_id']);

        foreach (['ACEPTADA', 'OTRO', 'arbitrario'] as $tipo) {
            $this->expectReclamoException(fn () => $this->service->crearBorrador(
                $compra,
                $actor,
                [[
                    'compra_recepcion_item_id' => $fixture['recepcion_item_id'],
                    'tipo_incidencia' => $tipo,
                    'cantidad_reclamada' => 1,
                ]]
            ));
        }

        foreach ([0, -1, 1.5, '1.5'] as $cantidad) {
            $this->expectReclamoException(fn () => $this->service->crearBorrador(
                $compra,
                $actor,
                [[
                    'compra_recepcion_item_id' => $fixture['recepcion_item_id'],
                    'tipo_incidencia' => ProveedorReclamoItem::TIPO_DANADA,
                    'cantidad_reclamada' => $cantidad,
                ]]
            ));
        }

        $this->assertSame(0, DB::table('proveedor_reclamos')->count());
        $this->assertSame(0, DB::table('proveedor_reclamo_items')->count());
        $this->assertSame(0, DB::table('proveedor_reclamo_eventos')->count());
    }

    public function test_historical_zero_incidents_cannot_be_claimed(): void
    {
        $fixture = $this->createReceptionFixture();
        $this->expectReclamoException(fn () => $this->service->crearBorrador(
            Compra::findOrFail($fixture['compra_id']),
            User::findOrFail($fixture['user_id']),
            [[
                'compra_recepcion_item_id' => $fixture['recepcion_item_id'],
                'tipo_incidencia' => ProveedorReclamoItem::TIPO_DANADA,
                'cantidad_reclamada' => 1,
            ]]
        ));

        $this->assertSame(0, DB::table('proveedor_reclamos')->count());
        $this->assertSame(0, DB::table('proveedor_reclamo_eventos')->count());
    }

    public function test_multiline_creation_is_atomic_when_one_line_exceeds_balance(): void
    {
        $fixture = $this->createReceptionFixture(['cantidad_danada' => 1]);
        $secondItem = $this->addReceptionItem($fixture, ['cantidad_incorrecta' => 1]);
        $actor = User::findOrFail($fixture['user_id']);

        $this->expectReclamoException(fn () => $this->service->crearBorrador(
            Compra::findOrFail($fixture['compra_id']),
            $actor,
            [
                [
                    'compra_recepcion_item_id' => $fixture['recepcion_item_id'],
                    'tipo_incidencia' => ProveedorReclamoItem::TIPO_DANADA,
                    'cantidad_reclamada' => 1,
                ],
                [
                    'compra_recepcion_item_id' => $secondItem,
                    'tipo_incidencia' => ProveedorReclamoItem::TIPO_INCORRECTA,
                    'cantidad_reclamada' => 2,
                ],
            ]
        ));

        $this->assertSame(0, DB::table('proveedor_reclamos')->count());
        $this->assertSame(0, DB::table('proveedor_reclamo_items')->count());
        $this->assertSame(0, DB::table('proveedor_reclamo_eventos')->count());
    }

    public function test_reception_item_from_another_purchase_is_rejected(): void
    {
        $first = $this->createReceptionFixture(['cantidad_danada' => 1]);
        $second = $this->createReceptionFixture(['cantidad_danada' => 1]);

        $this->expectReclamoException(fn () => $this->service->crearBorrador(
            Compra::findOrFail($first['compra_id']),
            User::findOrFail($first['user_id']),
            [[
                'compra_recepcion_item_id' => $second['recepcion_item_id'],
                'tipo_incidencia' => ProveedorReclamoItem::TIPO_DANADA,
                'cantidad_reclamada' => 1,
            ]]
        ));

        $this->assertSame(0, DB::table('proveedor_reclamos')->count());
        $this->assertSame(0, DB::table('proveedor_reclamo_eventos')->count());
    }

    public function test_partial_claims_and_excess_are_handled_without_changing_source(): void
    {
        $fixture = $this->createReceptionFixture(['cantidad_danada' => 3]);
        $actor = User::findOrFail($fixture['user_id']);
        $compra = Compra::findOrFail($fixture['compra_id']);

        $first = $this->service->crearBorrador($compra, $actor, [[
            'compra_recepcion_item_id' => $fixture['recepcion_item_id'],
            'tipo_incidencia' => ProveedorReclamoItem::TIPO_DANADA,
            'cantidad_reclamada' => 2,
        ]]);
        $second = $this->service->crearBorrador($compra, $actor, [[
            'compra_recepcion_item_id' => $fixture['recepcion_item_id'],
            'tipo_incidencia' => ProveedorReclamoItem::TIPO_DANADA,
            'cantidad_reclamada' => 1,
        ]]);

        $this->assertNotSame($first->id, $second->id);
        $this->assertSame(0, $this->service->cantidadReclamable(
            $fixture['recepcion_item_id'],
            ProveedorReclamoItem::TIPO_DANADA
        ));

        $this->expectReclamoException(fn () => $this->service->crearBorrador($compra, $actor, [[
            'compra_recepcion_item_id' => $fixture['recepcion_item_id'],
            'tipo_incidencia' => ProveedorReclamoItem::TIPO_DANADA,
            'cantidad_reclamada' => 1,
        ]]));

        $this->assertSame(3, DB::table('proveedor_reclamo_items')->sum('cantidad_reclamada'));
        $this->assertSame(3, DB::table('compra_recepcion_items')
            ->where('id', $fixture['recepcion_item_id'])
            ->value('cantidad_danada'));
    }

    public function test_resolved_states_continue_consuming_balance(): void
    {
        $fixture = $this->createReceptionFixture(['cantidad_danada' => 2]);
        $reclamo = $this->service->crearBorrador(
            Compra::findOrFail($fixture['compra_id']),
            User::findOrFail($fixture['user_id']),
            [[
                'compra_recepcion_item_id' => $fixture['recepcion_item_id'],
                'tipo_incidencia' => ProveedorReclamoItem::TIPO_DANADA,
                'cantidad_reclamada' => 1,
            ]]
        );

        $linea = $reclamo->items->first();
        $linea->update(['estado' => ProveedorReclamoItem::ESTADO_RESUELTO]);
        $reclamo->update(['estado' => ProveedorReclamo::ESTADO_RESUELTO]);

        $this->assertSame(1, $this->service->cantidadReclamable(
            $fixture['recepcion_item_id'],
            ProveedorReclamoItem::TIPO_DANADA
        ));
    }

    public function test_only_canceled_claim_or_line_releases_reclaimable_balance(): void
    {
        $fixture = $this->createReceptionFixture(['cantidad_danada' => 3]);
        $actor = User::findOrFail($fixture['user_id']);
        $compra = Compra::findOrFail($fixture['compra_id']);
        $lineaData = [
            'compra_recepcion_item_id' => $fixture['recepcion_item_id'],
            'tipo_incidencia' => ProveedorReclamoItem::TIPO_DANADA,
            'cantidad_reclamada' => 2,
        ];

        $reclamo = $this->service->crearBorrador($compra, $actor, [$lineaData]);
        $this->assertSame(1, $this->service->cantidadReclamable(
            $fixture['recepcion_item_id'],
            ProveedorReclamoItem::TIPO_DANADA
        ));

        $this->expectReclamoException(fn () => $this->service->crearBorrador(
            $compra,
            $actor,
            [$lineaData]
        ));

        $reclamo = $this->service->marcarEnviado($reclamo, $actor);
        $this->assertSame(1, $this->service->cantidadReclamable(
            $fixture['recepcion_item_id'],
            ProveedorReclamoItem::TIPO_DANADA
        ));
        $this->expectReclamoException(fn () => $this->service->crearBorrador(
            $compra,
            $actor,
            [$lineaData]
        ));

        $reclamo = $this->service->marcarEnGestion($reclamo, $actor);
        $this->assertSame(1, $this->service->cantidadReclamable(
            $fixture['recepcion_item_id'],
            ProveedorReclamoItem::TIPO_DANADA
        ));

        $reclamo->items()->first()->update(['estado' => ProveedorReclamoItem::ESTADO_RESUELTO]);
        $reclamo->update(['estado' => ProveedorReclamo::ESTADO_RESUELTO]);
        $this->assertSame(1, $this->service->cantidadReclamable(
            $fixture['recepcion_item_id'],
            ProveedorReclamoItem::TIPO_DANADA
        ));

        $lineFixture = $this->createReceptionFixture(['cantidad_danada' => 3]);
        $lineReclamo = $this->service->crearBorrador(
            Compra::findOrFail($lineFixture['compra_id']),
            User::findOrFail($lineFixture['user_id']),
            [[
                'compra_recepcion_item_id' => $lineFixture['recepcion_item_id'],
                'tipo_incidencia' => ProveedorReclamoItem::TIPO_DANADA,
                'cantidad_reclamada' => 2,
            ]]
        );
        $this->service->cancelarLinea(
            $lineReclamo->items->first(),
            User::findOrFail($lineFixture['user_id']),
            'Liberar saldo para un nuevo reclamo.'
        );
        $this->assertSame(3, $this->service->cantidadReclamable(
            $lineFixture['recepcion_item_id'],
            ProveedorReclamoItem::TIPO_DANADA
        ));

        $claimFixture = $this->createReceptionFixture(['cantidad_danada' => 3]);
        $claimActor = User::findOrFail($claimFixture['user_id']);
        $claim = $this->service->crearBorrador(
            Compra::findOrFail($claimFixture['compra_id']),
            $claimActor,
            [[
                'compra_recepcion_item_id' => $claimFixture['recepcion_item_id'],
                'tipo_incidencia' => ProveedorReclamoItem::TIPO_DANADA,
                'cantidad_reclamada' => 2,
            ]]
        );
        $this->service->cancelarReclamo($claim, $claimActor, 'Liberar el reclamo completo.');
        $this->assertSame(3, $this->service->cantidadReclamable(
            $claimFixture['recepcion_item_id'],
            ProveedorReclamoItem::TIPO_DANADA
        ));
    }

    public function test_adding_lines_is_only_allowed_on_draft_claims(): void
    {
        $fixture = $this->createReceptionFixture(['cantidad_danada' => 2]);
        $actor = User::findOrFail($fixture['user_id']);
        $reclamo = $this->service->crearBorrador(
            Compra::findOrFail($fixture['compra_id']),
            $actor,
            [[
                'compra_recepcion_item_id' => $fixture['recepcion_item_id'],
                'tipo_incidencia' => ProveedorReclamoItem::TIPO_DANADA,
                'cantidad_reclamada' => 1,
            ]]
        );
        $this->service->marcarEnviado($reclamo, $actor);

        $this->expectReclamoException(fn () => $this->service->agregarLinea(
            $reclamo->fresh(),
            $actor,
            [
                'compra_recepcion_item_id' => $fixture['recepcion_item_id'],
                'tipo_incidencia' => ProveedorReclamoItem::TIPO_DANADA,
                'cantidad_reclamada' => 1,
            ]
        ));
    }

    public function test_adds_a_second_incident_line_to_a_draft(): void
    {
        $fixture = $this->createReceptionFixture(['cantidad_danada' => 1]);
        $secondItem = $this->addReceptionItem($fixture, ['cantidad_incorrecta' => 1]);
        $actor = User::findOrFail($fixture['user_id']);
        $reclamo = $this->service->crearBorrador(
            Compra::findOrFail($fixture['compra_id']),
            $actor,
            [[
                'compra_recepcion_item_id' => $fixture['recepcion_item_id'],
                'tipo_incidencia' => ProveedorReclamoItem::TIPO_DANADA,
                'cantidad_reclamada' => 1,
            ]]
        );

        $linea = $this->service->agregarLinea($reclamo, $actor, [
            'compra_recepcion_item_id' => $secondItem,
            'tipo_incidencia' => ProveedorReclamoItem::TIPO_INCORRECTA,
            'cantidad_reclamada' => 1,
        ]);

        $this->assertSame($reclamo->id, $linea->proveedor_reclamo_id);
        $this->assertSame(2, $reclamo->fresh()->items()->count());
        $this->assertSame(0, $this->service->cantidadReclamable(
            $secondItem,
            ProveedorReclamoItem::TIPO_INCORRECTA
        ));
    }

    public function test_canceling_line_releases_balance_and_is_idempotent(): void
    {
        $fixture = $this->createReceptionFixture(['cantidad_danada' => 3]);
        $actor = User::findOrFail($fixture['user_id']);
        $reclamo = $this->service->crearBorrador(
            Compra::findOrFail($fixture['compra_id']),
            $actor,
            [[
                'compra_recepcion_item_id' => $fixture['recepcion_item_id'],
                'tipo_incidencia' => ProveedorReclamoItem::TIPO_DANADA,
                'cantidad_reclamada' => 2,
            ]]
        );
        $linea = $reclamo->items->first();

        $cancelada = $this->service->cancelarLinea($linea, $actor, 'Se registró en el reclamo equivocado.');
        $this->service->cancelarLinea($cancelada, $actor, 'Segundo intento idempotente.');

        $this->assertSame(ProveedorReclamoItem::ESTADO_CANCELADO, $cancelada->fresh()->estado);
        $this->assertSame(3, $this->service->cantidadReclamable(
            $fixture['recepcion_item_id'],
            ProveedorReclamoItem::TIPO_DANADA
        ));
        $this->assertSame(1, DB::table('proveedor_reclamo_eventos')
            ->where('accion', ProveedorReclamoEvento::ACCION_LINEA_CANCELADA)
            ->count());
    }

    public function test_canceling_claim_releases_all_lines_without_changing_line_history(): void
    {
        $fixture = $this->createReceptionFixture([
            'cantidad_danada' => 2,
            'cantidad_faltante_declarada' => 1,
        ]);
        $actor = User::findOrFail($fixture['user_id']);
        $reclamo = $this->service->crearBorrador(
            Compra::findOrFail($fixture['compra_id']),
            $actor,
            [
                [
                    'compra_recepcion_item_id' => $fixture['recepcion_item_id'],
                    'tipo_incidencia' => ProveedorReclamoItem::TIPO_DANADA,
                    'cantidad_reclamada' => 2,
                ],
                [
                    'compra_recepcion_item_id' => $fixture['recepcion_item_id'],
                    'tipo_incidencia' => ProveedorReclamoItem::TIPO_FALTANTE,
                    'cantidad_reclamada' => 1,
                ],
            ]
        );

        $this->service->cancelarReclamo($reclamo, $actor, 'Proveedor no procesará este reclamo.');
        $reclamo = $reclamo->fresh(['items']);

        $this->assertSame(ProveedorReclamo::ESTADO_CANCELADO, $reclamo->estado);
        $this->assertSame(
            [ProveedorReclamoItem::ESTADO_PENDIENTE, ProveedorReclamoItem::ESTADO_PENDIENTE],
            $reclamo->items->sortBy('id')->pluck('estado')->values()->all()
        );
        $this->assertSame(2, $this->service->cantidadReclamable(
            $fixture['recepcion_item_id'],
            ProveedorReclamoItem::TIPO_DANADA
        ));
        $this->assertSame(1, $this->service->cantidadReclamable(
            $fixture['recepcion_item_id'],
            ProveedorReclamoItem::TIPO_FALTANTE
        ));
        $this->assertSame(1, DB::table('proveedor_reclamo_eventos')
            ->where('accion', ProveedorReclamoEvento::ACCION_RECLAMO_CANCELADO)
            ->count());

        $this->expectReclamoException(fn () => $this->service->agregarLinea(
            $reclamo,
            $actor,
            [
                'compra_recepcion_item_id' => $fixture['recepcion_item_id'],
                'tipo_incidencia' => ProveedorReclamoItem::TIPO_DANADA,
                'cantidad_reclamada' => 1,
            ]
        ));
    }

    public function test_header_transitions_are_explicit_and_lines_remain_pending(): void
    {
        $fixture = $this->createReceptionFixture(['cantidad_danada' => 1]);
        $actor = User::findOrFail($fixture['user_id']);
        $reclamo = $this->service->crearBorrador(
            Compra::findOrFail($fixture['compra_id']),
            $actor,
            [[
                'compra_recepcion_item_id' => $fixture['recepcion_item_id'],
                'tipo_incidencia' => ProveedorReclamoItem::TIPO_DANADA,
                'cantidad_reclamada' => 1,
            ]]
        );

        $reclamo = $this->service->marcarEnviado($reclamo, $actor, 'Enviado al proveedor.');
        $reclamo = $this->service->marcarEnGestion($reclamo, $actor, 'Proveedor respondió.');

        $this->assertSame(ProveedorReclamo::ESTADO_EN_GESTION, $reclamo->estado);
        $this->assertSame(
            ProveedorReclamoItem::ESTADO_PENDIENTE,
            $reclamo->items->first()->estado
        );
        $this->assertSame([
            ProveedorReclamoEvento::ACCION_RECLAMO_CREADO,
            ProveedorReclamoEvento::ACCION_LINEA_AGREGADA,
            ProveedorReclamoEvento::ACCION_RECLAMO_ENVIADO,
            ProveedorReclamoEvento::ACCION_RECLAMO_EN_GESTION,
        ], DB::table('proveedor_reclamo_eventos')
            ->orderBy('id')
            ->pluck('accion')
            ->all());

        $this->expectReclamoException(fn () => $this->service->marcarEnviado($reclamo, $actor));
        $this->assertSame(0, DB::table('proveedor_reclamo_resoluciones')->count());
    }

    public function test_credit_resolution_can_close_a_line_and_claim(): void
    {
        $fixture = $this->createReceptionFixture(['cantidad_danada' => 2]);
        $actor = User::findOrFail($fixture['user_id']);
        $reclamo = $this->service->crearBorrador(
            Compra::findOrFail($fixture['compra_id']),
            $actor,
            [[
                'compra_recepcion_item_id' => $fixture['recepcion_item_id'],
                'tipo_incidencia' => ProveedorReclamoItem::TIPO_DANADA,
                'cantidad_reclamada' => 2,
            ]]
        );
        $this->service->marcarEnviado($reclamo, $actor);

        $resolucion = $this->service->registrarResolucion(
            $reclamo->items->first(),
            $actor,
            ProveedorReclamoResolucion::TIPO_NOTA_CREDITO,
            2,
            'NC-001',
            'Nota de crédito aceptada.',
            '25.50',
            'USD'
        );

        $this->assertSame(ProveedorReclamoResolucion::ESTADO_PENDIENTE, $resolucion->estado);
        $this->assertSame(ProveedorReclamo::ESTADO_EN_GESTION, $reclamo->fresh()->estado);
        $this->assertSame(
            ProveedorReclamoItem::ESTADO_EN_GESTION,
            $reclamo->items->first()->fresh()->estado
        );
        $this->assertSame(0, $this->service->cantidadPorResolver($reclamo->items->first()));

        $resolucion = $this->service->marcarResolucionResuelta($resolucion, $actor);

        $this->assertSame(ProveedorReclamoResolucion::ESTADO_RESUELTA, $resolucion->estado);
        $this->assertSame($fixture['user_id'], $resolucion->resuelto_por);
        $this->assertSame(
            ProveedorReclamoItem::ESTADO_RESUELTO,
            $reclamo->items->first()->fresh()->estado
        );
        $this->assertSame(ProveedorReclamo::ESTADO_RESUELTO, $reclamo->fresh()->estado);
        $this->assertSame(1, DB::table('proveedor_reclamo_eventos')
            ->where('accion', ProveedorReclamoEvento::ACCION_RECLAMO_RESUELTO)
            ->count());
    }

    public function test_reimbursement_no_compensation_and_other_can_be_finalized(): void
    {
        foreach ([
            ProveedorReclamoResolucion::TIPO_REEMBOLSO,
            ProveedorReclamoResolucion::TIPO_ACEPTADO_SIN_COMPENSACION,
            ProveedorReclamoResolucion::TIPO_OTRO,
        ] as $tipo) {
            $fixture = $this->createReceptionFixture(['cantidad_danada' => 1]);
            $actor = User::findOrFail($fixture['user_id']);
            $reclamo = $this->service->crearBorrador(
                Compra::findOrFail($fixture['compra_id']),
                $actor,
                [[
                    'compra_recepcion_item_id' => $fixture['recepcion_item_id'],
                    'tipo_incidencia' => ProveedorReclamoItem::TIPO_DANADA,
                    'cantidad_reclamada' => 1,
                ]]
            );
            $this->service->marcarEnviado($reclamo, $actor);

            if ($tipo === ProveedorReclamoResolucion::TIPO_OTRO) {
                $this->expectReclamoException(fn () => $this->service->registrarResolucion(
                    $reclamo->items->first(),
                    $actor,
                    $tipo,
                    1
                ));
            }

            $resolucion = $this->service->registrarResolucion(
                $reclamo->items->first(),
                $actor,
                $tipo,
                1,
                null,
                $tipo === ProveedorReclamoResolucion::TIPO_OTRO ? 'Acuerdo comercial documentado.' : null
            );
            $this->service->marcarResolucionResuelta($resolucion, $actor);

            $this->assertSame(ProveedorReclamoResolucion::ESTADO_RESUELTA, $resolucion->fresh()->estado);
            $this->assertSame(ProveedorReclamo::ESTADO_RESUELTO, $reclamo->fresh()->estado);
        }
    }

    public function test_reposition_remains_pending_and_does_not_touch_inventory(): void
    {
        $fixture = $this->createReceptionFixture(['cantidad_danada' => 1]);
        $actor = User::findOrFail($fixture['user_id']);
        $reclamo = $this->service->crearBorrador(
            Compra::findOrFail($fixture['compra_id']),
            $actor,
            [[
                'compra_recepcion_item_id' => $fixture['recepcion_item_id'],
                'tipo_incidencia' => ProveedorReclamoItem::TIPO_DANADA,
                'cantidad_reclamada' => 1,
            ]]
        );
        $this->service->marcarEnviado($reclamo, $actor);
        $before = $this->inventoryAndReceptionSnapshot($fixture);

        $resolucion = $this->service->registrarResolucion(
            $reclamo->items->first(),
            $actor,
            ProveedorReclamoResolucion::TIPO_REPOSICION,
            1,
            'REP-001'
        );

        $this->assertSame(ProveedorReclamoResolucion::ESTADO_PENDIENTE, $resolucion->estado);
        $this->assertSame(ProveedorReclamoItem::ESTADO_EN_GESTION, $reclamo->items->first()->fresh()->estado);
        $this->assertSame(ProveedorReclamo::ESTADO_EN_GESTION, $reclamo->fresh()->estado);
        $this->expectReclamoException(fn () => $this->service->marcarResolucionResuelta($resolucion, $actor));
        $this->assertSame($before, $this->inventoryAndReceptionSnapshot($fixture));
    }

    public function test_partial_resolutions_and_mixed_resolution_types_are_supported(): void
    {
        $fixture = $this->createReceptionFixture(['cantidad_danada' => 3]);
        $actor = User::findOrFail($fixture['user_id']);
        $reclamo = $this->service->crearBorrador(
            Compra::findOrFail($fixture['compra_id']),
            $actor,
            [[
                'compra_recepcion_item_id' => $fixture['recepcion_item_id'],
                'tipo_incidencia' => ProveedorReclamoItem::TIPO_DANADA,
                'cantidad_reclamada' => 3,
            ]]
        );
        $this->service->marcarEnviado($reclamo, $actor);

        $credito = $this->service->registrarResolucion(
            $reclamo->items->first(),
            $actor,
            ProveedorReclamoResolucion::TIPO_NOTA_CREDITO,
            2
        );
        $this->service->marcarResolucionResuelta($credito, $actor);
        $this->assertSame(1, $this->service->cantidadPorResolver($reclamo->items->first()));
        $this->assertSame(ProveedorReclamoItem::ESTADO_EN_GESTION, $reclamo->items->first()->fresh()->estado);

        $reposicion = $this->service->registrarResolucion(
            $reclamo->items->first(),
            $actor,
            ProveedorReclamoResolucion::TIPO_REPOSICION,
            1
        );
        $this->assertSame(0, $this->service->cantidadPorResolver($reclamo->items->first()));
        $this->assertSame(ProveedorReclamoItem::ESTADO_EN_GESTION, $reclamo->items->first()->fresh()->estado);
        $this->assertSame(ProveedorReclamo::ESTADO_EN_GESTION, $reclamo->fresh()->estado);
        $this->assertSame(ProveedorReclamoResolucion::ESTADO_PENDIENTE, $reposicion->estado);
    }

    public function test_credit_reimbursement_and_reposition_can_share_a_line_without_closing_it_early(): void
    {
        $fixture = $this->createReceptionFixture(['cantidad_danada' => 3]);
        $actor = User::findOrFail($fixture['user_id']);
        $reclamo = $this->service->crearBorrador(
            Compra::findOrFail($fixture['compra_id']),
            $actor,
            [[
                'compra_recepcion_item_id' => $fixture['recepcion_item_id'],
                'tipo_incidencia' => ProveedorReclamoItem::TIPO_DANADA,
                'cantidad_reclamada' => 3,
            ]]
        );
        $this->service->marcarEnviado($reclamo, $actor);
        $before = $this->inventoryAndReceptionSnapshot($fixture);
        $linea = $reclamo->items->first();

        $nota = $this->service->registrarResolucion(
            $linea,
            $actor,
            ProveedorReclamoResolucion::TIPO_NOTA_CREDITO,
            1
        );
        $this->service->marcarResolucionResuelta($nota, $actor);

        $reembolso = $this->service->registrarResolucion(
            $linea,
            $actor,
            ProveedorReclamoResolucion::TIPO_REEMBOLSO,
            1
        );
        $this->service->marcarResolucionResuelta($reembolso, $actor);

        $reposicion = $this->service->registrarResolucion(
            $linea,
            $actor,
            ProveedorReclamoResolucion::TIPO_REPOSICION,
            1
        );

        $this->assertSame(0, $this->service->cantidadPorResolver($linea));
        $this->assertSame(ProveedorReclamoItem::ESTADO_EN_GESTION, $linea->fresh()->estado);
        $this->assertSame(ProveedorReclamo::ESTADO_EN_GESTION, $reclamo->fresh()->estado);
        $this->assertSame(ProveedorReclamoResolucion::ESTADO_PENDIENTE, $reposicion->estado);
        $this->assertSame($before, $this->inventoryAndReceptionSnapshot($fixture));
    }

    public function test_excess_resolution_is_rejected_and_canceling_resolution_releases_balance(): void
    {
        $fixture = $this->createReceptionFixture(['cantidad_danada' => 3]);
        $actor = User::findOrFail($fixture['user_id']);
        $reclamo = $this->service->crearBorrador(
            Compra::findOrFail($fixture['compra_id']),
            $actor,
            [[
                'compra_recepcion_item_id' => $fixture['recepcion_item_id'],
                'tipo_incidencia' => ProveedorReclamoItem::TIPO_DANADA,
                'cantidad_reclamada' => 3,
            ]]
        );
        $this->service->marcarEnviado($reclamo, $actor);
        $resolucion = $this->service->registrarResolucion(
            $reclamo->items->first(),
            $actor,
            ProveedorReclamoResolucion::TIPO_REEMBOLSO,
            2
        );

        $this->assertSame(1, $this->service->cantidadPorResolver($reclamo->items->first()));
        $this->expectReclamoException(fn () => $this->service->registrarResolucion(
            $reclamo->items->first(),
            $actor,
            ProveedorReclamoResolucion::TIPO_NOTA_CREDITO,
            2
        ));

        $this->service->cancelarResolucion($resolucion, $actor, 'El proveedor cambió la solución.');
        $this->assertSame(3, $this->service->cantidadPorResolver($reclamo->items->first()));
        $this->assertSame(ProveedorReclamoItem::ESTADO_PENDIENTE, $reclamo->items->first()->fresh()->estado);
        $this->service->cancelarResolucion($resolucion, $actor, 'Reintento idempotente.');
        $this->assertSame(1, DB::table('proveedor_reclamo_eventos')
            ->where('accion', ProveedorReclamoEvento::ACCION_RESOLUCION_CANCELADA)
            ->count());
    }

    public function test_all_active_lines_must_be_resolved_before_claim_closes(): void
    {
        $fixture = $this->createReceptionFixture([
            'cantidad_danada' => 1,
            'cantidad_incorrecta' => 1,
        ]);
        $actor = User::findOrFail($fixture['user_id']);
        $reclamo = $this->service->crearBorrador(
            Compra::findOrFail($fixture['compra_id']),
            $actor,
            [
                [
                    'compra_recepcion_item_id' => $fixture['recepcion_item_id'],
                    'tipo_incidencia' => ProveedorReclamoItem::TIPO_DANADA,
                    'cantidad_reclamada' => 1,
                ],
                [
                    'compra_recepcion_item_id' => $fixture['recepcion_item_id'],
                    'tipo_incidencia' => ProveedorReclamoItem::TIPO_INCORRECTA,
                    'cantidad_reclamada' => 1,
                ],
            ]
        );
        $this->service->marcarEnviado($reclamo, $actor);
        $lineas = $reclamo->items->sortBy('id')->values();

        $primera = $this->service->registrarResolucion(
            $lineas[0],
            $actor,
            ProveedorReclamoResolucion::TIPO_NOTA_CREDITO,
            1
        );
        $this->service->marcarResolucionResuelta($primera, $actor);
        $this->assertSame(ProveedorReclamo::ESTADO_EN_GESTION, $reclamo->fresh()->estado);

        $segunda = $this->service->registrarResolucion(
            $lineas[1],
            $actor,
            ProveedorReclamoResolucion::TIPO_ACEPTADO_SIN_COMPENSACION,
            1
        );
        $this->service->marcarResolucionResuelta($segunda, $actor);

        $this->assertSame(ProveedorReclamo::ESTADO_RESUELTO, $reclamo->fresh()->estado);
        $this->assertSame(1, DB::table('proveedor_reclamo_eventos')
            ->where('accion', ProveedorReclamoEvento::ACCION_RECLAMO_RESUELTO)
            ->count());
    }

    public function test_resolutions_are_rejected_for_draft_canceled_claim_and_canceled_line(): void
    {
        $draft = $this->createReceptionFixture(['cantidad_danada' => 1]);
        $draftActor = User::findOrFail($draft['user_id']);
        $draftClaim = $this->service->crearBorrador(
            Compra::findOrFail($draft['compra_id']),
            $draftActor,
            [[
                'compra_recepcion_item_id' => $draft['recepcion_item_id'],
                'tipo_incidencia' => ProveedorReclamoItem::TIPO_DANADA,
                'cantidad_reclamada' => 1,
            ]]
        );
        $this->expectReclamoException(fn () => $this->service->registrarResolucion(
            $draftClaim->items->first(),
            $draftActor,
            ProveedorReclamoResolucion::TIPO_NOTA_CREDITO,
            1
        ));

        $canceledClaim = $this->createReceptionFixture(['cantidad_danada' => 1]);
        $canceledActor = User::findOrFail($canceledClaim['user_id']);
        $claim = $this->service->crearBorrador(
            Compra::findOrFail($canceledClaim['compra_id']),
            $canceledActor,
            [[
                'compra_recepcion_item_id' => $canceledClaim['recepcion_item_id'],
                'tipo_incidencia' => ProveedorReclamoItem::TIPO_DANADA,
                'cantidad_reclamada' => 1,
            ]]
        );
        $this->service->cancelarReclamo($claim, $canceledActor, 'Cancelación de prueba.');
        $this->expectReclamoException(fn () => $this->service->registrarResolucion(
            $claim->items->first(),
            $canceledActor,
            ProveedorReclamoResolucion::TIPO_NOTA_CREDITO,
            1
        ));

        $canceledLine = $this->createReceptionFixture(['cantidad_danada' => 1]);
        $lineActor = User::findOrFail($canceledLine['user_id']);
        $lineClaim = $this->service->crearBorrador(
            Compra::findOrFail($canceledLine['compra_id']),
            $lineActor,
            [[
                'compra_recepcion_item_id' => $canceledLine['recepcion_item_id'],
                'tipo_incidencia' => ProveedorReclamoItem::TIPO_DANADA,
                'cantidad_reclamada' => 1,
            ]]
        );
        $this->service->cancelarLinea(
            $lineClaim->items->first(),
            $lineActor,
            'Cancelación de línea de prueba.'
        );
        $this->expectReclamoException(fn () => $this->service->registrarResolucion(
            $lineClaim->items->first(),
            $lineActor,
            ProveedorReclamoResolucion::TIPO_NOTA_CREDITO,
            1
        ));
    }

    public function test_claim_operations_do_not_change_reception_or_inventory(): void
    {
        $fixture = $this->createReceptionFixture(['cantidad_danada' => 2]);
        DB::table('inventario_movimientos')->insert([
            'producto_id' => $fixture['producto_id'],
            'tipo_movimiento' => 'ENTRADA_COMPRA',
            'cantidad' => 8,
            'stock_antes' => 0,
            'stock_despues' => 8,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $before = [
            'reception' => (array) DB::table('compra_recepcion_items')
                ->whereKey($fixture['recepcion_item_id'])
                ->first(),
            'stock_producto' => DB::table('productos')->whereKey($fixture['producto_id'])->value('stock_venta_producto'),
            'stock_variante' => DB::table('colores_productos')->whereKey($fixture['variante_id'])->value('stock_por_color'),
            'movimientos' => DB::table('inventario_movimientos')->count(),
            'estado_recepcion' => DB::table('compras')->whereKey($fixture['compra_id'])->value('estado_recepcion'),
        ];
        $actor = User::findOrFail($fixture['user_id']);
        $reclamo = $this->service->crearBorrador(
            Compra::findOrFail($fixture['compra_id']),
            $actor,
            [[
                'compra_recepcion_item_id' => $fixture['recepcion_item_id'],
                'tipo_incidencia' => ProveedorReclamoItem::TIPO_DANADA,
                'cantidad_reclamada' => 1,
            ]]
        );
        $this->service->marcarEnviado($reclamo, $actor);
        $this->service->cancelarReclamo($reclamo, $actor, 'Cancelación de prueba.');

        $this->assertSame($before['reception'], (array) DB::table('compra_recepcion_items')
            ->whereKey($fixture['recepcion_item_id'])
            ->first());
        $this->assertSame($before['stock_producto'], DB::table('productos')->whereKey($fixture['producto_id'])->value('stock_venta_producto'));
        $this->assertSame($before['stock_variante'], DB::table('colores_productos')->whereKey($fixture['variante_id'])->value('stock_por_color'));
        $this->assertSame($before['movimientos'], DB::table('inventario_movimientos')->count());
        $this->assertSame($before['estado_recepcion'], DB::table('compras')->whereKey($fixture['compra_id'])->value('estado_recepcion'));
        $this->assertSame(0, DB::table('proveedor_reclamo_resoluciones')->count());
    }

    public function test_physical_reposition_is_partial_then_closes_with_accepted_units_only(): void
    {
        $fixture = $this->createReceptionFixture(['cantidad_danada' => 2]);
        $actor = User::findOrFail($fixture['user_id']);
        $reclamo = $this->sentClaimForFixture($fixture, $actor, 2);
        $linea = $reclamo->items->first();
        $resolucion = $this->service->registrarResolucion(
            $linea,
            $actor,
            ProveedorReclamoResolucion::TIPO_REPOSICION,
            2
        );
        $beforeReception = (array) DB::table('compra_recepcion_items')
            ->whereKey($fixture['recepcion_item_id'])
            ->first();
        $beforePurchaseState = DB::table('compras')
            ->whereKey($fixture['compra_id'])
            ->value('estado_recepcion');

        $primera = $this->service->recibirReposicion(
            $resolucion,
            $actor,
            1,
            0,
            0,
            '11111111-1111-4111-8111-111111111111'
        );

        $this->assertSame(1, $primera->cantidad_recibida);
        $this->assertSame(1, $primera->cantidad_aceptada);
        $this->assertSame(ProveedorReclamoResolucion::ESTADO_EN_GESTION, $resolucion->fresh()->estado);
        $this->assertSame(ProveedorReclamoItem::ESTADO_EN_GESTION, $linea->fresh()->estado);
        $this->assertSame(ProveedorReclamo::ESTADO_EN_GESTION, $reclamo->fresh()->estado);
        $this->assertSame(9, DB::table('colores_productos')
            ->where('id', $fixture['variante_id'])
            ->value('stock_por_color'));
        $this->assertSame(9, DB::table('productos')
            ->where('id', $fixture['producto_id'])
            ->value('stock_venta_producto'));
        $this->assertSame(1, DB::table('inventario_movimientos')->count());
        $this->assertSame(
            'ENTRADA_REPOSICION_PROVEEDOR',
            DB::table('inventario_movimientos')->value('tipo_movimiento')
        );
        $this->assertSame(
            $primera->id,
            DB::table('inventario_movimientos')->value('proveedor_reclamo_reposicion_id')
        );

        $segunda = $this->service->recibirReposicion(
            $resolucion,
            $actor,
            1,
            0,
            0,
            '22222222-2222-4222-8222-222222222222'
        );

        $this->assertSame(1, $segunda->cantidad_aceptada);
        $this->assertSame(ProveedorReclamoResolucion::ESTADO_RESUELTA, $resolucion->fresh()->estado);
        $this->assertSame($fixture['user_id'], $resolucion->fresh()->resuelto_por);
        $this->assertSame(ProveedorReclamoItem::ESTADO_RESUELTO, $linea->fresh()->estado);
        $this->assertSame(ProveedorReclamo::ESTADO_RESUELTO, $reclamo->fresh()->estado);
        $this->assertSame(10, DB::table('colores_productos')
            ->where('id', $fixture['variante_id'])
            ->value('stock_por_color'));
        $this->assertSame(10, DB::table('productos')
            ->where('id', $fixture['producto_id'])
            ->value('stock_venta_producto'));
        $this->assertSame(2, DB::table('inventario_movimientos')->count());
        $this->assertSame($beforeReception, (array) DB::table('compra_recepcion_items')
            ->whereKey($fixture['recepcion_item_id'])
            ->first());
        $this->assertSame($beforePurchaseState, DB::table('compras')
            ->whereKey($fixture['compra_id'])
            ->value('estado_recepcion'));
        $this->assertSame(2, DB::table('proveedor_reclamo_eventos')
            ->where('accion', ProveedorReclamoEvento::ACCION_REPOSICION_RECIBIDA)
            ->count());
    }

    public function test_damaged_and_incorrect_reposition_units_do_not_enter_stock_or_complete(): void
    {
        $damagedFixture = $this->createReceptionFixture(['cantidad_danada' => 2]);
        $damagedActor = User::findOrFail($damagedFixture['user_id']);
        $damagedClaim = $this->sentClaimForFixture($damagedFixture, $damagedActor, 2);
        $damagedResolution = $this->service->registrarResolucion(
            $damagedClaim->items->first(),
            $damagedActor,
            ProveedorReclamoResolucion::TIPO_REPOSICION,
            2
        );

        $this->service->recibirReposicion(
            $damagedResolution,
            $damagedActor,
            1,
            1,
            0,
            '33333333-3333-4333-8333-333333333333'
        );

        $this->assertSame(1, DB::table('colores_productos')
            ->where('id', $damagedFixture['variante_id'])
            ->value('stock_por_color') - 8);
        $this->assertSame(ProveedorReclamoResolucion::ESTADO_EN_GESTION, $damagedResolution->fresh()->estado);
        $this->assertSame(1, DB::table('proveedor_reclamo_reposiciones')
            ->where('proveedor_reclamo_resolucion_id', $damagedResolution->id)
            ->value('cantidad_danada'));

        $incorrectFixture = $this->createReceptionFixture(['cantidad_incorrecta' => 1]);
        $incorrectActor = User::findOrFail($incorrectFixture['user_id']);
        $incorrectClaim = $this->sentClaimForFixture(
            $incorrectFixture,
            $incorrectActor,
            1,
            ProveedorReclamoItem::TIPO_INCORRECTA
        );
        $incorrectResolution = $this->service->registrarResolucion(
            $incorrectClaim->items->first(),
            $incorrectActor,
            ProveedorReclamoResolucion::TIPO_REPOSICION,
            1
        );

        $this->service->recibirReposicion(
            $incorrectResolution,
            $incorrectActor,
            0,
            0,
            1,
            '44444444-4444-4444-8444-444444444444'
        );

        $this->assertSame(8, DB::table('colores_productos')
            ->where('id', $incorrectFixture['variante_id'])
            ->value('stock_por_color'));
        $this->assertSame(ProveedorReclamoResolucion::ESTADO_EN_GESTION, $incorrectResolution->fresh()->estado);
        $this->assertSame(ProveedorReclamoItem::ESTADO_EN_GESTION, $incorrectClaim->items->first()->fresh()->estado);
    }

    public function test_reposition_receipt_idempotency_creates_one_receipt_and_one_stock_effect(): void
    {
        $fixture = $this->createReceptionFixture(['cantidad_danada' => 2]);
        $actor = User::findOrFail($fixture['user_id']);
        $reclamo = $this->sentClaimForFixture($fixture, $actor, 2);
        $resolucion = $this->service->registrarResolucion(
            $reclamo->items->first(),
            $actor,
            ProveedorReclamoResolucion::TIPO_REPOSICION,
            2
        );
        $key = '55555555-5555-4555-8555-555555555555';

        $first = $this->service->recibirReposicion($resolucion, $actor, 1, 0, 0, $key);
        $second = $this->service->recibirReposicion($resolucion, $actor, 1, 0, 0, $key);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, DB::table('proveedor_reclamo_reposiciones')->count());
        $this->assertSame(1, DB::table('inventario_movimientos')->count());
        $this->assertSame(9, DB::table('colores_productos')
            ->where('id', $fixture['variante_id'])
            ->value('stock_por_color'));
    }

    public function test_reposition_over_acceptance_is_rejected_without_new_inventory_effect(): void
    {
        $fixture = $this->createReceptionFixture(['cantidad_danada' => 3]);
        $actor = User::findOrFail($fixture['user_id']);
        $reclamo = $this->sentClaimForFixture($fixture, $actor, 3);
        $resolucion = $this->service->registrarResolucion(
            $reclamo->items->first(),
            $actor,
            ProveedorReclamoResolucion::TIPO_REPOSICION,
            3
        );

        $this->service->recibirReposicion(
            $resolucion,
            $actor,
            2,
            0,
            0,
            '66666666-6666-4666-8666-666666666666'
        );
        $before = $this->inventoryAndReceptionSnapshot($fixture);

        $this->expectReclamoException(fn () => $this->service->recibirReposicion(
            $resolucion,
            $actor,
            2,
            0,
            0,
            '77777777-7777-4777-8777-777777777777'
        ));

        $this->assertSame($before, $this->inventoryAndReceptionSnapshot($fixture));
        $this->assertSame(1, DB::table('proveedor_reclamo_reposiciones')->count());
        $this->assertSame(1, DB::table('inventario_movimientos')->count());
    }

    public function test_reposition_uses_original_variant_and_missing_variant_aborts_receipt(): void
    {
        $fixture = $this->createReceptionFixture(['cantidad_danada' => 2]);
        $actor = User::findOrFail($fixture['user_id']);
        $reclamo = $this->sentClaimForFixture($fixture, $actor, 2);
        $resolucion = $this->service->registrarResolucion(
            $reclamo->items->first(),
            $actor,
            ProveedorReclamoResolucion::TIPO_REPOSICION,
            1
        );
        DB::table('colores_productos')->where('id', $fixture['variante_id'])->delete();

        $this->expectReclamoException(fn () => $this->service->recibirReposicion(
            $resolucion,
            $actor,
            1,
            0,
            0,
            '88888888-8888-4888-8888-888888888888'
        ));

        $this->assertSame(0, DB::table('proveedor_reclamo_reposiciones')->count());
        $this->assertSame(0, DB::table('inventario_movimientos')->count());
        $this->assertSame(ProveedorReclamoResolucion::ESTADO_PENDIENTE, $resolucion->fresh()->estado);
    }

    public function test_reposition_without_physical_effect_can_be_canceled_and_releases_assignment(): void
    {
        $fixture = $this->createReceptionFixture(['cantidad_danada' => 2]);
        $actor = User::findOrFail($fixture['user_id']);
        $reclamo = $this->sentClaimForFixture($fixture, $actor, 2);
        $resolucion = $this->service->registrarResolucion(
            $reclamo->items->first(),
            $actor,
            ProveedorReclamoResolucion::TIPO_REPOSICION,
            2
        );

        $this->service->cancelarResolucion($resolucion, $actor, 'El proveedor no repondrá.');

        $this->assertSame(ProveedorReclamoResolucion::ESTADO_CANCELADA, $resolucion->fresh()->estado);
        $this->assertSame(2, $this->service->cantidadPorResolver($reclamo->items->first()));
        $this->assertSame(ProveedorReclamoItem::ESTADO_PENDIENTE, $reclamo->items->first()->fresh()->estado);
        $this->assertSame(ProveedorReclamo::ESTADO_EN_GESTION, $reclamo->fresh()->estado);

        $nueva = $this->service->registrarResolucion(
            $reclamo->items->first(),
            $actor,
            ProveedorReclamoResolucion::TIPO_NOTA_CREDITO,
            2
        );
        $this->service->marcarResolucionResuelta($nueva, $actor);
        $this->assertSame(ProveedorReclamo::ESTADO_RESUELTO, $reclamo->fresh()->estado);
    }

    public function test_reposition_with_accepted_units_cannot_be_canceled(): void
    {
        $fixture = $this->createReceptionFixture(['cantidad_danada' => 2]);
        $actor = User::findOrFail($fixture['user_id']);
        $reclamo = $this->sentClaimForFixture($fixture, $actor, 2);
        $resolucion = $this->service->registrarResolucion(
            $reclamo->items->first(),
            $actor,
            ProveedorReclamoResolucion::TIPO_REPOSICION,
            2
        );
        $this->service->recibirReposicion(
            $resolucion,
            $actor,
            1,
            0,
            0,
            '99999999-9999-4999-8999-999999999999'
        );
        $before = $this->inventoryAndReceptionSnapshot($fixture);

        $this->expectReclamoException(fn () => $this->service->cancelarResolucion(
            $resolucion,
            $actor,
            'No debe revertir inventario automáticamente.'
        ));

        $this->assertSame($before, $this->inventoryAndReceptionSnapshot($fixture));
        $this->assertSame(ProveedorReclamoResolucion::ESTADO_EN_GESTION, $resolucion->fresh()->estado);
    }

    public function test_reposition_receipt_is_rejected_for_canceled_claim_or_line(): void
    {
        $claimFixture = $this->createReceptionFixture(['cantidad_danada' => 1]);
        $claimActor = User::findOrFail($claimFixture['user_id']);
        $claim = $this->sentClaimForFixture($claimFixture, $claimActor, 1);
        $claimResolution = $this->service->registrarResolucion(
            $claim->items->first(),
            $claimActor,
            ProveedorReclamoResolucion::TIPO_REPOSICION,
            1
        );
        $this->service->cancelarReclamo($claim, $claimActor, 'Reclamo cancelado.');
        $this->expectReclamoException(fn () => $this->service->recibirReposicion(
            $claimResolution,
            $claimActor,
            1,
            0,
            0,
            'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa'
        ));

        $lineFixture = $this->createReceptionFixture(['cantidad_danada' => 1]);
        $lineActor = User::findOrFail($lineFixture['user_id']);
        $lineClaim = $this->sentClaimForFixture($lineFixture, $lineActor, 1);
        $lineResolution = $this->service->registrarResolucion(
            $lineClaim->items->first(),
            $lineActor,
            ProveedorReclamoResolucion::TIPO_REPOSICION,
            1
        );
        $this->service->cancelarLinea($lineClaim->items->first(), $lineActor, 'Línea cancelada.');
        $this->expectReclamoException(fn () => $this->service->recibirReposicion(
            $lineResolution,
            $lineActor,
            1,
            0,
            0,
            'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb'
        ));

        $this->assertSame(0, DB::table('proveedor_reclamo_reposiciones')->count());
        $this->assertSame(0, DB::table('inventario_movimientos')->count());
    }

    public function test_three_commercial_resolution_types_can_close_one_line_without_inventory(): void
    {
        $fixture = $this->createReceptionFixture(['cantidad_danada' => 3]);
        $actor = User::findOrFail($fixture['user_id']);
        $reclamo = $this->sentClaimForFixture($fixture, $actor, 3);
        $linea = $reclamo->items->first();
        $before = $this->inventoryAndReceptionSnapshot($fixture);

        foreach ([
            ProveedorReclamoResolucion::TIPO_NOTA_CREDITO,
            ProveedorReclamoResolucion::TIPO_REEMBOLSO,
            ProveedorReclamoResolucion::TIPO_ACEPTADO_SIN_COMPENSACION,
        ] as $tipo) {
            $resolucion = $this->service->registrarResolucion($linea, $actor, $tipo, 1);
            $this->service->marcarResolucionResuelta($resolucion, $actor);
        }

        $this->assertSame(ProveedorReclamoItem::ESTADO_RESUELTO, $linea->fresh()->estado);
        $this->assertSame(ProveedorReclamo::ESTADO_RESUELTO, $reclamo->fresh()->estado);
        $this->assertSame($before, $this->inventoryAndReceptionSnapshot($fixture));
        $this->assertSame(0, DB::table('proveedor_reclamo_reposiciones')->count());
    }

    public function test_physical_reposition_requires_positive_integer_arrival_counts_and_reposition_type(): void
    {
        $fixture = $this->createReceptionFixture(['cantidad_danada' => 2]);
        $actor = User::findOrFail($fixture['user_id']);
        $reclamo = $this->sentClaimForFixture($fixture, $actor, 2);
        $linea = $reclamo->items->first();
        $resolucion = $this->service->registrarResolucion(
            $linea,
            $actor,
            ProveedorReclamoResolucion::TIPO_REPOSICION,
            1
        );

        foreach ([
            [1.5, 0, 0, 'cccccccc-cccc-4ccc-8ccc-cccccccccccc'],
            [-1, 0, 0, 'dddddddd-dddd-4ddd-8ddd-dddddddddddd'],
            [0, 0, 0, 'eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee'],
        ] as [$aceptada, $danada, $incorrecta, $key]) {
            $this->expectReclamoException(fn () => $this->service->recibirReposicion(
                $resolucion,
                $actor,
                $aceptada,
                $danada,
                $incorrecta,
                $key
            ));
        }

        $nota = $this->service->registrarResolucion(
            $linea,
            $actor,
            ProveedorReclamoResolucion::TIPO_NOTA_CREDITO,
            1
        );
        $this->assertSame(ProveedorReclamoResolucion::ESTADO_PENDIENTE, $nota->estado);
        $this->expectReclamoException(fn () => $this->service->recibirReposicion(
            $nota,
            $actor,
            1,
            0,
            0,
            'ffffffff-ffff-4fff-8fff-ffffffffffff'
        ));
    }

    public function test_source_lock_is_ordered_and_pessimistic_locking_is_present(): void
    {
        $source = file_get_contents(app_path('Services/Compras/ProveedorReclamoService.php'));

        $this->assertIsString($source);
        $this->assertStringContainsString("->orderBy('id')", $source);
        $this->assertStringContainsString('->lockForUpdate()', $source);
        $this->assertStringContainsString('return DB::transaction(', $source);
    }

    private function expectReclamoException(callable $callback): void
    {
        try {
            $callback();
            $this->fail('Se esperaba ProveedorReclamoException.');
        } catch (ProveedorReclamoException) {
            $this->assertTrue(true);
        }
    }

    private function inventoryAndReceptionSnapshot(array $fixture): array
    {
        return [
            'reception' => (array) DB::table('compra_recepcion_items')
                ->where('id', $fixture['recepcion_item_id'])
                ->first(),
            'stock_producto' => DB::table('productos')
                ->where('id', $fixture['producto_id'])
                ->value('stock_venta_producto'),
            'stock_variante' => DB::table('colores_productos')
                ->where('id', $fixture['variante_id'])
                ->value('stock_por_color'),
            'movimientos' => DB::table('inventario_movimientos')->count(),
            'estado_recepcion' => DB::table('compras')
                ->where('id', $fixture['compra_id'])
                ->value('estado_recepcion'),
        ];
    }

    private function sentClaimForFixture(
        array $fixture,
        User $actor,
        int $cantidad,
        string $tipoIncidencia = ProveedorReclamoItem::TIPO_DANADA
    ): ProveedorReclamo {
        $reclamo = $this->service->crearBorrador(
            Compra::findOrFail($fixture['compra_id']),
            $actor,
            [[
                'compra_recepcion_item_id' => $fixture['recepcion_item_id'],
                'tipo_incidencia' => $tipoIncidencia,
                'cantidad_reclamada' => $cantidad,
            ]]
        );

        return $this->service->marcarEnviado($reclamo, $actor);
    }

    private function addReceptionItem(array $fixture, array $overrides = []): int
    {
        return DB::table('compra_recepcion_items')->insertGetId(array_merge([
            'compra_recepcion_id' => $fixture['recepcion_id'],
            'compra_item_id' => DB::table('compra_items')->insertGetId([
                'compra_id' => $fixture['compra_id'],
                'producto_id' => $fixture['producto_id'],
                'colores_id' => 1,
                'talla' => 'M',
                'cantidad' => 3,
                'stock_ingresado' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]),
            'cantidad_recibida' => 0,
            'cantidad_aceptada' => 0,
            'cantidad_danada' => 0,
            'cantidad_incorrecta' => 0,
            'cantidad_faltante_declarada' => 0,
            'cierra_item' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    private function createReceptionFixture(array $overrides = []): array
    {
        $userId = DB::table('users')->insertGetId([
            'name' => 'Claims Service User',
            'email' => 'claims-service-'.uniqid().'@example.test',
            'password' => 'password',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $providerId = DB::table('proveedores')->insertGetId([
            'tienda_proveedor' => 'Proveedor de servicio',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $compraId = DB::table('compras')->insertGetId([
            'codigo_compra' => 'COMPRA-'.uniqid(),
            'descripcion_compra' => 'Compra de servicio',
            'proveedor_id' => $providerId,
            'estado_compra' => '1',
            'estado_recepcion' => 'CON_INCIDENCIAS',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $productoId = DB::table('productos')->insertGetId([
            'stock_venta_producto' => 8,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $varianteId = DB::table('colores_productos')->insertGetId([
            'producto_id' => $productoId,
            'colores_id' => 1,
            'talla_por_color' => 'M',
            'stock_por_color' => 8,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $compraItemId = DB::table('compra_items')->insertGetId([
            'compra_id' => $compraId,
            'producto_id' => $productoId,
            'colores_id' => 1,
            'talla' => 'M',
            'cantidad' => 3,
            'stock_ingresado' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $recepcionId = DB::table('compra_recepciones')->insertGetId([
            'compra_id' => $compraId,
            'user_id' => $userId,
            'fecha_recepcion' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $recepcionValues = array_merge([
            'compra_recepcion_id' => $recepcionId,
            'compra_item_id' => $compraItemId,
            'cantidad_recibida' => 0,
            'cantidad_aceptada' => 0,
            'cantidad_danada' => 0,
            'cantidad_incorrecta' => 0,
            'cantidad_faltante_declarada' => 0,
            'cierra_item' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides);
        $recepcionValues['cantidad_recibida'] ??= (int) $recepcionValues['cantidad_aceptada']
            + (int) $recepcionValues['cantidad_danada']
            + (int) $recepcionValues['cantidad_incorrecta'];
        $recepcionItemId = DB::table('compra_recepcion_items')->insertGetId($recepcionValues);

        return [
            'user_id' => $userId,
            'provider_id' => $providerId,
            'compra_id' => $compraId,
            'producto_id' => $productoId,
            'variante_id' => $varianteId,
            'compra_item_id' => $compraItemId,
            'recepcion_id' => $recepcionId,
            'recepcion_item_id' => $recepcionItemId,
        ];
    }

    private function runClaimMigrations(): void
    {
        $paths = glob(database_path('migrations/2026_10_05_16000*.php')) ?: [];
        sort($paths);
        foreach ($paths as $path) {
            $migration = require $path;
            $migration->up();
        }

        $reposicionMigration = require database_path(
            'migrations/2026_10_06_100000_create_proveedor_reclamo_reposiciones_table.php'
        );
        $reposicionMigration->up();
    }

    private function createBaseSchema(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('accion', 80);
            $table->string('entidad', 120)->nullable();
            $table->string('entidad_id', 120)->nullable();
            $table->json('valores_antes')->nullable();
            $table->json('valores_despues')->nullable();
            $table->string('ip', 64)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
        Schema::create('proveedores', function (Blueprint $table): void {
            $table->id();
            $table->string('tienda_proveedor');
            $table->timestamps();
        });
        Schema::create('compras', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo_compra');
            $table->string('descripcion_compra');
            $table->foreignId('proveedor_id')->constrained('proveedores');
            $table->string('estado_compra')->default('1');
            $table->string('estado_recepcion', 30)->default('EN_TRANSITO');
            $table->timestamps();
        });
        Schema::create('productos', function (Blueprint $table): void {
            $table->id();
            $table->integer('stock_venta_producto')->default(0);
            $table->timestamps();
        });
        Schema::create('colores_productos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos');
            $table->unsignedBigInteger('colores_id');
            $table->string('talla_por_color');
            $table->integer('stock_por_color')->default(0);
            $table->timestamps();
        });
        Schema::create('compra_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('compra_id')->constrained('compras');
            $table->unsignedBigInteger('producto_id')->nullable();
            $table->unsignedBigInteger('colores_id')->nullable();
            $table->string('talla')->nullable();
            $table->integer('cantidad')->default(0);
            $table->integer('stock_ingresado')->default(0);
            $table->timestamps();
        });
        Schema::create('compra_recepciones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('compra_id')->constrained('compras');
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->dateTime('fecha_recepcion');
            $table->timestamps();
        });
        Schema::create('compra_recepcion_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('compra_recepcion_id')->constrained('compra_recepciones');
            $table->foreignId('compra_item_id')->constrained('compra_items');
            $table->unsignedInteger('cantidad_recibida')->default(0);
            $table->unsignedInteger('cantidad_aceptada')->default(0);
            $table->unsignedInteger('cantidad_danada')->default(0);
            $table->unsignedInteger('cantidad_incorrecta')->default(0);
            $table->unsignedInteger('cantidad_faltante_declarada')->default(0);
            $table->boolean('cierra_item')->default(false);
            $table->timestamps();
        });
        Schema::create('inventario_movimientos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos');
            $table->unsignedBigInteger('colores_productos_id')->nullable();
            $table->unsignedBigInteger('pedido_id')->nullable();
            $table->unsignedBigInteger('compra_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('tipo_movimiento', 40);
            $table->integer('cantidad');
            $table->integer('stock_antes')->nullable();
            $table->integer('stock_despues')->nullable();
            $table->unsignedBigInteger('compra_item_id')->nullable();
            $table->unsignedBigInteger('compra_recepcion_id')->nullable();
            $table->unsignedBigInteger('compra_recepcion_item_id')->nullable();
            $table->string('motivo', 180)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }
}
