<?php

namespace App\Models\Notificaciones;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Notificacion extends BaseModel
{
    /**
     * La tabla asociada al modelo
     */
    protected $table = 'notificaciones';

    /**
     * Los atributos que son asignables en masa
     */
    protected $fillable = [
        'id_categoria',
        'tipo',
        'titulo',
        'mensaje',
        'prioridad',
        'datos_json',
        'accion_tipo',
        'accion_ruta',
        'accion_parametros',
        'id_usuario_creador',
    ];

    /**
     * Los atributos que deben ser cast a tipos nativos
     */
    protected $casts = [
        'datos_json' => 'array',
        'accion_parametros' => 'array',
        'creado_el' => 'datetime',
        'actualizado_el' => 'datetime',
        'eliminado_el' => 'datetime',
    ];

    /**
     * Relación con categoría
     */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaNotificacion::class, 'id_categoria');
    }

    /**
     * Relación con usuario creador
     */
    public function usuarioCreador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario_creador');
    }

    /**
     * Relación con usuarios (many-to-many a través de usuario_notificacion)
     */
    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'usuario_notificacion', 'id_notificacion', 'id_usuario')
            ->withPivot('leido', 'leido_el');
    }

    /**
     * Scope para filtrar por prioridad
     */
    public function scopePorPrioridad($query, string $prioridad)
    {
        return $query->where('prioridad', $prioridad);
    }

    /**
     * Scope para filtrar por tipo
     */
    public function scopePorTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    /**
     * Scope para filtrar por categoría
     */
    public function scopePorCategoria($query, string $tipoCategoria)
    {
        return $query->whereHas('categoria', function ($q) use ($tipoCategoria) {
            $q->where('tipo', $tipoCategoria);
        });
    }
}
