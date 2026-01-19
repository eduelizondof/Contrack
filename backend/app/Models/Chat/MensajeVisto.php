<?php

namespace App\Models\Chat;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MensajeVisto extends Model
{
    /**
     * La tabla asociada al modelo
     */
    protected $table = 'mensajes_vistos';

    /**
     * El nombre de la columna de creación
     */
    const CREATED_AT = 'creado_el';
    const UPDATED_AT = null;

    /**
     * Los atributos que son asignables en masa
     */
    protected $fillable = [
        'mensaje_id',
        'user_id',
    ];

    /**
     * Los atributos que deben ser cast a tipos nativos
     */
    protected $casts = [
        'creado_el' => 'datetime',
    ];

    /**
     * Relación con el mensaje
     */
    public function mensaje(): BelongsTo
    {
        return $this->belongsTo(Mensaje::class, 'mensaje_id');
    }

    /**
     * Relación con el usuario que vio el mensaje
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
