<?php

namespace App\Models\Notificaciones;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsuarioNotificacion extends Model
{
    /**
     * Indica que el modelo no usa timestamps automáticos
     */
    public $timestamps = false;

    /**
     * La tabla asociada al modelo
     */
    protected $table = 'usuario_notificacion';

    /**
     * Los atributos que son asignables en masa
     */
    protected $fillable = [
        'id_usuario',
        'id_notificacion',
        'leido',
        'leido_el',
    ];

    /**
     * Los atributos que deben ser cast a tipos nativos
     */
    protected $casts = [
        'leido' => 'boolean',
        'leido_el' => 'datetime',
    ];

    /**
     * Relación con usuario
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    /**
     * Relación con notificación
     */
    public function notificacion(): BelongsTo
    {
        return $this->belongsTo(Notificacion::class, 'id_notificacion');
    }

    /**
     * Marcar como leída
     */
    public function marcarComoLeida(): bool
    {
        $this->leido = true;
        $this->leido_el = now();
        return $this->save();
    }
}
