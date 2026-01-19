<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inmueble extends BaseModel
{
    use HasFactory;

    protected $table = 'inmuebles';

    protected $fillable = [
        'nombre',
        'tipo',
        'direccion',
        'ciudad',
        'estado',
        'codigo_postal',
        'total_unidades',
        'reglamento_url',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'total_unidades' => 'integer',
    ];

    // ==========================================
    // RELACIONES
    // ==========================================

    public function unidades(): HasMany
    {
        return $this->hasMany(Unidad::class, 'inmueble_id');
    }

    public function cuentasBancarias(): HasMany
    {
        return $this->hasMany(CuentaBancaria::class, 'inmueble_id');
    }

    public function conceptos(): HasMany
    {
        return $this->hasMany(Concepto::class, 'inmueble_id');
    }

    public function inmuebleUsuarios(): HasMany
    {
        return $this->hasMany(InmuebleUsuario::class, 'inmueble_id');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function scopeCondominios($query)
    {
        return $query->where('tipo', 'condominio');
    }

    public function scopeFraccionamientos($query)
    {
        return $query->where('tipo', 'fraccionamiento');
    }

    public function scopeEdificios($query)
    {
        return $query->where('tipo', 'edificio');
    }
}
