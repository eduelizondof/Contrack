<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketComentario extends Model
{
    use HasFactory;

    protected $table = 'ticket_comentarios';

    /**
     * Nombre de la columna created_at
     */
    const CREATED_AT = 'creado_el';

    /**
     * Nombre de la columna updated_at
     */
    const UPDATED_AT = 'actualizado_el';

    protected $fillable = [
        'ticket_id',
        'usuario_id',
        'comentario',
        'interno',
    ];

    protected $casts = [
        'interno' => 'boolean',
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

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopePublicos($query)
    {
        return $query->where('interno', false);
    }

    public function scopeInternos($query)
    {
        return $query->where('interno', true);
    }
}
