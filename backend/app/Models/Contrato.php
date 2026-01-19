<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contrato extends BaseModel
{
    use HasFactory;

    protected $table = 'contratos';

    protected $fillable = [
        'unidad_id',
        'usuario_id',
        'tipo',
        'fecha_inicio',
        'fecha_fin',
        'monto_mensual',
        'dia_pago',
        'renovacion_automatica',
        'archivo_contrato',
        'estado',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'monto_mensual' => 'decimal:2',
        'dia_pago' => 'integer',
        'renovacion_automatica' => 'boolean',
    ];

    // ==========================================
    // RELACIONES
    // ==========================================

    public function unidad(): BelongsTo
    {
        return $this->belongsTo(Unidad::class, 'unidad_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function cargos(): HasMany
    {
        return $this->hasMany(Cargo::class, 'contrato_id');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }

    public function scopeFinalizados($query)
    {
        return $query->where('estado', 'finalizado');
    }

    public function scopeCancelados($query)
    {
        return $query->where('estado', 'cancelado');
    }

    public function scopeDeRenta($query)
    {
        return $query->where('tipo', 'renta');
    }

    public function scopeDePropiedad($query)
    {
        return $query->where('tipo', 'propiedad');
    }

    public function scopeVigentes($query)
    {
        return $query->where('estado', 'activo')
            ->where('fecha_inicio', '<=', now())
            ->where(function ($q) {
                $q->whereNull('fecha_fin')
                    ->orWhere('fecha_fin', '>=', now());
            });
    }

    public function scopePorVencer($query, int $dias = 30)
    {
        return $query->where('estado', 'activo')
            ->whereNotNull('fecha_fin')
            ->whereBetween('fecha_fin', [now(), now()->addDays($dias)]);
    }

    // ==========================================
    // ACCESSORS
    // ==========================================

    /**
     * Verificar si el contrato está vigente
     */
    public function getEstaVigenteAttribute(): bool
    {
        if ($this->estado !== 'activo') {
            return false;
        }

        $hoy = now()->startOfDay();

        if ($this->fecha_inicio > $hoy) {
            return false;
        }

        if ($this->fecha_fin && $this->fecha_fin < $hoy) {
            return false;
        }

        return true;
    }

    /**
     * Obtener días restantes del contrato
     */
    public function getDiasRestantesAttribute(): ?int
    {
        if (!$this->fecha_fin) {
            return null;
        }

        return now()->diffInDays($this->fecha_fin, false);
    }
}
