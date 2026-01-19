<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

abstract class BaseModel extends Model
{
    use SoftDeletes, LogsActivity;

    /**
     * Nombre de la columna para soft deletes
     */
    const DELETED_AT = 'eliminado_el';

    /**
     * Nombre de la columna created_at
     */
    const CREATED_AT = 'creado_el';

    /**
     * Nombre de la columna updated_at
     */
    const UPDATED_AT = 'actualizado_el';

    /**
     * Configurar opciones de Activity Log
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly($this->getFillable())
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName($this->getTable());
    }

    /**
     * Obtener el nombre de la clase sin namespace
     */
    protected function getClassName(): string
    {
        return class_basename($this);
    }

    /**
     * Nombre del modelo para Activity Log
     */
    public function getDescriptionForEvent(string $eventName): string
    {
        $modelName = $this->getClassName();
        
        return match ($eventName) {
            'created' => "Se creó un nuevo {$modelName}",
            'updated' => "Se actualizó el {$modelName}",
            'deleted' => "Se eliminó el {$modelName}",
            'restored' => "Se restauró el {$modelName}",
            default => "Se realizó una acción en el {$modelName}",
        };
    }
}
