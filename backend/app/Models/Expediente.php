<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expediente extends BaseModel
{
    use HasFactory;

    protected $table = 'expedientes';

    protected $fillable = [
        'usuario_id',
        'tipo_documento',
        'nombre_archivo',
        'ruta_archivo',
        'fecha_vigencia',
        'verificado',
        'verificado_por',
        'verificado_at',
        'notas',
    ];

    protected $casts = [
        'fecha_vigencia' => 'date',
        'verificado' => 'boolean',
        'verificado_at' => 'datetime',
    ];

    // ==========================================
    // RELACIONES
    // ==========================================

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function verificador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verificado_por');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeVerificados($query)
    {
        return $query->where('verificado', true);
    }

    public function scopePendientesVerificacion($query)
    {
        return $query->where('verificado', false);
    }

    public function scopeTipoDocumento($query, string $tipo)
    {
        return $query->where('tipo_documento', $tipo);
    }

    public function scopeVigentes($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('fecha_vigencia')
                ->orWhere('fecha_vigencia', '>=', now());
        });
    }

    public function scopeVencidos($query)
    {
        return $query->whereNotNull('fecha_vigencia')
            ->where('fecha_vigencia', '<', now());
    }

    // ==========================================
    // ACCESSORS
    // ==========================================

    /**
     * Verificar si el documento está vencido
     */
    public function getEstaVencidoAttribute(): bool
    {
        if (!$this->fecha_vigencia) {
            return false;
        }

        return $this->fecha_vigencia < now();
    }
}
