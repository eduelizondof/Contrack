<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pago extends BaseModel
{
    use HasFactory;

    protected $table = 'pagos';

    protected $fillable = [
        'cargo_id',
        'usuario_id',
        'metodo_pago',
        'referencia',
        'monto',
        'fecha_pago',
        'comprobante_url',
        'estatus',
        'notas',
        'procesado_por',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha_pago' => 'datetime',
    ];

    // ==========================================
    // RELACIONES
    // ==========================================

    public function cargo(): BelongsTo
    {
        return $this->belongsTo(Cargo::class, 'cargo_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function procesador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'procesado_por');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopePagados($query)
    {
        return $query->where('estatus', 'pagado');
    }

    public function scopePendientesVerificacion($query)
    {
        return $query->where('estatus', 'pendiente_verificacion');
    }

    public function scopeRechazados($query)
    {
        return $query->where('estatus', 'rechazado');
    }

    public function scopeRevertidos($query)
    {
        return $query->where('estatus', 'revertido');
    }

    public function scopeMetodoPago($query, string $metodo)
    {
        return $query->where('metodo_pago', $metodo);
    }

    public function scopeTransferencias($query)
    {
        return $query->where('metodo_pago', 'transferencia');
    }

    public function scopeEfectivo($query)
    {
        return $query->where('metodo_pago', 'efectivo');
    }

    public function scopeTarjeta($query)
    {
        return $query->where('metodo_pago', 'tarjeta');
    }

    public function scopeDelPeriodo($query, string $fechaInicio, string $fechaFin)
    {
        return $query->whereBetween('fecha_pago', [$fechaInicio, $fechaFin]);
    }

    public function scopeDelMes($query, int $mes = null, int $anio = null)
    {
        $mes = $mes ?? now()->month;
        $anio = $anio ?? now()->year;

        return $query->whereMonth('fecha_pago', $mes)
            ->whereYear('fecha_pago', $anio);
    }
}
