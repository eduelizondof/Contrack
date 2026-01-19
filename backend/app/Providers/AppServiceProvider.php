<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //Permitir que las migraciones esten organizadas en subcarpetas
        Schema::defaultStringLength(191);

        // Reunimos todos los archivos .php en database/migrations y subcarpetas
        $migrationFiles = collect(File::allFiles(database_path('migrations')))
            ->filter(fn ($file) => $file->getExtension() === 'php')
            ->map(fn ($file) => $file->getPath())
            ->unique()
            ->toArray();

        // Incluye las rutas en el sistema de migraciones
        $this->loadMigrationsFrom($migrationFiles);
    }
}
