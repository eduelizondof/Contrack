<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Concepto extends BaseModel
{
    use HasFactory;

    protected $table = 'conceptos';

    protected $fillable = [
        'inmueble_id',
        'nombre',
        'descripcion',
        'tipo',
        'monto_default',
        'activo',
    ];

    protected $casts = [
        'monto_default' => 'decimal:2',
        'activo' => 'boolean',
    ];

    // ==========================================
    // RELACIONES
    // ==========================================

    public function inmueble(): BelongsTo
    {
        return $this->belongsTo(Inmueble::class, 'inmueble_id');
    }

    public function cargos(): HasMany
    {
        return $this->hasMany(Cargo::class, 'concepto_id');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function scopeFijos($query)
    {
        return $query->where('tipo', 'fijo');
    }

    public function scopeVariables($query)
    {
        return $query->where('tipo', 'variable');
    }

    public function scopeExtraordinarios($query)
    {
        return $query->where('tipo', 'extraordinario');
    }
}
