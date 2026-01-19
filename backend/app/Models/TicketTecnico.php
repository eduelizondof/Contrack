<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketTecnico extends Model
{
    use HasFactory;

    protected $table = 'ticket_tecnicos';

    /**
     * Nombre de la columna created_at
     */
    const CREATED_AT = 'creado_el';

    /**
     * Sin updated_at
     */
    const UPDATED_AT = null;

    protected $fillable = [
        'ticket_id',
        'usuario_id',
        'asignado_por',
        'fecha_asignacion',
        'activo',
    ];

    protected $casts = [
        'fecha_asignacion' => 'datetime',
        'activo' => 'boolean',
    ];

    // ==========================================
    // RELACIONES
    // ==========================================

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function asignadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asignado_por');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeInactivos($query)
    {
        return $query->where('activo', false);
    }
}
