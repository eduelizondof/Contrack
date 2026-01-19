<?php

namespace App\Models\Chat;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mensaje extends BaseModel
{
    /**
     * La tabla asociada al modelo
     */
    protected $table = 'mensajes';

    /**
     * Indica que el modelo no usa soft deletes tradicional
     * (usamos campo booleano 'eliminado' en su lugar)
     */
    use \Illuminate\Database\Eloquent\SoftDeletes {
        \Illuminate\Database\Eloquent\SoftDeletes::bootSoftDeletes as parentBootSoftDeletes;
    }

    /**
     * Sobrescribir para desactivar soft deletes tradicional
     */
    public static function bootSoftDeletes(): void
    {
        // No usar soft deletes tradicional
    }

    /**
     * Los atributos que son asignables en masa
     */
    protected $fillable = [
        'conversacion_id',
        'user_id',
        'responde_a_id',
        'tipo',
        'contenido',
        'editado',
        'eliminado',
    ];

    /**
     * Los atributos que deben ser cast a tipos nativos
     */
    protected $casts = [
        'editado' => 'boolean',
        'eliminado' => 'boolean',
        'creado_el' => 'datetime',
        'actualizado_el' => 'datetime',
    ];

    /**
     * Tipos de mensaje válidos
     */
    const TIPOS = ['texto', 'imagen', 'archivo', 'link'];

    /**
     * Relación con la conversación
     */
    public function conversacion(): BelongsTo
    {
        return $this->belongsTo(Conversacion::class, 'conversacion_id');
    }

    /**
     * Relación con el usuario que envió el mensaje
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relación con los adjuntos del mensaje
     */
    public function adjuntos(): HasMany
    {
        return $this->hasMany(MensajeAdjunto::class, 'mensaje_id');
    }

    /**
     * Relación con los registros de visto
     */
    public function vistos(): HasMany
    {
        return $this->hasMany(MensajeVisto::class, 'mensaje_id');
    }

    /**
     * Relación con el mensaje al que responde
     */
    public function respondeA(): BelongsTo
    {
        return $this->belongsTo(Mensaje::class, 'responde_a_id');
    }

    /**
     * Relación con las respuestas a este mensaje
     */
    public function respuestas(): HasMany
    {
        return $this->hasMany(Mensaje::class, 'responde_a_id');
    }

    /**
     * Verificar si el mensaje fue visto por un usuario
     */
    public function fueVistoPor(int $userId): bool
    {
        return $this->vistos()->where('user_id', $userId)->exists();
    }

    /**
     * Verificar si el usuario puede editar el mensaje
     */
    public function puedeEditar(int $userId): bool
    {
        return $this->user_id === $userId && !$this->eliminado;
    }

    /**
     * Verificar si el usuario puede eliminar el mensaje
     */
    public function puedeEliminar(int $userId): bool
    {
        return $this->user_id === $userId && !$this->eliminado;
    }

    /**
     * Obtener preview del contenido (para listas)
     */
    public function getPreviewAttribute(): string
    {
        if ($this->eliminado) {
            return 'Mensaje eliminado';
        }

        if ($this->tipo === 'imagen') {
            return '📷 Imagen';
        }

        if ($this->tipo === 'archivo') {
            return '📎 Archivo';
        }

        if ($this->tipo === 'link') {
            return '🔗 Enlace';
        }

        $contenido = strip_tags($this->contenido ?? '');
        return strlen($contenido) > 50 
            ? substr($contenido, 0, 50) . '...' 
            : $contenido;
    }

    /**
     * Scope para excluir mensajes eliminados
     */
    public function scopeNoEliminados($query)
    {
        return $query->where('eliminado', false);
    }

    /**
     * Scope para filtrar por conversación
     */
    public function scopePorConversacion($query, int $conversacionId)
    {
        return $query->where('conversacion_id', $conversacionId);
    }

    /**
     * Scope para mensajes después de cierta fecha/id
     */
    public function scopeDespuesDe($query, int $mensajeId)
    {
        return $query->where('id', '>', $mensajeId);
    }

    /**
     * Scope para mensajes antes de cierta fecha/id (paginación hacia atrás)
     */
    public function scopeAntesDe($query, int $mensajeId)
    {
        return $query->where('id', '<', $mensajeId);
    }
}
