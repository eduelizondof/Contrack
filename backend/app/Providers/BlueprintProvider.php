<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Schema\Blueprint;

class BlueprintProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if (! Blueprint::hasMacro('auditable')) {
            Blueprint::macro('auditable', function (): void {
                $this->timestamp('creado_el')->useCurrent();
                $this->timestamp('actualizado_el')->nullable()->useCurrent()->useCurrentOnUpdate();
                $this->timestamp('eliminado_el')->nullable();
            });
        }

        if (! Blueprint::hasMacro('dropAuditable')) {
            Blueprint::macro('dropAuditable', function (): void {
                $this->dropColumn(['eliminado_el', 'actualizado_el', 'creado_el']);
            });
        }
    }
}
