<?php

namespace App\Models\Chat;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class MensajeAdjunto extends Model
{
    /**
     * La tabla asociada al modelo
     */
    protected $table = 'mensajes_adjuntos';

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
        'tipo',
        'ruta',
        'nombre_original',
        'mime',
        'peso',
    ];

    /**
     * Los atributos que deben ser cast a tipos nativos
     */
    protected $casts = [
        'peso' => 'integer',
        'creado_el' => 'datetime',
    ];

    /**
     * Tipos de adjunto válidos
     */
    const TIPOS = ['imagen', 'archivo', 'documento'];

    /**
     * Extensiones permitidas por tipo
     */
    const EXTENSIONES_PERMITIDAS = [
        'imagen' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'documento' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'],
        'archivo' => ['zip', 'rar', '7z'],
    ];

    /**
     * Tamaño máximo permitido (10MB en bytes)
     */
    const TAMANO_MAXIMO = 10485760;

    /**
     * Relación con el mensaje
     */
    public function mensaje(): BelongsTo
    {
        return $this->belongsTo(Mensaje::class, 'mensaje_id');
    }

    /**
     * Obtener URL pública del archivo
     */
    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->ruta);
    }

    /**
     * Obtener tamaño formateado
     */
    public function getPesoFormateadoAttribute(): string
    {
        $bytes = $this->peso;
        
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        }
        
        if ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        
        return $bytes . ' bytes';
    }

    /**
     * Verificar si es una imagen
     */
    public function getEsImagenAttribute(): bool
    {
        return $this->tipo === 'imagen';
    }

    /**
     * Obtener icono según el tipo de archivo
     */
    public function getIconoAttribute(): string
    {
        return match($this->tipo) {
            'imagen' => 'image',
            'documento' => 'file-text',
            'archivo' => 'archive',
            default => 'file',
        };
    }

    /**
     * Determinar el tipo basado en la extensión
     */
    public static function determinarTipo(string $extension): string
    {
        $extension = strtolower($extension);
        
        foreach (self::EXTENSIONES_PERMITIDAS as $tipo => $extensiones) {
            if (in_array($extension, $extensiones)) {
                return $tipo;
            }
        }
        
        return 'archivo';
    }

    /**
     * Verificar si la extensión está permitida
     */
    public static function extensionPermitida(string $extension): bool
    {
        $extension = strtolower($extension);
        
        foreach (self::EXTENSIONES_PERMITIDAS as $extensiones) {
            if (in_array($extension, $extensiones)) {
                return true;
            }
        }
        
        return false;
    }
}
