<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cargo extends BaseModel
{
    use HasFactory;

    protected $table = 'cargos';

    protected $fillable = [
        'contrato_id',
        'unidad_id',
        'concepto_id',
        'periodo',
        'monto',
        'fecha_vencimiento',
        'estado',
        'notas',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha_vencimiento' => 'date',
    ];

    // ==========================================
    // RELACIONES
    // ==========================================

    public function contrato(): BelongsTo
    {
        return $this->belongsTo(Contrato::class, 'contrato_id');
    }

    public function unidad(): BelongsTo
    {
        return $this->belongsTo(Unidad::class, 'unidad_id');
    }

    public function concepto(): BelongsTo
    {
        return $this->belongsTo(Concepto::class, 'concepto_id');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'cargo_id');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    public function scopePagados($query)
    {
        return $query->where('estado', 'pagado');
    }

    public function scopeVencidos($query)
    {
        return $query->where('estado', 'vencido');
    }

    public function scopeCancelados($query)
    {
        return $query->where('estado', 'cancelado');
    }

    public function scopeDelPeriodo($query, string $periodo)
    {
        return $query->where('periodo', $periodo);
    }

    public function scopeConAdeudo($query)
    {
        return $query->whereIn('estado', ['pendiente', 'vencido']);
    }

    public function scopeProximosAVencer($query, int $dias = 7)
    {
        return $query->where('estado', 'pendiente')
            ->whereBetween('fecha_vencimiento', [now(), now()->addDays($dias)]);
    }

    public function scopeVencidosHoy($query)
    {
        return $query->where('estado', 'pendiente')
            ->where('fecha_vencimiento', '<', now());
    }

    // ==========================================
    // ACCESSORS
    // ==========================================

    /**
     * Obtener el monto pagado total
     */
    public function getMontoPagadoAttribute(): float
    {
        return $this->pagos()
            ->where('estatus', 'pagado')
            ->sum('monto');
    }

    /**
     * Obtener el saldo pendiente
     */
    public function getSaldoPendienteAttribute(): float
    {
        return max(0, $this->monto - $this->monto_pagado);
    }

    /**
     * Verificar si está vencido
     */
    public function getEstaVencidoAttribute(): bool
    {
        return $this->fecha_vencimiento < now() && in_array($this->estado, ['pendiente', 'vencido']);
    }
}
