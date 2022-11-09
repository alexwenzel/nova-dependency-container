<?php

namespace Alexwenzel\DependencyContainer;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Laravel\Nova\Events\NovaServiceProviderRegistered;
use Laravel\Nova\Events\ServingNova;
use Laravel\Nova\Nova;

class FieldServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        // assets
        Nova::serving(function (ServingNova $event) {
            Nova::script('dependency-container', __DIR__.'/../dist/js/field.js');
            Nova::style('dependency-container', __DIR__.'/../dist/css/field.css');
        });

        // Override ActionController after NovaServiceProvider loaded
        Event::listen(NovaServiceProviderRegistered::class, function () {
            app()->bind(
                \Laravel\Nova\Http\Controllers\ActionController::class,
                \Alexwenzel\DependencyContainer\Http\Controllers\ActionController::class
            );
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        //
    }
}
