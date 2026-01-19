<?php

namespace App\Models\Chat;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversacion extends BaseModel
{
    /**
     * La tabla asociada al modelo
     */
    protected $table = 'conversaciones';

    /**
     * Los atributos que son asignables en masa
     */
    protected $fillable = [
        'nombre',
        'es_grupo',
        'creado_por',
    ];

    /**
     * Los atributos que deben ser cast a tipos nativos
     */
    protected $casts = [
        'es_grupo' => 'boolean',
        'creado_el' => 'datetime',
        'actualizado_el' => 'datetime',
        'eliminado_el' => 'datetime',
    ];

    /**
     * Relación con el usuario creador
     */
    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    /**
     * Relación con usuarios (participantes)
     */
    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversacion_usuarios', 'conversacion_id', 'user_id')
            ->withPivot('es_admin', 'archivada', 'ultimo_visto_at', 'creado_el');
    }

    /**
     * Relación con mensajes
     */
    public function mensajes(): HasMany
    {
        return $this->hasMany(Mensaje::class, 'conversacion_id');
    }

    /**
     * Obtener el último mensaje de la conversación
     */
    public function ultimoMensaje(): HasOne
    {
        return $this->hasOne(Mensaje::class, 'conversacion_id')
            ->where('eliminado', false)
            ->orderByDesc('creado_el');
    }

    /**
     * Obtener la cantidad de mensajes no leídos para un usuario
     */
    public function mensajesNoLeidos(int $userId): int
    {
        $pivot = $this->usuarios()->where('user_id', $userId)->first()?->pivot;
        
        if (!$pivot || !$pivot->ultimo_visto_at) {
            return $this->mensajes()->where('eliminado', false)->count();
        }

        return $this->mensajes()
            ->where('eliminado', false)
            ->where('user_id', '!=', $userId)
            ->where('creado_el', '>', $pivot->ultimo_visto_at)
            ->count();
    }

    /**
     * Verificar si tiene mensajes nuevos (último mensaje es de otro usuario y no leído)
     */
    public function tieneMensajesNuevos(int $userId): bool
    {
        $ultimoMensaje = $this->ultimoMensaje;
        
        // Si no hay mensajes, no hay mensajes nuevos
        if (!$ultimoMensaje) {
            return false;
        }

        // Si el último mensaje es del usuario logueado, no hay mensajes nuevos
        if ($ultimoMensaje->user_id === $userId) {
            return false;
        }

        // Verificar si el mensaje ha sido leído por el usuario
        $fueVisto = $ultimoMensaje->fueVistoPor($userId);
        
        // Si no fue visto, hay mensajes nuevos
        return !$fueVisto;
    }

    /**
     * Verificar si el usuario es admin de la conversación
     */
    public function esAdmin(int $userId): bool
    {
        return $this->usuarios()
            ->where('user_id', $userId)
            ->wherePivot('es_admin', true)
            ->exists();
    }

    /**
     * Verificar si el usuario pertenece a la conversación
     */
    public function perteneceUsuario(int $userId): bool
    {
        return $this->usuarios()->where('user_id', $userId)->exists();
    }

    /**
     * Obtener nombre de la conversación para mostrar
     * Si no tiene nombre, genera uno con los nombres de los participantes
     */
    public function getNombreMostrarAttribute(): string
    {
        if ($this->nombre) {
            return $this->nombre;
        }

        $usuarios = $this->usuarios()->limit(3)->get();
        $nombres = $usuarios->pluck('name')->toArray();
        
        if (count($nombres) > 2) {
            return implode(', ', array_slice($nombres, 0, 2)) . ' y más...';
        }

        return implode(', ', $nombres);
    }

    /**
     * Scope para conversaciones activas (no archivadas) de un usuario
     */
    public function scopeActivasPorUsuario($query, int $userId)
    {
        return $query->whereHas('usuarios', function ($q) use ($userId) {
            $q->where('user_id', $userId)
              ->where('archivada', false);
        });
    }

    /**
     * Scope para conversaciones archivadas de un usuario
     */
    public function scopeArchivadasPorUsuario($query, int $userId)
    {
        return $query->whereHas('usuarios', function ($q) use ($userId) {
            $q->where('user_id', $userId)
              ->where('archivada', true);
        });
    }

    /**
     * Scope para conversaciones con mensajes no leídos
     */
    public function scopeConMensajesNoLeidos($query, int $userId)
    {
        return $query->whereHas('mensajes', function ($q) use ($userId) {
            $q->where('eliminado', false)
              ->where('user_id', '!=', $userId)
              ->whereDoesntHave('vistos', function ($subQ) use ($userId) {
                  $subQ->where('user_id', $userId);
              });
        });
    }

    /**
     * Scope para ordenar por último mensaje
     */
    public function scopeOrdenarPorUltimoMensaje($query)
    {
        return $query->addSelect([
            'ultimo_mensaje_fecha' => Mensaje::select('creado_el')
                ->whereColumn('conversacion_id', 'conversaciones.id')
                ->where('eliminado', false)
                ->orderByDesc('creado_el')
                ->limit(1)
        ])->orderByDesc('ultimo_mensaje_fecha');
    }
}
