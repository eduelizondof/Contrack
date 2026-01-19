<?php

namespace App\Services\Notificaciones;

use App\Models\Notificaciones\CategoriaNotificacion;
use App\Models\Notificaciones\Notificacion;
use App\Models\Notificaciones\UsuarioNotificacion;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ServicioNotificaciones
{
    /**
     * Crear notificación y asignar a usuarios
     *
     * @param array $datos Datos de la notificación
     * @param array|int $usuariosDestino IDs de usuarios o array de IDs
     * @return Notificacion
     */
    public function crear(array $datos, array|int $usuariosDestino): Notificacion
    {
        // Normalizar usuarios destino
        if (is_int($usuariosDestino)) {
            $usuariosDestino = [$usuariosDestino];
        }

        // Obtener categoría por tipo si no se proporciona id_categoria
        if (!isset($datos['id_categoria']) && isset($datos['tipo_categoria'])) {
            $categoria = CategoriaNotificacion::where('tipo', $datos['tipo_categoria'])->first();
            if ($categoria) {
                $datos['id_categoria'] = $categoria->id;
            }
            unset($datos['tipo_categoria']);
        }

        // Crear notificación
        $notificacion = Notificacion::create($datos);

        // Asignar a usuarios
        if (!empty($usuariosDestino)) {
            $this->asignarAUsuarios($notificacion->id, $usuariosDestino);
        }

        return $notificacion->fresh(['categoria']);
    }

    /**
     * Asignar notificación a usuarios
     *
     * @param int $idNotificacion
     * @param array $usuariosDestino
     * @return void
     */
    public function asignarAUsuarios(int $idNotificacion, array $usuariosDestino): void
    {
        $inserts = [];
        $now = now();

        foreach ($usuariosDestino as $idUsuario) {
            // Verificar que no exista ya la relación
            $existe = UsuarioNotificacion::where('id_usuario', $idUsuario)
                ->where('id_notificacion', $idNotificacion)
                ->exists();

            if (!$existe) {
                $inserts[] = [
                    'id_usuario' => $idUsuario,
                    'id_notificacion' => $idNotificacion,
                    'leido' => false,
                    'leido_el' => null,
                ];
            }
        }

        if (!empty($inserts)) {
            UsuarioNotificacion::insert($inserts);
        }
    }

    /**
     * Marcar notificación como leída para un usuario
     *
     * @param int $idUsuario
     * @param int $idNotificacion
     * @return bool
     */
    public function marcarComoLeida(int $idUsuario, int $idNotificacion): bool
    {
        $usuarioNotificacion = UsuarioNotificacion::where('id_usuario', $idUsuario)
            ->where('id_notificacion', $idNotificacion)
            ->first();

        if ($usuarioNotificacion && !$usuarioNotificacion->leido) {
            return $usuarioNotificacion->marcarComoLeida();
        }

        return false;
    }

    /**
     * Marcar todas las notificaciones como leídas para un usuario
     *
     * @param int $idUsuario
     * @return int Número de notificaciones marcadas
     */
    public function marcarTodasComoLeidas(int $idUsuario): int
    {
        return UsuarioNotificacion::where('id_usuario', $idUsuario)
            ->where('leido', false)
            ->update([
                'leido' => true,
                'leido_el' => now(),
            ]);
    }

    /**
     * Obtener notificaciones no leídas de un usuario
     *
     * @param int $idUsuario
     * @param int|null $limite
     * @return Collection
     */
    public function obtenerNoLeidas(int $idUsuario, ?int $limite = null): Collection
    {
        $query = Notificacion::whereHas('usuarios', function ($q) use ($idUsuario) {
            $q->where('id_usuario', $idUsuario)
              ->where('leido', false);
        })
        ->with(['categoria', 'usuarioCreador'])
        ->orderBy('creado_el', 'desc');

        if ($limite) {
            $query->limit($limite);
        }

        return $query->get()->map(function ($notificacion) use ($idUsuario) {
            $pivot = UsuarioNotificacion::where('id_usuario', $idUsuario)
                ->where('id_notificacion', $notificacion->id)
                ->first();

            $notificacion->leido = $pivot->leido ?? false;
            $notificacion->leido_el = $pivot->leido_el ?? null;

            return $notificacion;
        });
    }

    /**
     * Obtener todas las notificaciones de un usuario
     *
     * @param int $idUsuario
     * @param int|null $limite
     * @return Collection
     */
    public function obtenerTodas(int $idUsuario, ?int $limite = null): Collection
    {
        $query = Notificacion::whereHas('usuarios', function ($q) use ($idUsuario) {
            $q->where('id_usuario', $idUsuario);
        })
        ->with(['categoria', 'usuarioCreador'])
        ->orderBy('creado_el', 'desc');

        if ($limite) {
            $query->limit($limite);
        }

        return $query->get()->map(function ($notificacion) use ($idUsuario) {
            $pivot = UsuarioNotificacion::where('id_usuario', $idUsuario)
                ->where('id_notificacion', $notificacion->id)
                ->first();

            $notificacion->leido = $pivot->leido ?? false;
            $notificacion->leido_el = $pivot->leido_el ?? null;

            return $notificacion;
        });
    }

    /**
     * Contar notificaciones no leídas de un usuario
     *
     * @param int $idUsuario
     * @return int
     */
    public function contarNoLeidas(int $idUsuario): int
    {
        return UsuarioNotificacion::where('id_usuario', $idUsuario)
            ->where('leido', false)
            ->count();
    }

    /**
     * Eliminar notificación para un usuario (solo la relación, no la notificación)
     *
     * @param int $idUsuario
     * @param int $idNotificacion
     * @return bool
     */
    public function eliminar(int $idUsuario, int $idNotificacion): bool
    {
        return UsuarioNotificacion::where('id_usuario', $idUsuario)
            ->where('id_notificacion', $idNotificacion)
            ->delete() > 0;
    }

    /**
     * Obtener notificaciones paginadas de un usuario con filtros
     *
     * @param int $idUsuario
     * @param array $filtros ['leidas' => true/false, 'prioridad' => 'alta', 'categoria' => 'proyecto']
     * @param int $porPagina
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function obtenerPaginadas(int $idUsuario, array $filtros = [], int $porPagina = 15)
    {
        $query = Notificacion::whereHas('usuarios', function ($q) use ($idUsuario, $filtros) {
            $q->where('id_usuario', $idUsuario);
            
            if (isset($filtros['leidas'])) {
                $q->where('leido', $filtros['leidas']);
            }
        })
        ->with(['categoria', 'usuarioCreador']);

        // Filtro por prioridad
        if (isset($filtros['prioridad'])) {
            $query->where('prioridad', $filtros['prioridad']);
        }

        // Filtro por categoría
        if (isset($filtros['categoria'])) {
            $query->whereHas('categoria', function ($q) use ($filtros) {
                $q->where('tipo', $filtros['categoria']);
            });
        }

        $notificaciones = $query->orderBy('creado_el', 'desc')
            ->paginate($porPagina);

        // Agregar información de leído a cada notificación
        $notificaciones->getCollection()->transform(function ($notificacion) use ($idUsuario) {
            $pivot = UsuarioNotificacion::where('id_usuario', $idUsuario)
                ->where('id_notificacion', $notificacion->id)
                ->first();

            $notificacion->leido = $pivot->leido ?? false;
            $notificacion->leido_el = $pivot->leido_el ?? null;

            return $notificacion;
        });

        return $notificaciones;
    }
}
