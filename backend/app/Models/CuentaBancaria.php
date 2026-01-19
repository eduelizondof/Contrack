<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CuentaBancaria extends BaseModel
{
    use HasFactory;

    protected $table = 'cuentas_bancarias';

    protected $fillable = [
        'inmueble_id',
        'banco',
        'titular',
        'numero_cuenta',
        'clabe',
        'tipo',
        'activa',
    ];

    protected $casts = [
        'activa' => 'boolean',
    ];

    // ==========================================
    // RELACIONES
    // ==========================================

    public function inmueble(): BelongsTo
    {
        return $this->belongsTo(Inmueble::class, 'inmueble_id');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeActivas($query)
    {
        return $query->where('activa', true);
    }

    public function scopeTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function scopeOperacion($query)
    {
        return $query->where('tipo', 'operacion');
    }

    public function scopeMantenimiento($query)
    {
        return $query->where('tipo', 'mantenimiento');
    }

    public function scopeReserva($query)
    {
        return $query->where('tipo', 'reserva');
    }
}
