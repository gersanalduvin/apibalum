<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class MensajeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Interfaces\MensajeRepositoryInterface::class,
            \App\Repositories\MensajeRepository::class
        );

        $this->app->bind(
            \App\Interfaces\MensajeRespuestaRepositoryInterface::class,
            \App\Repositories\MensajeRespuestaRepository::class
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
