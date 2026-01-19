<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Verificar si el usuario tiene un rol específico
     * 
     * @param string|array $roles
     * @return bool
     */
    public function tieneRol($roles): bool
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }

        return $this->hasAnyRole($roles);
    }

    /**
     * Relación con notificaciones (many-to-many)
     */
    public function notificaciones(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Models\Notificaciones\Notificacion::class,
            'usuario_notificacion',
            'id_usuario',
            'id_notificacion'
        )->withPivot('leido', 'leido_el')
          ->orderBy('notificaciones.creado_el', 'desc');
    }

    /**
     * Obtener notificaciones no leídas
     */
    public function notificacionesNoLeidas()
    {
        return $this->notificaciones()
            ->wherePivot('leido', false);
    }
}
