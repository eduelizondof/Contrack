<?php

namespace App\Models\Notificaciones;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoriaNotificacion extends BaseModel
{
    /**
     * La tabla asociada al modelo
     */
    protected $table = 'categorias_notificacion';

    /**
     * Los atributos que son asignables en masa
     */
    protected $fillable = [
        'tipo',
        'nombre',
        'icono',
        'color',
    ];

    /**
     * Los atributos que deben ser cast a tipos nativos
     */
    protected $casts = [
        'creado_el' => 'datetime',
        'actualizado_el' => 'datetime',
        'eliminado_el' => 'datetime',
    ];

    /**
     * Relación con notificaciones
     */
    public function notificaciones(): HasMany
    {
        return $this->hasMany(Notificacion::class, 'id_categoria');
    }

    /**
     * Scope para obtener por tipo
     */
    public function scopePorTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }
}
