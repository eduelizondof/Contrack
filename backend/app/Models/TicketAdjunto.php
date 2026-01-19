<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketAdjunto extends Model
{
    use HasFactory;

    protected $table = 'ticket_adjuntos';

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
        'nombre_archivo',
        'ruta_archivo',
        'tipo_archivo',
        'descripcion',
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

    public function scopeTipoArchivo($query, string $tipo)
    {
        return $query->where('tipo_archivo', $tipo);
    }

    public function scopeImagenes($query)
    {
        return $query->where('tipo_archivo', 'like', 'image/%');
    }

    public function scopeDocumentos($query)
    {
        return $query->where('tipo_archivo', 'like', 'application/%');
    }
}
