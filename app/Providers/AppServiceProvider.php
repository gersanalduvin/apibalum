<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Queue;
use Illuminate\Queue\Connectors\SqsConnector;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Registrar repositories
        $this->app->bind(
            \App\Repositories\RoleRepository::class,
            \App\Repositories\RoleRepository::class
        );

        // Registrar services
        $this->app->bind(
            \App\Services\PermissionService::class,
            \App\Services\PermissionService::class
        );

        $this->app->bind(
            \App\Services\RoleService::class,
            \App\Services\RoleService::class
        );
        $this->app->bind(
            \App\Repositories\Contracts\ListasGrupoRepositoryInterface::class,
            \App\Repositories\ListasGrupoRepository::class
        );
        $this->app->bind(
            \App\Interfaces\AgendaRepositoryInterface::class,
            \App\Repositories\AgendaRepository::class
        );
        $this->app->bind(
            \App\Repositories\Contracts\CalificacionRepositoryInterface::class,
            \App\Repositories\CalificacionRepository::class
        );
        $this->app->bind(
            \App\Repositories\Contracts\StudentExportRepositoryInterface::class,
            \App\Repositories\StudentExportRepository::class
        );
        $this->app->bind(
            \App\Repositories\Contracts\ReporteNotasRepositoryInterface::class,
            \App\Repositories\ReporteNotasRepository::class
        );
        $this->app->bind(
            \App\Repositories\Contracts\ReporteRetiradosRepositoryInterface::class,
            \App\Repositories\ReporteRetiradosRepository::class
        );
        $this->app->bind(
            \App\Repositories\Contracts\AvisoRepositoryInterface::class,
            \App\Repositories\AvisoRepository::class
        );
        $this->app->bind(
            \App\Interfaces\BoletinEscolarRepositoryInterface::class,
            \App\Repositories\BoletinEscolarRepository::class
        );
        $this->app->bind(
            \App\Repositories\Interfaces\LoginLogRepositoryInterface::class,
            \App\Repositories\LoginLogRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Sobrescribir el conector SQS para establecer verificación SSL con CA bundle
        Queue::extend('sqs', function () {
            return new class extends SqsConnector {
                public function connect(array $config)
                {
                    $ca = env('AWS_CA_BUNDLE');
                    $verify = env('AWS_VERIFY_SSL', true) ? ($ca ?: true) : false;

                    // Inyectar opciones HTTP/Guzzle para AWS SDK
                    $http = $config['http'] ?? [];
                    $config['http'] = array_merge([
                        'timeout' => 60,
                        'connect_timeout' => 60,
                    ], $http, [
                        'verify' => $verify,
                        'curl' => $ca ? [
                            CURLOPT_CAINFO => $ca,
                        ] : [],
                    ]);

                    return parent::connect($config);
                }
            };
        });
    }
}
