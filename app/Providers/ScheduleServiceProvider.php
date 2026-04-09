<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Interfaces\AulaRepositoryInterface;
use App\Repositories\AulaRepository;
use App\Interfaces\ScheduleRepositoryInterface;
use App\Repositories\ScheduleRepository;

class ScheduleServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(AulaRepositoryInterface::class, AulaRepository::class);
        $this->app->bind(ScheduleRepositoryInterface::class, ScheduleRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
