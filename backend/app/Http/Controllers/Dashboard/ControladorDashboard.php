<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Cargo;
use App\Models\Contrato;
use App\Models\Inmueble;
use App\Models\InmuebleUsuario;
use App\Models\Pago;
use App\Models\Ticket;
use App\Models\Unidad;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ControladorDashboard extends Controller
{
    /**
     * Dashboard para inquilinos
     * Muestra sus unidades, contratos activos, adeudos y tickets
     */
    public function dashboardInquilino(Request $request): JsonResponse
    {
        $usuario = $request->user();

        // Obtener unidades donde el usuario es inquilino
        $asignaciones = InmuebleUsuario::with(['unidad.inmueble', 'unidad.contratos' => function ($q) use ($usuario) {
            $q->where('usuario_id', $usuario->id)->activos();
        }])
            ->where('usuario_id', $usuario->id)
            ->where('rol', 'inquilino')
            ->activos()
            ->get();

        $unidadIds = $asignaciones->pluck('unidad_id')->filter();

        // Adeudos pendientes
        $adeudosPendientes = Cargo::whereIn('unidad_id', $unidadIds)
            ->conAdeudo()
            ->sum('monto');

        // Cargos vencidos
        $cargosVencidos = Cargo::whereIn('unidad_id', $unidadIds)
            ->vencidos()
            ->count();

        // Tickets activos del usuario
        $ticketsActivos = Ticket::where('usuario_reporta_id', $usuario->id)
            ->activos()
            ->count();

        // Próximos pagos (cargos pendientes próximos a vencer)
        $proximosPagos = Cargo::whereIn('unidad_id', $unidadIds)
            ->pendientes()
            ->orderBy('fecha_vencimiento')
            ->take(5)
            ->with('concepto')
            ->get();

        return response()->json([
            'datos' => [
                'resumen' => [
                    'unidades_asignadas' => $asignaciones->count(),
                    'adeudo_total' => $adeudosPendientes,
                    'cargos_vencidos' => $cargosVencidos,
                    'tickets_activos' => $ticketsActivos,
                ],
                'proximos_pagos' => $proximosPagos,
                'unidades' => $asignaciones->map(fn ($a) => [
                    'id' => $a->unidad_id,
                    'identificador' => $a->unidad?->identificador,
                    'inmueble' => $a->unidad?->inmueble?->nombre,
                ]),
            ],
        ]);
    }

    /**
     * Dashboard para técnicos
     * Muestra tickets asignados y estadísticas de resolución
     */
    public function dashboardTecnico(Request $request): JsonResponse
    {
        $usuario = $request->user();

        // Tickets asignados actualmente
        $ticketsAsignados = Ticket::whereHas('tecnicos', function ($q) use ($usuario) {
            $q->where('usuario_id', $usuario->id)->where('activo', true);
        })->get();

        $ticketsPorEstado = $ticketsAsignados->groupBy('estado')->map->count();

        // Tickets urgentes
        $ticketsUrgentes = $ticketsAsignados->where('prioridad', 'urgente')->count();

        // Tickets resueltos este mes
        $ticketsResueltosEsteMes = Ticket::whereHas('tecnicos', function ($q) use ($usuario) {
            $q->where('usuario_id', $usuario->id);
        })
            ->whereIn('estado', ['resuelto', 'cerrado'])
            ->whereMonth('fecha_resolucion', now()->month)
            ->whereYear('fecha_resolucion', now()->year)
            ->count();

        // Promedio de valoración
        $promedioValoracion = Ticket::whereHas('tecnicos', function ($q) use ($usuario) {
            $q->where('usuario_id', $usuario->id);
        })
            ->whereNotNull('valoracion')
            ->avg('valoracion');

        return response()->json([
            'datos' => [
                'resumen' => [
                    'tickets_asignados' => $ticketsAsignados->count(),
                    'tickets_urgentes' => $ticketsUrgentes,
                    'resueltos_este_mes' => $ticketsResueltosEsteMes,
                    'valoracion_promedio' => round($promedioValoracion ?? 0, 1),
                ],
                'tickets_por_estado' => $ticketsPorEstado,
                'tickets_pendientes' => $ticketsAsignados
                    ->whereIn('estado', ['abierto', 'en_proceso'])
                    ->take(10)
                    ->map(fn ($t) => [
                        'id' => $t->id,
                        'titulo' => $t->titulo,
                        'prioridad' => $t->prioridad,
                        'estado' => $t->estado,
                        'categoria' => $t->categoria,
                    ])->values(),
            ],
        ]);
    }

    /**
     * Dashboard para administradores y supervisores
     * Muestra resumen general de inmuebles asignados
     */
    public function dashboardAdministrador(Request $request): JsonResponse
    {
        $usuario = $request->user();

        // Inmuebles donde el usuario es administrador
        $inmuebleIds = InmuebleUsuario::where('usuario_id', $usuario->id)
            ->whereIn('rol', ['administrador', 'supervisor'])
            ->activos()
            ->pluck('inmueble_id');

        // Si no tiene asignaciones específicas, mostrar todos (super admin)
        if ($inmuebleIds->isEmpty() && $usuario->hasRole('super-admin')) {
            $inmuebleIds = Inmueble::pluck('id');
        }

        // Estadísticas de inmuebles
        $totalInmuebles = Inmueble::whereIn('id', $inmuebleIds)->count();
        $totalUnidades = Unidad::whereIn('inmueble_id', $inmuebleIds)->count();

        // Contratos activos
        $contratosActivos = Contrato::whereHas('unidad', function ($q) use ($inmuebleIds) {
            $q->whereIn('inmueble_id', $inmuebleIds);
        })->activos()->count();

        // Adeudos totales
        $adeudoTotal = Cargo::whereHas('unidad', function ($q) use ($inmuebleIds) {
            $q->whereIn('inmueble_id', $inmuebleIds);
        })->conAdeudo()->sum('monto');

        // Unidades con adeudo
        $unidadesConAdeudo = Unidad::whereIn('inmueble_id', $inmuebleIds)
            ->conAdeudos()
            ->count();

        // Tickets abiertos
        $ticketsAbiertos = Ticket::whereHas('unidad', function ($q) use ($inmuebleIds) {
            $q->whereIn('inmueble_id', $inmuebleIds);
        })->activos()->count();

        // Pagos pendientes de verificación
        $pagosPendientes = Pago::whereHas('cargo.unidad', function ($q) use ($inmuebleIds) {
            $q->whereIn('inmueble_id', $inmuebleIds);
        })->pendientesVerificacion()->count();

        // Ingresos del mes
        $ingresosDelMes = Pago::whereHas('cargo.unidad', function ($q) use ($inmuebleIds) {
            $q->whereIn('inmueble_id', $inmuebleIds);
        })
            ->pagados()
            ->delMes()
            ->sum('monto');

        // Contratos por vencer (próximos 30 días)
        $contratosPorVencer = Contrato::whereHas('unidad', function ($q) use ($inmuebleIds) {
            $q->whereIn('inmueble_id', $inmuebleIds);
        })->porVencer(30)->count();

        return response()->json([
            'datos' => [
                'resumen' => [
                    'inmuebles' => $totalInmuebles,
                    'unidades' => $totalUnidades,
                    'contratos_activos' => $contratosActivos,
                    'adeudo_total' => $adeudoTotal,
                    'unidades_con_adeudo' => $unidadesConAdeudo,
                    'tickets_abiertos' => $ticketsAbiertos,
                    'pagos_pendientes_verificar' => $pagosPendientes,
                    'ingresos_del_mes' => $ingresosDelMes,
                    'contratos_por_vencer' => $contratosPorVencer,
                ],
                'inmuebles' => Inmueble::whereIn('id', $inmuebleIds)
                    ->withCount('unidades')
                    ->get()
                    ->map(fn ($i) => [
                        'id' => $i->id,
                        'nombre' => $i->nombre,
                        'tipo' => $i->tipo,
                        'unidades_count' => $i->unidades_count,
                    ]),
            ],
        ]);
    }

    /**
     * Estadísticas generales del sistema
     */
    public function estadisticasGenerales(Request $request): JsonResponse
    {
        $usuario = $request->user();

        // Determinar inmuebles accesibles según rol
        $inmuebleIds = $this->obtenerInmueblesAccesibles($usuario);

        // Estadísticas de cobranza por mes (últimos 6 meses)
        $cobranzaPorMes = [];
        for ($i = 5; $i >= 0; $i--) {
            $fecha = now()->subMonths($i);
            $mes = $fecha->format('Y-m');

            $cobrado = Pago::whereHas('cargo.unidad', function ($q) use ($inmuebleIds) {
                $q->whereIn('inmueble_id', $inmuebleIds);
            })
                ->pagados()
                ->whereMonth('fecha_pago', $fecha->month)
                ->whereYear('fecha_pago', $fecha->year)
                ->sum('monto');

            $cobranzaPorMes[] = [
                'mes' => $mes,
                'mes_nombre' => $fecha->translatedFormat('M Y'),
                'cobrado' => $cobrado,
            ];
        }

        // Tickets por categoría
        $ticketsPorCategoria = Ticket::whereHas('unidad', function ($q) use ($inmuebleIds) {
            $q->whereIn('inmueble_id', $inmuebleIds);
        })
            ->selectRaw('categoria, count(*) as total')
            ->groupBy('categoria')
            ->pluck('total', 'categoria');

        return response()->json([
            'datos' => [
                'cobranza_por_mes' => $cobranzaPorMes,
                'tickets_por_categoria' => $ticketsPorCategoria,
            ],
        ]);
    }

    /**
     * Obtener IDs de inmuebles accesibles para el usuario
     */
    private function obtenerInmueblesAccesibles($usuario): \Illuminate\Support\Collection
    {
        if ($usuario->hasRole('super-admin')) {
            return Inmueble::pluck('id');
        }

        return InmuebleUsuario::where('usuario_id', $usuario->id)
            ->activos()
            ->pluck('inmueble_id');
    }
}
