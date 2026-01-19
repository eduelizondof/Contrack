<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DatoInquilino extends Model
{
    use HasFactory;

    protected $table = 'datos_inquilinos';

    /**
     * Nombre de la columna created_at
     */
    const CREATED_AT = 'creado_el';

    /**
     * Nombre de la columna updated_at
     */
    const UPDATED_AT = 'actualizado_el';

    protected $fillable = [
        'usuario_id',
        'ocupacion',
        'empresa',
        'referencia_nombre',
        'referencia_telefono',
        'contacto_emergencia_nombre',
        'contacto_emergencia_telefono',
        'numero_personas_habitan',
        'tiene_mascotas',
        'tipo_mascotas',
    ];

    protected $casts = [
        'numero_personas_habitan' => 'integer',
        'tiene_mascotas' => 'boolean',
    ];

    // ==========================================
    // RELACIONES
    // ==========================================

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeConMascotas($query)
    {
        return $query->where('tiene_mascotas', true);
    }

    public function scopeSinMascotas($query)
    {
        return $query->where('tiene_mascotas', false);
    }
}
