<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unidad extends BaseModel
{
    use HasFactory;

    protected $table = 'unidades';

    protected $fillable = [
        'inmueble_id',
        'identificador',
        'tipo',
        'nivel',
        'area_m2',
        'recamaras',
        'banos',
        'estacionamientos',
        'caracteristicas',
    ];

    protected $casts = [
        'area_m2' => 'decimal:2',
        'banos' => 'decimal:1',
        'recamaras' => 'integer',
        'estacionamientos' => 'integer',
        'caracteristicas' => 'array',
    ];

    // ==========================================
    // RELACIONES
    // ==========================================

    public function inmueble(): BelongsTo
    {
        return $this->belongsTo(Inmueble::class, 'inmueble_id');
    }

    public function contratos(): HasMany
    {
        return $this->hasMany(Contrato::class, 'unidad_id');
    }

    public function cargos(): HasMany
    {
        return $this->hasMany(Cargo::class, 'unidad_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'unidad_id');
    }

    public function inmuebleUsuarios(): HasMany
    {
        return $this->hasMany(InmuebleUsuario::class, 'unidad_id');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function scopeCasas($query)
    {
        return $query->where('tipo', 'casa');
    }

    public function scopeDepartamentos($query)
    {
        return $query->where('tipo', 'departamento');
    }

    public function scopeLocales($query)
    {
        return $query->where('tipo', 'local');
    }

    public function scopeBodegas($query)
    {
        return $query->where('tipo', 'bodega');
    }

    /**
     * Unidades con adeudos pendientes o vencidos
     */
    public function scopeConAdeudos($query)
    {
        return $query->whereHas('cargos', function ($q) {
            $q->whereIn('estado', ['pendiente', 'vencido']);
        });
    }

    /**
     * Unidades sin adeudos
     */
    public function scopeSinAdeudos($query)
    {
        return $query->whereDoesntHave('cargos', function ($q) {
            $q->whereIn('estado', ['pendiente', 'vencido']);
        });
    }

    /**
     * Unidades con contrato activo
     */
    public function scopeConContratoActivo($query)
    {
        return $query->whereHas('contratos', function ($q) {
            $q->where('estado', 'activo');
        });
    }

    // ==========================================
    // ACCESSORS Y MÉTODOS
    // ==========================================

    /**
     * Obtener el saldo pendiente total de la unidad
     */
    public function getSaldoPendienteAttribute(): float
    {
        return $this->cargos()
            ->whereIn('estado', ['pendiente', 'vencido'])
            ->sum('monto');
    }

    /**
     * Obtener el contrato activo actual
     */
    public function getContratoActivoAttribute()
    {
        return $this->contratos()
            ->where('estado', 'activo')
            ->first();
    }
}
