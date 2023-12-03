<?php

namespace PrasadChinwal\Box;

use Illuminate\Support\ServiceProvider;

class BoxServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/box.php' => config_path('box.php'),
        ], 'box-config');
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/box.php', 'box-config'
        );
        $this->app->singleton('box', function () {
            return new Box();
        });
    }
}
