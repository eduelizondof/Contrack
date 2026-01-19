<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends BaseModel
{
    use HasFactory;

    protected $table = 'tickets';

    protected $fillable = [
        'unidad_id',
        'usuario_reporta_id',
        'categoria',
        'titulo',
        'descripcion',
        'prioridad',
        'estado',
        'fecha_reporte',
        'fecha_asignacion',
        'fecha_resolucion',
        'valoracion',
        'comentario_valoracion',
    ];

    protected $casts = [
        'fecha_reporte' => 'datetime',
        'fecha_asignacion' => 'datetime',
        'fecha_resolucion' => 'datetime',
        'valoracion' => 'integer',
    ];

    // ==========================================
    // RELACIONES
    // ==========================================

    public function unidad(): BelongsTo
    {
        return $this->belongsTo(Unidad::class, 'unidad_id');
    }

    public function usuarioReporta(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_reporta_id');
    }

    public function tecnicos(): HasMany
    {
        return $this->hasMany(TicketTecnico::class, 'ticket_id');
    }

    public function comentarios(): HasMany
    {
        return $this->hasMany(TicketComentario::class, 'ticket_id');
    }

    public function adjuntos(): HasMany
    {
        return $this->hasMany(TicketAdjunto::class, 'ticket_id');
    }

    // ==========================================
    // SCOPES - Por Estado
    // ==========================================

    public function scopeAbiertos($query)
    {
        return $query->where('estado', 'abierto');
    }

    public function scopeEnProceso($query)
    {
        return $query->where('estado', 'en_proceso');
    }

    public function scopePausados($query)
    {
        return $query->where('estado', 'pausado');
    }

    public function scopeResueltos($query)
    {
        return $query->where('estado', 'resuelto');
    }

    public function scopeCerrados($query)
    {
        return $query->where('estado', 'cerrado');
    }

    public function scopeCancelados($query)
    {
        return $query->where('estado', 'cancelado');
    }

    public function scopeActivos($query)
    {
        return $query->whereIn('estado', ['abierto', 'en_proceso', 'pausado']);
    }

    // ==========================================
    // SCOPES - Por Prioridad
    // ==========================================

    public function scopePrioridad($query, string $prioridad)
    {
        return $query->where('prioridad', $prioridad);
    }

    public function scopeBaja($query)
    {
        return $query->where('prioridad', 'baja');
    }

    public function scopeMedia($query)
    {
        return $query->where('prioridad', 'media');
    }

    public function scopeAlta($query)
    {
        return $query->where('prioridad', 'alta');
    }

    public function scopeUrgentes($query)
    {
        return $query->where('prioridad', 'urgente');
    }

    // ==========================================
    // SCOPES - Por Categoría
    // ==========================================

    public function scopeCategoria($query, string $categoria)
    {
        return $query->where('categoria', $categoria);
    }

    public function scopePlomeria($query)
    {
        return $query->where('categoria', 'plomeria');
    }

    public function scopeElectricidad($query)
    {
        return $query->where('categoria', 'electricidad');
    }

    public function scopeLimpieza($query)
    {
        return $query->where('categoria', 'limpieza');
    }

    public function scopeSeguridad($query)
    {
        return $query->where('categoria', 'seguridad');
    }

    // ==========================================
    // SCOPES - Otros
    // ==========================================

    public function scopeSinAsignar($query)
    {
        return $query->whereNull('fecha_asignacion')
            ->whereDoesntHave('tecnicos', fn ($q) => $q->where('activo', true));
    }

    public function scopeConValoracion($query)
    {
        return $query->whereNotNull('valoracion');
    }

    // ==========================================
    // ACCESSORS
    // ==========================================

    /**
     * Obtener el técnico activo asignado
     */
    public function getTecnicoActivoAttribute()
    {
        return $this->tecnicos()
            ->where('activo', true)
            ->with('usuario')
            ->first();
    }

    /**
     * Verificar si está asignado
     */
    public function getEstaAsignadoAttribute(): bool
    {
        return $this->tecnicos()->where('activo', true)->exists();
    }

    /**
     * Tiempo de respuesta en horas
     */
    public function getTiempoRespuestaAttribute(): ?float
    {
        if (!$this->fecha_asignacion) {
            return null;
        }

        return $this->fecha_reporte->diffInHours($this->fecha_asignacion);
    }

    /**
     * Tiempo de resolución en horas
     */
    public function getTiempoResolucionAttribute(): ?float
    {
        if (!$this->fecha_resolucion) {
            return null;
        }

        return $this->fecha_reporte->diffInHours($this->fecha_resolucion);
    }
}
