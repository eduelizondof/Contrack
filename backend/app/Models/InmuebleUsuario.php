<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InmuebleUsuario extends BaseModel
{
    use HasFactory;

    protected $table = 'inmueble_usuarios';

    protected $fillable = [
        'inmueble_id',
        'usuario_id',
        'rol',
        'unidad_id',
        'activo',
        'fecha_inicio',
        'fecha_fin',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    // ==========================================
    // RELACIONES
    // ==========================================

    public function inmueble(): BelongsTo
    {
        return $this->belongsTo(Inmueble::class, 'inmueble_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function unidad(): BelongsTo
    {
        return $this->belongsTo(Unidad::class, 'unidad_id');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeRol($query, string $rol)
    {
        return $query->where('rol', $rol);
    }

    public function scopePropietarios($query)
    {
        return $query->where('rol', 'propietario');
    }

    public function scopeInquilinos($query)
    {
        return $query->where('rol', 'inquilino');
    }

    public function scopeAdministradores($query)
    {
        return $query->where('rol', 'administrador');
    }

    public function scopeTecnicos($query)
    {
        return $query->where('rol', 'tecnico');
    }

    public function scopeVigilancia($query)
    {
        return $query->where('rol', 'vigilancia');
    }

    public function scopeVigentes($query)
    {
        return $query->where('activo', true)
            ->where('fecha_inicio', '<=', now())
            ->where(function ($q) {
                $q->whereNull('fecha_fin')
                    ->orWhere('fecha_fin', '>=', now());
            });
    }
}
